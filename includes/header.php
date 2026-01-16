<?php
/**
 * Ortak Header Bileşeni
 */

// Config ve includes
if (!defined('SITE_NAME')) {
    require_once dirname(__DIR__) . '/config.php';
}

$pageTitle = $pageTitle ?? SITE_TITLE;
$pageDescription = $pageDescription ?? 'Türkiye genelinde en ucuz mazot fiyatlarını harita üzerinde karşılaştırın.';
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <script>
        // Flash of light mode prevention
        (function () {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0VH3HREBR3"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-0VH3HREBR3');
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- SEO Meta -->
    <title>
        <?= e($pageTitle) ?>
    </title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="keywords" content="mazot fiyatı, ucuz mazot, akaryakıt fiyatları, benzin istasyonu, tır şoförü">
    <meta name="author" content="UcuzMazot">
    <meta name="robots" content="index, follow">
    <meta name="google-site-verification" content="4WIvhmmzRD2HLL6yh3pNbTPLYVrTQqcjK04TobT_dco">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= SITE_URL ?>">
    <meta property="og:site_name" content="<?= SITE_NAME ?>">
    <?= $extraMeta ?? '' ?>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= url('favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('favicon.png') ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.cdnfonts.com/css/seven-segment" rel="stylesheet">

    <!-- Leaflet Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <!-- Ana Stil -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">

    <!-- PWA -->
    <link rel="manifest" href="<?= url('manifest.json') ?>">

    <!-- iOS PWA Meta -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="UcuzMazot">

    <!-- Dynamic Theme Color -->
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#111827" media="(prefers-color-scheme: dark)">
    <script src="<?= asset('js/theme-toggle.js') ?>"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= url('service-worker.js') ?>')
                    .then(reg => console.log('Service Worker registered'))
                    .catch(err => console.log('Service Worker registration failed', err));
            });
        }
    </script>

    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="<?= asset($extraCss) ?>">
    <?php endif; ?>
</head>

