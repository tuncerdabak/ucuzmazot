<?php
/**
 * UcuzMazot.com - Fiyatlar Listesi
 */

require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

// Filtreler
$city = $_GET['city'] ?? null;
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'price_asc';
$lat = $_GET['lat'] ?? null;
$lng = $_GET['lng'] ?? null;

// Sıralama
$orderClause = "fp.diesel_price ASC";
switch ($sort) {
    case 'price_desc':
        $orderClause = "fp.diesel_price DESC";
        break;
    case 'date_desc':
        $orderClause = "fp.created_at DESC";
        break;
    case 'rating_desc':
        $orderClause = "avg_rating DESC";
        break;
    case 'near_me':
        $orderClause = "fp.diesel_price ASC"; // En ucuz olan, mesafe filtresi WHERE içinde olacak
        break;
}

// Mesafe hesaplama formülü (Haversine ya da Basitleştirilmiş)
$distanceSql = "NULL as distance";
if ($lat && $lng) {
    // 6371 * acos(...) formülü MySQL için ağır olabilir ama 50 istasyon için sorun değil.
    // Ancak basitleştirilmiş Pisagor formülü de bu ölçekte (50km) yeterlidir.
    $distanceSql = "(6371 * acos(cos(radians(?)) * cos(radians(s.lat)) * cos(radians(s.lng) - radians(?)) + sin(radians(?)) * sin(radians(s.lat)))) as distance";
}

// SQL Sorgusu
$sql = "SELECT s.*, fp.diesel_price, fp.gasoline_price, fp.lpg_price, fp.created_at as price_updated_at,
        $distanceSql,
        (SELECT AVG(rating) FROM reviews WHERE station_id = s.id AND is_visible = 1) as avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE station_id = s.id AND is_visible = 1) as review_count
        FROM stations s
        LEFT JOIN (
            SELECT station_id, diesel_price, gasoline_price, lpg_price, created_at
            FROM fuel_prices fp1
            WHERE created_at = (SELECT MAX(created_at) FROM fuel_prices fp2 WHERE fp2.station_id = fp1.station_id)
        ) fp ON s.id = fp.station_id
        WHERE s.is_active = 1 AND s.is_approved = 1";

$params = [];
if ($lat && $lng) {
    $params[] = $lat;
    $params[] = $lng;
    $params[] = $lat;
}

if ($city) {
    $sql .= " AND s.city = ?";
    $params[] = $city;
}

if ($search) {
    $sql .= " AND (s.name LIKE ? OR s.district LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($sort === 'near_me' && $lat && $lng) {
    $sql .= " HAVING distance <= ?";
    $params[] = SEARCH_RADIUS_KM;
}

// Fiyatı olmayanları sona at
$sql .= " ORDER BY (fp.diesel_price IS NULL), $orderClause LIMIT 50";

$stations = db()->fetchAll($sql, $params);

// Şehirler
$cities = db()->fetchAll("SELECT DISTINCT city FROM stations WHERE is_active = 1 ORDER BY city");

$pageTitle = 'Güncel Akaryakıt Fiyatları - ' . SITE_NAME;
require_once INCLUDES_PATH . '/header.php';
?>


