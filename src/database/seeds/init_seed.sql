-- Seed Data: Roles, Permissions, Default Tenant & SuperAdmin
INSERT INTO roles (id, name, mask, description) VALUES
(1, 'SuperAdmin', 1, 'Full cross-tenant system control'),
(2, 'Admin', 2, 'Tenant administrator'),
(3, 'User', 4, 'Standard tenant user');

INSERT INTO permissions (id, name, mask, description) VALUES
(1, 'TENANT_MANAGE', 1, 'Manage tenant configuration'),
(2, 'USER_MANAGE', 2, 'Provision and edit tenant users'),
(3, 'DATA_READ', 4, 'Read operational tenant data'),
(4, 'DATA_WRITE', 8, 'Create and update operational tenant data');

INSERT INTO tenants (id, name, subdomain, is_active) VALUES
('tenant-cvsu-main-001', 'Cavite State University Main', 'cvsu-main', TRUE);

INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role_mask) VALUES
('usr-superadmin-001', 'tenant-cvsu-main-001', 'superadmin@cvsu.edu.ph', '$2y$10$abcdefghijklmnopqrstuvwxyz012345678901234567890123456789', 'System', 'SuperAdmin', 1);
