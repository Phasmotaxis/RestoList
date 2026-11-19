<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * FeatureFlagService: Per-tenant feature flag management.
 * Handles enabling/disabling optional modules (floor_plan, inventory_erp, deposits, loyalty).
 */
class FeatureFlagService
{
    private const CACHE_TTL = 300; // 5 minutes
    private static array $cache = [];
    private static array $cacheTime = [];

    /**
     * Check if a feature is enabled for an organization.
     */
    public static function isEnabled(string $orgId, string $flagName): bool
    {
        $cacheKey = "{$orgId}:{$flagName}";

        // Check cache
        if (isset(self::$cache[$cacheKey]) && time() - self::$cacheTime[$cacheKey] < self::CACHE_TTL) {
            return self::$cache[$cacheKey];
        }

        $row = Database::executeQuery(
            'SELECT enabled FROM org_feature_flags WHERE org_id = :org_id AND flag_name = :flag_name',
            ['org_id' => $orgId, 'flag_name' => $flagName]
        )->fetch();

        $enabled = (bool) ($row['enabled'] ?? false);

        // Cache result
        self::$cache[$cacheKey] = $enabled;
        self::$cacheTime[$cacheKey] = time();

        return $enabled;
    }

    /**
     * Get all flags for an organization.
     */
    public static function getAllFlags(string $orgId): array
    {
        $cacheKey = "all:{$orgId}";

        if (isset(self::$cache[$cacheKey]) && time() - self::$cacheTime[$cacheKey] < self::CACHE_TTL) {
            return self::$cache[$cacheKey];
        }

        $rows = Database::executeQuery(
            'SELECT flag_name, enabled FROM org_feature_flags WHERE org_id = :org_id',
            ['org_id' => $orgId]
        )->fetchAll();

        $flags = [];
        foreach ($rows as $row) {
            $flags[$row['flag_name']] = (bool) $row['enabled'];
        }

        self::$cache[$cacheKey] = $flags;
        self::$cacheTime[$cacheKey] = time();

        return $flags;
    }

    /**
     * Enable or disable a feature for an organization.
     */
    public static function toggle(string $orgId, string $flagName, bool $enabled): void
    {
        self::clearCache($orgId, $flagName);

        $existing = Database::executeQuery(
            'SELECT id FROM org_feature_flags WHERE org_id = :org_id AND flag_name = :flag_name',
            ['org_id' => $orgId, 'flag_name' => $flagName]
        )->fetch();

        if ($existing !== false) {
            Database::executeQuery(
                'UPDATE org_feature_flags SET enabled = :enabled, updated_at = CURRENT_TIMESTAMP WHERE id = :id',
                ['enabled' => (int) $enabled, 'id' => $existing['id']]
            );
            return;
        }

        Database::executeQuery(
            'INSERT INTO org_feature_flags (id, org_id, flag_name, enabled)
             VALUES (:id, :org_id, :flag_name, :enabled)',
            [
                'id' => bin2hex(random_bytes(18)),
                'org_id' => $orgId,
                'flag_name' => $flagName,
                'enabled' => (int) $enabled
            ]
        );
    }

    /**
     * Clear cache for testing or manual invalidation.
     */
    public static function clearCache(string $orgId, ?string $flagName = null): void
    {
        if ($flagName === null) {
            $keys = array_keys(self::$cache);
            foreach ($keys as $key) {
                if (str_starts_with($key, "{$orgId}:")) {
                    unset(self::$cache[$key], self::$cacheTime[$key]);
                }
            }
            unset(self::$cache["all:{$orgId}"], self::$cacheTime["all:{$orgId}"]);
        } else {
            $cacheKey = "{$orgId}:{$flagName}";
            unset(self::$cache[$cacheKey], self::$cacheTime[$cacheKey]);
            unset(self::$cache["all:{$orgId}"], self::$cacheTime["all:{$orgId}"]);
        }
    }
}
