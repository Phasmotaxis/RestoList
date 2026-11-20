<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\RoleMiddleware;

/**
 * User Dashboard Controller: Personal activity view and data portability export.
 *
 * Standard user access to own dashboard (recent audit logs, account info) and
 * RA 10173 data portability export (CSV download of personal data and activity history).
 */
class UserController
{
    /**
     * Render user personal dashboard with account info and recent activity.
     *
     * Requires User role. Fetches user profile joined with tenant info and 10 most recent
     * audit log entries. Displays in user dashboard view.
     *
     * @param Request $request HTTP request object.
     * @param Response $response HTTP response object.
     *
     * @return void Requires user dashboard view.
     *
     * @throws Exit On role check failure (HTTP 403).
     */
    public function dashboard(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_USER);

        $userId = Session::get('user_id');
        $tenantId = Session::get('tenant_id');

        $user = Database::executeQuery('SELECT u.*, t.name as tenant_name FROM users u JOIN tenants t ON u.tenant_id = t.id WHERE u.id = :id', ['id' => $userId])->fetch();
        $activities = Database::executeQuery('SELECT * FROM audit_logs WHERE user_id = :id ORDER BY created_at DESC LIMIT 10', ['id' => $userId])->fetchAll();

        require_once __DIR__ . '/../Views/dashboards/user.php';
    }

    /**
     * Export personal data as CSV (RA 10173 data portability right).
     *
     * Requires User role. Generates CSV with user profile and complete audit log history.
     * CSV header identifies it as RA 10173 Data Privacy Act portability export. Downloads
     * with filename eims_user_data_export.csv and exits after output.
     *
     * @param Request $request HTTP request object.
     * @param Response $response HTTP response object.
     *
     * @return void Outputs CSV and exits.
     *
     * @throws Exit Always exits after CSV output (no return to caller).
     */
    public function exportData(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_USER);

        $userId = Session::get('user_id');
        $user = Database::executeQuery('SELECT u.*, t.name as tenant_name FROM users u JOIN tenants t ON u.tenant_id = t.id WHERE u.id = :id', ['id' => $userId])->fetch();
        $logs = Database::executeQuery('SELECT * FROM audit_logs WHERE user_id = :id ORDER BY created_at DESC', ['id' => $userId])->fetchAll();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="eims_user_data_export.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['# Republic Act 10173 - Data Privacy Act of 2012 Right to Data Portability Export']);
        fputcsv($out, ['Property', 'Value']);
        fputcsv($out, ['User ID', $user['id'] ?? '']);
        fputcsv($out, ['First Name', $user['first_name'] ?? '']);
        fputcsv($out, ['Last Name', $user['last_name'] ?? '']);
        fputcsv($out, ['Email', $user['email'] ?? '']);
        fputcsv($out, ['Tenant ID', $user['tenant_id'] ?? '']);
        fputcsv($out, ['Tenant Name', $user['tenant_name'] ?? '']);
        fputcsv($out, ['Role Mask', (string) ($user['role_mask'] ?? '4')]);
        fputcsv($out, ['Account Created At', $user['created_at'] ?? '']);
        fputcsv($out, []);
        fputcsv($out, ['Audit Log ID', 'Action', 'Entity', 'Entity ID', 'Details', 'IP Address', 'Timestamp']);

        foreach ($logs as $log) {
            fputcsv($out, [
                $log['id'],
                $log['action'],
                $log['entity'],
                $log['entity_id'],
                $log['details'],
                $log['ip_address'],
                $log['created_at'],
            ]);
        }

        fclose($out);
        exit;
    }
}
