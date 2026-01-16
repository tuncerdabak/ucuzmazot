<?php
/**
 * İstasyon Paneli - Dashboard
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

requireStation();

$station = getCurrentStation();

if (!$station) {
    setFlash('error', 'İstasyon bulunamadı.');
    logout();
    redirect('/station/login.php');
}

// Güncel fiyat
$currentPrice = db()->fetchOne("
    SELECT * FROM fuel_prices 
    WHERE station_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
", [$station['id']]);

// Son 7 gün fiyat geçmişi
$priceHistory = db()->fetchAll("
    SELECT diesel_price, DATE(created_at) as date 
    FROM fuel_prices 
    WHERE station_id = ? 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY created_at DESC
", [$station['id']]);

// Son yorumlar
$recentReviews = db()->fetchAll("
    SELECT r.*, u.name as user_name 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE r.station_id = ? AND r.is_visible = 1
    ORDER BY r.created_at DESC 
    LIMIT 5
", [$station['id']]);

// İstatistikler
$stats = [
    'avg_rating' => db()->fetchColumn("SELECT AVG(rating) FROM reviews WHERE station_id = ? AND is_visible = 1", [$station['id']]) ?: 0,
    'review_count' => db()->fetchColumn("SELECT COUNT(*) FROM reviews WHERE station_id = ? AND is_visible = 1", [$station['id']]),
    'campaign_count' => db()->fetchColumn("SELECT COUNT(*) FROM campaigns WHERE station_id = ? AND is_active = 1", [$station['id']])
];

$pageTitle = 'Kontrol Paneli - İstasyon Paneli';
require_once __DIR__ . '/includes/header.php';
?>

<header class="panel-header">
    <h1>Kontrol Paneli</h1>
    <div class="header-actions">
        <span class="welcome-text">Hoş geldin,
            <?= e($_SESSION['user_name']) ?>
        </span>
    </div>
</header>

<?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'info-circle' ?>"></i>
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Onay Durumu -->
<?php if (!$station['is_approved']): ?>
    <div class="alert alert-warning">
        <i class="fas fa-clock"></i>
        <div>
            <strong>Onay Bekliyor</strong>
            <p style="margin:0">İstasyonunuz henüz onaylanmadı. Onaylandıktan sonra haritada görünür olacak.</p>
        </div>
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--gradient-primary);">
            <i class="fas fa-gas-pump"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Güncel Fiyat</span>
            <span class="stat-value">
                <?= $currentPrice ? formatPrice($currentPrice['diesel_price']) : '-' ?>
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: var(--gradient-secondary);">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Ortalama Puan</span>
            <span class="stat-value">
                <?= number_format($stats['avg_rating'], 1) ?>
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: var(--gradient-success);">
            <i class="fas fa-comments"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Değerlendirme</span>
            <span class="stat-value">
                <?= $stats['review_count'] ?>
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
            <i class="fas fa-tags"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Aktif Kampanya</span>
            <span class="stat-value">
                <?= $stats['campaign_count'] ?>
            </span>
        </div>
    </div>
</div>

<div class="content-grid">
    <!-- Hızlı Fiyat Güncelleme -->
    <div class="card" style="border: 2px solid var(--primary); box-shadow: 0 4px 20px rgba(37, 99, 235, 0.1);">
        <div class="card-header" style="background: var(--primary); color: white;">
            <h3><i class="fas fa-bolt"></i> 10 Saniyede Fiyat Güncelle</h3>
        </div>
        <div class="card-body">
            <form action="fiyat-guncelle.php" method="POST" class="quick-price-form">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <!-- Mazot -->
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700; color: var(--primary);">Motorin
                            (Mazot)</label>
                        <div class="price-input-group">
                            <span class="currency">₺</span>
                            <input type="number" name="diesel_price" step="0.01" min="0" max="100" class="price-input"
                                placeholder="00.00" style="font-size: 1.5rem; height: 60px;"
                                value="<?= $currentPrice ? $currentPrice['diesel_price'] : '' ?>">
                        </div>
                    </div>

                    <!-- TIR Mazot -->
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700; color: #dc2626;"><i class="fas fa-truck"></i>
                            TIR ÖZEL</label>
                        <div class="price-input-group">
                            <span class="currency">₺</span>
                            <input type="number" name="truck_diesel_price" step="0.01" min="0" max="100"
                                class="price-input" placeholder="00.00"
                                style="font-size: 1.5rem; height: 60px; border-color: #fca5a5;"
                                value="<?= $currentPrice ? $currentPrice['truck_diesel_price'] : '' ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-full mt-4"
                    style="height: 60px; font-size: 1.2rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-save"></i>
                    ŞİMDİ YAYINLA
                </button>
            </form>
            <?php if ($currentPrice): ?>
                <p class="price-last-update" style="text-align: center; margin-top: 10px; font-weight: 500;">
                    <i class="fas fa-history"></i> Son güncelleme:
                    <?= timeAgo($currentPrice['created_at']) ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Son Yorumlar -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-comments"></i> Son Yorumlar</h3>
            <a href="yorumlar.php" class="btn btn-sm btn-outline">Tümü</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentReviews)): ?>
                <p class="text-center text-gray">Henüz yorum yok.</p>
            <?php else: ?>
                <div class="review-list">
                    <?php foreach ($recentReviews as $review): ?>
                        <div class="review-item-compact">
                            <div class="review-stars">
                                <?= renderStars($review['rating']) ?>
                            </div>
                            <p class="review-text">
                                <?= e(mb_substr($review['comment'] ?? '', 0, 80)) ?>...
                            </p>
                            <span class="review-meta">
                                <?= e($review['user_name'] ?? 'Anonim') ?> -
                                <?= timeAgo($review['created_at']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>