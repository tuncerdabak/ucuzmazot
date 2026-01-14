<?php
/**
 * İstasyon Detay Sayfası
 */

require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

$stationId = (int) ($_GET['id'] ?? 0);

if (!$stationId) {
    setFlash('error', 'İstasyon bulunamadı.');
    redirect('/');
}

// Fiyat Onayı İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_price') {
    if (!isLoggedIn()) {
        setFlash('error', 'Fiyat onaylamak için giriş yapmalısınız.');
    } else {
        $priceId = (int) $_POST['price_id'];
        $userId = currentUserId();

        try {
            db()->insert('price_confirmations', [
                'price_id' => $priceId,
                'user_id' => $userId
            ]);
            setFlash('success', 'Fiyat onayınız için teşekkürler!');
        } catch (Exception $e) {
            // Zaten onaylamışsa hata verebilir, sessizce geçebiliriz veya bildirebiliriz
            setFlash('info', 'Bu fiyatı zaten onaylamışsınız.');
        }
    }
    redirect('/istasyon-detay.php?id=' . $stationId);
}

// İstasyon bilgileri
$station = db()->fetchOne("
    SELECT s.*, u.name as owner_name,
        fp.id as current_price_id,
        fp.diesel_price as current_diesel,
        fp.truck_diesel_price as current_truck_diesel,
        fp.gasoline_price as current_gasoline,
        fp.lpg_price as current_lpg,
        fp.created_at as price_updated_at,
        (SELECT COUNT(*) FROM price_confirmations WHERE price_id = fp.id) as confirmation_count,
        (SELECT AVG(rating) FROM reviews WHERE station_id = s.id AND is_visible = 1) as avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE station_id = s.id AND is_visible = 1) as review_count
    FROM stations s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN (
        SELECT * FROM fuel_prices WHERE station_id = ? ORDER BY created_at DESC LIMIT 1
    ) fp ON s.id = fp.station_id
    WHERE s.id = ? AND s.is_active = 1 AND s.is_approved = 1
", [$stationId, $stationId]);

if (!$station) {
    setFlash('error', 'İstasyon bulunamadı veya pasif durumda.');
    redirect('/');
}

