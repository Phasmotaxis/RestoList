<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Middleware/RoleMiddleware.php';

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\RoleMiddleware;

/**
 * Analytics Controller: Operational reporting, seat turnover, peak hours, and revenue metrics.
 *
 * Implements Subdomain 6 per docs/SPECIFICATION.md Section 2.2.B.
 */
class AnalyticsController
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
     * Executive analytics summary dashboard.
     */
    public function dashboard(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        // 1. Turnover & Capacity
        $capStmt = Database::executeQuery(
            'SELECT COALESCE(SUM(capacity), 0) as total_cap FROM dining_tables WHERE org_id = :org_id AND archived_at IS NULL',
            ['org_id' => $orgId]
        );
        $totalCapacity = (int) ($capStmt->fetch()['total_cap'] ?? 0);

        $resStmt = Database::executeQuery(
            'SELECT COUNT(*) as res_count, COALESCE(SUM(party_size), 0) as total_guests
             FROM reservations
             WHERE org_id = :org_id AND status IN ("confirmed", "seated")',
            ['org_id' => $orgId]
        );
        $resRow = $resStmt->fetch();
        $totalReservations = (int) ($resRow['res_count'] ?? 0);
        $totalGuests = (int) ($resRow['total_guests'] ?? 0);
        $turnoverRate = $totalCapacity > 0 ? round(($totalGuests / $totalCapacity), 2) : 0.0;

        // 2. No-Show Rate
        $allResStmt = Database::executeQuery(
            'SELECT COUNT(*) as total_all,
                    SUM(CASE WHEN status IN ("cancelled", "no_show") THEN 1 ELSE 0 END) as no_shows
             FROM reservations WHERE org_id = :org_id',
            ['org_id' => $orgId]
        );
        $allResRow = $allResStmt->fetch();
        $totalAll = (int) ($allResRow['total_all'] ?? 0);
        $noShows = (int) ($allResRow['no_shows'] ?? 0);
        $noShowRate = $totalAll > 0 ? round(($noShows / $totalAll) * 100, 2) : 0.0;

        // 3. Revenue in Centavos
        $revStmt = Database::executeQuery(
            'SELECT COALESCE(SUM(amount_centavos), 0) as rev FROM deposit_ledger WHERE org_id = :org_id',
            ['org_id' => $orgId]
        );
        $revenueCentavos = (int) ($revStmt->fetch()['rev'] ?? 0);

        $metrics = [
            'org_id' => $orgId,
            'total_capacity' => $totalCapacity,
            'total_reservations' => $totalReservations,
            'total_guests' => $totalGuests,
            'turnover_rate' => $turnoverRate,
            'no_shows' => $noShows,
            'no_show_rate_percent' => $noShowRate,
            'revenue_centavos' => $revenueCentavos,
            'revenue_formatted' => sprintf('$%.2f', $revenueCentavos / 100.0)
        ];

        if ($this->isJsonRequest()) {
            $response->json($metrics, 200);
            return;
        }

        $title = 'Restaurant Analytics & KPI';
        require_once __DIR__ . '/../Views/admin/analytics.php';
    }

    /**
     * Table and seat turnover rate metric.
     */
    public function turnover(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $capStmt = Database::executeQuery(
            'SELECT COALESCE(SUM(capacity), 0) as total_cap FROM dining_tables WHERE org_id = :org_id AND archived_at IS NULL',
            ['org_id' => $orgId]
        );
        $totalCapacity = (int) ($capStmt->fetch()['total_cap'] ?? 0);

        $resStmt = Database::executeQuery(
            'SELECT COUNT(*) as res_count, COALESCE(SUM(party_size), 0) as total_guests
             FROM reservations
             WHERE org_id = :org_id AND status IN ("confirmed", "seated")',
            ['org_id' => $orgId]
        );
        $resRow = $resStmt->fetch();
        $totalGuests = (int) ($resRow['total_guests'] ?? 0);
        $turnoverRate = $totalCapacity > 0 ? round(($totalGuests / $totalCapacity), 2) : 0.0;

        $response->json([
            'org_id' => $orgId,
            'total_capacity' => $totalCapacity,
            'seated_guests' => $totalGuests,
            'turnover_rate' => $turnoverRate
        ], 200);
    }

    /**
     * Peak booking hours distribution across operating day.
     */
    public function peakHours(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $driver = Database::getDriverName();
        $hourExpr = ($driver === 'sqlite') ? "strftime('%H', start_at)" : "LPAD(HOUR(start_at), 2, '0')";

        $stmt = Database::executeQuery(
            "SELECT {$hourExpr} as hour_of_day, COUNT(*) as reservation_count, SUM(party_size) as total_guests
             FROM reservations
             WHERE org_id = :org_id
             GROUP BY hour_of_day
             ORDER BY hour_of_day ASC",
            ['org_id' => $orgId]
        );
        $distribution = $stmt->fetchAll();

        $response->json([
            'org_id' => $orgId,
            'peak_hours' => $distribution
        ], 200);
    }

    /**
     * No-show and cancellation rate percentage.
     */
    public function noShowRate(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $stmt = Database::executeQuery(
            'SELECT COUNT(*) as total,
                    SUM(CASE WHEN status IN ("cancelled", "no_show") THEN 1 ELSE 0 END) as no_shows
             FROM reservations WHERE org_id = :org_id',
            ['org_id' => $orgId]
        );
        $row = $stmt->fetch();
        $total = (int) ($row['total'] ?? 0);
        $noShows = (int) ($row['no_shows'] ?? 0);
        $rate = $total > 0 ? round(($noShows / $total) * 100, 2) : 0.0;

        $response->json([
            'org_id' => $orgId,
            'total_reservations' => $total,
            'no_shows' => $noShows,
            'no_show_rate_percent' => $rate
        ], 200);
    }

    /**
     * Top menu items catalog.
     */
    public function topItems(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $stmt = Database::executeQuery(
            'SELECT id, name, price_centavos, is_available
             FROM menu_items
             WHERE org_id = :org_id AND archived_at IS NULL
             ORDER BY price_centavos DESC
             LIMIT 10',
            ['org_id' => $orgId]
        );
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            $item['price_centavos'] = (int) $item['price_centavos'];
            $item['is_available'] = (int) $item['is_available'];
        }
        unset($item);

        $response->json([
            'org_id' => $orgId,
            'top_items' => $items
        ], 200);
    }

    /**
     * Revenue metrics aggregated from deposit ledger (centavos).
     */
    public function revenue(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $stmt = Database::executeQuery(
            'SELECT COALESCE(SUM(amount_centavos), 0) as total_centavos FROM deposit_ledger WHERE org_id = :org_id',
            ['org_id' => $orgId]
        );
        $centavos = (int) ($stmt->fetch()['total_centavos'] ?? 0);

        $response->json([
            'org_id' => $orgId,
            'revenue_centavos' => $centavos,
            'revenue_formatted' => sprintf('$%.2f', $centavos / 100.0)
        ], 200);
    }
}
