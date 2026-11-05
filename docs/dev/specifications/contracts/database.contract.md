# Subsystem Contract: Relational Database & Offline Persistence

- **Parent Specification**: [System Architecture](../architecture.md)
- **Active Task Ledger**: [Subsystem Tasks](../../ai/tasks.md)
- **Changelog Tracker**: [Project Changelog](../../../CHANGELOG.md)
- **Target Implementation**: `src/app/Core/Database.php`

---

## 1. Interface Specification
The Database subsystem provides a unified PDO connection abstraction supporting dynamic engine failover from MySQL 8.0 to SQLite3.

```php
namespace App\Core;

use PDO;

interface DatabaseConnectionInterface
{
    public static function getConnection(): PDO;
    public static function isFallbackActive(): bool;
    public static function executeQuery(string $sql, array $params = []): \PDOStatement;
}
```

## 2. Invariants & Failover Semantics
1. **Primary Engine**: MySQL 8.0 accessed via `PDO_MYSQL` driver using credentials from environment variables (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`).
2. **Fallback Engine**: SQLite3 accessed via `PDO_SQLITE` driver pointing to an isolated local file (e.g. `storage/fallback.sqlite`).
3. **Automatic Failover**: If the primary MySQL connection fails with a `\PDOException`, the connection manager must:
   - Log the failure state with diagnostic details.
   - Initialize the SQLite fallback database.
   - Run baseline schema migrations if tables are absent.
   - Set internal status flag `is_fallback = true`.
4. **Statement Execution**: Prepared statements must always be used for parameterized inputs to prevent SQL injection vulnerabilities.

## 3. Schema Invariants
- Multi-tenancy must be enforced via `tenant_id` foreign keys on tenant-scoped tables.
- Foreign keys must be indexed and enforce referential integrity (`ON DELETE RESTRICT` or `ON DELETE CASCADE` as specified).