<div class="container py-4">
    <!-- Premium Page Header -->
    <div class="page-header-premium animate__animated animate__fadeIn">
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1>En Ucuz Mazotu Bul</h1>
                <p class="subtitle">Türkiye genelindeki akaryakıt istasyonlarını karşılaştırın, tasarruf edin.</p>
            </div>
            <div class="update-badge">
                <i class="material-symbols-outlined">schedule</i>
                Son Güncelleme: <?= date('d.m.Y') ?>
            </div>
        </div>
    </div>

    <!-- Premium Filter Card -->
    <div class="filter-card-premium animate__animated animate__fadeIn">
        <form action="" method="GET">
            <!-- Mobile Toggle -->
            <button type="button" class="filter-toggle-btn d-md-none w-100" id="filterToggleBtn">
                <span class="d-flex align-items-center gap-2">
                    <i class="material-symbols-outlined">filter_alt</i>
                    Filtrele ve Sırala
                </span>
                <i class="material-symbols-outlined arrow">expand_more</i>
            </button>

            <!-- Filter Content -->
            <div class="filter-grid filter-grid-collapse d-md-grid" id="filterContent">
                <div class="filter-group">
                    <label>Şehir</label>
                    <div class="filter-input-wrapper">
                        <i class="material-symbols-outlined filter-icon">location_city</i>
                        <select name="city" onchange="this.form.submit()">
                            <option value="">Tüm Türkiye</option>
                            <?php foreach ($cities as $c): ?>
                                <option value="<?= e($c['city']) ?>" <?= $city === $c['city'] ? 'selected' : '' ?>>
                                    <?= e($c['city']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="filter-group">
                    <label>İstasyon Ara</label>
                    <div class="filter-input-wrapper">
                        <i class="material-symbols-outlined filter-icon">search</i>
                        <input type="text" name="q" placeholder="İsim veya ilçe..." value="<?= e($search) ?>">
                    </div>
                </div>

                <div class="filter-group">
                    <label>Sıralama</label>
                    <div class="filter-input-wrapper">
                        <i class="material-symbols-outlined filter-icon">sort</i>
                        <select name="sort" onchange="this.form.submit()">
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>En Ucuz Mazot</option>
                            <option value="near_me" <?= $sort === 'near_me' ? 'selected' : '' ?>>Yanımdaki En Ucuzlar
                            </option>
                            <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>En Yeni Güncel
                            </option>
                            <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Müşteri Puanı
                            </option>
                        </select>
                    </div>
                </div>

                <div class="filter-group">
                    <label class="d-none d-md-block">&nbsp;</label>
                    <button type="submit" class="filter-btn-premium w-100">
                        <i class="material-symbols-outlined">filter_alt</i>
                        Sonuçları Getir
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- İstasyon Listesi -->
    <?php if (empty($stations)): ?>
        <div class="text-center py-5 animate__animated animate__fadeIn">
            <i class="material-symbols-outlined display-1 text-muted">search_off</i>
            <h3 class="mt-4">Aradığınız kriterlere uygun sonuç bulunamadı.</h3>
            <p class="text-muted">Filtreleri değiştirerek tekrar aramayı deneyebilirsiniz.</p>
            <a href="fiyatlar.php" class="btn btn-outline-primary rounded-pill mt-3">Tüm Liste</a>
        </div>
    <?php else: ?>
        <?php if (!isLoggedIn()): ?>
            <div
                class="alert alert-info border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center animate__animated animate__fadeIn">
                <i class="material-symbols-outlined me-3 fs-3">info</i>
                <div>
                    <strong>Fiyatları Göremiyorum?</strong> Tüm istasyonların net fiyatlarını görmek için lütfen
                    <a href="<?= url('login.php') ?>" class="fw-bold">Giriş yapın</a> veya
                    <a href="<?= url('register.php') ?>" class="fw-bold">Ücretsiz Üye Olun</a>.
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4 animate__animated animate__fadeInUp">
            <?php
            // En ucuz mazot fiyatını bul (Badge için)
            $minDiesel = min(array_filter(array_column($stations, 'diesel_price')) ?: [0]);

            foreach ($stations as $station):
                $logo = getBrandLogo($station['brand']);
                $isGuest = !isLoggedIn();
                $isCheapest = ($station['diesel_price'] > 0 && $station['diesel_price'] == $minDiesel);

                if ($isGuest) {
                    $pDiesel = formatObfuscatedPrice($station['diesel_price']);
                    $pGas = formatObfuscatedPrice($station['gasoline_price']);
                    $pLpg = formatObfuscatedPrice($station['lpg_price']);
                } else {
                    $pDiesel = ['visible' => formatPrice($station['diesel_price']), 'blurred' => ''];
                    $pGas = ['visible' => formatPrice($station['gasoline_price']), 'blurred' => ''];
                    $pLpg = ['visible' => formatPrice($station['lpg_price']), 'blurred' => ''];
                }
                ?>
                <div class="col-12">
                    <div class="card station-card rounded-4 border-0 shadow-sm overflow-hidden mb-2">
                        <div class="card-body p-3">
                            <div class="station-card-grid">
                                <!-- Info Cell -->
                                <div class="station-info-cell">
                                    <div class="station-info-content">
                                        <div class="station-logo-wrapper">
                                            <?php if ($logo): ?>
                                                <img src="<?= $logo ?>" alt="<?= e($station['brand']) ?>" class="station-logo-img">
                                            <?php else: ?>
                                                <div class="station-logo-placeholder">
                                                    <i class="material-symbols-outlined text-primary">local_gas_station</i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h4 class="fw-bold mb-1">
                                                <a href="istasyon-detay.php?id=<?= $station['id'] ?>"
                                                    class="text-dark text-decoration-none hover-primary">
                                                    <?= e($station['name']) ?>
                                                </a>
                                            </h4>
                                            <div class="text-muted small">
                                                <i class="material-symbols-outlined fs-6 align-middle me-1">location_on</i>
                                                <?= e($station['district']) ?>, <?= e($station['city']) ?>
                                                <?php if (isset($station['distance']) && $station['distance'] !== null): ?>
                                                    <span class="ms-2 badge bg-light text-dark fw-normal border">
                                                        <i class="material-symbols-outlined fs-6 align-middle">near_me</i>
                                                        <?= number_format($station['distance'], 1) ?> km
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($station['avg_rating']): ?>
                                                <div class="mt-1 small">
                                                    <i class="material-symbols-outlined fs-6 align-middle text-warning">star</i>
                                                    <span class="fw-bold"><?= number_format($station['avg_rating'], 1) ?></span>
                                                    <span class="text-muted ms-1">(<?= $station['review_count'] ?>)</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Prices Cell -->
                                <div class="station-prices-cell">
                                    <div class="price-display-grid">
                                        <!-- Mazot -->
                                        <div class="price-box primary-price <?= $isGuest ? 'auth-trigger' : '' ?>">
                                            <?php if ($isCheapest): ?>
                                                <div class="cheapest-badge">EN UCUZ</div>
                                            <?php endif; ?>
                                            <span class="fuel-type">Mazot</span>
                                            <span class="price-val">
                                                <?= $pDiesel['visible'] ?><span
                                                    class="blurred"><?= $pDiesel['blurred'] ?></span>
                                            </span>
                                        </div>
                                        <!-- Benzin -->
                                        <div class="price-box <?= $isGuest ? 'auth-trigger' : '' ?>">
                                            <span class="fuel-type">Benzin</span>
                                            <span class="price-val">
                                                <?= $pGas['visible'] ?><span class="blurred"><?= $pGas['blurred'] ?></span>
                                            </span>
                                        </div>
                                        <!-- LPG -->
                                        <div class="price-box <?= $isGuest ? 'auth-trigger' : '' ?>">
                                            <span class="fuel-type">LPG</span>
                                            <span class="price-val">
                                                <?= $pLpg['visible'] ?><span class="blurred"><?= $pLpg['blurred'] ?></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions Cell -->
                                <div class="station-actions-cell">
                                    <div class="text-lg-end">
                                        <div class="small text-muted mb-2 d-none d-lg-block">
                                            <i class="material-symbols-outlined fs-6 align-middle">update</i>
                                            <?= timeAgo($station['price_updated_at']) ?>
                                        </div>
                                        <div class="d-flex gap-2 justify-content-end">
                                            <a href="istasyon-detay.php?id=<?= $station['id'] ?>"
                                                class="btn btn-light rounded-pill px-3">
                                                Detay
                                            </a>
                                            <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $station['lat'] ?>,<?= $station['lng'] ?>"
                                                target="_blank" class="btn btn-primary rounded-pill px-4">
                                                Git
                                            </a>
                                        </div>
                                        <div class="small text-muted mt-2 d-lg-none">
                                            <i class="material-symbols-outlined fs-6 align-middle">update</i>
                                            <?= timeAgo($station['price_updated_at']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .hover-primary:hover {
        color: var(--primary) !important;
    }
</style>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>

<script src="<?= asset('js/map.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Sort change handling
        const sortSelect = document.querySelector('select[name="sort"]');
        if (sortSelect) {
            sortSelect.addEventListener('change', function (e) {
                if (this.value === 'near_me') {
                    e.preventDefault();
                    getUserLocation(function (pos) {
                        const url = new URL(window.location.href);
                        url.searchParams.set('sort', 'near_me');
                        url.searchParams.set('lat', pos.lat);
                        url.searchParams.set('lng', pos.lng);
                        url.searchParams.delete('city');
                        window.location.href = url.toString();
                    });
                }
            });
        }

        // Mobile Filter Toggle
        const filterToggleBtn = document.getElementById('filterToggleBtn');
        const filterContent = document.getElementById('filterContent');

        if (filterToggleBtn && filterContent) {
            filterToggleBtn.addEventListener('click', function () {
                this.classList.toggle('active');
                filterContent.classList.toggle('show');
            });
        }
    });
</script>