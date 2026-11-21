<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

/**
 * Review Submission Controller: Diner ratings, reviews, and anti-spam moderation.
 *
 * Implements review submission lifecycle:
 * - 1 to 5 star rating enforcement
 * - Minimum comment length validation
 * - Anti-spam heuristic filters: auto-moderates and unapproves reviews containing links/URLs
 * - Moderation queue integration for restaurant staff
 */
class ReviewSubmissionController
{
    /**
     * Detect JSON request format.
     *
     * @return bool
     */
    private function isJsonRequest(): bool
    {
        return (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) || (isset($_GET['format']) && $_GET['format'] === 'json');
    }

    /**
     * List approved reviews for a restaurant.
     *
     * Routes: GET /discover/:id/reviews, GET /reviews
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function index(Request $request, Response $response): void
    {
        $orgId = $request->getParam('id') ?? $request->getParam('restaurant_id') ?? $request->getParam('org_id');
        if (!$orgId) {
            $response->json(['error' => 'Restaurant ID is required'], 400);
            return;
        }

        $pdo = Database::getConnection();

        // Verify restaurant
        $restStmt = $pdo->prepare('SELECT id, name FROM tenants WHERE (id = :id OR subdomain = :id) AND is_active = 1');
        $restStmt->execute([':id' => $orgId]);
        $restaurant = $restStmt->fetch();

        if (!$restaurant) {
            $response->json(['error' => 'Restaurant not found'], 404);
            return;
        }

        $orgId = $restaurant['id'];

        $page = max(1, (int) ($request->getParam('page') ?? 1));
        $perPage = max(1, min(50, (int) ($request->getParam('per_page') ?? 20)));
        $offset = ($page - 1) * $perPage;

        // Count approved reviews
        $countStmt = $pdo->prepare('SELECT COUNT(*) as total, AVG(rating) as avg_rating FROM reviews WHERE org_id = :org_id AND (approved = 1 OR approved IS NULL)');
        $countStmt->execute([':org_id' => $orgId]);
        $stats = $countStmt->fetch();

        $total = (int) ($stats['total'] ?? 0);
        $avgRating = round((float) ($stats['avg_rating'] ?? 0.0), 1);
        $totalPages = (int) ceil($total / $perPage);

        // Fetch reviews
        $query = 'SELECT r.id, r.rating, r.comment, r.created_at, r.restaurant_response, r.response_at, u.first_name, u.last_name FROM reviews r LEFT JOIN users u ON r.customer_id = u.id WHERE r.org_id = :org_id AND (r.approved = 1 OR r.approved IS NULL) ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset';
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':org_id', $orgId);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $reviews = $stmt->fetchAll();

        $response->json([
            'restaurant_id' => $orgId,
            'restaurant_name' => $restaurant['name'],
            'average_rating' => $avgRating,
            'total_reviews' => $total,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => $totalPages,
            ],
            'reviews' => $reviews,
        ], 200);
    }

    /**
     * Submit a diner review with spam heuristic moderation.
     *
     * Routes: POST /reviews, POST /discover/:id/reviews
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function create(Request $request, Response $response): void
    {
        $body = $request->getBody();
        $pdo = Database::getConnection();

        $orgId = trim((string) ($body['org_id'] ?? $body['restaurant_id'] ?? $request->getParam('id') ?? ''));
        $rating = (int) ($body['rating'] ?? 0);
        $comment = trim((string) ($body['comment'] ?? ''));
        $email = trim((string) ($body['customer_email'] ?? $body['email'] ?? ''));
        $reservationId = trim((string) ($body['reservation_id'] ?? ''));

        // Validation
        $errors = [];

        if ($orgId === '') {
            $errors['restaurant_id'] = 'Restaurant selection is required';
        } else {
            $restStmt = $pdo->prepare('SELECT id, name FROM tenants WHERE (id = :id OR subdomain = :id) AND is_active = 1');
            $restStmt->execute([':id' => $orgId]);
            $rest = $restStmt->fetch();
            if (!$rest) {
                $errors['restaurant_id'] = 'Restaurant not found or inactive';
            } else {
                $orgId = $rest['id'];
            }
        }

        if ($rating < 1 || $rating > 5) {
            $errors['rating'] = 'Rating must be an integer between 1 and 5';
        }

        if (mb_strlen($comment) < 10) {
            $errors['comment'] = 'Review comment must be at least 10 characters long';
        }

        if ($email === '' || Validator::email($email) !== null) {
            // Check if user is logged in
            $currentUser = Session::get('user');
            if ($currentUser && !empty($currentUser['email'])) {
                $email = $currentUser['email'];
            } else {
                $errors['customer_email'] = 'A valid email address is required';
            }
        }

        if (!empty($errors)) {
            $response->json(['error' => 'Validation failed', 'fields' => $errors], 422);
            return;
        }

        // Spam Heuristic Auto-Moderation
        // Flags URLs, links, or promotional patterns
        $isSpam = false;
        $spamReason = null;
        if (preg_match('/(https?:\/\/|www\.[^\s]+|\b[a-zA-Z0-9.-]+\.(com|org|net|io|xyz|top|biz)\b)/i', $comment)) {
            $isSpam = true;
            $spamReason = 'Contains promotional hyperlink or URL';
        } elseif (preg_match('/(viagra|casino|crypto|telegram|whatsapp|free followers)/i', $comment)) {
            $isSpam = true;
            $spamReason = 'Contains prohibited promotional terms';
        }

        // Auto-approve clean reviews; hold spam for staff moderation queue
        $approvedStatus = $isSpam ? 0 : 1;

        // Resolve customer user ID
        $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND tenant_id = :org_id');
        $userStmt->execute([':email' => $email, ':org_id' => $orgId]);
        $userRow = $userStmt->fetch();

        $customerId = null;
        if ($userRow) {
            $customerId = $userRow['id'];
        } else {
            $customerId = bin2hex(random_bytes(18));
            $insUser = $pdo->prepare('INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role_mask, created_at) VALUES (?, ?, ?, ?, ?, ?, 4, CURRENT_TIMESTAMP)');
            $insUser->execute([
                $customerId,
                $orgId,
                $email,
                password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
                'Verified',
                'Diner',
            ]);
        }

        // Validate reservation link if provided
        $linkedReservation = null;
        if ($reservationId !== '') {
            $resStmt = $pdo->prepare('SELECT id FROM reservations WHERE id = :id AND org_id = :org_id');
            $resStmt->execute([':id' => $reservationId, ':org_id' => $orgId]);
            if ($resStmt->fetch()) {
                $linkedReservation = $reservationId;
            }
        }

        $reviewId = bin2hex(random_bytes(18));
        $insStmt = $pdo->prepare('INSERT INTO reviews (id, org_id, reservation_id, customer_id, rating, comment, approved, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
        $insStmt->execute([
            $reviewId,
            $orgId,
            $linkedReservation,
            $customerId,
            $rating,
            $comment,
            $approvedStatus,
        ]);

        Audit::log(
            $orgId,
            $customerId,
            'CREATE',
            'review',
            $reviewId,
            "Review {$reviewId} submitted with rating {$rating}/5 (approved: {$approvedStatus})" . ($isSpam ? " - Auto-moderated: {$spamReason}" : '')
        );

        $response->json([
            'id' => $reviewId,
            'org_id' => $orgId,
            'rating' => $rating,
            'comment' => $comment,
            'approved' => $approvedStatus === 1,
            'moderation_status' => $approvedStatus === 1 ? 'approved' : 'pending_moderation',
            'moderation_notice' => $isSpam ? 'Your review is awaiting staff approval due to automated content moderation.' : 'Your review has been published.',
        ], 201);
    }
}
