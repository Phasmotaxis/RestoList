<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Audit.php';
require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Middleware/RoleMiddleware.php';

use App\Core\Audit;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\RoleMiddleware;

/**
 * Review Moderation Controller: Customer feedback moderation and management responses.
 *
 * Implements Subdomain 7 per docs/SPECIFICATION.md Section 2.2.B.
 */
class ReviewModerationController
{
    private function isJsonRequest(): bool
    {
        return (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
            || (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false)
            || (isset($_GET['format']) && $_GET['format'] === 'json');
    }

    private function getAuthorizedOrgId(Request $request, array $params = []): string
    {
        $requestedOrgId = (string) ($params['id'] ?? $request->getParam('id') ?? Session::get('tenant_id') ?? Session::get('org_id') ?? '');
        $sessionTenantId = (string) (Session::get('tenant_id') ?? Session::get('org_id') ?? '');
        $userMask = (int) (Session::get('role_mask') ?? 0);

        if (($userMask & RoleMiddleware::ROLE_SUPERADMIN) === RoleMiddleware::ROLE_SUPERADMIN) {
            return $requestedOrgId ?: $sessionTenantId;
        }

        if ($requestedOrgId === '') {
            $requestedOrgId = $sessionTenantId;
        }

        if ($sessionTenantId !== '' && $requestedOrgId !== '' && $requestedOrgId !== $sessionTenantId) {
            http_response_code(403);
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Forbidden', 'message' => 'Cross-tenant access prohibited']);
                exit;
            }
            require_once __DIR__ . '/../Views/errors/403.php';
            exit;
        }

        return $requestedOrgId;
    }

    /**
     * List reviews awaiting review or response.
     */
    public function pending(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $stmt = Database::executeQuery(
            'SELECT r.*, u.first_name, u.last_name, u.email as reviewer_email
             FROM reviews r
             LEFT JOIN users u ON r.customer_id = u.id
             WHERE r.org_id = :org_id
             ORDER BY r.created_at DESC',
            ['org_id' => $orgId]
        );
        $reviews = $stmt->fetchAll();

        foreach ($reviews as &$rev) {
            $rev['rating'] = (int) $rev['rating'];
            // Spam heuristic flag
            $comment = (string) ($rev['comment'] ?? '');
            $urlCount = substr_count(strtolower($comment), 'http://') + substr_count(strtolower($comment), 'https://');
            $rev['is_spam_suspect'] = ($urlCount > 1 || strlen(trim($comment)) < 3);
        }
        unset($rev);

        if ($this->isJsonRequest()) {
            $response->json([
                'org_id' => $orgId,
                'data' => $reviews,
                'count' => count($reviews)
            ], 200);
            return;
        }

        $title = 'Review Moderation';
        require_once __DIR__ . '/../Views/admin/reviews.php';
    }

    /**
     * Approve customer review.
     */
    public function approve(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $reviewId = (string) ($params['review_id'] ?? $request->getParam('review_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT * FROM reviews WHERE id = :id AND org_id = :org_id',
            ['id' => $reviewId, 'org_id' => $orgId]
        );
        $review = $stmt->fetch();

        if (!$review) {
            $response->json(['error' => 'Review not found'], 404);
            return;
        }

        Audit::record($orgId, 'APPROVE_REVIEW', 'reviews', $reviewId, 'Review approved by manager');

        $response->json([
            'id' => $reviewId,
            'status' => 'approved'
        ], 200);
    }

    /**
     * Reject and remove abusive or spam review.
     */
    public function reject(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $reviewId = (string) ($params['review_id'] ?? $request->getParam('review_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT * FROM reviews WHERE id = :id AND org_id = :org_id',
            ['id' => $reviewId, 'org_id' => $orgId]
        );
        $review = $stmt->fetch();

        if (!$review) {
            $response->json(['error' => 'Review not found'], 404);
            return;
        }

        Database::executeQuery(
            'DELETE FROM reviews WHERE id = :id AND org_id = :org_id',
            ['id' => $reviewId, 'org_id' => $orgId]
        );

        Audit::record($orgId, 'REJECT_REVIEW', 'reviews', $reviewId, 'Review rejected and purged');

        $response->json([
            'id' => $reviewId,
            'status' => 'rejected'
        ], 200);
    }

    /**
     * Submit official restaurant response to customer review.
     */
    public function reply(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $reviewId = (string) ($params['review_id'] ?? $request->getParam('review_id') ?? '');
        $body = $request->getBody();

        $reply = trim((string) ($body['response'] ?? $body['reply'] ?? ''));

        if ($reply === '') {
            $response->json(['error' => 'Validation failed', 'fields' => ['response' => 'Response text is required']], 422);
            return;
        }

        $stmt = Database::executeQuery(
            'SELECT * FROM reviews WHERE id = :id AND org_id = :org_id',
            ['id' => $reviewId, 'org_id' => $orgId]
        );
        $review = $stmt->fetch();

        if (!$review) {
            $response->json(['error' => 'Review not found'], 404);
            return;
        }

        Database::executeQuery(
            'UPDATE reviews SET restaurant_response = :resp, response_at = CURRENT_TIMESTAMP WHERE id = :id AND org_id = :org_id',
            ['resp' => $reply, 'id' => $reviewId, 'org_id' => $orgId]
        );

        Audit::record($orgId, 'REPLY_REVIEW', 'reviews', $reviewId, json_encode(['response_length' => strlen($reply)]));

        $response->json([
            'id' => $reviewId,
            'restaurant_response' => $reply,
            'response_at' => date('Y-m-d H:i:s'),
            'status' => 'responded'
        ], 200);
    }
}
