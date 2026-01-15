<?php
/**
 * UcuzMazot.com - Ana Sayfa
 * Harita üzerinde istasyonları ve fiyatları görüntüler
 */

require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

// İstasyonları al
$city = $_GET['city'] ?? null;
$sort = $_GET['sort'] ?? 'price'; // price | distance | rating

$sql = "SELECT s.*, fp.diesel_price, fp.truck_diesel_price, fp.gasoline_price, fp.lpg_price, fp.created_at as price_updated_at,
        (SELECT AVG(rating) FROM reviews WHERE station_id = s.id AND is_visible = 1) as avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE station_id = s.id AND is_visible = 1) as review_count
        FROM stations s
        LEFT JOIN (
            SELECT station_id, diesel_price, truck_diesel_price, gasoline_price, lpg_price, created_at
            FROM fuel_prices fp1
            WHERE created_at = (SELECT MAX(created_at) FROM fuel_prices fp2 WHERE fp2.station_id = fp1.station_id)
        ) fp ON s.id = fp.station_id
        WHERE s.is_active = 1 AND s.is_approved = 1";

$params = [];

if ($city) {
    $sql .= " AND s.city = ?";
    $params[] = $city;
}

if ($sort === 'price') {
    $sql .= " ORDER BY (fp.diesel_price IS NULL), fp.diesel_price ASC";
} elseif ($sort === 'rating') {
    $sql .= " ORDER BY (avg_rating IS NULL), avg_rating DESC";
} else {
    $sql .= " ORDER BY s.created_at DESC";
}

$sql .= " LIMIT 100";

$stations = db()->fetchAll($sql, $params);

// Şehirler listesi
$cities = db()->fetchAll("SELECT DISTINCT city FROM stations WHERE is_active = 1 ORDER BY city");

