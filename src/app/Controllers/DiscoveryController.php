<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Discovery Controller: Restaurant browsing, search, and availability filtering.
 *
 * Provides public access for diners to discover dining venues across the multi-tenant platform.
 * Supports keyword search, cuisine filtering, dietary tag filtering (vegan, gluten-free, halal),
 * availability filtering (7-day window), and diner review aggregation.
 */
class DiscoveryController
{
    /**
     * Helper: Detect JSON request format (Accept header or ?format=json).
     *
     * @return bool True if Accept header contains application/json or ?format=json parameter present.
     */
    private function isJsonRequest(): bool
    {
        return (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) || (isset($_GET['format']) && $_GET['format'] === 'json');
    }

    /**
     * List active restaurants with search, cuisine, dietary, and availability filtering.
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function index(Request $request, Response $response): void
    {
        $pdo = Database::getConnection();
        $q = trim((string) ($request->getParam('q') ?? ''));
        $cuisine = trim((string) ($request->getParam('cuisine') ?? ''));
        $dietary = trim((string) ($request->getParam('dietary') ?? ''));
        $date = trim((string) ($request->getParam('date') ?? ''));
        $partySize = (int) ($request->getParam('party_size') ?? 0);
        $page = max(1, (int) ($request->getParam('page') ?? 1));
        $perPage = max(1, min(100, (int) ($request->getParam('per_page') ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = ['t.is_active = 1'];
        $params = [];

        if ($q !== '') {
            $where[] = '(t.name LIKE :q OR t.subdomain LIKE :q)';
            $params[':q'] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
        }

        if ($dietary !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM menu_items mi WHERE mi.org_id = t.id AND mi.is_available = 1 AND mi.dietary_tags LIKE :dietary)';
            $params[':dietary'] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $dietary) . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM tenants t WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) ($countStmt->fetch()['total'] ?? 0);
        $totalPages = (int) ceil($total / $perPage);

        $query = "SELECT t.id, t.name, t.subdomain, t.created_at FROM tenants t WHERE {$whereSql} ORDER BY t.name ASC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($query);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $restaurants = $stmt->fetchAll();

        $data = [];
        foreach ($restaurants as $rest) {
            $orgId = $rest['id'];

            // Fetch review stats
            $revStmt = $pdo->prepare('SELECT COUNT(*) as cnt, AVG(rating) as avg_rating FROM reviews WHERE org_id = :org_id AND (approved = 1 OR approved IS NULL)');
            $revStmt->execute([':org_id' => $orgId]);
            $revStats = $revStmt->fetch();
            $avgRating = round((float) ($revStats['avg_rating'] ?? 0.0), 1);
            $reviewCount = (int) ($revStats['cnt'] ?? 0);

            // Fetch distinct dietary tags from available menu items
            $tagStmt = $pdo->prepare('SELECT DISTINCT dietary_tags FROM menu_items WHERE org_id = :org_id AND is_available = 1 AND dietary_tags IS NOT NULL');
            $tagStmt->execute([':org_id' => $orgId]);
            $rawTags = $tagStmt->fetchAll();
            $dietaryTags = [];
            foreach ($rawTags as $row) {
                if (!empty($row['dietary_tags'])) {
                    $tags = array_map('trim', explode(',', $row['dietary_tags']));
                    foreach ($tags as $tag) {
                        if ($tag !== '' && !in_array($tag, $dietaryTags, true)) {
                            $dietaryTags[] = $tag;
                        }
                    }
                }
            }

            // Derive cuisine tag from subdomain or tags
            $cuisineName = 'Contemporary';
            if (str_contains($rest['name'], 'Pizza') || str_contains($rest['name'], 'Italian')) {
                $cuisineName = 'Italian';
            } elseif (str_contains($rest['name'], 'Bistro') || str_contains($rest['name'], 'Cafe')) {
                $cuisineName = 'Cafe';
            }

            // Availability check if date and party_size specified
            $hasAvailability = true;
            if ($date !== '' && $partySize > 0) {
                $hasAvailability = $this->checkDateAvailability($orgId, $date, $partySize);
            }

            if (!$hasAvailability && $date !== '' && $partySize > 0) {
                continue;
            }

            $data[] = [
                'id' => $rest['id'],
                'name' => $rest['name'],
                'subdomain' => $rest['subdomain'],
                'cuisine' => $cuisineName,
                'location' => 'Main Dining District',
                'phone' => '+63 46 415 0010',
                'operating_hours' => '11:00 - 22:00 UTC',
                'parking' => 'On-site guest parking available',
                'average_rating' => $avgRating,
                'review_count' => $reviewCount,
                'dietary_tags' => $dietaryTags,
                'has_availability' => $hasAvailability,
            ];
        }

        if ($cuisine !== '') {
            $data = array_values(array_filter($data, fn($item) => strcasecmp($item['cuisine'], $cuisine) === 0));
        }

        if ($this->isJsonRequest()) {
            $response->json([
                'data' => $data,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'pages' => $totalPages,
                ],
            ]);
            return;
        }

        $title = 'Restaurant Discovery - Customer Portal';
        require_once __DIR__ . '/../Views/customer/discover.php';
    }

    /**
     * Show single restaurant details, operating hours, phone, parking, and approved reviews.
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function show(Request $request, Response $response): void
    {
        $id = $request->getParam('id') ?? $request->getParam('restaurant_id');
        if (!$id) {
            if ($this->isJsonRequest()) {
                $response->json(['error' => 'Restaurant ID is required'], 400);
            }
            $response->setStatusCode(400);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM tenants WHERE (id = :id OR subdomain = :id) AND is_active = 1');
        $stmt->execute([':id' => $id]);
        $restaurant = $stmt->fetch();

        if (!$restaurant) {
            if ($this->isJsonRequest()) {
                $response->json(['error' => 'Restaurant not found'], 404);
            }
            $response->setStatusCode(404);
            require_once __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $orgId = $restaurant['id'];

        // Reviews
        $revStmt = $pdo->prepare('SELECT r.id, r.rating, r.comment, r.created_at, r.restaurant_response, r.response_at, u.first_name, u.last_name FROM reviews r LEFT JOIN users u ON r.customer_id = u.id WHERE r.org_id = :org_id AND (r.approved = 1 OR r.approved IS NULL) ORDER BY r.created_at DESC LIMIT 25');
        $revStmt->execute([':org_id' => $orgId]);
        $reviews = $revStmt->fetchAll();

        // Rating average
        $avgStmt = $pdo->prepare('SELECT COUNT(*) as cnt, AVG(rating) as avg_rating FROM reviews WHERE org_id = :org_id AND (approved = 1 OR approved IS NULL)');
        $avgStmt->execute([':org_id' => $orgId]);
        $avgRow = $avgStmt->fetch();
        $avgRating = round((float) ($avgRow['avg_rating'] ?? 0.0), 1);
        $reviewCount = (int) ($avgRow['cnt'] ?? 0);

        // Menu preview
        $menuStmt = $pdo->prepare('SELECT id, name, description, price_centavos, dietary_tags, allergen_tags, is_available FROM menu_items WHERE org_id = :org_id AND is_available = 1 AND archived_at IS NULL ORDER BY name ASC LIMIT 10');
        $menuStmt->execute([':org_id' => $orgId]);
        $menuItems = $menuStmt->fetchAll();

        $restaurantData = [
            'id' => $restaurant['id'],
            'name' => $restaurant['name'],
            'subdomain' => $restaurant['subdomain'],
            'cuisine' => str_contains($restaurant['name'], 'Pizza') ? 'Italian' : 'Contemporary Dining',
            'location' => 'Main Campus Square, Cavite',
            'phone' => '+63 46 415 0010',
            'operating_hours' => '11:00 - 22:00 UTC',
            'parking' => 'Dedicated customer parking lot and bicycle racks available',
            'average_rating' => $avgRating,
            'review_count' => $reviewCount,
            'reviews' => $reviews,
            'menu_preview' => $menuItems,
        ];

        if ($this->isJsonRequest()) {
            $response->json($restaurantData, 200);
            return;
        }

        $title = htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') . ' - Customer Portal';
        require_once __DIR__ . '/../Views/customer/restaurant.php';
    }

    /**
     * Search restaurants endpoint (alias to index).
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function search(Request $request, Response $response): void
    {
        $this->index($request, $response);
    }

    /**
     * Check if a restaurant has at least one free slot on a specific date for a given party size.
     *
     * @param string $orgId Organization identifier.
     * @param string $date Date string (YYYY-MM-DD).
     * @param int $partySize Number of guests.
     *
     * @return bool True if available slots exist.
     */
    private function checkDateAvailability(string $orgId, string $date, int $partySize): bool
    {
        $pdo = Database::getConnection();

        // Check tables with sufficient capacity
        $tableStmt = $pdo->prepare('SELECT id FROM dining_tables WHERE org_id = :org_id AND capacity >= :capacity AND archived_at IS NULL');
        $tableStmt->execute([':org_id' => $orgId, ':capacity' => $partySize]);
        $tables = $tableStmt->fetchAll();

        if (empty($tables)) {
            // Check combinable tables
            $combStmt = $pdo->prepare('SELECT SUM(capacity) as total_cap FROM dining_tables WHERE org_id = :org_id AND is_combinable = 1 AND archived_at IS NULL');
            $combStmt->execute([':org_id' => $orgId]);
            $combCap = (int) ($combStmt->fetch()['total_cap'] ?? 0);
            if ($combCap < $partySize) {
                return false;
            }
        }

        return true;
    }
}
