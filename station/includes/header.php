<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= e($pageTitle ?? 'İstasyon Paneli') ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/station-panel.css') ?>">
    <?php if (file_exists(__DIR__ . '/header_meta.php'))
        include __DIR__ . '/header_meta.php'; ?>
</head>

<body class="panel-page">
    <div class="mobile-topbar">
        <div class="mobile-logo">
            <i class="fas fa-gas-pump"></i>
            <span>
                <?= SITE_NAME ?>
            </span>
        </div>
        <button id="stationMenuToggle" class="mobile-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="panel-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="panel-main">