$pageTitle = SITE_TITLE;
$extraCss = 'css/home.css';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="home-page">
    <!-- Harita Bölümü -->
    <div class="map-section">
        <div id="map" class="map-container"></div>

        <!-- Üst Arama Çubuğu -->
        <div class="map-search-bar">
            <div class="search-inner">
                <div class="search-icon">
                    <i class="fas fa-search"></i>
                </div>
                <input type="text" id="searchInput" placeholder="İstasyon veya şehir ara..." class="search-input">
                <select id="cityFilter" class="city-select">
                    <option value="">Tüm Şehirler</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= e($c['city']) ?>" <?= $city === $c['city'] ? 'selected' : '' ?>>
                            <?= e($c['city']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- İstasyon Listesi Paneli -->
    <div class="stations-panel" id="stationsPanel">
        <div class="panel-header">
            <div class="panel-title">
                <h2>Yakındaki İstasyonlar</h2>
                <span class="station-count">
                    <?= count($stations) ?> istasyon
                </span>
            </div>

            <div class="sort-buttons">
                <button class="sort-btn <?= $sort === 'price' ? 'active' : '' ?>" data-sort="price">
                    En Ucuz TL
                </button>
                <button class="sort-btn <?= $sort === 'distance' ? 'active' : '' ?>" data-sort="distance">
                    <i class="fas fa-map-marker-alt"></i>
                    En Yakın
                </button>
                <button class="sort-btn <?= $sort === 'rating' ? 'active' : '' ?>" data-sort="rating">
                    <i class="fas fa-star"></i>
                    Puan
                </button>
            </div>
        </div>

        <div class="stations-list" id="stationsList">
            <?php if (empty($stations)): ?>
                <div class="empty-state">
                    <i class="fas fa-gas-pump"></i>
                    <p>Henüz istasyon bulunamadı.</p>
                </div>
            <?php else: ?>
                <?php foreach ($stations as $index => $station): ?>
                    <div class="station-card" data-id="<?= $station['id'] ?>" data-lat="<?= $station['lat'] ?>"
                        data-lng="<?= $station['lng'] ?>">
                        <div class="station-rank">
                            <?= $index + 1 ?>
                        </div>
                        <div class="station-brand">
                            <?php
                            $logo = getBrandLogo($station['brand']);
                            if ($logo):
                                ?>
                                <img src="<?= $logo ?>" alt="<?= e($station['brand']) ?>"
                                    style="max-width: 100%; max-height: 100%; object-fit: contain;"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <i class="fas fa-gas-pump" style="display: none;"></i>
                            <?php else: ?>
                                <i class="fas fa-gas-pump"></i>
                            <?php endif; ?>
                        </div>
                        <div class="station-info">
                            <div class="station-name">
                                <?= e($station['name']) ?>
                            </div>
                            <div class="station-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= e($station['city']) ?>
                                <?php if ($station['district']): ?>
                                    /
                                    <?= e($station['district']) ?>
                                <?php endif; ?>
                            </div>
                            <?php if ($station['avg_rating']): ?>
                                <div class="station-rating">
                                    <?= renderStars($station['avg_rating']) ?>
                                    <span>(
                                        <?= $station['review_count'] ?>)
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="station-price">
                            <?php if ($station['diesel_price']): ?>
                                <?php if (isLoggedIn()): ?>
                                    <div class="price-row">
                                        <span class="fuel-label">Mazot</span>
                                        <div class="price-tag <?= $station['diesel_price'] < DIESEL_MIN_PRICE + 2 ? 'cheap' : '' ?>">
                                            <span
                                                class="digital-price"><?= number_format($station['diesel_price'], 2, ',', '.') ?></span>
                                            <span class="currency">TL</span>
                                        </div>
                                    </div>
                                    <?php if ($station['truck_diesel_price']): ?>
                                        <div class="price-row truck-price"
                                            style="background: #fef2f2; border: 1px solid #fee2e2; border-radius: 4px; padding: 2px 4px;">
                                            <span class="fuel-label" style="color: #b91c1c; font-weight: 600;"><i class="fas fa-truck"></i>
                                                TIR</span>
                                            <div class="price-tag" style="color: #b91c1c;">
                                                <span class="digital-price"
                                                    style="color: inherit;"><?= number_format($station['truck_diesel_price'], 2, ',', '.') ?></span>
                                                <span class="currency" style="color: inherit;">TL</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($station['gasoline_price']): ?>
                                        <div class="price-row">
                                            <span class="fuel-label">Benzin</span>
                                            <div class="price-tag">
                                                <span
                                                    class="digital-price"><?= number_format($station['gasoline_price'], 2, ',', '.') ?></span>
                                                <span class="currency">TL</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($station['lpg_price']): ?>
                                        <div class="price-row">
                                            <span class="fuel-label">LPG</span>
                                            <div class="price-tag">
                                                <span class="digital-price"><?= number_format($station['lpg_price'], 2, ',', '.') ?></span>
                                                <span class="currency">TL</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="station-meta">
                                        <div class="station-distance">
                                            <i class="fas fa-location-arrow"></i>
                                            <span class="distance-val" data-lat="<?= $station['lat'] ?>"
                                                data-lng="<?= $station['lng'] ?>">Hesa...</span>
                                        </div>
                                        <div class="station-updated">
                                            <i class="fas fa-clock"></i>
                                            <?= timeAgo($station['price_updated_at']) ?>
                                            <?php
                                            $isOld = (time() - strtotime($station['price_updated_at'])) > (24 * 3600);
                                            if ($isOld):
                                                ?>
                                                <span class="old-price-warning"
                                                    style="color: #ef4444; font-weight: 600; font-size: 0.75rem;"><i
                                                        class="fas fa-exclamation-triangle"></i> GÜNCEL OLMAYABİLİR</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="price-obfuscated auth-trigger" title="Fiyatları görmek için tıklayın">
                                        <?php
                                        // Kismi gizleme
                                        $dObj = formatObfuscatedPrice($station['diesel_price']);
                                        $gObj = formatObfuscatedPrice($station['gasoline_price']);
                                        $lObj = formatObfuscatedPrice($station['lpg_price']); // Varsa
                                        ?>

                                        <div class="price-row">
                                            <span class="fuel-label">Mazot</span>
                                            <div class="price-tag">
                                                <span><?= $dObj['visible'] ?></span><span
                                                    class="filter-blur"><?= $dObj['blurred'] ?></span>
                                            </div>
                                        </div>
                                        <?php if ($station['gasoline_price']): ?>
                                            <div class="price-row">
                                                <span class="fuel-label">Benzin</span>
                                                <div class="price-tag">
                                                    <span><?= $gObj['visible'] ?></span><span
                                                        class="filter-blur"><?= $gObj['blurred'] ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="obfuscated-overlay">
                                            <i class="fas fa-lock"></i>
                                            <span>Fiyatı Gör</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="price-tag text-gray">-</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Panel Toggle (Mobile) -->
    <button class="panel-toggle" id="panelToggle">
        <i class="fas fa-chevron-up"></i>
        <span>
            <?= count($stations) ?> İstasyon
        </span>
    </button>
</div>

<!-- İstasyon verileri (JS için) -->
<script>
    <?php
    // GÜVENLİK: Giriş yapmamış/Şoför olmayanlar için fiyat gizle
    // Fiyat Seviyesi Hesaplama (Renkler için)
    $allDieselPrices = array_filter(array_column($stations, 'diesel_price'));
    $avgDiesel = !empty($allDieselPrices) ? array_sum($allDieselPrices) / count($allDieselPrices) : 0;

    $jsStations = array_map(function ($s) use ($avgDiesel) {
        $loggedIn = isLoggedIn();

        // Fiyat Seviyesi (Harita Renkleri)
        $priceLevel = 'average'; // Varsayılan: Sarı
        if ($s['diesel_price'] > 0 && $avgDiesel > 0) {
            $diffRatio = ($s['diesel_price'] - $avgDiesel) / $avgDiesel;
            if ($diffRatio <= -0.01) { // %1 ve daha ucuzsa Yeşil
                $priceLevel = 'cheap';
            } elseif ($diffRatio >= 0.01) { // %1 ve daha pahalıysa Kırmızı
                $priceLevel = 'expensive';
            }
        }

        // Fiyat verilerini hazırla (Gizli/Açık)
        $dObj = formatObfuscatedPrice($s['diesel_price']);
        $gObj = formatObfuscatedPrice($s['gasoline_price']);

        return [
            'id' => $s['id'],
            'name' => $s['name'],
            'city' => $s['city'],
            'district' => $s['district'],
            'lat' => (float) $s['lat'],
            'lng' => (float) $s['lng'],
            'diesel_price' => $loggedIn ? ($s['diesel_price'] ? (float) $s['diesel_price'] : null) : null,
            'truck_diesel_price' => $loggedIn ? ($s['truck_diesel_price'] ? (float) $s['truck_diesel_price'] : null) : null,
            'gasoline_price' => $loggedIn ? ($s['gasoline_price'] ? (float) $s['gasoline_price'] : null) : null,
            'lpg_price' => $loggedIn ? ($s['lpg_price'] ? (float) $s['lpg_price'] : null) : null,
            'diesel_teaser' => $s['diesel_price'] ? $dObj['visible'] : '-',
            'truck_diesel_teaser' => $s['truck_diesel_price'] ? formatObfuscatedPrice($s['truck_diesel_price'])['visible'] : '-',
            'gasoline_teaser' => $s['gasoline_price'] ? $gObj['visible'] : '-',
            'lpg_teaser' => $s['lpg_price'] ? formatObfuscatedPrice($s['lpg_price'])['visible'] : '-',
            'avg_rating' => $s['avg_rating'] ? (float) $s['avg_rating'] : null,
            'price_level' => $priceLevel,
            'locked' => !$loggedIn
        ];
    }, $stations);
    ?>
    const stationsData = <?= json_encode($jsStations, JSON_UNESCAPED_UNICODE) ?>;
    const isLoggedIn = <?= isLoggedIn() ? 'true' : 'false' ?>;

</script>

<script src="<?= asset('js/map.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Haritayı başlat
        initMap('map');

        // Otomatik konum algıla
        getUserLocation();

        // İstasyonları haritaya ekle
        addStationsToMap(stationsData, function (station) {
            // İstasyon seçildiğinde
            const card = document.querySelector(`.station-card[data-id="${station.id}"]`);
            if (card) {
                document.querySelectorAll('.station-card').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        // İstasyon Kartlarına Tıklama (Event Delegation)
        const stationsList = document.getElementById('stationsList');
        if (stationsList) {
            stationsList.addEventListener('click', function (e) {
                const card = e.target.closest('.station-card');
                if (!card) return;

                // GÜVENLİK: Giriş yapmamışsa modal aç
                if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Auth check failed. Opening modal.');
                    if (typeof openAuthModal === 'function') {
                        openAuthModal();
                    } else {
                        console.error('openAuthModal function not found!');
                        // Fallback: Manually show modal
                        const m = document.getElementById('authModal');
                        if (m) m.classList.add('active');
                    }
                    return;
                }

                const lat = parseFloat(card.dataset.lat);
                const lng = parseFloat(card.dataset.lng);

                map.setView([lat, lng], 15);

                document.querySelectorAll('.station-card').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
            });

            // Çift Tıklama (Event Delegation)
            stationsList.addEventListener('dblclick', function (e) {
                const card = e.target.closest('.station-card');
                if (!card) return;

                if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                window.location.href = '/istasyon-detay.php?id=' + card.dataset.id;
            });
        }

        // Şehir filtresi
        document.getElementById('cityFilter').addEventListener('change', function () {
            const city = this.value;
            window.location.href = '/?city=' + encodeURIComponent(city);
        });

        // Sıralama butonları
        document.querySelectorAll('.sort-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const sort = this.dataset.sort;
                const url = new URL(window.location);
                url.searchParams.set('sort', sort);
                window.location.href = url.toString();
            });
        });

        // Panel toggle (mobile)
        const panelToggle = document.getElementById('panelToggle');
        const stationsPanel = document.getElementById('stationsPanel');

        panelToggle.addEventListener('click', function () {
            stationsPanel.classList.toggle('expanded');
            this.querySelector('i').classList.toggle('fa-chevron-up');
            this.querySelector('i').classList.toggle('fa-chevron-down');
        });

        // Arama
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', debounce(function () {
            const query = this.value.toLowerCase();

            document.querySelectorAll('.station-card').forEach(card => {
                const name = card.querySelector('.station-name').textContent.toLowerCase();
                const location = card.querySelector('.station-location').textContent.toLowerCase();

                if (name.includes(query) || location.includes(query)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }, 300));
    });
