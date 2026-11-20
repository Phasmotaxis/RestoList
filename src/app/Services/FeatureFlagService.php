<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Feature Flag Service: Per-tenant optional module toggles.
 *
 * Manages feature flag state for optional restaurant ERP modules (floor_plan, inventory_erp,
 * deposits, loyalty programs). Flags are stored per-tenant in org_feature_flags table with
 * in-memory cache (5-minute TTL) to avoid per-request database queries. Supports individual
 * flag checks and bulk retrieval for admin configuration dashboards.
 */
class FeatureFlagService
{
    private const CACHE_TTL = 300; // 5 minutes
    private static array $cache = [];
    private static array $cacheTime = [];

    /**
     * Check if feature flag is enabled for tenant.
     *
     * Queries in-memory cache first (if within TTL); on cache miss, fetches from org_feature_flags
     * table and caches result. Returns false if flag not found (safe default for unknown features).
     *
     * @param string $orgId Tenant/organization identifier.
     * @param string $flagName Feature flag name (e.g. 'floor_plan', 'inventory_erp', 'deposits').
     *
     * @return bool true if enabled; false if disabled or not found.
     *
     * @see FeatureFlagService::toggle() Enable/disable a flag.
     */
    public static function isEnabled(string $orgId, string $flagName): bool
    {
        $cacheKey = "{$orgId}:{$flagName}";

        // Serve from cache if within TTL.
        if (isset(self::$cache[$cacheKey]) && time() - self::$cacheTime[$cacheKey] < self::CACHE_TTL) {
            return self::$cache[$cacheKey];
        }

        // Cache miss: query database and store result.
        $row = Database::executeQuery(
            'SELECT enabled FROM org_feature_flags WHERE org_id = :org_id AND flag_name = :flag_name',
            ['org_id' => $orgId, 'flag_name' => $flagName]
        )->fetch();

        $enabled = (bool) ($row['enabled'] ?? false);

        // Cache result with timestamp for TTL evaluation.
        self::$cache[$cacheKey] = $enabled;
        self::$cacheTime[$cacheKey] = time();

        return $enabled;
    }

    /**
     * Retrieve all feature flags for tenant.
     *
     * Queries all flags in org_feature_flags for $orgId and returns associative array
     * [flag_name => enabled_bool]. Caches full result for 5 minutes. Used on admin settings pages.
     *
     * @param string $orgId Tenant/organization identifier.
     *
     * @return array<string, bool> Map of flag name to enabled state (e.g. ['floor_plan' => true, 'deposits' => false]).
     */
    public static function getAllFlags(string $orgId): array
    {
        $cacheKey = "all:{$orgId}";

        // Serve cached result if within TTL.
        if (isset(self::$cache[$cacheKey]) && time() - self::$cacheTime[$cacheKey] < self::CACHE_TTL) {
            return self::$cache[$cacheKey];
        }

        // Cache miss: query all flags for tenant.
        $rows = Database::executeQuery(
            'SELECT flag_name, enabled FROM org_feature_flags WHERE org_id = :org_id',
            ['org_id' => $orgId]
        )->fetchAll();

        $flags = [];
        foreach ($rows as $row) {
            $flags[$row['flag_name']] = (bool) $row['enabled'];
        }

        // Cache result.
        self::$cache[$cacheKey] = $flags;
        self::$cacheTime[$cacheKey] = time();

        return $flags;
    }

    /**
     * Enable or disable feature flag for tenant.
     *
     * Updates existing flag or inserts new flag entry. Clears cache for this org/flag
     * to ensure immediate visibility of toggle. Used by admin configuration actions.
     *
     * @param string $orgId Tenant/organization identifier.
     * @param string $flagName Feature flag name.
     * @param bool $enabled New state (true to enable, false to disable).
     *
     * @return void
     *
     * @throws PDOException If INSERT or UPDATE fails.
     */
    public static function toggle(string $orgId, string $flagName, bool $enabled): void
    {
        self::clearCache($orgId, $flagName);

        // Check if flag already exists for this tenant.
        $existing = Database::executeQuery(
            'SELECT id FROM org_feature_flags WHERE org_id = :org_id AND flag_name = :flag_name',
            ['org_id' => $orgId, 'flag_name' => $flagName]
        )->fetch();

        if ($existing !== false) {
            // Update existing flag.
            Database::executeQuery(
                'UPDATE org_feature_flags SET enabled = :enabled, updated_at = CURRENT_TIMESTAMP WHERE id = :id',
                ['enabled' => (int) $enabled, 'id' => $existing['id']]
            );
            return;
        }

        // Insert new flag entry.
        Database::executeQuery(
            'INSERT INTO org_feature_flags (id, org_id, flag_name, enabled) VALUES (:id, :org_id, :flag_name, :enabled)',
            [
                'id' => bin2hex(random_bytes(18)),
                'org_id' => $orgId,
                'flag_name' => $flagName,
                'enabled' => (int) $enabled
            ]
        );
    }

    /**
     * Manually clear cache (test isolation or forced invalidation).
     *
     * Clears all cache entries for $orgId, or a specific flag within the org if $flagName is provided.
     * Also clears the bulk "all:$orgId" cache to ensure consistency.
     *
     * @param string $orgId Tenant/organization identifier.
     * @param ?string $flagName Specific flag name to clear, or null to clear all org flags.
     *
     * @return void
     */
    public static function clearCache(string $orgId, ?string $flagName = null): void
    {
        if ($flagName === null) {
            // Clear all flags for this org from cache.
            $keys = array_keys(self::$cache);
            foreach ($keys as $key) {
                if (str_starts_with($key, "{$orgId}:")) {
                    unset(self::$cache[$key], self::$cacheTime[$key]);
                }
            }
            unset(self::$cache["all:{$orgId}"], self::$cacheTime["all:{$orgId}"]);
        } else {
            // Clear specific flag and invalidate bulk cache.
            $cacheKey = "{$orgId}:{$flagName}";
            unset(self::$cache[$cacheKey], self::$cacheTime[$cacheKey]);
            unset(self::$cache["all:{$orgId}"], self::$cacheTime["all:{$orgId}"]);
        }
    }
}
