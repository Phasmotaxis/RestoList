-- Seed Data: Roles, Permissions, Default Tenants, Users & Audit Logs
INSERT INTO roles (id, name, mask, description) VALUES
(1, 'SuperAdmin', 1, 'Full cross-tenant system control'),
(2, 'Admin', 2, 'Tenant administrator'),
(3, 'User', 4, 'Standard tenant user')
ON DUPLICATE KEY UPDATE name = VALUES(name), mask = VALUES(mask), description = VALUES(description);

INSERT INTO permissions (id, name, mask, description) VALUES
(1, 'TENANT_MANAGE', 1, 'Manage tenant configuration'),
(2, 'USER_MANAGE', 2, 'Provision and edit tenant users'),
(3, 'DATA_READ', 4, 'Read operational tenant data'),
(4, 'DATA_WRITE', 8, 'Create and update operational tenant data')
ON DUPLICATE KEY UPDATE name = VALUES(name), mask = VALUES(mask), description = VALUES(description);

INSERT INTO tenants (id, name, subdomain, is_active) VALUES
('tenant-cvsu-main-001', 'Cavite State University Main Campus', 'cvsu-main', TRUE),
('tenant-cvsu-educ-002', 'CvSU College of Education', 'cvsu-educ', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = VALUES(is_active);

-- No user rows are seeded. Create the first SuperAdmin with:
--   SUPERADMIN_EMAIL=... SUPERADMIN_PASSWORD=... php src/database/bootstrap_superadmin.php

INSERT INTO audit_logs (id, tenant_id, user_id, action, entity, entity_id, details, ip_address) VALUES
('log-init-001', 'tenant-cvsu-main-001', 'usr-superadmin-001', 'SYSTEM_INIT', 'system', 'root', 'Initial system genesis provisioning executed', '127.0.0.1'),
('log-init-002', 'tenant-cvsu-main-001', 'usr-admin-002', 'TENANT_PROVISION', 'tenants', 'tenant-cvsu-main-001', 'Tenant verified and activated', '127.0.0.1'),
('log-init-003', 'tenant-cvsu-main-001', 'usr-user-003', 'USER_ONBOARD', 'users', 'usr-user-003', 'Standard user initialized', '127.0.0.1')
ON DUPLICATE KEY UPDATE details = VALUES(details);