<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <!-- Logo -->
                <a href="<?= url('/') ?>" class="logo">
                    <i class="fas fa-gas-pump"></i>
                    <span>
                        <?= SITE_NAME ?>
                    </span>
                </a>

                <!-- Navigation -->
                <nav class="main-nav">
                    <a href="<?= url('/') ?>" class="nav-link">
                        <i class="fas fa-map-marked-alt"></i>
                        <span>Harita</span>
                    </a>
                    <a href="<?= url('/fiyatlar.php') ?>" class="nav-link">
                        <i class="fas fa-list"></i>
                        <span>Fiyatlar</span>
                    </a>
                    <a href="<?= url('/markalar.php') ?>" class="nav-link">
                        <i class="fas fa-filter"></i>
                        <span>Markalar</span>
                    </a>
                    <div class="nav-dropdown">
                        <a href="#" class="nav-link">
                            <i class="fas fa-city"></i>
                            <span>Şehirler</span>
                        </a>
                        <div class="dropdown-menu">
                            <?php
                            $navCities = ['İstanbul', 'Ankara', 'İzmir', 'Bursa', 'Antalya', 'Kocaeli', 'Adana', 'Konya', 'Mersin', 'Gaziantep'];
                            foreach ($navCities as $nCity):
                                ?>
                                <a
                                    href="<?= url('/sehir.php?slug=' . slugify($nCity . '-en-ucuz-mazot')) ?>"><?= $nCity ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </nav>

                <!-- Actions -->
                <div class="header-actions">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <a href="<?= url('/admin/') ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-cog"></i>
                                Admin Panel
                            </a>
                        <?php elseif (isStation()): ?>
                            <a href="<?= url('/station/') ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-store"></i>
                                İstasyonum
                            </a>
                        <?php else: ?>
                            <a href="<?= url('/profil.php') ?>" class="btn btn-sm btn-outline">
                                <i class="fas fa-user"></i>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button onclick="openAuthModal()" class="btn btn-sm btn-primary">
                            <i class="fas fa-user-circle"></i>
                            Giriş Yap / Üye Ol
                        </button>
                    <?php endif; ?>

                    <!-- Theme Toggle -->
                    <button class="btn btn-icon btn-outline theme-toggle-btn" title="Tema Değiştir">
                        <i class="fas fa-moon"></i>
                    </button>

                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- Mobile Menu Container -->
        <div class="mobile-menu" id="mobileMenu">
            <a href="<?= url('/') ?>" class="nav-link">
                <i class="fas fa-map-marked-alt"></i>
                <span>Harita</span>
            </a>
            <a href="<?= url('/fiyatlar.php') ?>" class="nav-link">
                <i class="fas fa-list"></i>
                <span>Fiyatlar</span>
            </a>
            <a href="<?= url('/markalar.php') ?>" class="nav-link">
                <i class="fas fa-filter"></i>
                <span>Markalar</span>
            </a>
            <a href="<?= url('/indir.php') ?>" class="nav-link hide-in-app">
                <i class="fas fa-download"></i>
                <span>Uygulamayı İndir</span>
            </a>

            <div class="border-t my-2 pt-2"></div>

            <?php if (isLoggedIn()): ?>
                <a href="<?= url('/profil.php') ?>" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Profilim</span>
                </a>
                <?php if (isAdmin()): ?>
                    <a href="<?= url('/admin/') ?>" class="nav-link text-primary">
                        <i class="fas fa-cog"></i>
                        <span>Admin Panel</span>
                    </a>
                <?php endif; ?>
                <a href="<?= url('/logout.php') ?>" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Çıkış Yap</span>
                </a>
            <?php else: ?>
                <button onclick="openAuthModal()" class="nav-link w-full text-left"
                    style="background:none; border:none; padding: var(--space-3) var(--space-4);">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Giriş Yap / Üye Ol</span>
                </button>
            <?php endif; ?>

            <div class="border-t my-2 pt-2"></div>

            <button class="nav-link theme-toggle-btn w-full text-left"
                style="background:none; border:none; padding: var(--space-3) var(--space-4);">
                <i class="fas fa-moon"></i>
                <span>Tema Değiştir</span>
            </button>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toggle = document.getElementById('mobileMenuToggle');
                const menu = document.getElementById('mobileMenu');

                if (toggle && menu) {
                    toggle.addEventListener('click', function (e) {
                        e.stopPropagation();
                        menu.classList.toggle('active');
                    });

                    // Close when clicking outside
                    document.addEventListener('click', function (e) {
                        if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                            menu.classList.remove('active');
                        }
                    });
                }
            });
        </script>
    </header>

    <!-- Flash Messages -->
    <?php if ($flash = getFlash()): ?>
        <div class="container mt-4">
            <div class="alert alert-<?= e($flash['type']) ?>">
                <i
                    class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                <span>
                    <?= e($flash['message']) ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php if (isLoggedIn() && currentUserRole() === 'driver' && !($_SESSION['is_password_set'] ?? 1)): ?>
            <div class="password-warning-banner">
                <div class="container d-flex align-items-center justify-content-between">
                    <span>
                        <i class="fas fa-exclamation-triangle"></i>
                        Henüz bir şifre belirlemediniz. Güvenliğiniz için lütfen profilinizden şifre oluşturun.
                    </span>
                    <a href="<?= url('/profil.php') ?>" class="btn btn-sm btn-warning">
                        Şifre Belirle
                    </a>
                </div>
            </div>
            <style>
                .password-warning-banner {
                    background: #fffbeb;
                    border-bottom: 1px solid #fde68a;
                    padding: var(--space-3) 0;
                    color: #92400e;
                    font-size: 0.9375rem;
                }

                .password-warning-banner .container {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: var(--space-4);
                }

                .password-warning-banner i {
                    margin-right: var(--space-2);
                    color: #d97706;
                }

                @media (max-width: 640px) {
                    .password-warning-banner .container {
                        flex-direction: column;
                        text-align: center;
                    }
                }
            </style>
        <?php endif; ?>