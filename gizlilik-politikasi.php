<?php
/**
 * UcuzMazot.com - Gizlilik Politikası
 */

require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

$pageTitle = 'Gizlilik Politikası - ' . SITE_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-12">
    <div class="content-box">
        <h1>Gizlilik Politikası</h1>
        <p class="updated-date">Son Güncelleme:
            <?= date('d.m.Y') ?>
        </p>

        <p>UcuzMazot.com olarak kişisel verilerinizin güvenliğine önem veriyoruz.</p>

        <h3>1. Toplanan Bilgiler</h3>
        <p>Sitemizi ziyaret ettiğinizde, IP adresiniz, tarayıcı türünüz ve erişim zamanınız gibi teknik bilgiler
            otomatik olarak kaydedilebilir. Ayrıca, iletişim formu veya kayıt formu aracılığıyla bize ilettiğiniz
            kişisel bilgiler (ad, e-posta, vb.) saklanmaktadır.</p>

        <h3>2. Bilgilerin Kullanımı</h3>
        <p>Toplanan bilgiler, hizmetlerimizi sunmak, geliştirmek ve sizinle iletişim kurmak amacıyla kullanılmaktadır.
            Kişisel verileriniz, yasal zorunluluklar dışında üçüncü taraflarla paylaşılmamaktadır.</p>

        <h3>3. Çerezler (Cookies)</h3>
        <p>Sitemizde kullanıcı deneyimini iyileştirmek amacıyla çerezler kullanılmaktadır. Tarayıcı ayarlarınızdan çerez
            kullanımını yönetebilirsiniz.</p>

        <h3>4. Güvenlik</h3>
        <p>Kişisel verilerinizin güvenliği için gerekli teknik ve idari önlemler alınmaktadır. Ancak, internet üzerinden
            yapılan veri aktarımlarının %100 güvenli olduğu garanti edilemez.</p>

        <h3>5. İletişim</h3>
        <p>Gizlilik politikamızla ilgili sorularınız için <a href="mailto:<?= SITE_EMAIL ?>">
                <?= SITE_EMAIL ?>
            </a> adresinden bizimle iletişime geçebilirsiniz.</p>
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

    .content-box a {
        color: var(--primary);
    }
</style>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>