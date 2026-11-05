<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Zero-Dependency Native YAML Configuration Parser
 *
 * Implements pure PHP string extraction for two-level nested YAML dictionaries
 * without requiring PECL ext-yaml or external composer packages.
 */
class Config
{
    private static ?array $data = null;

    /**
     * Retrieve configuration value by key or dot-notation path.
     * Example: Config::get('system') or Config::get('system.name')
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$data === null) {
            self::load();
        }

        if (array_key_exists($key, self::$data)) {
            return self::$data[$key];
        }

        if (strpos($key, '.') !== false) {
            $segments = explode('.', $key);
            $current = self::$data;
            foreach ($segments as $segment) {
                if (is_array($current) && array_key_exists($segment, $current)) {
                    $current = $current[$segment];
                } else {
                    return $default;
                }
            }
            return $current;
        }

        return $default;
    }

    /**
     * Load and parse configuration from candidate paths.
     */
    public static function load(?string $customPath = null): array
    {
        if ($customPath !== null && file_exists($customPath)) {
            self::$data = self::parseYamlFile($customPath);
            return self::$data;
        }

        $candidates = [
            dirname(__DIR__, 4) . '/dev/values/constants/main.yaml',
            dirname(__DIR__, 3) . '/dev/values/constants/main.yaml',
            '/var/www/dev/values/constants/main.yaml',
            '/var/www/html/dev/values/constants/main.yaml',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                self::$data = self::parseYamlFile($candidate);
                return self::$data;
            }
        }

        // Fallback default dictionary
        self::$data = [
            'system' => [
                'name' => 'Enterprise Information Management System',
                'short_name' => 'EIMS',
                'academic_term' => '2nd year 1st semester 2026-27',
                'institution' => 'Cavite State University - Main Campus',
                'author' => 'EIMS Development Team',
                'domain' => 'eims-cvsu.lyra-on.top',
                'site_url' => 'https://eims-cvsu.lyra-on.top',
                'description' => 'Enterprise Information Management System for Multi-Tenant Data Governance',
                'banner_image' => '/assets/images/banner.png',
                'logo_image' => '/assets/images/icons/logo.png',
            ],
        ];

        return self::$data;
    }

    /**
     * Return all loaded configuration data.
     */
    public static function all(): array
    {
        if (self::$data === null) {
            self::load();
        }
        return self::$data;
    }

    /**
     * Pure PHP string parser for YAML key-value dictionaries.
     */
    public static function parseYamlFile(string $filePath): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return [];
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $parsed = [];
        $currentSection = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            // Top-level section header (e.g. "system:")
            if (preg_match('/^([a-zA-Z0-9_-]+):\s*$/', $trimmed, $matches)) {
                $currentSection = $matches[1];
                if (!isset($parsed[$currentSection])) {
                    $parsed[$currentSection] = [];
                }
                continue;
            }

            // Key-value entry (e.g. "  name: \"EIMS\"" or "short_name: EIMS")
            if (preg_match('/^([a-zA-Z0-9_-]+):\s*(.*)$/', $trimmed, $matches)) {
                $key = $matches[1];
                $rawValue = trim($matches[2]);

                // Strip quotes if present
                if (
                    (str_starts_with($rawValue, '"') && str_ends_with($rawValue, '"')) ||
                    (str_starts_with($rawValue, "'") && str_ends_with($rawValue, "'"))
                ) {
                    $value = substr($rawValue, 1, -1);
                } else {
                    $value = match (strtolower($rawValue)) {
                        'true' => true,
                        'false' => false,
                        'null' => null,
                        default => is_numeric($rawValue) ? (str_contains($rawValue, '.') ? (float) $rawValue : (int) $rawValue) : $rawValue,
                    };
                }

                if ($currentSection !== null) {
                    $parsed[$currentSection][$key] = $value;
                } else {
                    $parsed[$key] = $value;
                }
            }
        }

        return $parsed;
    }
}
