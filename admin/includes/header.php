<?php
if (!defined('SITE_NAME')) {
    require_once dirname(dirname(__DIR__)) . '/config.php';
}
$pageTitle = $pageTitle ?? 'Admin Paneli - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= e($pageTitle) ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/seven-segment" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/panel-layout.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin-panel.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="<?= asset($extraCss) ?>">
    <?php endif; ?>
    <?php include dirname(dirname(__DIR__)) . '/station/includes/header_meta.php'; // Favicon ?>
</head>

<body class="panel-page">
    <div class="panel-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="panel-main">
            <button type="button" class="mobile-menu-toggle" id="sidebarToggle" aria-label="Menüyü Aç/Kapat">
                <i class="fas fa-bars"></i>
            </button>
            <div class="sidebar-overlay" id="sidebarOverlay"></div>