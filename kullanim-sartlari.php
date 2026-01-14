<?php
/**
 * UcuzMazot.com - Kullanım Şartları
 */

require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

$pageTitle = 'Kullanım Şartları - ' . SITE_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-12">
    <div class="content-box">
        <h1>Kullanım Şartları</h1>
        <p class="updated-date">Son Güncelleme:
            <?= date('d.m.Y') ?>
        </p>

        <p>Lütfen sitemizi kullanmadan önce bu kullanım şartlarını dikkatlice okuyunuz.</p>

        <h3>1. Hizmetin Tanımı</h3>
        <p>UcuzMazot.com, kullanıcılara akaryakıt istasyonlarının konum ve fiyat bilgilerini görüntüleme hizmeti sunan
            bir platformdur. Sitemizde yer alan fiyatlar bilgilendirme amaçlıdır ve istasyonlardaki gerçek fiyatlarla
            farklılık gösterebilir.</p>

        <h3>2. Kullanım Kuralları</h3>
        <p>Kullanıcılar, siteyi yasalara uygun şekilde kullanmayı kabul eder. Site içeriğinin izinsiz kopyalanması,
            çoğaltılması veya ticari amaçla kullanılması yasaktır.</p>

        <h3>3. Sorumluluk Reddi</h3>
        <p>UcuzMazot.com, sitede yer alan bilgilerin doğruluğu konusunda azami özeni göstermekle birlikte, olası
            hatalardan veya güncel olmayan bilgilerden sorumlu tutulamaz. Akaryakıt alımı yapmadan önce ilgili
            istasyondan fiyat teyidi yapılması önerilir.</p>

        <h3>4. Değişiklikler</h3>
        <p>UcuzMazot.com, işbu kullanım şartlarını dilediği zaman değiştirme hakkını saklı tutar. Değişiklikler sitede
            yayınlandığı tarihte yürürlüğe girer.</p>
    </div>
</div>

<style>
    .py-12 {
        padding-top: var(--space-12);
        padding-bottom: var(--space-12);
    }

    .content-box {
        background: var(--white);
        padding: var(--space-8);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-lg);
        max-width: 800px;
        margin: 0 auto;
    }

    .content-box h1 {
        margin-bottom: var(--space-2);
        color: var(--gray-900);
    }

    .updated-date {
        color: var(--gray-500);
        font-size: 0.875rem;
        margin-bottom: var(--space-6);
    }

    .content-box h3 {
        margin-top: var(--space-6);
        margin-bottom: var(--space-3);
        color: var(--gray-800);
        font-size: 1.25rem;
    }

    .content-box p {
        color: var(--gray-600);
        line-height: 1.6;
        margin-bottom: var(--space-4);
    }
</style>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>