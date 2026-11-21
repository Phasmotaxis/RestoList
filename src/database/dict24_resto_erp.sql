-- ==============================================================================
-- DICT24 Multi-Tenant Restaurant ERP & Reservation Platform
-- Unified Production & XAMPP SQL Genesis Schema + Realistic Seed Dataset
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tenants / Organizations Table
CREATE TABLE IF NOT EXISTS tenants (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subdomain VARCHAR(64) NOT NULL UNIQUE,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. RBAC Roles Table
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    mask INT NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. RBAC Permissions Table
CREATE TABLE IF NOT EXISTS permissions (
    id INT PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    mask INT NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Users Table (Multi-tenant scoped via tenant_id)
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(128) NOT NULL,
    last_name VARCHAR(128) NOT NULL,
    role_mask INT NOT NULL DEFAULT 4,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_users_tenant (tenant_id),
    KEY idx_users_email (email),
    CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Login Attempts (Rate limiting defense)
CREATE TABLE IF NOT EXISTS login_attempts (
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255) NOT NULL,
    attempts INT NOT NULL DEFAULT 1,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ip_address, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Audit Logs Table (Immutable append-only compliance trail)
CREATE TABLE IF NOT EXISTS audit_logs (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36),
    action VARCHAR(128) NOT NULL,
    entity VARCHAR(64) NOT NULL,
    entity_id VARCHAR(36),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_logs_tenant (tenant_id),
    KEY idx_audit_logs_created_at (created_at),
    CONSTRAINT fk_audit_logs_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Organization Feature Flags (Per-tenant optional module toggles)
CREATE TABLE IF NOT EXISTS org_feature_flags (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    flag_name VARCHAR(64) NOT NULL,
    enabled INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_org_flag (org_id, flag_name),
    CONSTRAINT fk_feature_flags_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Organization Invites Table
CREATE TABLE IF NOT EXISTS org_invites (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    email VARCHAR(255) NOT NULL,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    claimed_by_user_id VARCHAR(36),
    KEY idx_org_invites_email (email),
    KEY idx_org_invites_expires (expires_at),
    CONSTRAINT fk_invites_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Dining Zones Table (Floor plan areas)
CREATE TABLE IF NOT EXISTS dining_zones (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    name VARCHAR(128) NOT NULL,
    capacity INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_at TIMESTAMP NULL,
    KEY idx_dining_zones_org (org_id),
    CONSTRAINT fk_dining_zones_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Dining Tables Table (12x8 layout grid placement)
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
    KEY idx_dining_tables_org (org_id),
    KEY idx_dining_tables_zone (zone_id),
    CONSTRAINT fk_dining_tables_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_dining_tables_zone FOREIGN KEY (zone_id) REFERENCES dining_zones(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Reservations Table
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_reservations_org_status (org_id, status, start_at, end_at),
    KEY idx_reservations_customer (customer_id),
    CONSTRAINT fk_reservations_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_reservations_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Reservation Tables (Many-to-many junction)
CREATE TABLE IF NOT EXISTS reservation_tables (
    reservation_id VARCHAR(36) NOT NULL,
    table_id VARCHAR(36) NOT NULL,
    PRIMARY KEY (reservation_id, table_id),
    KEY idx_res_tables_table (table_id),
    CONSTRAINT fk_res_tables_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    CONSTRAINT fk_res_tables_table FOREIGN KEY (table_id) REFERENCES dining_tables(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Reservation Slots Table (15-minute discrete bucketing; UNIQUE double-booking guard)
CREATE TABLE IF NOT EXISTS reservation_slots (
    org_id VARCHAR(36) NOT NULL,
    table_id VARCHAR(36) NOT NULL,
    slot_start_utc DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (org_id, table_id, slot_start_utc),
    CONSTRAINT fk_slots_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_slots_table FOREIGN KEY (table_id) REFERENCES dining_tables(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Waitlist Entries Table
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
    KEY idx_waitlist_org_status (org_id, status, position),
    CONSTRAINT fk_waitlist_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_waitlist_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_waitlist_zone FOREIGN KEY (preferred_zone) REFERENCES dining_zones(id) ON DELETE SET NULL,
    CONSTRAINT fk_waitlist_promo_res FOREIGN KEY (promoted_to_reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Menu Items Table (Pricing stored as signed centavos)
CREATE TABLE IF NOT EXISTS menu_items (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price_centavos BIGINT NOT NULL,
    is_available INTEGER DEFAULT 1,
    dietary_tags VARCHAR(255),
    allergen_tags VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_at TIMESTAMP NULL,
    KEY idx_menu_items_org_avail (org_id, is_available, archived_at),
    CONSTRAINT fk_menu_items_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Reviews Table (Diner feedback)
CREATE TABLE IF NOT EXISTS reviews (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    reservation_id VARCHAR(36),
    customer_id VARCHAR(36),
    rating INTEGER NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    restaurant_response TEXT,
    response_at TIMESTAMP NULL,
    KEY idx_reviews_org (org_id, created_at),
    CONSTRAINT fk_reviews_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
    CONSTRAINT fk_reviews_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Deposit Ledger Table (Immutable signed centavo balance tracking)
CREATE TABLE IF NOT EXISTS deposit_ledger (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    customer_id VARCHAR(36),
    amount_centavos BIGINT NOT NULL,
    action VARCHAR(32) NOT NULL,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_deposit_ledger_org_cust (org_id, customer_id, created_at),
    CONSTRAINT fk_deposit_ledger_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_deposit_ledger_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Loyalty Ledger Table (Immutable points transactions)
CREATE TABLE IF NOT EXISTS loyalty_ledger (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    customer_id VARCHAR(36),
    points_delta INTEGER NOT NULL,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_loyalty_ledger_org_cust (org_id, customer_id, created_at),
    CONSTRAINT fk_loyalty_ledger_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_loyalty_ledger_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Ingredients Table (Inventory ERP)
CREATE TABLE IF NOT EXISTS ingredients (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    unit VARCHAR(32) NOT NULL,
    on_hand DECIMAL(10,2) NOT NULL DEFAULT 0,
    reorder_level DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_at TIMESTAMP NULL,
    KEY idx_ingredients_org (org_id, archived_at),
    CONSTRAINT fk_ingredients_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Menu Item Recipes Table (BOM mapping)
CREATE TABLE IF NOT EXISTS menu_item_recipes (
    id VARCHAR(36) PRIMARY KEY,
    item_id VARCHAR(36) NOT NULL,
    ingredient_id VARCHAR(36) NOT NULL,
    quantity_required DECIMAL(10,2) NOT NULL,
    UNIQUE KEY uq_item_ingredient (item_id, ingredient_id),
    CONSTRAINT fk_recipes_item FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_recipes_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Purchase Orders Table
CREATE TABLE IF NOT EXISTS purchase_orders (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    ingredient_id VARCHAR(36) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    received_at TIMESTAMP NULL,
    KEY idx_purchase_orders_org_status (org_id, status),
    CONSTRAINT fk_po_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_po_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Stock Movements Table (Immutable inventory audit trail)
CREATE TABLE IF NOT EXISTS stock_movements (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    ingredient_id VARCHAR(36) NOT NULL,
    quantity_delta DECIMAL(10,2) NOT NULL,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_stock_movements_org_ingr (org_id, ingredient_id, created_at),
    CONSTRAINT fk_stock_movements_org FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_stock_movements_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- REALISTIC SEED DATASET
-- 2+ Multi-Tenant Restaurants: Hiraya Bistro & Katipunan Grill
-- 5 Roles: SuperAdmin, Admin, Staff, FrontOfHouse, Kitchen, plus Customer
-- Password for all accounts: Password123!
-- ==============================================================================

-- Seed RBAC Roles
INSERT INTO roles (id, name, mask, description) VALUES
(1, 'SuperAdmin', 1, 'Full platform governance across all organizations'),
(2, 'Admin', 2, 'Restaurant organization owner and administrator'),
(3, 'User', 4, 'Standard restaurant operational staff member'),
(4, 'FrontOfHouse', 8, 'Host and server managing reservations and tables'),
(5, 'Kitchen', 16, 'Chef and kitchen team viewing orders and recipes')
ON DUPLICATE KEY UPDATE name = VALUES(name), mask = VALUES(mask), description = VALUES(description);

-- Seed Permissions
INSERT INTO permissions (id, name, mask, description) VALUES
(1, 'TENANT_MANAGE', 1, 'Manage tenant settings and lifecycle'),
(2, 'USER_MANAGE', 2, 'Provision, configure, and archive users'),
(3, 'DATA_READ', 4, 'Read operational restaurant data'),
(4, 'DATA_WRITE', 8, 'Create and modify restaurant data'),
(5, 'RESERVATION_MANAGE', 16, 'Full access to floor plan, tables, and reservations'),
(6, 'RECIPE_VIEW', 32, 'Access to kitchen recipes, ingredients, and BOM')
ON DUPLICATE KEY UPDATE name = VALUES(name), mask = VALUES(mask), description = VALUES(description);

-- Seed Tenants (Restaurants)
INSERT INTO tenants (id, name, subdomain, is_active) VALUES
('tenant-cvsu-main-001', 'CvSU Main Institutional Commissary', 'cvsu-main', 1),
('tenant-hiraya-001', 'Hiraya Bistro & Farm-to-Table', 'hiraya-bistro', 1),
('tenant-katipunan-002', 'Katipunan Grill & Smokehouse', 'katipunan-grill', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = VALUES(is_active);

-- Seed Feature Flags
INSERT INTO org_feature_flags (id, org_id, flag_name, enabled) VALUES
('ff-hiraya-001', 'tenant-hiraya-001', 'enable_floor_plan', 1),
('ff-hiraya-002', 'tenant-hiraya-001', 'enable_inventory_erp', 1),
('ff-hiraya-003', 'tenant-hiraya-001', 'enable_deposits', 1),
('ff-hiraya-004', 'tenant-hiraya-001', 'enable_loyalty', 1),
('ff-katipunan-001', 'tenant-katipunan-002', 'enable_floor_plan', 1),
('ff-katipunan-002', 'tenant-katipunan-002', 'enable_inventory_erp', 0),
('ff-katipunan-003', 'tenant-katipunan-002', 'enable_deposits', 0),
('ff-katipunan-004', 'tenant-katipunan-002', 'enable_loyalty', 1)
ON DUPLICATE KEY UPDATE enabled = VALUES(enabled);

-- Seed Users (Bcrypt hash: $2y$10$Jcoo52mXYib70fPHpAuIiO63hUzxiti7d.mhPuicyx6b.JYhcYqne for Password123!)
INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role_mask) VALUES
('usr-superadmin-001', 'tenant-cvsu-main-001', 'superadmin@cvsu.edu.ph', '$2y$10$Jcoo52mXYib70fPHpAuIiO63hUzxiti7d.mhPuicyx6b.JYhcYqne', 'System', 'SuperAdmin', 1),
('usr-admin-002', 'tenant-cvsu-main-001', 'admin@cvsu.edu.ph', '$2y$10$Jcoo52mXYib70fPHpAuIiO63hUzxiti7d.mhPuicyx6b.JYhcYqne', 'Main', 'Admin', 2),
('usr-user-003', 'tenant-cvsu-main-001', 'user@cvsu.edu.ph', '$2y$10$Jcoo52mXYib70fPHpAuIiO63hUzxiti7d.mhPuicyx6b.JYhcYqne', 'Standard', 'Staff', 4),
('usr-hiraya-admin', 'tenant-hiraya-001', 'manager@hirayabistro.ph', '$2y$10$Jcoo52mXYib70fPHpAuIiO63hUzxiti7d.mhPuicyx6b.JYhcYqne', 'Maria', 'Santos', 2),
('usr-hiraya-foh', 'tenant-hiraya-001', 'foh@cvsu.edu.ph', '$2y$10$Jcoo52mXYib70fPHpAuIiO63hUzxiti7d.mhPuicyx6b.JYhcYqne', 'Paolo', 'Reyes', 8),
('usr-hiraya-kitchen', 'tenant-hiraya-001', 'kitchen@cvsu.edu.ph', '$2y$10$Jcoo52mXYib70fPHpAuIiO63hUzxiti7d.mhPuicyx6b.JYhcYqne', 'Chef Marco', 'Cruz', 16),
('usr-katipunan-admin', 'tenant-katipunan-002', 'admin@katipunangrill.ph', '$2y$10$Jcoo52mXYib70fPHpAuIiO63hUzxiti7d.mhPuicyx6b.JYhcYqne', 'Mateo', 'Del Rosario', 2),
('usr-customer-001', 'tenant-hiraya-001', 'customer@cvsu.edu.ph', '$2y$10$Jcoo52mXYib70fPHpAuIiO63hUzxiti7d.mhPuicyx6b.JYhcYqne', 'Ana', 'Bautista', 32)
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash);

-- Seed Dining Zones
INSERT INTO dining_zones (id, org_id, name, capacity) VALUES
('zone-hiraya-main', 'tenant-hiraya-001', 'Main Dining Hall', 40),
('zone-hiraya-patio', 'tenant-hiraya-001', 'Garden Patio', 24),
('zone-hiraya-private', 'tenant-hiraya-001', 'Silid Hiraya Private Room', 12),
('zone-katipunan-hall', 'tenant-katipunan-002', 'Pitmaster Main Hall', 50),
('zone-katipunan-deck', 'tenant-katipunan-002', 'Al Fresco Deck', 30)
ON DUPLICATE KEY UPDATE name = VALUES(name), capacity = VALUES(capacity);

-- Seed Dining Tables (with 12x8 grid coordinates)
INSERT INTO dining_tables (id, org_id, zone_id, label, capacity, x_coord, y_coord, is_combinable) VALUES
('tbl-h-01', 'tenant-hiraya-001', 'zone-hiraya-main', 'T1', 4, 1, 1, 1),
('tbl-h-02', 'tenant-hiraya-001', 'zone-hiraya-main', 'T2', 4, 3, 1, 1),
('tbl-h-03', 'tenant-hiraya-001', 'zone-hiraya-main', 'T3', 6, 5, 1, 0),
('tbl-h-04', 'tenant-hiraya-001', 'zone-hiraya-main', 'T4', 2, 1, 3, 0),
('tbl-h-05', 'tenant-hiraya-001', 'zone-hiraya-main', 'T5', 2, 3, 3, 0),
('tbl-h-06', 'tenant-hiraya-001', 'zone-hiraya-main', 'T6', 4, 5, 3, 1),
('tbl-h-07', 'tenant-hiraya-001', 'zone-hiraya-patio', 'P1', 4, 1, 5, 1),
('tbl-h-08', 'tenant-hiraya-001', 'zone-hiraya-patio', 'P2', 4, 3, 5, 1),
('tbl-h-09', 'tenant-hiraya-001', 'zone-hiraya-private', 'VIP-1', 12, 1, 7, 0),
('tbl-k-01', 'tenant-katipunan-002', 'zone-katipunan-hall', 'K1', 4, 1, 1, 1),
('tbl-k-02', 'tenant-katipunan-002', 'zone-katipunan-hall', 'K2', 4, 3, 1, 1),
('tbl-k-03', 'tenant-katipunan-002', 'zone-katipunan-hall', 'K3', 8, 5, 1, 0)
ON DUPLICATE KEY UPDATE label = VALUES(label), capacity = VALUES(capacity);

-- Seed Menu Items (Prices in signed centavos: PHP 380.00 = 38000)
INSERT INTO menu_items (id, org_id, name, description, price_centavos, is_available, dietary_tags, allergen_tags) VALUES
('menu-h-001', 'tenant-hiraya-001', 'Sinigang na Baboy sa Sampalok', 'Tender pork belly simmered in fresh tamarind broth with local water spinach and radish', 38000, 1, 'Pork, Gluten-Free', 'None'),
('menu-h-002', 'tenant-hiraya-001', 'Kare-Kareng Baka', 'Slow-braised beef shank and oxtail in roasted peanut sauce with native bagoong alamang', 52000, 1, 'Beef', 'Peanuts, Shellfish'),
('menu-h-003', 'tenant-hiraya-001', 'Crispy Pata Hiraya Style', 'Deep-fried pork knuckle served with spiced soy-vinegar dipping sauce', 75000, 1, 'Pork', 'Soy'),
('menu-h-004', 'tenant-hiraya-001', 'Pancit Canton Special', 'Wok-tossed egg noodles with shredded chicken, shrimp, cabbage, and carrots', 28000, 1, 'Chicken, Pork', 'Gluten, Shellfish'),
('menu-h-005', 'tenant-hiraya-001', 'Halo-Halo Supreme', 'Shaved ice with sweetened beans, nata de coco, macapuno, leche flan, and ube ice cream', 16000, 1, 'Vegetarian', 'Dairy'),
('menu-h-006', 'tenant-hiraya-001', 'Fresh Calamansi Cooler', 'Freshly squeezed Philippine lime with wild forest honey over crushed ice', 8500, 1, 'Vegan, Gluten-Free', 'None'),
('menu-k-001', 'tenant-katipunan-002', 'Smoked Beef Brisket Platter', '14-hour hickory smoked Batangas beef brisket with house barbecue glaze', 68000, 1, 'Beef', 'Soy, Mustard'),
('menu-k-002', 'tenant-katipunan-002', 'Inasal Chicken Leg Quarter', 'Marinated in annatto, lemongrass, and coconut vinegar, char-grilled over coconut charcoal', 26000, 1, 'Chicken', 'None')
ON DUPLICATE KEY UPDATE name = VALUES(name), price_centavos = VALUES(price_centavos);

-- Seed Ingredients (Inventory ERP)
INSERT INTO ingredients (id, org_id, name, unit, on_hand, reorder_level) VALUES
('ing-h-001', 'tenant-hiraya-001', 'Pork Belly (Liempo)', 'kg', 45.50, 15.00),
('ing-h-002', 'tenant-hiraya-001', 'Beef Shank (Kamto)', 'kg', 30.00, 10.00),
('ing-h-003', 'tenant-hiraya-001', 'Roasted Peanut Butter Paste', 'kg', 18.00, 5.00),
('ing-h-004', 'tenant-hiraya-001', 'Fresh Tamarind Fruit', 'kg', 12.00, 3.00),
('ing-h-005', 'tenant-hiraya-001', 'Egg Noodles', 'kg', 25.00, 8.00),
('ing-h-006', 'tenant-hiraya-001', 'Ube Ice Cream Tub', 'liter', 20.00, 6.00),
('ing-k-001', 'tenant-katipunan-002', 'Beef Brisket Raw', 'kg', 80.00, 20.00),
('ing-k-002', 'tenant-katipunan-002', 'Whole Dressed Chicken', 'kg', 50.00, 15.00)
ON DUPLICATE KEY UPDATE on_hand = VALUES(on_hand);

-- Seed Sample Reservations & Slots
INSERT INTO reservations (id, org_id, customer_id, party_size, start_at, end_at, status, notes) VALUES
('res-demo-001', 'tenant-hiraya-001', 'usr-customer-001', 4, '2026-11-25 18:00:00', '2026-11-25 19:30:00', 'confirmed', 'Window seating requested; birthday dinner'),
('res-demo-002', 'tenant-hiraya-001', 'usr-customer-001', 2, '2026-11-25 20:00:00', '2026-11-25 21:00:00', 'confirmed', 'Quiet table for two')
ON DUPLICATE KEY UPDATE status = VALUES(status);

INSERT INTO reservation_tables (reservation_id, table_id) VALUES
('res-demo-001', 'tbl-h-01'),
('res-demo-002', 'tbl-h-04')
ON DUPLICATE KEY UPDATE table_id = VALUES(table_id);

INSERT INTO reservation_slots (org_id, table_id, slot_start_utc) VALUES
('tenant-hiraya-001', 'tbl-h-01', '2026-11-25 18:00:00'),
('tenant-hiraya-001', 'tbl-h-01', '2026-11-25 18:15:00'),
('tenant-hiraya-001', 'tbl-h-01', '2026-11-25 18:30:00'),
('tenant-hiraya-001', 'tbl-h-01', '2026-11-25 18:45:00'),
('tenant-hiraya-001', 'tbl-h-01', '2026-11-25 19:00:00'),
('tenant-hiraya-001', 'tbl-h-01', '2026-11-25 19:15:00'),
('tenant-hiraya-001', 'tbl-h-04', '2026-11-25 20:00:00'),
('tenant-hiraya-001', 'tbl-h-04', '2026-11-25 20:15:00'),
('tenant-hiraya-001', 'tbl-h-04', '2026-11-25 20:30:00'),
('tenant-hiraya-001', 'tbl-h-04', '2026-11-25 20:45:00')
ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP;

-- Seed Waitlist Entries
INSERT INTO waitlist_entries (id, org_id, customer_id, party_size, preferred_zone, position, status) VALUES
('wl-demo-001', 'tenant-hiraya-001', 'usr-customer-001', 4, 'zone-hiraya-main', 1, 'waiting'),
('wl-demo-002', 'tenant-hiraya-001', NULL, 2, 'zone-hiraya-patio', 2, 'waiting')
ON DUPLICATE KEY UPDATE position = VALUES(position);

-- Seed Deposit Ledger
INSERT INTO deposit_ledger (id, org_id, customer_id, amount_centavos, action, reason) VALUES
('dep-001', 'tenant-hiraya-001', 'usr-customer-001', 100000, 'hold', 'Deposit hold for VIP reservation res-demo-001')
ON DUPLICATE KEY UPDATE amount_centavos = VALUES(amount_centavos);

-- Seed Loyalty Ledger
INSERT INTO loyalty_ledger (id, org_id, customer_id, points_delta, reason) VALUES
('loy-001', 'tenant-hiraya-001', 'usr-customer-001', 150, 'Points earned for dining reservation res-demo-001')
ON DUPLICATE KEY UPDATE points_delta = VALUES(points_delta);

-- Seed Reviews
INSERT INTO reviews (id, org_id, reservation_id, customer_id, rating, comment, restaurant_response, response_at) VALUES
('rev-001', 'tenant-hiraya-001', 'res-demo-001', 'usr-customer-001', 5, 'Exquisite Sinigang broth and authentic Kare-Kare. Impeccable service by front-of-house team!', 'Thank you Ana! We look forward to welcoming you back to Hiraya Bistro.', '2026-11-26 10:00:00')
ON DUPLICATE KEY UPDATE rating = VALUES(rating);

-- Seed Initial Audit Logs
INSERT INTO audit_logs (id, tenant_id, user_id, action, entity, entity_id, details, ip_address) VALUES
('log-init-001', 'tenant-cvsu-main-001', 'usr-superadmin-001', 'SYSTEM_INIT', 'system', 'root', 'Initial system genesis provisioning executed', '127.0.0.1'),
('log-init-002', 'tenant-hiraya-001', 'usr-hiraya-admin', 'TENANT_PROVISION', 'tenants', 'tenant-hiraya-001', 'Hiraya Bistro organization activated with floor plan and ERP flags', '127.0.0.1'),
('log-init-003', 'tenant-katipunan-002', 'usr-katipunan-admin', 'TENANT_PROVISION', 'tenants', 'tenant-katipunan-002', 'Katipunan Grill activated with standard dining configuration', '127.0.0.1')
ON DUPLICATE KEY UPDATE details = VALUES(details);
