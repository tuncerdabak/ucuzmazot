<?php
/**
 * UcuzMazot.com - İletişim
 */

require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

$pageTitle = 'İletişim - ' . SITE_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-12">
    <div class="content-box">
        <h1>İletişim</h1>
        <p class="lead">Görüş, öneri ve sorularınız için bizimle iletişime geçebilirsiniz.</p>

        <div class="contact-info">
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <h3>E-posta</h3>
                    <a href="mailto:<?= SITE_EMAIL ?>">
                        <?= SITE_EMAIL ?>
                    </a>
                </div>
            </div>

            <?php if (!empty(SITE_CONTACT_PHONE)): ?>
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <div>
                        <h3>Telefon</h3>
                        <a href="tel:<?= e(SITE_CONTACT_PHONE) ?>"><?= e(SITE_CONTACT_PHONE) ?></a>
                    </div>
                </div>

                <div class="contact-item">
                    <i class="fab fa-whatsapp"></i>
                    <div>
                        <h3>WhatsApp</h3>
                        <a href="https://wa.me/<?= cleanPhone(SITE_CONTACT_PHONE) ?>" target="_blank">WhatsApp Destek
                            Hattı</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
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

    /* Dark mode adjustments for glass card */
    [data-theme="dark"] .content-box {
        background: rgba(31, 41, 55, 0.9);
        color: var(--gray-100);
    }

    [data-theme="dark"] .content-box h1,
    [data-theme="dark"] .content-box h3,
    [data-theme="dark"] .content-box .lead {
        color: var(--white);
    }

    [data-theme="dark"] .contact-item {
        background: rgba(255, 255, 255, 0.05);
    }

    .content-box h1 {
        margin-bottom: var(--space-6);
        color: var(--gray-900);
    }

    .content-box .lead {
        font-size: 1.25rem;
        color: var(--gray-800);
        font-weight: 500;
        margin-bottom: var(--space-8);
    }

    .contact-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-4);
    }

    .contact-item {
        display: flex;
        align-items: flex-start;
        gap: var(--space-4);
        padding: var(--space-4);
        background: var(--gray-50);
        border-radius: var(--radius-lg);
    }

    .contact-item i {
        font-size: 1.5rem;
        color: var(--primary);
        margin-top: 4px;
    }

    .contact-item h3 {
        font-size: 1.125rem;
        margin: 0 0 var(--space-1) 0;
        color: var(--gray-900);
    }

    .contact-item a {
        color: var(--primary);
        font-weight: 500;
    }
</style>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>