// Fiyat geçmişi
$priceHistory = db()->fetchAll("
    SELECT diesel_price, gasoline_price, lpg_price, created_at
    FROM fuel_prices
    WHERE station_id = ?
    ORDER BY created_at DESC
    LIMIT 30
", [$stationId]);

// Aktif kampanyalar
$campaigns = db()->fetchAll("
    SELECT * FROM campaigns
    WHERE station_id = ? AND is_active = 1
    AND (end_date IS NULL OR end_date >= CURDATE())
    ORDER BY created_at DESC
", [$stationId]);

// Yorumlar
$reviews = db()->fetchAll("
    SELECT r.*, u.name as user_name
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.station_id = ? AND r.is_visible = 1
    ORDER BY r.created_at DESC
    LIMIT 20
", [$stationId]);

// Olanaklar
$facilities = $station['facilities'] ? json_decode($station['facilities'], true) : [];

$pageTitle = $station['name'] . ' - ' . SITE_NAME;
$extraMeta = '<meta property="og:image" content="' . url('api/station-og-image.php?id=' . $station['id']) . '">';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="station-detail-page">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="/">Ana Sayfa</a>
            <i class="fas fa-chevron-right"></i>
            <a href="/?city=<?= urlencode($station['city']) ?>">
                <?= e($station['city']) ?>
            </a>
            <i class="fas fa-chevron-right"></i>
            <span>
                <?= e($station['name']) ?>
            </span>
        </nav>

        <div class="detail-grid">
            <!-- Sol: İstasyon Bilgileri -->
            <div class="detail-main">
                <!-- Başlık -->
                <div class="station-header glass-card">
                    <div class="station-brand-logo">
                        <?php $brandLogo = getBrandLogo($station['brand']); ?>
                        <?php if ($brandLogo): ?>
                            <img src="<?= $brandLogo ?>" alt="<?= e($station['brand']) ?>"
                                style="max-width: 100%; max-height: 100%; object-fit: contain;"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <i class="fas fa-gas-pump" style="display: none;"></i>
                        <?php elseif ($station['brand']): ?>
                            <span style="font-size: 1rem; text-align: center; line-height: 1.2;">
                                <?= e($station['brand']) ?>
                            </span>
                        <?php else: ?>
                            <i class="fas fa-gas-pump"></i>
                        <?php endif; ?>
                    </div>
                    <div class="station-header-info">
                        <h1>
                            <?= e($station['name']) ?>
                        </h1>
                        <p class="station-address">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= e($station['address'] ?? $station['city'] . ($station['district'] ? ', ' . $station['district'] : '')) ?>
                        </p>
                        <?php if ($station['avg_rating']): ?>
                            <div class="station-rating-large">
                                <?= renderStars($station['avg_rating']) ?>
                                <span class="rating-number">
                                    <?= number_format($station['avg_rating'], 1) ?>
                                </span>
                                <span class="review-count">(
                                    <?= $station['review_count'] ?> değerlendirme)
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Fiyat Kartı -->
                <div class="price-card glass-card">
                    <div class="price-main">
                        <span class="price-label">Güncel Akaryakıt Fiyatları</span>

                        <div class="price-list">
                            <!-- Mazot -->
                            <div class="price-item">
                                <span class="fuel-name">Motorin</span>
                                <?php if ($station['current_diesel']): ?>
                                    <?php if (isLoggedIn()): ?>
                                        <span
                                            class="fuel-price <?= $station['current_diesel'] < DIESEL_MIN_PRICE + 2 ? 'cheap' : '' ?>">
                                            <span
                                                class="digital-price"><?= number_format($station['current_diesel'], 2, ',', '.') ?></span>
                                            <span class="currency">TL</span>
                                        </span>
                                    <?php else: ?>
                                        <?php $dObj = formatObfuscatedPrice($station['current_diesel']); ?>
                                        <span class="fuel-price auth-trigger" title="Fiyatı görmek için tıklayın">
                                            <span><?= $dObj['visible'] ?></span><span
                                                class="filter-blur"><?= $dObj['blurred'] ?></span>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="fuel-price text-gray">-</span>
                                <?php endif; ?>
                            </div>

                            <!-- TIR Mazot -->
                            <?php if ($station['current_truck_diesel']): ?>
                                <div class="price-item truck-price-row"
                                    style="background: rgba(220, 38, 38, 0.1); border: 1px dashed rgba(220, 38, 38, 0.3); padding: 8px; border-radius: 6px; margin: 4px 0;">
                                    <span class="fuel-name" style="color: #f87171; font-weight: 600;"><i
                                            class="fas fa-truck"></i> TIR ÖZEL</span>
                                    <?php if (isLoggedIn()): ?>
                                        <span class="fuel-price" style="color: #b91c1c;">
                                            <span class="digital-price"
                                                style="color: inherit;"><?= number_format($station['current_truck_diesel'], 2, ',', '.') ?></span>
                                            <span class="currency" style="color: inherit;">TL</span>
                                        </span>
                                    <?php else: ?>
                                        <?php $tdObj = formatObfuscatedPrice($station['current_truck_diesel']); ?>
                                        <span class="fuel-price auth-trigger" style="color: #ef4444;">
                                            <span><?= $tdObj['visible'] ?></span><span
                                                class="filter-blur"><?= $tdObj['blurred'] ?></span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Benzin -->
                            <?php if ($station['current_gasoline']): ?>
                                <div class="price-item">
                                    <span class="fuel-name">Benzin</span>
                                    <?php if (isLoggedIn()): ?>
                                        <span class="fuel-price">
                                            <span
                                                class="digital-price"><?= number_format($station['current_gasoline'], 2, ',', '.') ?></span>
                                            <span class="currency">TL</span>
                                        </span>
                                    <?php else: ?>
                                        <?php $gObj = formatObfuscatedPrice($station['current_gasoline']); ?>
                                        <span class="fuel-price auth-trigger" title="Fiyatı görmek için tıklayın">
                                            <span><?= $gObj['visible'] ?></span><span
                                                class="filter-blur"><?= $gObj['blurred'] ?></span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- LPG -->
                            <?php if ($station['current_lpg']): ?>
                                <div class="price-item">
                                    <span class="fuel-name">LPG</span>
                                    <?php if (isLoggedIn()): ?>
                                        <span class="fuel-price">
                                            <span
                                                class="digital-price"><?= number_format($station['current_lpg'], 2, ',', '.') ?></span>
                                            <span class="currency">TL</span>
                                        </span>
                                    <?php else: ?>
                                        <?php $lObj = formatObfuscatedPrice($station['current_lpg']); ?>
                                        <span class="fuel-price auth-trigger" title="Fiyatı görmek için tıklayın">
                                            <span><?= $lObj['visible'] ?></span><span
                                                class="filter-blur"><?= $lObj['blurred'] ?></span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($station['price_updated_at']): ?>
                            <div class="price-meta-info"
                                style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                                <span class="price-updated">
                                    <i class="fas fa-clock"></i>
                                    <?= timeAgo($station['price_updated_at']) ?> güncellendi
                                    <?php
                                    $isOld = (time() - strtotime($station['price_updated_at'])) > (24 * 3600);
                                    if ($isOld):
                                        ?>
                                        <div class="old-price-alert"
                                            style="color: #ef4444; font-weight: 700; margin-top: 5px; font-size: 0.85rem;">
                                            <i class="fas fa-exclamation-circle"></i> BU FİYAT 24 SAATTEN ESKİDİR, GÜNCEL
                                            OLMAYABİLİR!
                                        </div>
                                    <?php endif; ?>
                                </span>

                                <?php if ($station['confirmation_count'] > 0): ?>
                                    <span class="confirmation-badge"
                                        style="background: #10b981; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                        <i class="fas fa-check-double"></i> <?= $station['confirmation_count'] ?> Doğrulama
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Social Sharing Section -->
                            <div class="share-actions mt-4 p-3 rounded-4"
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                                <span class="small fw-semibold text-muted d-block mb-3 text-uppercase"
                                    style="letter-spacing: 1px;">Bu Fiyatı Paylaş</span>
                                <div class="d-flex gap-2">
                                    <?php
                                    $shareText = urlencode($station['name'] . " istasyonunda mazot fiyatı: " . number_format($station['current_diesel'], 2, ',', '.') . " TL! Detaylar: " . url('/istasyon-detay.php?id=' . $station['id']));
                                    $whatsappUrl = "https://wa.me/?text=" . $shareText;
                                    ?>
                                    <a href="<?= $whatsappUrl ?>" target="_blank" class="btn btn-sm w-full"
                                        style="background: #25D366; color: white; border: none;">
                                        <i class="fab fa-whatsapp"></i> WhatsApp Status
                                    </a>
                                    <button onclick="copyShareLink()" class="btn btn-sm btn-light border"
                                        style="width: 50px;" title="Linki Kopyala">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <script>
                                function copyShareLink() {
                                    const url = "<?= url('/istasyon-detay.php?id=' . $station['id']) ?>";
                                    navigator.clipboard.writeText(url).then(() => {
                                        alert('Link kopyalandı! Şimdi WhatsApp veya Instagram\'da paylaşabilirsiniz.');
                                    });
                                }
                            </script>
                        <?php endif; ?>

                        <!-- Fiyat Onay Butonu -->
                        <?php if (isLoggedIn() && $station['current_price_id']): ?>
                            <form method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="action" value="confirm_price">
                                <input type="hidden" name="price_id" value="<?= $station['current_price_id'] ?>">
                                <button type="submit" class="btn btn-success w-full"
                                    style="background: #059669; border: none; font-weight: 600;">
                                    <i class="fas fa-thumbs-up"></i> Bu fiyattan aldım, Doğru!
                                </button>
                            </form>
                        <?php elseif (!isLoggedIn()): ?>
                            <button class="btn btn-success w-full auth-trigger"
                                style="margin-top: 15px; opacity: 0.8; background: #059669; border: none;">
                                <i class="fas fa-check"></i> Fiyatı Doğrulamak İçin Giriş Yap
                            </button>
                        <?php endif; ?>
                    </div>

                    <style>
                        .price-list {
                            display: flex;
                            flex-direction: column;
                            gap: 8px;
                            margin: 12px 0;
                            width: 100%;
                        }

                        .price-item {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
                            padding-bottom: 4px;
                        }

                        .price-item:last-child {
                            border-bottom: none;
                        }

                        .fuel-name {
                            opacity: 0.9;
                        }

                        .fuel-price {
                            font-weight: 700;
                            font-size: 1.25rem;
                        }

                        .fuel-price.cheap {
                            color: #86efac;
                        }

                        /* Light green on dark bg */
                    </style>

                    <div class="price-actions">
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $station['lat'] ?>,<?= $station['lng'] ?>"
                            target="_blank" class="btn btn-primary btn-lg">
                            <i class="fas fa-directions"></i>
                            Yol Tarifi Al
                        </a>
                        <?php if ($station['phone']): ?>
                            <a href="tel:<?= e($station['phone']) ?>" class="btn btn-outline btn-lg">
                                <i class="fas fa-phone"></i>
                                Ara
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Olanaklar -->
                <?php if (!empty($facilities)): ?>
                    <div class="facilities-section card">
                        <div class="card-header">
                            <h3><i class="fas fa-concierge-bell"></i> Olanaklar</h3>
                        </div>
                        <div class="card-body">
                            <div class="facilities-grid">
                                <?php foreach ($facilities as $key): ?>
                                    <?php if (isset(STATION_FACILITIES[$key])): ?>
                                        <div class="facility-item">
                                            <i class="fas fa-check-circle"></i>
                                            <?= STATION_FACILITIES[$key] ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Kampanyalar -->
                <?php if (!empty($campaigns)): ?>
                    <div class="campaigns-section card">
                        <div class="card-header">
                            <h3><i class="fas fa-tags"></i> Kampanyalar</h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($campaigns as $campaign): ?>
                                <div class="campaign-card">
                                    <div class="campaign-icon">
                                        <i class="fas fa-gift"></i>
                                    </div>
                                    <div class="campaign-info">
                                        <h4>
                                            <?= e($campaign['title']) ?>
                                        </h4>
                                        <p>
                                            <?= e($campaign['description']) ?>
                                        </p>
                                        <?php if ($campaign['end_date']): ?>
                                            <span class="campaign-date">
                                                <i class="fas fa-calendar"></i>
                                                <?= formatDate($campaign['end_date'], 'd.m.Y') ?> tarihine kadar
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Fiyat Geçmişi -->
                <?php if (!empty($priceHistory) && count($priceHistory) > 1): ?>
                    <div class="price-history-section card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Fiyat Geçmişi</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="priceChart" height="200"></canvas>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Yorumlar -->
                <div class="reviews-section card">
                    <div class="card-header">
                        <h3><i class="fas fa-comments"></i> Değerlendirmeler</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reviews)): ?>
                            <p class="text-gray text-center">Henüz değerlendirme yapılmamış.</p>
                        <?php else: ?>
                            <div class="reviews-list">
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-item">
                                        <div class="review-header">
                                            <div class="review-avatar">
                                                <?= strtoupper(substr($review['user_name'] ?? 'A', 0, 1)) ?>
                                            </div>
                                            <div class="review-meta">
                                                <div class="review-author">
                                                    <?= e($review['user_name'] ?? 'Anonim') ?>
                                                </div>
                                                <div class="review-date">
                                                    <?= timeAgo($review['created_at']) ?>
                                                </div>
                                            </div>
                                            <div class="review-rating">
                                                <?= renderStars($review['rating']) ?>
                                            </div>
                                        </div>
                                        <?php if ($review['comment']): ?>
                                            <p class="review-comment">
                                                <?= nl2br(e($review['comment'])) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sağ: Harita -->
            <div class="detail-sidebar">
                <div class="map-card card">
                    <div id="detailMap" style="height: 300px; border-radius: var(--radius-lg);"></div>
                </div>

                <!-- İletişim -->
                <div class="contact-card card">
                    <div class="card-body">
                        <h4>İletişim</h4>
                        <?php if ($station['phone']): ?>
                            <p><i class="fas fa-phone"></i>
                                <?= e($station['phone']) ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($station['email']): ?>
                            <p><i class="fas fa-envelope"></i>
                                <?= e($station['email']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('js/map.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>

    document.addEventListener('DOMContentLoaded', function () {
        // Mini harita
        const detailMap = L.map('detailMap').setView([<?= $station['lat'] ?>, <?= $station['lng'] ?>], 15);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
        }).addTo(detailMap);
        L.marker([<?= $station['lat'] ?>, <?= $station['lng'] ?>]).addTo(detailMap)
            .bindPopup('<?= e($station['name']) ?>').openPopup();

        // GÜVENLİK: Giriş yapmamışsa modalı otomatik aç
        const isLoggedIn = <?= isLoggedIn() ? 'true' : 'false' ?>;
        if (!isLoggedIn) {
            setTimeout(function () {
                if (typeof openAuthModal === 'function') {
                    openAuthModal();
                }
            }, 800); // Sayfa yüklendikten kısa bir süre sonra aç
        }

        <?php if (!empty($priceHistory) && count($priceHistory) > 1): ?>
            // Fiyat grafiği
            const ctx = document.getElementById('priceChart').getContext('2d');
            const priceData = <?= json_encode(array_reverse($priceHistory)) ?>;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: priceData.map(p => new Date(p.created_at).toLocaleDateString('tr-TR')),
                    datasets: [
                        {
                            label: 'Mazot (₺)',
                            data: priceData.map(p => p.diesel_price),
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Benzin (₺)',
                            data: priceData.map(p => p.gasoline_price),
                            borderColor: '#dc2626',
                            backgroundColor: 'rgba(220, 38, 38, 0.1)',
                            fill: false,
                            tension: 0.4
                        },
                        {
                            label: 'LPG (₺)',
                            data: priceData.map(p => p.lpg_price),
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22, 163, 74, 0.1)',
                            fill: false,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { usePointStyle: true }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grace: '10%' // %10'luk esneklik payı ekleyerek küçük oynamaların devasa görünmesini engeller
                        }
                    }
                }
            });
        <?php endif; ?>
    });
