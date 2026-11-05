-- Migration 002: Create Roles & Permissions Tables
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    mask INT NOT NULL UNIQUE,
    description VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS permissions (
    id INT PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    mask INT NOT NULL UNIQUE,
    description VARCHAR(255)
);
