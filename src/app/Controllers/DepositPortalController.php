<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\FeatureFlagService;

/**
 * Deposit Portal Controller: Feature-gated diner deposit ledger operations.
 *
 * Enforces feature flag guard: operations are strictly rejected if the restaurant has
 * disabled deposits. Maintains append-only immutable financial records in deposit_ledger.
 */
class DepositPortalController
{
    /**
     * Check if deposits feature is enabled for restaurant.
     *
     * @param string $orgId Organization identifier.
     *
     * @return bool True if deposits enabled.
     */
    private function isFeatureEnabled(string $orgId): bool
    {
        return FeatureFlagService::isEnabled($orgId, 'deposits')
            || FeatureFlagService::isEnabled($orgId, 'enable_deposits');
    }

    /**
     * Get diner deposit balance and ledger history.
     *
     * Route: GET /deposits/balance
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function getBalance(Request $request, Response $response): void
    {
        $orgId = trim((string) ($request->getParam('org_id') ?? $request->getParam('restaurant_id') ?? ''));
        if ($orgId === '') {
            $response->json(['error' => 'Organization ID is required'], 400);
            return;
        }

        // Feature flag enforcement
        if (!$this->isFeatureEnabled($orgId)) {
            $response->json([
                'error' => 'Feature disabled',
                'message' => 'Deposits are not enabled for this restaurant.',
                'feature' => 'deposits',
            ], 403);
            return;
        }

        $email = trim((string) ($request->getParam('customer_email') ?? $request->getParam('email') ?? ''));
        $customerId = trim((string) ($request->getParam('customer_id') ?? ''));

        $pdo = Database::getConnection();

        if ($customerId === '' && $email !== '') {
            $uStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND tenant_id = :org_id');
            $uStmt->execute([':email' => $email, ':org_id' => $orgId]);
            $uRow = $uStmt->fetch();
            if ($uRow) {
                $customerId = $uRow['id'];
            }
        }

        if ($customerId === '') {
            $currentUser = Session::get('user');
            if ($currentUser && !empty($currentUser['id'])) {
                $customerId = $currentUser['id'];
            }
        }

        if ($customerId === '') {
            $response->json(['error' => 'Customer identification (customer_id or email) is required'], 400);
            return;
        }

        // Calculate running balance: SUM(amount_centavos)
        $balStmt = $pdo->prepare('SELECT COALESCE(SUM(amount_centavos), 0) as balance_centavos FROM deposit_ledger WHERE org_id = :org_id AND customer_id = :customer_id');
        $balStmt->execute([':org_id' => $orgId, ':customer_id' => $customerId]);
        $balanceCentavos = (int) ($balStmt->fetch()['balance_centavos'] ?? 0);

        // Fetch recent ledger history
        $histStmt = $pdo->prepare('SELECT id, amount_centavos, action, reason, created_at FROM deposit_ledger WHERE org_id = :org_id AND customer_id = :customer_id ORDER BY created_at DESC LIMIT 50');
        $histStmt->execute([':org_id' => $orgId, ':customer_id' => $customerId]);
        $history = $histStmt->fetchAll();

        $formattedHistory = [];
        foreach ($history as $row) {
            $cents = (int) $row['amount_centavos'];
            $formattedHistory[] = [
                'id' => $row['id'],
                'amount_centavos' => $cents,
                'amount_formatted' => sprintf('$%.2f', $cents / 100.0),
                'action' => $row['action'],
                'reason' => $row['reason'],
                'created_at' => $row['created_at'],
            ];
        }

        $response->json([
            'org_id' => $orgId,
            'customer_id' => $customerId,
            'balance_centavos' => $balanceCentavos,
            'balance_formatted' => sprintf('$%.2f', $balanceCentavos / 100.0),
            'ledger' => $formattedHistory,
        ], 200);
    }

    /**
     * Place deposit hold for reservation.
     *
     * Route: POST /deposits/hold
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function hold(Request $request, Response $response): void
    {
        $body = $request->getBody();
        $orgId = trim((string) ($body['org_id'] ?? $body['restaurant_id'] ?? ''));

        if ($orgId === '') {
            $response->json(['error' => 'Organization ID is required'], 400);
            return;
        }

        // Feature flag enforcement
        if (!$this->isFeatureEnabled($orgId)) {
            $response->json([
                'error' => 'Feature disabled',
                'message' => 'Deposits are not enabled for this restaurant.',
                'feature' => 'deposits',
            ], 403);
            return;
        }

        $amountCentavos = (int) ($body['amount_centavos'] ?? 0);
        $email = trim((string) ($body['customer_email'] ?? $body['email'] ?? ''));
        $customerId = trim((string) ($body['customer_id'] ?? ''));
        $reservationId = trim((string) ($body['reservation_id'] ?? ''));
        $reason = trim((string) ($body['reason'] ?? 'Reservation deposit hold'));

        $errors = [];
        if ($amountCentavos <= 0) {
            $errors['amount_centavos'] = 'Deposit amount must be a positive integer in centavos';
        }

        $pdo = Database::getConnection();

        if ($customerId === '' && $email !== '') {
            $uStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND tenant_id = :org_id');
            $uStmt->execute([':email' => $email, ':org_id' => $orgId]);
            $uRow = $uStmt->fetch();
            if ($uRow) {
                $customerId = $uRow['id'];
            } else {
                $customerId = bin2hex(random_bytes(18));
                $insUser = $pdo->prepare('INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role_mask, created_at) VALUES (?, ?, ?, ?, ?, ?, 4, CURRENT_TIMESTAMP)');
                $insUser->execute([
                    $customerId,
                    $orgId,
                    $email,
                    password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
                    'Guest',
                    'Diner',
                ]);
            }
        }

        if ($customerId === '') {
            $errors['customer_id'] = 'Valid customer email or customer_id is required';
        }

        if (!empty($errors)) {
            $response->json(['error' => 'Validation failed', 'fields' => $errors], 422);
            return;
        }

        $ledgerId = bin2hex(random_bytes(18));
        $fullReason = $reservationId !== '' ? "{$reason} (Res: {$reservationId})" : $reason;

        $ins = $pdo->prepare('INSERT INTO deposit_ledger (id, org_id, customer_id, amount_centavos, action, reason, created_at) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
        $ins->execute([
            $ledgerId,
            $orgId,
            $customerId,
            $amountCentavos,
            'hold',
            $fullReason,
        ]);

        Audit::log(
            $orgId,
            $customerId,
            'HOLD',
            'deposit_ledger',
            $ledgerId,
            "Held deposit of {$amountCentavos} centavos for customer {$customerId}"
        );

        // Fetch updated balance
        $balStmt = $pdo->prepare('SELECT COALESCE(SUM(amount_centavos), 0) as balance_centavos FROM deposit_ledger WHERE org_id = :org_id AND customer_id = :customer_id');
        $balStmt->execute([':org_id' => $orgId, ':customer_id' => $customerId]);
        $newBalance = (int) ($balStmt->fetch()['balance_centavos'] ?? 0);

        $response->json([
            'id' => $ledgerId,
            'org_id' => $orgId,
            'customer_id' => $customerId,
            'action' => 'hold',
            'amount_centavos' => $amountCentavos,
            'amount_formatted' => sprintf('$%.2f', $amountCentavos / 100.0),
            'new_balance_centavos' => $newBalance,
            'new_balance_formatted' => sprintf('$%.2f', $newBalance / 100.0),
            'reason' => $fullReason,
        ], 201);
    }
}
