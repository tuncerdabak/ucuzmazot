<?php
/**
 * UcuzMazot.com - Hakkımızda
 */

require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

$pageTitle = 'Hakkımızda - ' . SITE_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-12">
    <div class="content-box">
        <h1>Hakkımızda</h1>
        <p class="lead">UcuzMazot.com olarak Türkiye genelindeki akaryakıt istasyonlarının güncel fiyatlarını
            kullanıcılarımızla buluşturuyoruz.</p>

        <p>Amacımız, sürücülerin en uygun fiyatlı yakıta kolayca ulaşmasını sağlamak ve tasarruf etmelerine yardımcı
            olmaktır. Platformumuz üzerinden dizel, benzin ve LPG fiyatlarını karşılaştırabilir, size en yakın ve en
            hesaplı istasyonu bulabilirsiniz.</p>

        <h2>Misyonumuz</h2>
        <p>Şeffaf ve güncel fiyat bilgisi sunarak akaryakıt piyasasında rekabeti artırmak ve tüketicinin bütçesine
            katkıda bulunmak.</p>

        <h2>Vizyonumuz</h2>
        <p>Türkiye'nin en güvenilir ve en çok tercih edilen akaryakıt fiyat karşılaştırma platformu olmak.</p>
    </div>
</div>

<style>
    body {
        background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('banner.jpg');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        min-height: 100vh;
    }

    .py-12 {
        padding-top: var(--space-12);
        padding-bottom: var(--space-12);
    }

    .content-box {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        padding: var(--space-8);
        border-radius: var(--radius-xl);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        max-width: 800px;
        margin: 0 auto;
    }

    /* Dark mode adjustments for glass card if needed */
    [data-theme="dark"] .content-box {
        background: rgba(31, 41, 55, 0.9);
        color: var(--gray-100);
    }

    [data-theme="dark"] .content-box h1,
    [data-theme="dark"] .content-box h2,
    [data-theme="dark"] .content-box .lead {
        color: var(--white);
    }

    [data-theme="dark"] .content-box p {
        color: var(--gray-300);
    }

    .content-box h1 {
        margin-bottom: var(--space-6);
        color: var(--gray-900);
    }

    .content-box h2 {
        margin-top: var(--space-8);
        margin-bottom: var(--space-4);
        color: var(--gray-800);
        font-size: 1.5rem;
    }

    .content-box p {
        color: var(--gray-600);
        line-height: 1.6;
        margin-bottom: var(--space-4);
    }

    .content-box .lead {
        font-size: 1.25rem;
        color: var(--gray-800);
        font-weight: 500;
    }
</style>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>