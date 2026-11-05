<?php

declare(strict_types=1);

/**
 * Global Header Partial
 * Dynamically resolves meta properties via App\Core\Config
 */

require_once __DIR__ . '/../../Core/Config.php';

use App\Core\Config;

$sys = Config::get('system', []);

$pageTitle = htmlspecialchars($pageTitle ?? ($sys['short_name'] ?? 'EIMS'), ENT_QUOTES, 'UTF-8');
$author = htmlspecialchars($sys['author'] ?? 'EIMS Development Team', ENT_QUOTES, 'UTF-8');
$desc = htmlspecialchars($sys['description'] ?? 'EIMS', ENT_QUOTES, 'UTF-8');
$domain = htmlspecialchars($sys['domain'] ?? 'eims-cvsu.lyra-on.top', ENT_QUOTES, 'UTF-8');
$siteUrl = htmlspecialchars($sys['site_url'] ?? 'https://eims-cvsu.lyra-on.top', ENT_QUOTES, 'UTF-8');
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
