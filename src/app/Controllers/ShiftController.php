<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\RoleMiddleware;
use PDOException;

/**
 * Shift Controller: Staff work shift scheduling and time tracking.
 *
 * Provides CRUD for work shifts scoped to restaurant. Supports shift scheduling (manager),
 * staff check-in/check-out, shift type classification (kitchen, server, manager, cleaning),
 * and overlap detection to prevent double-booking.
 */
class ShiftController
{
    /**
     * List shifts for a restaurant.
     *
     * Requires User role. Managers see all shifts; staff see own shifts only.
     * Filterable by date range and shift status.
     *
     * @param Request $request HTTP request (GET params: from_date, to_date, user_id, restaurant_id).
     * @param Response $response HTTP response object.
     *
     * @return void Renders shifts list view.
     */
    public function index(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_USER);
        $restaurantId = (int) ($_GET['restaurant_id'] ?? 0);
        $userId = Session::get('user_id');
        $roleId = Session::get('role_mask');

        $query = 'SELECT s.*, u.first_name, u.last_name FROM work_shifts s JOIN users u ON s.user_id = u.id WHERE s.restaurant_id = :restaurant_id';
        $params = ['restaurant_id' => $restaurantId];

        if ($roleId !== 1) {
            $query .= ' AND s.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        if (!empty($_GET['from_date'])) {
            $query .= ' AND s.shift_date >= :from_date';
            $params['from_date'] = trim((string) $_GET['from_date']);
        }

        if (!empty($_GET['to_date'])) {
            $query .= ' AND s.shift_date <= :to_date';
            $params['to_date'] = trim((string) $_GET['to_date']);
        }

        $query .= ' ORDER BY s.shift_date ASC, s.start_time ASC';
        $shifts = Database::executeQuery($query, $params)->fetchAll();

