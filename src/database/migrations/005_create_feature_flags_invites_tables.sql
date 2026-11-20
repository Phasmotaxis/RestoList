-- Migration 005: Create Feature Flags & Org Invites Tables
CREATE TABLE IF NOT EXISTS org_feature_flags (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    flag_name VARCHAR(64) NOT NULL,
    enabled INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE (org_id, flag_name)
);

CREATE TABLE IF NOT EXISTS org_invites (
    id VARCHAR(36) PRIMARY KEY,
    org_id VARCHAR(36) NOT NULL,
    email VARCHAR(255) NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    claimed_by_user_id VARCHAR(36),
    FOREIGN KEY (org_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE (token_hash)
);

CREATE INDEX IF NOT EXISTS idx_org_invites_email ON org_invites(email);
CREATE INDEX IF NOT EXISTS idx_org_invites_expires_at ON org_invites(expires_at);

