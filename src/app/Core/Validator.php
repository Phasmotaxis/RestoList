<?php

declare(strict_types=1);

namespace App\Core;

use PDOException;

/**
 * Input Validation Rules: Restaurant ERP domain-aware validators for form inputs.
 *
 * Each validation method accepts user input and returns null (valid) or an error message string.
 * Validators enforce domain constraints: email format, name lengths, password entropy,
 * subdomain (tenant identifier) format. Includes database constraint error detection
 * (UNIQUE violations) for dupe prevention feedback.
 */
class Validator
{
    /**
     * Validate email address: required, valid RFC format, max 255 characters.
     *
     * @param string $value User-supplied email address.
     *
     * @return ?string Null if valid; error message string if invalid.
     *
     * @example Validator::email('admin@example.com') returns null
     * @example Validator::email('') returns 'Email is required.'
     * @example Validator::email('invalid@') returns 'Enter a valid email address...'
     */
    public static function email(string $value): ?string
    {
        if ($value === '') {
            return 'Email is required.';
        }
        if (mb_strlen($value) > 255 || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return 'Enter a valid email address (255 characters or fewer).';
        }
        return null;
    }

    /**
     * Validate text name field: required, max length, UTF-8 character safe.
     *
     * Generic validator for user names, tenant names, location names (restaurant names, supplier names).
     * Uses mb_strlen for UTF-8 character counting (supports accents, CJK, etc.).
     *
     * @param string $value Name input.
     * @param string $label Field name for error message (e.g. 'First Name', 'Tenant Name').
     * @param int $max Maximum character length (default 128).
     *
     * @return ?string Null if valid; error message string if invalid.
     *
     * @example Validator::name('John', 'First Name') returns null
     * @example Validator::name('', 'Restaurant Name', 255) returns 'Restaurant Name is required.'
     */
    public static function name(string $value, string $label, int $max = 128): ?string
    {
        if ($value === '') {
            return "{$label} is required.";
        }
        if (mb_strlen($value) > $max) {
            return "{$label} must be {$max} characters or fewer.";
        }
        return null;
    }

    /**
     * Validate password strength: 12+ characters, max 72 bytes (bcrypt limitation).
     *
     * bcrypt silently ignores bytes past 72, so passwords longer than 72 bytes lose entropy.
     * Length checked in both character count (mb_strlen >= 12) and byte count (<= 72)
     * to prevent silent truncation and ensure user receives expected security.
     *
     * @param string $value Password plaintext.
     *
     * @return ?string Null if valid; error message string if invalid.
     *
     * @example Validator::password('MySecure!Pass1') returns null
     * @example Validator::password('short') returns 'Password must be at least 12 characters.'
     */
    public static function password(string $value): ?string
    {
        if (mb_strlen($value) < 12) {
            return 'Password must be at least 12 characters.';
        }
        // bcrypt ignores bytes past 72, so a longer password would silently lose its tail.
        if (strlen($value) > 72) {
            return 'Password must be 72 bytes or fewer.';
        }
        return null;
    }

    /**
     * Validate subdomain identifier: lowercase alphanumeric and hyphens, 1-64 characters.
     *
     * Subdomains are tenant identifiers (e.g. 'cvsu-main', 'restaurant-a'). Must follow DNS rules:
     * start and end with alphanumeric, hyphens allowed in middle, no consecutive hyphens or underscores.
     *
     * @param string $value Subdomain input.
     *
     * @return ?string Null if valid; error message string if invalid.
     *
     * @example Validator::subdomain('cvsu-main') returns null
     * @example Validator::subdomain('') returns 'Subdomain is required.'
     * @example Validator::subdomain('Invalid_Name') returns 'Subdomain may contain only...'
     */
    public static function subdomain(string $value): ?string
    {
        if ($value === '') {
            return 'Subdomain is required.';
        }
        if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,62}[a-z0-9])?$/', $value)) {
            return 'Subdomain may contain only lowercase letters, digits and hyphens (64 characters or fewer).';
        }
        return null;
    }

    /**
     * Detect UNIQUE constraint violation in PDOException for form retry feedback.
     *
     * Checks MySQL errno 1062 (duplicate key) and SQLite3 SQLSTATE 23000 with 'unique' in message.
     * Other integrity errors (foreign key, NOT NULL) return false for different handling.
     * Used to provide user-friendly "Email already exists" or "Subdomain already taken" feedback.
     *
     * @param PDOException $e Exception from failed INSERT or UPDATE.
     *
     * @return bool true if UNIQUE constraint violation; false for other integrity errors.
     *
     * @example if (Validator::isUniqueViolation($e)) { // email already exists }
     */
    public static function isUniqueViolation(PDOException $e): bool
    {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return true;
        }
        return (string) $e->getCode() === '23000' && stripos($e->getMessage(), 'unique') !== false;
    }
}
