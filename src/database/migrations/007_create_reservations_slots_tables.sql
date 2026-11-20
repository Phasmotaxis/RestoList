-- Migration 007: Create Reservations & Slot Bucketing Tables
CREATE TABLE IF NOT EXISTS reservations (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    customer_id VARCHAR(36),
    party_size INTEGER NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_org_status_time ON reservations(org_id, status, start_at, end_at);
CREATE INDEX IF NOT EXISTS idx_customer ON reservations(customer_id);

CREATE TABLE IF NOT EXISTS reservation_tables (
    reservation_id VARCHAR(36) NOT NULL,
    table_id VARCHAR(36) NOT NULL,
    PRIMARY KEY (reservation_id, table_id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES dining_tables(id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_table_reservation ON reservation_tables(table_id, reservation_id);

CREATE TABLE IF NOT EXISTS reservation_slots (
    org_id VARCHAR(36) NOT NULL,
    table_id VARCHAR(36) NOT NULL,
    slot_start_utc DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (org_id, table_id, slot_start_utc),
    FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES dining_tables(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS waitlist_entries (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    customer_id VARCHAR(36),
    party_size INTEGER NOT NULL,
    preferred_zone VARCHAR(36),
    position INTEGER NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'waiting',
    join_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    promoted_to_reservation_id VARCHAR(36),
    FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (preferred_zone) REFERENCES dining_zones(id) ON DELETE SET NULL,
    FOREIGN KEY (promoted_to_reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_org_status_position ON waitlist_entries(org_id, status, position);