</script>

<style>
    .station-detail-page {
        padding: var(--space-6) 0;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: var(--space-6);
    }

    .detail-main {
        display: flex;
        flex-direction: column;
        gap: var(--space-5);
    }

    .station-header {
        display: flex;
        gap: var(--space-5);
        padding: var(--space-6);
    }

    .station-brand-logo {
        width: 80px;
        height: 80px;
        background: var(--gray-100);
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: var(--gray-400);
    }

    .station-header-info h1 {
        font-size: 1.75rem;
        margin-bottom: var(--space-2);
    }

    .station-address {
        color: var(--gray-500);
        margin-bottom: var(--space-2);
    }

    .station-rating-large {
        display: flex;
        align-items: center;
        gap: var(--space-2);
    }

    .rating-number {
        font-weight: 600;
        font-size: 1.125rem;
    }

    .review-count {
        color: var(--gray-500);
        font-size: 0.875rem;
    }

    .price-card {
        padding: var(--space-6);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--space-4);
    }

    .price-label {
        display: block;
        font-size: 0.875rem;
        color: var(--gray-500);
        margin-bottom: var(--space-2);
    }

    .price-value {
        font-size: 3rem;
        font-weight: 800;
        color: var(--primary);
    }

    .price-value.cheap {
        color: var(--success);
    }

    .price-value.expensive {
        color: var(--danger);
    }

    .price-updated {
        font-size: 0.8125rem;
        color: var(--gray-400);
    }

    .price-actions {
        display: flex;
        gap: var(--space-3);
    }

    .facilities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: var(--space-3);
    }

    .facility-item {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        font-size: 0.9375rem;
    }

    .facility-item i {
        color: var(--success);
    }

    .campaign-card {
        display: flex;
        gap: var(--space-4);
        padding: var(--space-4);
        background: var(--gray-50);
        border-radius: var(--radius);
        margin-bottom: var(--space-3);
    }

    .campaign-icon {
        width: 48px;
        height: 48px;
        background: var(--secondary);
        color: white;
        border-radius: var(--radius);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .campaign-info h4 {
        margin-bottom: var(--space-1);
    }

    .campaign-info p {
        font-size: 0.875rem;
        color: var(--gray-600);
        margin: 0;
    }

    .campaign-date {
        font-size: 0.75rem;
        color: var(--gray-400);
    }

    .review-item {
        padding: var(--space-4) 0;
        border-bottom: 1px solid var(--gray-100);
    }

    .review-item:last-child {
        border-bottom: none;
    }

    .review-header {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        margin-bottom: var(--space-3);
    }

    .review-avatar {
        width: 40px;
        height: 40px;
        background: var(--primary);
        color: white;
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }

    .review-meta {
        flex: 1;
    }

    .review-author {
        font-weight: 500;
    }

    .review-date {
        font-size: 0.75rem;
        color: var(--gray-400);
    }

    .review-comment {
        color: var(--gray-600);
        font-size: 0.9375rem;
        margin: 0;
    }

    .detail-sidebar {
        display: flex;
        flex-direction: column;
        gap: var(--space-4);
    }

    .contact-card p {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        margin-bottom: var(--space-2);
        color: var(--gray-600);
    }

    .contact-card i {
        color: var(--gray-400);
        width: 20px;
    }

    @media (max-width: 768px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }

        .station-header {
            flex-direction: column;
            text-align: center;
        }

        .price-card {
            flex-direction: column;
            text-align: center;
        }

        .price-actions {
            width: 100%;
        }

        .price-actions .btn {
            flex: 1;
        }
    }
</style>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>