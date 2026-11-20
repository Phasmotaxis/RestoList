<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Query Parameter Parser for User List Search and Sort.
 *
 * Parses GET parameters (search query, sort column, direction, role filter) with strict
 * whitelist validation. SQL identifiers (column names, roles) come from code-authored
 * whitelists; search terms are parameterized to prevent injection. Prevents common
 * attack vectors: SQL injection, arbitrary sort, undefined filters.
 */
class ListQuery
{
    private const MAX_SEARCH_LENGTH = 100;

    /**
     * Parse and validate search, sort, and role filter from request parameters.
     *
     * Extracts GET query parameters and validates against whitelists:
     * - Search query (q): Trimmed, UTF-8 truncated to MAX_SEARCH_LENGTH, LIKE-escaped.
     * - Sort column (sort): Validated against $sortColumns keys; defaults to first key if missing/invalid.
     * - Direction (dir): ASC or DESC, defaults to ASC.
     * - Role filter (role): Validated against $roleMasks keys; empty string if missing/invalid.
     *
     * Returns array with parsed values and pre-formatted SQL fragments (orderBy, likePattern).
     * All SQL identifiers (sort columns, role masks) are author-controlled; no user input reaches SQL.
     *
     * @param array<string, string[]> $sortColumns Map of sort key => array of SQL column identifiers (e.g. ['name' => ['users.first_name', 'users.last_name']]).
     * @param array<string, int> $roleMasks Map of role key => role_mask bit value (e.g. ['superadmin' => 1, 'admin' => 2, 'user' => 4]).
     *
     * @return array{q: string, sort: string, dir: string, role: string, roleMask: ?int, orderBy: string, likePattern: string}
     *         - q: Sanitized search term
     *         - sort: Validated sort column key
     *         - dir: ASC or DESC
     *         - role: Role key (empty string if no filter)
     *         - roleMask: Bit mask for role filter, or null if no role filter
     *         - orderBy: Pre-formatted SQL ORDER BY clause (safe, from $sortColumns)
     *         - likePattern: LIKE pattern with wildcards escaped and user term wrapped in % (for parameterized query)
     *
     * @example
     * $result = ListQuery::fromRequest(
     *   ['name' => ['users.first_name', 'users.last_name'], 'email' => ['users.email']],
     *   ['admin' => 2, 'user' => 4]
     * );
     * // $_GET['q'] = 'John', $_GET['sort'] = 'name', $_GET['dir'] = 'DESC', $_GET['role'] = 'admin'
     * // Returns: ['q' => 'John', 'sort' => 'name', 'dir' => 'DESC', 'role' => 'admin', 'roleMask' => 2, 'orderBy' => 'users.first_name DESC, users.last_name DESC', 'likePattern' => '%John%']
     */
    public static function fromRequest(array $sortColumns, array $roleMasks): array
    {
        // Extract and sanitize search query: UTF-8 safe truncation, whitespace trim.
        $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, self::MAX_SEARCH_LENGTH);

        // Validate sort column against whitelist; fallback to first whitelisted key.
        $sort = (string) ($_GET['sort'] ?? '');
        if (!isset($sortColumns[$sort])) {
            $sort = (string) array_key_first($sortColumns);
        }

        // Normalize direction: DESC if explicitly requested, otherwise ASC (safe default).
        $dir = strtoupper((string) ($_GET['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        // Validate role filter against whitelist; empty string (no filter) if invalid/missing.
        $role = (string) ($_GET['role'] ?? '');
        if (!isset($roleMasks[$role])) {
            $role = '';
        }

        // Build ORDER BY clause from whitelisted columns (no user input in SQL fragment).
        $orderBy = implode(', ', array_map(static fn (string $col): string => "{$col} {$dir}", $sortColumns[$sort]));

        // Escape LIKE special characters (%, _) with '!' so search term matches literally, not as wildcards.
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q);

        return [
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'role' => $role,
            'roleMask' => $role === '' ? null : $roleMasks[$role],
            'orderBy' => $orderBy,
            'likePattern' => '%' . $escaped . '%',
        ];
    }
}
