<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Middleware\RoleMiddleware;
use PDOException;

/**
 * Invoice Controller: Supplier invoice management and accounts payable.
 *
 * Provides CRUD for invoices scoped to restaurant. Tracks invoice status (draft, issued, paid, overdue),
 * payment amounts, and due dates. Supports marking invoices paid and filtering by date range.
 */
class InvoiceController
{
    /**
     * List invoices for a restaurant.
     *
     * Requires Admin role. Filterable by status, supplier, and date range.
     * Highlights overdue invoices and shows payment summary.
     *
     * @param Request $request HTTP request (GET params: status, supplier_id, from_date, to_date, restaurant_id).
     * @param Response $response HTTP response object.
     *
     * @return void Renders invoices list view.
     */
    public function index(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $restaurantId = (int) ($_GET['restaurant_id'] ?? 0);

        $query = 'SELECT i.*, s.name as supplier_name FROM invoices i JOIN suppliers s ON i.supplier_id = s.id WHERE i.restaurant_id = :restaurant_id';
        $params = ['restaurant_id' => $restaurantId];

        if (!empty($_GET['status'])) {
            $query .= ' AND i.status = :status';
            $params['status'] = trim((string) $_GET['status']);
        }

        if (!empty($_GET['supplier_id'])) {
            $query .= ' AND i.supplier_id = :supplier_id';
            $params['supplier_id'] = (int) $_GET['supplier_id'];
        }

        if (!empty($_GET['from_date'])) {
            $query .= ' AND i.invoice_date >= :from_date';
            $params['from_date'] = trim((string) $_GET['from_date']);
        }

        if (!empty($_GET['to_date'])) {
            $query .= ' AND i.invoice_date <= :to_date';
            $params['to_date'] = trim((string) $_GET['to_date']);
        }

        $query .= ' ORDER BY i.due_date ASC';
        $invoices = Database::executeQuery($query, $params)->fetchAll();

        $overdueCount = (int) (Database::executeQuery('SELECT COUNT(*) as count FROM invoices WHERE restaurant_id = :restaurant_id AND status != :status AND due_date < CURDATE()', ['restaurant_id' => $restaurantId, 'status' => 'paid'])->fetch()['count'] ?? 0);
        $totalPayable = Database::executeQuery('SELECT SUM(total_amount - paid_amount) as total FROM invoices WHERE restaurant_id = :restaurant_id AND status != :status', ['restaurant_id' => $restaurantId, 'status' => 'paid'])->fetch()['total'] ?? 0;

        require_once __DIR__ . '/../Views/invoices/index.php';
    }

