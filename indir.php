<?php
require_once 'config.php';
$pageTitle = 'Mobil Uygulamamızı İndirin - ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="download-page">
    <div class="container">
        <div class="download-card animate__animated animate__fadeInUp">
            <div class="download-header">
                <i class="fas fa-mobile-alt main-icon"></i>
                <h1>UcuzMazot Mobil Uygulama</h1>
                <p>En güncel akaryakıt fiyatları artık her an cebinizde!</p>
            </div>

            <div class="download-actions">
                <a href="/android_uygulama/ucuzmazot_v1.apk" class="btn btn-primary btn-lg download-btn">
                    <i class="fab fa-android"></i> Android APK İndir (V1.0.0)
                </a>
                <p class="file-info text-muted">Boyut: ~5.4 MB | Versiyon: 1.0.0</p>
            </div>

            <div class="install-guide">
                <h3><i class="fas fa-info-circle"></i> Kurulum Rehberi</h3>
                <div class="steps">
                    <div class="step">
                        <span class="step-num">1</span>
                        <p>Yukarıdaki butona tıklayarak <strong>.apk</strong> dosyasını indirin.</p>
                    </div>
                    <div class="step">
                        <span class="step-num">2</span>
                        <p>İndirme tamamlandığında dosyayı açın. "Güvenlik nedeniyle bu kaynaktan uygulama yüklenemez"
                            uyarısı alırsanız <strong>Ayarlar</strong>'a gidin.</p>
                    </div>
                    <div class="step">
                        <span class="step-num">3</span>
                        <p><strong>"Bu kaynaktan izin ver"</strong> seçeneğini aktif hale getirin ve yüklemeyi
                            tamamlayın.</p>
                    </div>
                </div>
            </div>

            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-bolt"></i>
                    <span>Daha Hızlı</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-bell"></i>
                    <span>Anlık Bildirimler</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Konum Bazlı</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .download-page {
        padding: var(--space-10) 0;
        background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
        min-height: calc(100vh - 300px);
    }

    .download-card {
        max-width: 600px;
        margin: 0 auto;
        background: var(--white);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-xl);
        padding: var(--space-8);
        text-align: center;
    }

    .main-icon {
        font-size: 4rem;
        color: var(--primary);
        margin-bottom: var(--space-4);
    }

    .download-header h1 {
        font-size: 2rem;
        margin-bottom: var(--space-2);
    }

    .download-header p {
        color: var(--gray-500);
        font-size: 1.1rem;
    }

    .download-actions {
        margin: var(--space-8) 0;
        padding: var(--space-6);
        background: var(--gray-50);
        border-radius: var(--radius-lg);
    }

    .download-btn {
        width: 100%;
        margin-bottom: var(--space-3);
        font-size: 1.25rem;
        padding: var(--space-4);
    }

    .file-info {
        font-size: 0.875rem;
    }

    .install-guide {
        text-align: left;
        margin-top: var(--space-8);
        padding-top: var(--space-8);
        border-top: 1px solid var(--gray-100);
    }

    .install-guide h3 {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        margin-bottom: var(--space-4);
    }

    .steps {
        display: flex;
        flex-direction: column;
        gap: var(--space-4);
    }

    .step {
        display: flex;
        gap: var(--space-4);
        align-items: flex-start;
    }

    .step-num {
        width: 24px;
        height: 24px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
        flex-shrink: 0;
        margin-top: 4px;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--space-4);
        margin-top: var(--space-8);
    }

    .feature-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--space-2);
        color: var(--gray-600);
    }

    .feature-item i {
        font-size: 1.5rem;
        color: var(--primary);
    }

    @media (max-width: 640px) {
        .download-card {
            padding: var(--space-5);
        }

        .features-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>