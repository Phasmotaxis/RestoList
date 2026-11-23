<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\FeatureFlagService;
use App\Services\LoyaltyLedgerService;

/**
 * Loyalty Portal Controller: Feature-gated customer loyalty points and rewards.
 *
 * Enforces feature flag guard: operations are strictly rejected if the restaurant has
 * disabled loyalty programs. Calculates net points from append-only loyalty_ledger.
 */
class LoyaltyPortalController
{
    /**
     * Check if loyalty feature is enabled for restaurant.
     *
     * @param string $orgId Organization identifier.
     *
     * @return bool True if loyalty enabled.
     */
    private function isFeatureEnabled(string $orgId): bool
    {
        return FeatureFlagService::isEnabled($orgId, 'loyalty')
            || FeatureFlagService::isEnabled($orgId, 'enable_loyalty');
    }

    /**
     * Resolve or register customer by email.
     */
    private function resolveCustomerId(\PDO $pdo, string $orgId, string $customerId, string $email): string
    {
        if ($customerId !== '') {
            return $customerId;
        }

        if ($email === '') {
            $currentUser = Session::get('user');
            if ($currentUser && !empty($currentUser['id'])) {
                return (string) $currentUser['id'];
            }
            return '';
        }

        $uStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND tenant_id = :org_id');
        $uStmt->execute([':email' => $email, ':org_id' => $orgId]);
        $uRow = $uStmt->fetch();
        if ($uRow) {
            return (string) $uRow['id'];
        }

        $newId = bin2hex(random_bytes(18));
        $insUser = $pdo->prepare('INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role_mask, created_at) VALUES (?, ?, ?, ?, ?, ?, 4, CURRENT_TIMESTAMP)');
        $insUser->execute([
            $newId,
            $orgId,
            $email,
            password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
            'Guest',
            'Diner',
        ]);
        return $newId;
    }

    /**
     * Get diner loyalty points balance and ledger history.
     *
     * Route: GET /loyalty/points
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function getPoints(Request $request, Response $response): void
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
                'message' => 'Loyalty program is not enabled for this restaurant.',
                'feature' => 'loyalty',
            ], 403);
            return;
        }

        $email = trim((string) ($request->getParam('customer_email') ?? $request->getParam('email') ?? ''));
        $customerId = trim((string) ($request->getParam('customer_id') ?? ''));

        $pdo = Database::getConnection();
        $customerId = $this->resolveCustomerId($pdo, $orgId, $customerId, $email);

        if ($customerId === '') {
            $response->json(['error' => 'Customer identification (customer_id or email) is required'], 400);
            return;
        }

        // Calculate running balance and fetch recent ledger history via LoyaltyLedgerService
        $balancePoints = LoyaltyLedgerService::getBalance($orgId, $customerId);
        $history = LoyaltyLedgerService::getLedger($orgId, $customerId, 50);

        $response->json([
            'org_id' => $orgId,
            'customer_id' => $customerId,
            'balance_points' => $balancePoints,
            'ledger' => $history,
        ], 200);
    }

    /**
     * Redeem diner loyalty points for rewards.
     *
     * Route: POST /loyalty/redeem
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function redeem(Request $request, Response $response): void
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
                'message' => 'Loyalty program is not enabled for this restaurant.',
                'feature' => 'loyalty',
            ], 403);
            return;
        }

        $pointsToRedeem = (int) ($body['points'] ?? $body['points_delta'] ?? 0);
        $email = trim((string) ($body['customer_email'] ?? $body['email'] ?? ''));
        $customerId = trim((string) ($body['customer_id'] ?? ''));
        $rewardReason = trim((string) ($body['reason'] ?? $body['reward_description'] ?? 'Reward redemption'));

        $errors = [];
        if ($pointsToRedeem <= 0) {
            $errors['points'] = 'Points to redeem must be a positive integer';
        }

        $pdo = Database::getConnection();
        $customerId = $this->resolveCustomerId($pdo, $orgId, $customerId, $email);

        if ($customerId === '') {
            $errors['customer_id'] = 'Valid customer email or customer_id is required';
        }

        if (!empty($errors)) {
            $response->json(['error' => 'Validation failed', 'fields' => $errors], 422);
            return;
        }

        // Check sufficient point balance via LoyaltyLedgerService
        $currentBalance = LoyaltyLedgerService::getBalance($orgId, $customerId);

        if ($currentBalance < $pointsToRedeem) {
            $response->json([
                'error' => 'Insufficient points',
                'current_balance' => $currentBalance,
                'requested_points' => $pointsToRedeem,
            ], 422);
            return;
        }

        try {
            $ledgerId = LoyaltyLedgerService::redeemPoints($orgId, $customerId, $pointsToRedeem, $rewardReason);
        } catch (\RuntimeException $e) {
            $response->json([
                'error' => 'Insufficient points',
                'current_balance' => $currentBalance,
                'requested_points' => $pointsToRedeem,
            ], 422);
            return;
        }

        Audit::log(
            $orgId,
            $customerId,
            'REDEEM',
            'loyalty_ledger',
            $ledgerId,
            "Redeemed {$pointsToRedeem} points for customer {$customerId}"
        );

        $newBalance = $currentBalance - $pointsToRedeem;

        $response->json([
            'id' => $ledgerId,
            'org_id' => $orgId,
            'customer_id' => $customerId,
            'points_redeemed' => $pointsToRedeem,
            'new_balance' => $newBalance,
            'reason' => $rewardReason,
        ], 200);
    }

    /**
     * Award loyalty points to a diner.
     *
     * Route: POST /loyalty/earn
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function earn(Request $request, Response $response): void
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
                'message' => 'Loyalty program is not enabled for this restaurant.',
                'feature' => 'loyalty',
            ], 403);
            return;
        }

        $points = (int) ($body['points'] ?? 0);
        $email = trim((string) ($body['customer_email'] ?? $body['email'] ?? ''));
        $customerId = trim((string) ($body['customer_id'] ?? ''));
        $reason = trim((string) ($body['reason'] ?? 'Dining rewards earned'));

        if ($points <= 0) {
            $response->json(['error' => 'Points to award must be a positive integer'], 422);
            return;
        }

        $pdo = Database::getConnection();
        $customerId = $this->resolveCustomerId($pdo, $orgId, $customerId, $email);

        if ($customerId === '') {
            $response->json(['error' => 'Customer identification is required'], 400);
            return;
        }

        $ledgerId = LoyaltyLedgerService::earnPoints($orgId, $customerId, $points, $reason);

        Audit::log(
            $orgId,
            $customerId,
            'EARN',
            'loyalty_ledger',
            $ledgerId,
            "Awarded {$points} points to customer {$customerId}"
        );

        $newBalance = LoyaltyLedgerService::getBalance($orgId, $customerId);

        $response->json([
            'id' => $ledgerId,
            'org_id' => $orgId,
            'customer_id' => $customerId,
            'points_awarded' => $points,
            'new_balance' => $newBalance,
            'reason' => $reason,
        ], 201);
    }
}
