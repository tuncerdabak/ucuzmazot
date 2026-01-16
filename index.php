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
    </div>

    <!-- Floating Sidebar -->
    <aside class="stations-panel" id="stationsPanel">
        <div class="panel-header">
            <!-- Search -->
            <div class="search-wrapper">
                <div class="search-inner">
                    <div class="search-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="searchInput" placeholder="İstasyon ara..." class="search-input">
                    <select id="cityFilter" class="city-select"
                        style="border:none; background:none; font-size:0.8rem; color:var(--gray-500);">
                        <option value="">Tüm Şehirler</option>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?= e($c['city']) ?>" <?= $city === $c['city'] ? 'selected' : '' ?>>
                                <?= e($c['city']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="panel-title">
                <h2>Yakındaki İstasyonlar</h2>
                <span class="station-count"><?= count($stations) ?> istasyon</span>
            </div>

            <!-- Filtreler -->
            <div class="filter-wrapper">
                <button class="filter-btn-premium <?= $sort === 'price' ? 'active' : '' ?>" data-sort="price">
                    <i class="fas fa-savings"></i>
                    En Ucuz
                </button>
                <button class="filter-btn-premium <?= $sort === 'distance' ? 'active' : '' ?>" data-sort="distance">
                    <i class="fas fa-near-me"></i>
                    En Yakın
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
                    <?php
                    $isCheap = (isLoggedIn() && $station['diesel_price'] < DIESEL_MIN_PRICE + 2);
                    $logo = getBrandLogo($station['brand']);
                    ?>
                    <div class="station-card" data-id="<?= $station['id'] ?>" data-lat="<?= $station['lat'] ?>"
                        data-lng="<?= $station['lng'] ?>">
                        <div class="brand-box">
                            <?php if ($logo): ?>
                                <img src="<?= $logo ?>" alt="<?= e($station['brand']) ?>"
                                    onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiNjY2MiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cGF0aCBkPSJNOSAxMiBMOSAxMiIgLz48Y2lyY2xlIGN4PSIxMiIgY3k9IjEyIiByPSIxMCIgLz48L3N2Zz4=';">
                            <?php else: ?>
                                <i class="fas fa-gas-pump" style="color:var(--gray-300);"></i>
                            <?php endif; ?>
                        </div>

                        <div class="info-box">
                            <div class="station-name-premium"><?= e($station['name']) ?></div>
                            <div class="meta-row">
                                <div class="distance-badge">
                                    <i class="fas fa-location-arrow"></i>
                                    <span class="distance-val" data-lat="<?= $station['lat'] ?>"
                                        data-lng="<?= $station['lng'] ?>">---</span>
                                </div>
                                <?php if ($isCheap): ?>
                                    <span class="status-badge status-best">En İyi Fiyat</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="price-box-premium">
                            <?php if ($station['diesel_price']): ?>
                                <div class="main-price">
                                    <?php if (isLoggedIn()): ?>
                                        <span class="digital-price"><?= number_format($station['diesel_price'], 2, ',', '.') ?></span>
                                        <span class="currency-small">₺</span>
                                    <?php else: ?>
                                        <?php $dObj = formatObfuscatedPrice($station['diesel_price']); ?>
                                        <span><?= $dObj['visible'] ?></span><span class="blurred"><?= $dObj['blurred'] ?></span>
                                        <span class="currency-small">₺</span>
                                    <?php endif; ?>
                                </div>
                                <div class="price-date" style="font-size:0.6rem; color:var(--gray-400);">
                                    <?= timeAgo($station['price_updated_at']) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-gray">-</span>
                            <?php endif; ?>
                        </div>

                        <a href="/istasyon-detay.php?id=<?= $station['id'] ?>" class="nav-btn-circle" title="Detaylar">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="panel-footer">
            <button class="btn-premium-full" onclick="getUserLocation()">
                <i class="fas fa-explore"></i>
                En Ucuz Mazotu Bul
            </button>
        </div>
    </aside>

    <!-- Panel Toggle (Mobile) -->
    <button class="panel-toggle" id="panelToggle">
        <i class="fas fa-chevron-up"></i>
        <span><?= count($stations) ?> İstasyon</span>
    </button>

    <!-- App Download Banner (Mobile Only) -->
    <div class="app-download-banner show-on-mobile hide-in-app"
        style="position:fixed; top:80px; left:10px; right:10px; z-index:9999; background:var(--white); padding:10px; border-radius:12px; box-shadow:var(--shadow-lg); display:flex; align-items:center; gap:12px; border:1px solid var(--gray-100);">
        <i class="fas fa-mobile-alt" style="font-size:1.5rem; color:var(--primary);"></i>
        <div style="flex:1;">
            <div style="font-weight:700; font-size:0.875rem;">Hemen Başla!</div>
            <div style="font-size:0.75rem; color:var(--gray-500);">Fiyatlar cebine gelsin.</div>
        </div>
        <a href="/indir.php" class="btn btn-sm btn-primary" style="font-size:0.7rem; padding:6px 12px;">İndir</a>
    </div>
</div>

<!-- İstasyon verileri (JS için) -->
<script>
    <?php
    $allDieselPrices = array_filter(array_column($stations, 'diesel_price'));
    $avgDiesel = !empty($allDieselPrices) ? array_sum($allDieselPrices) / count($allDieselPrices) : 0;

    $jsStations = array_map(function ($s) use ($avgDiesel) {
        $loggedIn = isLoggedIn();
        $priceLevel = 'average';
        if ($s['diesel_price'] > 0 && $avgDiesel > 0) {
            $diffRatio = ($s['diesel_price'] - $avgDiesel) / $avgDiesel;
            if ($diffRatio <= -0.01)
                $priceLevel = 'cheap';
            elseif ($diffRatio >= 0.01)
                $priceLevel = 'expensive';
        }

        $dObj = formatObfuscatedPrice($s['diesel_price']);

        return [
            'id' => $s['id'],
            'name' => $s['name'],
            'city' => $s['city'],
            'district' => $s['district'],
            'lat' => (float) $s['lat'],
            'lng' => (float) $s['lng'],
            'diesel_price' => $loggedIn ? ($s['diesel_price'] ? (float) $s['diesel_price'] : null) : null,
            'diesel_teaser' => $s['diesel_price'] ? $dObj['visible'] : '-',
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
            const card = document.querySelector(`.station-card[data-id="${station.id}"]`);
            if (card) {
                document.querySelectorAll('.station-card').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        // İstasyon Kartlarına Tıklama
        const stationsList = document.getElementById('stationsList');
        if (stationsList) {
            stationsList.addEventListener('click', function (e) {
                const card = e.target.closest('.station-card');
                const navBtn = e.target.closest('.nav-btn-circle');

                if (!card) return;

                // Eğer navigasyon butonuna tıklandıysa detaya git (HTML handled) ama click event'i durdurma
                if (navBtn) return;

                // GÜVENLİK: Giriş yapmamışsa modal aç
                if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
                    e.preventDefault();
                    if (typeof openAuthModal === 'function') openAuthModal();
                    return;
                }

                const lat = parseFloat(card.dataset.lat);
                const lng = parseFloat(card.dataset.lng);
                map.setView([lat, lng], 15);

                document.querySelectorAll('.station-card').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
            });
        }

        // Şehir filtresi
        document.getElementById('cityFilter').addEventListener('change', function () {
            window.location.href = '/?city=' + encodeURIComponent(this.value);
        });

        // Sıralama butonları
        document.querySelectorAll('.filter-btn-premium').forEach(btn => {
            btn.addEventListener('click', function () {
                const url = new URL(window.location);
                url.searchParams.set('sort', this.dataset.sort);
                window.location.href = url.toString();
            });
        });

        // Panel toggle (mobile)
        const panelToggle = document.getElementById('panelToggle');
        const stationsPanel = document.getElementById('stationsPanel');

        if (panelToggle) {
            panelToggle.addEventListener('click', function () {
                stationsPanel.classList.toggle('expanded');
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-chevron-up');
                icon.classList.toggle('fa-chevron-down');
            });
        }

        // Arama
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', debounce(function () {
                const query = this.value.toLowerCase();
                document.querySelectorAll('.station-card').forEach(card => {
                    const name = card.querySelector('.station-name-premium').textContent.toLowerCase();
                    if (name.includes(query)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }, 300));
        }
    });
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>