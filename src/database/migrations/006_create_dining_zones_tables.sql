-- Migration 006: Create Dining Zones & Tables
CREATE TABLE IF NOT EXISTS dining_zones (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    name VARCHAR(128) NOT NULL,
    capacity INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_at TIMESTAMP NULL,
    FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_org_zone_archived ON dining_zones(org_id, archived_at);

CREATE TABLE IF NOT EXISTS dining_tables (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    zone_id VARCHAR(36),
    label VARCHAR(64) NOT NULL,
    capacity INTEGER NOT NULL,
    x_coord INTEGER NOT NULL DEFAULT 0,
    y_coord INTEGER NOT NULL DEFAULT 0,
    is_combinable INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_at TIMESTAMP NULL,
    FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (zone_id) REFERENCES dining_zones(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_org_table_archived ON dining_tables(org_id, archived_at);
CREATE INDEX IF NOT EXISTS idx_zone_combinable ON dining_tables(zone_id, is_combinable);