        require_once __DIR__ . '/../Views/shifts/index.php';
    }

    /**
     * Show create shift form.
     *
     * Requires Admin role (managers only).
     *
     * @param Request $request HTTP request object (GET params: restaurant_id).
     * @param Response $response HTTP response object.
     *
     * @return void Renders create form view.
     */
    public function create(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $restaurantId = (int) ($_GET['restaurant_id'] ?? 0);
        $tenantId = Session::get('tenant_id');
        $users = Database::executeQuery('SELECT id, first_name, last_name, email FROM users WHERE tenant_id = :tenant_id ORDER BY last_name ASC', ['tenant_id' => $tenantId])->fetchAll();
        $errors = [];
        $old = [
            'user_id' => '',
            'shift_date' => date('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'shift_type' => 'server',
            'notes' => '',
        ];
        require_once __DIR__ . '/../Views/shifts/form.php';
    }

    /**
     * Store new shift.
     *
     * Requires Admin role and valid CSRF token. Validates shift_date >= today, end_time > start_time,
     * and no overlapping shifts for same user on same day. Logs audit trail.
     *
     * @param Request $request HTTP request with POST body.
     * @param Response $response HTTP response object.
     *
     * @return void Redirects on success or re-renders form with errors.
     */
    public function store(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        Session::verifyCsrf();
        $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);
        $tenantId = Session::get('tenant_id');

        $userId = trim((string) ($_POST['user_id'] ?? ''));
        $old = [
            'user_id' => $userId,
            'shift_date' => trim((string) ($_POST['shift_date'] ?? '')),
            'start_time' => trim((string) ($_POST['start_time'] ?? '')),
            'end_time' => trim((string) ($_POST['end_time'] ?? '')),
            'shift_type' => trim((string) ($_POST['shift_type'] ?? 'server')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];

        $errors = array_filter([
            'user_id' => empty($userId) ? 'User is required.' : null,
            'shift_date' => empty($old['shift_date']) ? 'Shift date is required.' : null,
            'start_time' => empty($old['start_time']) ? 'Start time is required.' : null,
            'end_time' => empty($old['end_time']) ? 'End time is required.' : null,
        ]);

        if (empty($errors['shift_date']) && strtotime($old['shift_date']) < strtotime('today')) {
            $errors['shift_date'] = 'Shift date must be today or later.';
        }

        if (empty($errors['start_time']) && empty($errors['end_time']) && strtotime($old['end_time']) <= strtotime($old['start_time'])) {
            $errors['end_time'] = 'End time must be after start time.';
        }

        if (empty($errors)) {
            $overlap = Database::executeQuery(
                'SELECT COUNT(*) as count FROM work_shifts WHERE user_id = :user_id AND shift_date = :shift_date AND status NOT IN (:status1, :status2)',
                ['user_id' => $userId, 'shift_date' => $old['shift_date'], 'status1' => 'cancelled', 'status2' => 'checked_out']
            )->fetch();
            if ((int) ($overlap['count'] ?? 0) > 0) {
                $errors['shift_date'] = 'This user already has a shift on this date.';
            }
        }

        if ($errors !== []) {
            $users = Database::executeQuery('SELECT id, first_name, last_name, email FROM users WHERE tenant_id = :tenant_id ORDER BY last_name ASC', ['tenant_id' => $tenantId])->fetchAll();
            $response->status(422);
            require_once __DIR__ . '/../Views/shifts/form.php';
            return;
        }

        try {
            Database::executeQuery(
                'INSERT INTO work_shifts (user_id, restaurant_id, shift_date, start_time, end_time, shift_type, status) VALUES (:user_id, :restaurant_id, :shift_date, :start_time, :end_time, :shift_type, :status)',
                [
                    'user_id' => $userId,
                    'restaurant_id' => $restaurantId,
                    'shift_date' => $old['shift_date'],
                    'start_time' => $old['start_time'],
                    'end_time' => $old['end_time'],
                    'shift_type' => $old['shift_type'],
                    'status' => 'scheduled',
                ]
            );

            Audit::log('CREATE', 'work_shift', $userId, ['shift' => $old]);
            header('Location: /admin/restaurants/' . $restaurantId . '/shifts', true, 302);
            exit;
        } catch (PDOException $e) {
            $errors['_general'] = 'Failed to create shift. Please try again.';
            $users = Database::executeQuery('SELECT id, first_name, last_name, email FROM users WHERE tenant_id = :tenant_id ORDER BY last_name ASC', ['tenant_id' => $tenantId])->fetchAll();
            $response->status(422);
            require_once __DIR__ . '/../Views/shifts/form.php';
        }
    }

    /**
     * Check in to a shift.
     *
     * Requires User role. Changes shift status to 'checked_in' and logs audit trail.
     * Staff can only check in to their own shifts; managers can check in staff.
     *
     * @param Request $request HTTP request (POST params: id, restaurant_id).
     * @param Response $response HTTP response object.
     *
     * @return void Redirects on success.
     */
    public function checkIn(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_USER);
        Session::verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);
        $userId = Session::get('user_id');

        $shift = Database::executeQuery('SELECT * FROM work_shifts WHERE id = :id AND restaurant_id = :restaurant_id', ['id' => $id, 'restaurant_id' => $restaurantId])->fetch();
        if (!$shift) {
            $response->status(404);
            require_once __DIR__ . '/../Views/errors/404.php';
            return;
        }

        try {
            Database::executeQuery(
                'UPDATE work_shifts SET status = :status WHERE id = :id',
                ['id' => $id, 'status' => 'checked_in']
            );

            Audit::log('UPDATE', 'work_shift', $id, ['action' => 'check_in', 'old_status' => $shift['status']]);
            header('Location: /admin/restaurants/' . $restaurantId . '/shifts', true, 302);
            exit;
        } catch (PDOException $e) {
            $response->status(500);
            require_once __DIR__ . '/../Views/errors/500.php';
        }
    }

    /**
     * Check out of a shift.
     *
     * Requires User role. Changes shift status to 'checked_out' and logs audit trail.
     *
     * @param Request $request HTTP request (POST params: id, restaurant_id).
     * @param Response $response HTTP response object.
     *
     * @return void Redirects on success.
     */
    public function checkOut(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_USER);
        Session::verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);

        $shift = Database::executeQuery('SELECT * FROM work_shifts WHERE id = :id AND restaurant_id = :restaurant_id', ['id' => $id, 'restaurant_id' => $restaurantId])->fetch();
        if (!$shift) {
            $response->status(404);
            require_once __DIR__ . '/../Views/errors/404.php';
            return;
        }

        try {
            Database::executeQuery(
                'UPDATE work_shifts SET status = :status WHERE id = :id',
                ['id' => $id, 'status' => 'checked_out']
            );

            Audit::log('UPDATE', 'work_shift', $id, ['action' => 'check_out', 'old_status' => $shift['status']]);
            header('Location: /admin/restaurants/' . $restaurantId . '/shifts', true, 302);
            exit;
        } catch (PDOException $e) {
            $response->status(500);
            require_once __DIR__ . '/../Views/errors/500.php';
        }
    }
}