</script>

<style>
    /* Ana Sayfa Stilleri */
    .home-page {
        display: flex;
        height: calc(100vh - 64px);
        position: relative;
    }

    .map-section {
        flex: 1;
        position: relative;
    }

    .map-container {
        width: 100%;
        height: 100%;
    }

    /* Arama Çubuğu */
    .map-search-bar {
        position: absolute;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        width: 90%;
        max-width: 600px;
    }

    .search-inner {
        display: flex;
        align-items: center;
        background: white;
        border-radius: var(--radius-full);
        box-shadow: var(--shadow-lg);
        padding: var(--space-2);
        gap: var(--space-2);
    }

    .search-icon {
        padding: 0 var(--space-3);
        color: var(--gray-400);
    }

    .search-input {
        flex: 1;
        border: none;
        outline: none;
        font-size: 1rem;
        padding: var(--space-2);
    }

    .city-select {
        border: none;
        background: var(--gray-100);
        padding: var(--space-2) var(--space-4);
        border-radius: var(--radius-full);
        font-size: 0.875rem;
        cursor: pointer;
    }

    /* İstasyon Listesi Paneli */
    .stations-panel {
        width: 400px;
        background: var(--white);
        box-shadow: var(--shadow-lg);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .panel-header {
        padding: var(--space-4);
        border-bottom: 1px solid var(--gray-200);
    }

    .panel-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--space-3);
    }

    .panel-title h2 {
        font-size: 1.125rem;
        margin: 0;
    }

    .station-count {
        font-size: 0.875rem;
        color: var(--gray-500);
    }

    .sort-buttons {
        display: flex;
        gap: var(--space-2);
    }

    .sort-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-1);
        padding: var(--space-2);
        background: var(--gray-100);
        border: none;
        border-radius: var(--radius);
        font-size: 0.8125rem;
        color: var(--gray-600);
        cursor: pointer;
        transition: all var(--transition);
    }

    .sort-btn:hover,
    .sort-btn.active {
        background: var(--primary);
        color: white;
    }

    .stations-list {
        flex: 1;
        overflow-y: auto;
        padding: var(--space-2);
    }

    /* İstasyon Kartı */
    .station-card {
        display: flex;
        align-items: flex-start;
        gap: var(--space-3);
        padding: 15px;
        background: var(--white);
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 16px;
        margin-bottom: var(--space-3);
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .station-card:hover {
        border-color: var(--primary-light);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .station-card.active {
        border-color: var(--primary);
        background: rgba(37, 99, 235, 0.03);
        box-shadow: 0 10px 30px rgba(37, 99, 235, 0.1);
    }

    .station-rank {
        width: 24px;
        height: 24px;
        background: var(--gray-100);
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--gray-500);
    }

    .station-card:nth-child(1) .station-rank {
        background: #ffd700;
        color: #000;
    }

    .station-card:nth-child(2) .station-rank {
        background: #c0c0c0;
        color: #000;
    }

    .station-card:nth-child(3) .station-rank {
        background: #cd7f32;
        color: #fff;
    }

    .station-brand {
        width: 48px;
        height: 48px;
        background: var(--gray-100);
        border-radius: var(--radius);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: var(--gray-400);
    }

    .station-info {
        flex: 1;
        min-width: 0;
    }

    .station-name {
        font-weight: 600;
        font-size: 0.9375rem;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .station-location {
        font-size: 0.8125rem;
        color: var(--gray-500);
    }

    .station-rating {
        display: flex;
        align-items: center;
        gap: var(--space-1);
        font-size: 0.75rem;
        margin-top: 2px;
    }

    .station-price {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 6px;
        flex-shrink: 0;
    }

    .price-row {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
    }

    .price-row.truck-price {
        transform: scale(0.95);
        transform-origin: right;
    }

    .fuel-label {
        font-size: 0.75rem;
        color: var(--gray-500);
    }

    .station-price .price-tag {
        font-size: 1.1rem;
        font-weight: 800;
        padding: 4px 10px;
        background: var(--gray-50);
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 10px;
        color: var(--gray-800);
        min-width: 85px;
        text-align: right;
    }

    .station-price .price-tag.cheap {
        background: white;
        color: var(--primary);
        border-color: var(--primary-light);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
    }

    .price-date {
        font-size: 0.6875rem;
        color: var(--gray-400);
        margin-top: 2px;
    }

    /* Empty State */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: var(--space-8);
        color: var(--gray-400);
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: var(--space-4);
    }

    /* Panel Toggle (Mobile) */
    .panel-toggle {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: var(--white);
        border: none;
        padding: var(--space-4);
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
        border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        z-index: 1001;
        cursor: pointer;
    }

    .panel-toggle span {
        margin-left: var(--space-2);
        font-weight: 500;
    }

    /* Mobile */
    @media (max-width: 768px) {
        .home-page {
            flex-direction: column;
        }

        .map-section {
            height: 50%;
            flex: 0 0 50%;
        }

        .stations-panel {
            position: relative;
            width: 100%;
            height: 50%;
            transform: none;
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
            z-index: 10;
            bottom: auto;
            left: auto;
            right: auto;
        }

        /* Liste başlığı her zaman görünsün */
        .panel-header {
            position: sticky;
            top: 0;
            background: white;
            z-index: 20;
            border-bottom: 1px solid var(--gray-200);
        }

        .stations-panel.expanded {
            transform: none;
        }

        .panel-toggle {
            display: none;
        }

        .map-search-bar {
            width: 90%;
            top: 10px;
        }

        .city-select {
            display: none;
        }
    }
</style>


<?php require_once INCLUDES_PATH . '/footer.php'; ?>