    /**
     * Show create invoice form.
     *
     * Requires Admin role.
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
        $suppliers = Database::executeQuery('SELECT id, name FROM suppliers WHERE is_active = true ORDER BY name ASC')->fetchAll();
        $errors = [];
        $old = [
            'supplier_id' => '',
            'invoice_number' => '',
            'invoice_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'total_amount' => '0',
            'notes' => '',
        ];
        require_once __DIR__ . '/../Views/invoices/form.php';
    }

    /**
     * Store new invoice.
     *
     * Requires Admin role and valid CSRF token. Validates invoice_number uniqueness per supplier,
     * due_date >= invoice_date, and total > 0. Logs audit trail.
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

        $supplierId = (int) ($_POST['supplier_id'] ?? 0);
        $old = [
            'supplier_id' => $supplierId,
            'invoice_number' => trim((string) ($_POST['invoice_number'] ?? '')),
            'invoice_date' => trim((string) ($_POST['invoice_date'] ?? '')),
            'due_date' => trim((string) ($_POST['due_date'] ?? '')),
            'total_amount' => (float) ($_POST['total_amount'] ?? 0),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];

        $errors = array_filter([
            'supplier_id' => $supplierId === 0 ? 'Supplier is required.' : null,
            'invoice_number' => empty($old['invoice_number']) ? 'Invoice number is required.' : null,
            'invoice_date' => empty($old['invoice_date']) ? 'Invoice date is required.' : null,
            'due_date' => empty($old['due_date']) ? 'Due date is required.' : null,
            'total_amount' => $old['total_amount'] <= 0 ? 'Total amount must be greater than zero.' : null,
        ]);

        if (empty($errors['due_date']) && strtotime($old['due_date']) < strtotime($old['invoice_date'])) {
            $errors['due_date'] = 'Due date must be on or after invoice date.';
        }

        if (empty($errors) && !empty($old['invoice_number'])) {
            $existing = Database::executeQuery('SELECT id FROM invoices WHERE supplier_id = :supplier_id AND invoice_number = :invoice_number', ['supplier_id' => $supplierId, 'invoice_number' => $old['invoice_number']])->fetch();
            if ($existing) {
                $errors['invoice_number'] = 'Invoice number already exists for this supplier.';
            }
        }

        if ($errors !== []) {
            $suppliers = Database::executeQuery('SELECT id, name FROM suppliers WHERE is_active = true ORDER BY name ASC')->fetchAll();
            $response->status(422);
            require_once __DIR__ . '/../Views/invoices/form.php';
            return;
        }

        try {
            Database::executeQuery(
                'INSERT INTO invoices (supplier_id, restaurant_id, invoice_number, invoice_date, due_date, total_amount, status) VALUES (:supplier_id, :restaurant_id, :invoice_number, :invoice_date, :due_date, :total_amount, :status)',
                [
                    'supplier_id' => $supplierId,
                    'restaurant_id' => $restaurantId,
                    'invoice_number' => $old['invoice_number'],
                    'invoice_date' => $old['invoice_date'],
                    'due_date' => $old['due_date'],
                    'total_amount' => $old['total_amount'],
                    'status' => 'issued',
                ]
            );

            Audit::log('CREATE', 'invoice', $old['invoice_number'], ['invoice' => $old]);
            header('Location: /admin/restaurants/' . $restaurantId . '/invoices', true, 302);
            exit;
        } catch (PDOException $e) {
            $errors['_general'] = 'Failed to create invoice. Please try again.';
            $suppliers = Database::executeQuery('SELECT id, name FROM suppliers WHERE is_active = true ORDER BY name ASC')->fetchAll();
            $response->status(422);
            require_once __DIR__ . '/../Views/invoices/form.php';
        }
    }

    /**
     * Show edit invoice form.
     *
     * Requires Admin role. Fetches invoice by ID.
     *
     * @param Request $request HTTP request object (params: id, restaurant_id).
     * @param Response $response HTTP response object.
     *
     * @return void Renders edit form view.
     */
    public function edit(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $id = (int) ($_GET['id'] ?? 0);
        $restaurantId = (int) ($_GET['restaurant_id'] ?? 0);

        $old = Database::executeQuery('SELECT * FROM invoices WHERE id = :id AND restaurant_id = :restaurant_id', ['id' => $id, 'restaurant_id' => $restaurantId])->fetch();
        if (!$old) {
            $response->status(404);
            require_once __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $suppliers = Database::executeQuery('SELECT id, name FROM suppliers WHERE is_active = true ORDER BY name ASC')->fetchAll();
        $errors = [];
        require_once __DIR__ . '/../Views/invoices/form.php';
    }

    /**
     * Update invoice details.
     *
     * Requires Admin role and valid CSRF token.
     *
     * @param Request $request HTTP request with POST body.
     * @param Response $response HTTP response object.
     *
     * @return void Redirects on success or re-renders form with errors.
     */
    public function update(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        Session::verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);

        $existing = Database::executeQuery('SELECT * FROM invoices WHERE id = :id AND restaurant_id = :restaurant_id', ['id' => $id, 'restaurant_id' => $restaurantId])->fetch();
        if (!$existing) {
            $response->status(404);
            require_once __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $new = [
            'total_amount' => (float) ($_POST['total_amount'] ?? 0),
            'paid_amount' => (float) ($_POST['paid_amount'] ?? 0),
            'status' => trim((string) ($_POST['status'] ?? 'issued')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];

        $errors = array_filter([
            'total_amount' => $new['total_amount'] <= 0 ? 'Total amount must be greater than zero.' : null,
            'paid_amount' => $new['paid_amount'] < 0 ? 'Paid amount cannot be negative.' : null,
        ]);

        if ($errors !== []) {
            $suppliers = Database::executeQuery('SELECT id, name FROM suppliers WHERE is_active = true ORDER BY name ASC')->fetchAll();
            $response->status(422);
            require_once __DIR__ . '/../Views/invoices/form.php';
            return;
        }

        try {
            Database::executeQuery(
                'UPDATE invoices SET total_amount = :total_amount, paid_amount = :paid_amount, status = :status, notes = :notes WHERE id = :id',
                [
                    'id' => $id,
                    'total_amount' => $new['total_amount'],
                    'paid_amount' => $new['paid_amount'],
                    'status' => $new['status'],
                    'notes' => $new['notes'],
                ]
            );

            Audit::log('UPDATE', 'invoice', $id, ['old' => $existing, 'new' => $new]);
            header('Location: /admin/restaurants/' . $restaurantId . '/invoices', true, 302);
            exit;
        } catch (PDOException $e) {
            $errors['_general'] = 'Failed to update invoice. Please try again.';
            $suppliers = Database::executeQuery('SELECT id, name FROM suppliers WHERE is_active = true ORDER BY name ASC')->fetchAll();
            $response->status(422);
            require_once __DIR__ . '/../Views/invoices/form.php';
        }
    }

    /**
     * Mark invoice as paid.
     *
     * Requires Admin role and valid CSRF token. Sets paid_amount to total_amount
     * and status to 'paid'. Logs audit trail.
     *
     * @param Request $request HTTP request with POST body (id, restaurant_id).
     * @param Response $response HTTP response object.
     *
     * @return void Redirects on success.
     */
    public function markPaid(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        Session::verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);

        $invoice = Database::executeQuery('SELECT * FROM invoices WHERE id = :id AND restaurant_id = :restaurant_id', ['id' => $id, 'restaurant_id' => $restaurantId])->fetch();
        if (!$invoice) {
            $response->status(404);
            require_once __DIR__ . '/../Views/errors/404.php';
            return;
        }

        try {
            Database::executeQuery(
                'UPDATE invoices SET status = :status, paid_amount = :paid_amount WHERE id = :id',
                [
                    'id' => $id,
                    'status' => 'paid',
                    'paid_amount' => $invoice['total_amount'],
                ]
            );

            Audit::log('UPDATE', 'invoice', $id, ['action' => 'mark_paid', 'old_status' => $invoice['status'], 'new_status' => 'paid']);
            header('Location: /admin/restaurants/' . $restaurantId . '/invoices', true, 302);
            exit;
        } catch (PDOException $e) {
            $response->status(500);
            require_once __DIR__ . '/../Views/errors/500.php';
        }
    }
}
