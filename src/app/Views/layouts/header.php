<!DOCTYPE html>
<html lang="en">
<?php
$pageTitle = $title ?? null;
require __DIR__ . '/../partials/header.php';
?>
<body>
    <header class="site-header">
        <div class="container">
            <h1><?= htmlspecialchars($sys['name'] ?? 'Enterprise Information Management System', ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="subtitle"><?= htmlspecialchars(($sys['institution'] ?? 'Cavite State University') . ' - ' . ($sys['academic_term'] ?? 'DICT24 Information Management'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </header>
    <main class="container">
