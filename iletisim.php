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