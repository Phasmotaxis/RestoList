<?php

declare(strict_types=1);

/**
 * Global Header Partial
 * Dynamically resolves meta properties from dev/values/constants/main.yaml
 */
$candidates = [
    dirname(__DIR__, 4) . '/dev/values/constants/main.yaml',
    dirname(__DIR__, 3) . '/dev/values/constants/main.yaml',
    '/var/www/dev/values/constants/main.yaml',
    '/var/www/html/dev/values/constants/main.yaml',
];
$configPath = null;
foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
        $configPath = $candidate;
        break;
    }
}

if (!function_exists('parseYamlSimple')) {
    function parseYamlSimple(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $res = [];
        $section = null;
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '' || str_starts_with($t, '#')) {
                continue;
            }
            if (preg_match('/^([a-zA-Z0-9_-]+):\s*$/', $t, $m)) {
                $section = $m[1];
                $res[$section] = [];
            } elseif (preg_match('/^([a-zA-Z0-9_-]+):\s*["\']?(.*?)["\']?\s*$/', $t, $m)) {
                $k = $m[1];
                $v = trim($m[2], "\"'");
                if ($section !== null) {
                    $res[$section][$k] = $v;
                } else {
                    $res[$k] = $v;
                }
            }
        }
        return $res;
    }
}

$constants = ($configPath !== null && file_exists($configPath))
    ? (function_exists('yaml_parse_file') ? yaml_parse_file($configPath) : parseYamlSimple($configPath))
    : [
        'system' => [
            'name' => 'Enterprise Information Management System',
            'short_name' => 'EIMS',
            'author' => 'EIMS Development Team',
            'description' => 'Enterprise Information Management System for Multi-Tenant Data Governance',
            'domain' => 'eims.cvsu.edu.ph',
            'site_url' => 'https://eims.cvsu.edu.ph',
            'banner_image' => '/assets/images/banner.png',
            'logo_image' => '/assets/images/icons/logo.png',
        ],
    ];

$sys = $constants['system'] ?? [];
$pageTitle = htmlspecialchars($pageTitle ?? ($sys['short_name'] ?? 'EIMS'), ENT_QUOTES, 'UTF-8');
$author = htmlspecialchars($sys['author'] ?? 'EIMS Development Team', ENT_QUOTES, 'UTF-8');
$desc = htmlspecialchars($sys['description'] ?? 'EIMS', ENT_QUOTES, 'UTF-8');
$domain = htmlspecialchars($sys['domain'] ?? 'eims.cvsu.edu.ph', ENT_QUOTES, 'UTF-8');
$siteUrl = htmlspecialchars($sys['site_url'] ?? 'https://eims.cvsu.edu.ph', ENT_QUOTES, 'UTF-8');
$banner = htmlspecialchars($sys['banner_image'] ?? '/assets/images/banner.png', ENT_QUOTES, 'UTF-8');
$logo = htmlspecialchars($sys['logo_image'] ?? '/assets/images/icons/logo.png', ENT_QUOTES, 'UTF-8');
?>
<head>
    <meta name="apple-mobile-web-app-title" content="<?= $pageTitle ?>" />
    <meta name="twitter:title" content="<?= $pageTitle ?>" />
    <meta property="og:site_name" content="<?= $pageTitle ?>" />
    <meta property="og:title" content="<?= $pageTitle ?>" />
    <title><?= $pageTitle ?></title>

    <meta name="author" content="<?= $author ?>">

    <meta name="description" content="<?= $desc ?>" />
    <meta property="og:description" content="<?= $desc ?>" />

    <meta name="twitter:site:domain" content="<?= $domain ?>" />
    <meta name="twitter:url" content="<?= $siteUrl ?>" />
    <meta property="og:url" content="<?= $siteUrl ?>" />
    <link rel="canonical" href="<?= $siteUrl ?>" />

    <meta name="twitter:card" content="<?= $banner ?>" />
    <meta name="twitter:image" content="<?= $banner ?>" />
    <meta property="og:image" content="<?= $banner ?>" />
    <meta property="og:image:width" content="1280" />
    <meta property="og:image:height" content="800" />

    <link rel="icon" type="image/x-icon" href="<?= $logo ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $logo ?>" />

    <meta property="twitter:card" content="summary_large_image" />

    <meta property="og:type" content="website" />

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no" />

    <link rel="preload stylesheet" as="style" href="/assets/css/style.css" />
    <script defer src="/assets/js/app.js"></script>
</head>
