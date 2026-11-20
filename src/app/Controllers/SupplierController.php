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
 * Supplier Controller: Organization-wide supplier management.
 *
 * Provides CRUD for suppliers scoped to organization (tenant).
 * Suppliers are linked to restaurants and inventory items for supply chain tracking.
 */
class SupplierController
{
    /**
     * List all suppliers for organization.
     *
     * Requires Admin role. Scoped to tenant organization.
     *
     * @param Request $request HTTP request object.
     * @param Response $response HTTP response object.
     *
     * @return void Renders suppliers list view.
     */
    public function index(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $organizationId = Session::get('tenant_id');

        $query = 'SELECT * FROM suppliers WHERE organization_id = :organization_id AND is_active = true';
        $params = ['organization_id' => $organizationId];

        if (!empty($_GET['q'])) {
            $query .= ' AND (name LIKE :search OR contact_email LIKE :search)';
            $params['search'] = '%' . trim((string) $_GET['q']) . '%';
        }

        $query .= ' ORDER BY name ASC';
        $suppliers = Database::executeQuery($query, $params)->fetchAll();

        require_once __DIR__ . '/../Views/suppliers/index.php';
    }

    /**
     * Show create supplier form.
     *
     * Requires Admin role.
     *
     * @param Request $request HTTP request object.
     * @param Response $response HTTP response object.
     *
     * @return void Renders create form view.
     */
    public function create(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $errors = [];
        $old = [
            'name' => '',
            'contact_email' => '',
            'phone' => '',
            'address' => '',
            'payment_terms' => '',
        ];
        require_once __DIR__ . '/../Views/suppliers/form.php';
    }

    /**
     * Store new supplier.
     *
     * Requires Admin role and valid CSRF token.
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
        $organizationId = Session::get('tenant_id');

        $old = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'contact_email' => trim((string) ($_POST['contact_email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'payment_terms' => trim((string) ($_POST['payment_terms'] ?? '')),
        ];

        $errors = array_filter([
            'name' => empty($old['name']) ? 'Supplier name is required.' : null,
            'contact_email' => !empty($old['contact_email']) ? Validator::email($old['contact_email']) : null,
            'phone' => !empty($old['phone']) && !Validator::phone($old['phone']) ? 'Invalid phone format.' : null,
        ]);

        if ($errors !== []) {
            $response->status(422);
            require_once __DIR__ . '/../Views/suppliers/form.php';
            return;
        }

        try {
            Database::executeQuery(
                'INSERT INTO suppliers (organization_id, name, contact_email, phone, address, payment_terms) VALUES (:organization_id, :name, :contact_email, :phone, :address, :payment_terms)',
                [
                    'organization_id' => $organizationId,
                    'name' => $old['name'],
                    'contact_email' => $old['contact_email'],
                    'phone' => $old['phone'],
                    'address' => $old['address'],
                    'payment_terms' => $old['payment_terms'],
                ]
            );

            Audit::log('CREATE', 'supplier', $old['name'], ['supplier' => $old]);
            header('Location: /admin/suppliers', true, 302);
            exit;
        } catch (PDOException $e) {
            $errors['_general'] = 'Failed to create supplier. Please try again.';
            $response->status(422);
            require_once __DIR__ . '/../Views/suppliers/form.php';
        }
    }

    /**
     * Show edit supplier form.
     *
     * Requires Admin role. Fetches supplier by ID.
     *
     * @param Request $request HTTP request object (params: id).
     * @param Response $response HTTP response object.
     *
     * @return void Renders edit form view.
     */
    public function edit(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $organizationId = Session::get('tenant_id');
        $id = (int) ($_GET['id'] ?? 0);

        $old = Database::executeQuery('SELECT * FROM suppliers WHERE id = :id AND organization_id = :organization_id', ['id' => $id, 'organization_id' => $organizationId])->fetch();
        if (!$old) {
            $response->status(404);
            require_once __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $errors = [];
        require_once __DIR__ . '/../Views/suppliers/form.php';
    }

    /**
     * Update supplier details.
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
        $organizationId = Session::get('tenant_id');
        $id = (int) ($_POST['id'] ?? 0);

        $existing = Database::executeQuery('SELECT * FROM suppliers WHERE id = :id AND organization_id = :organization_id', ['id' => $id, 'organization_id' => $organizationId])->fetch();
        if (!$existing) {
            $response->status(404);
            require_once __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $old = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'contact_email' => trim((string) ($_POST['contact_email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'payment_terms' => trim((string) ($_POST['payment_terms'] ?? '')),
        ];

        $errors = array_filter([
            'name' => empty($old['name']) ? 'Supplier name is required.' : null,
            'contact_email' => !empty($old['contact_email']) ? Validator::email($old['contact_email']) : null,
            'phone' => !empty($old['phone']) && !Validator::phone($old['phone']) ? 'Invalid phone format.' : null,
        ]);

        if ($errors !== []) {
            $response->status(422);
            require_once __DIR__ . '/../Views/suppliers/form.php';
            return;
        }

        try {
            Database::executeQuery(
                'UPDATE suppliers SET name = :name, contact_email = :contact_email, phone = :phone, address = :address, payment_terms = :payment_terms WHERE id = :id',
                [
                    'id' => $id,
                    'name' => $old['name'],
                    'contact_email' => $old['contact_email'],
                    'phone' => $old['phone'],
                    'address' => $old['address'],
                    'payment_terms' => $old['payment_terms'],
                ]
            );

            Audit::log('UPDATE', 'supplier', $id, ['old' => $existing, 'new' => $old]);
            header('Location: /admin/suppliers', true, 302);
            exit;
        } catch (PDOException $e) {
            $errors['_general'] = 'Failed to update supplier. Please try again.';
            $response->status(422);
            require_once __DIR__ . '/../Views/suppliers/form.php';
        }
    }
}
