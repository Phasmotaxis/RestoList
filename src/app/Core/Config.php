<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Zero-Dependency Native YAML Configuration Parser
 *
 * Implements pure PHP string extraction for two-level nested YAML dictionaries
 * without requiring PECL ext-yaml or external composer packages.
 * Supports dot-notation path access and fallback to hardcoded EIMS defaults.
 */
class Config
{
    private static ?array $data = null;

    /**
     * Retrieve configuration value by top-level key or dot-notation nested path.
     *
     * Loads configuration from candidate YAML files on first call, with fallback to
     * hardcoded EIMS system defaults. Supports both 'system' and 'system.name' notation.
     *
     * @param string $key Top-level key (e.g. 'system') or dot-separated path (e.g. 'system.name').
     * @param mixed $default Value to return if key not found (default null).
     *
     * @return mixed Configuration value, or $default if path not found.
     *
     * @example Config::get('system.name') returns 'Enterprise Information Management System'
     * @example Config::get('system.domain', 'localhost') returns configured domain or 'localhost'
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
     * Load and parse configuration from candidate YAML file paths.
     *
     * Searches for main.yaml in multiple candidate paths (relative to project root and
     * common Docker mount points). If found, parses and caches; otherwise returns hardcoded
     * EIMS system defaults for academic institution setup (CvSU Main Campus).
     *
     * @param ?string $customPath Optional custom file path to load (takes precedence over candidates).
     *
     * @return array Parsed configuration data.
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

        // Fallback: hardcoded EIMS system defaults for CvSU academic deployment.
        self::$data = [
            'system' => [
                'name' => 'Enterprise Information Management System',
                'short_name' => 'EIMS',
                'university' => 'Cavite State University - Main Campus',
                'institution' => 'Cavite State University - Main Campus',
                'college' => 'College of Engineering and Information Technology (CEIT)',
                'department' => 'Department of Information Technology (DIT)',
                'course' => 'DCIT 24 - Information Management',
                'academic_term' => '1st Semester, Academic Year 2026-2027',
                'submission_date' => 'December 18, 2026',
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
     * Retrieve all loaded configuration data as associative array.
     *
     * Lazily loads configuration if not yet loaded. Returns full parsed or fallback config dictionary.
     *
     * @return array Complete configuration data.
     */
    public static function all(): array
    {
        if (self::$data === null) {
            self::load();
        }
        return self::$data;
    }

    /**
     * Parse YAML file into nested associative array.
     *
     * Implements pure PHP parser for subset of YAML syntax: top-level sections (keys ending in :),
     * nested key-value pairs (indented 2+ spaces), and inline string/number/boolean values.
     * Strips quotes from values and auto-detects bool (true/false), null, numeric types.
     * Ignores comment lines (#) and blank lines.
     *
     * Returns empty array if file not found or not readable.
     *
     * @param string $filePath Absolute path to YAML file.
     *
     * @return array Parsed YAML data structure.
     *
     * @example YAML: "system:\n  name: \"EIMS\"" parses to ['system' => ['name' => 'EIMS']]
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
                    // Type coercion: bool, null, numeric, or string
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
