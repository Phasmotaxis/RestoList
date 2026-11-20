<!DOCTYPE html>
<html lang="en">
<?php
$pageTitle = $title ?? null;
require __DIR__ . '/../partials/header.php';
?>
<body>
    <header class="site-header">
        <div class="container site-header-content">
            <div>
                <h1><a href="/"><?= htmlspecialchars($sys['name'] ?? 'Enterprise Information Management System', ENT_QUOTES, 'UTF-8') ?></a></h1>
                <p class="subtitle"><?= htmlspecialchars(($sys['institution'] ?? 'Cavite State University') . ' - ' . ($sys['academic_term'] ?? 'DICT24 Information Management'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="theme-switcher-container">
                <label for="theme-select" class="theme-switcher-label">Theme:</label>
                <select id="theme-select" class="theme-select" aria-label="Select Color Theme">
                    <option value="default">Default (Neutral)</option>
                    <option value="lyra">Lyra (Piiiiink WCAG AA)</option>
                </select>
            </div>
        </div>
    </header>
    <main class="container">
