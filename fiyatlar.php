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

<div class="container py-5">
    <!-- Header Section -->
    <div class="row mb-5 align-items-center animate__animated animate__fadeIn">
        <div class="col-md-8">
            <h1 class="display-5 fw-bold text-gradient mb-2">Güncel Akaryakıt Fiyatları</h1>
            <p class="lead text-muted">Türkiye genelindeki tüm istasyonların en güncel mazot, benzin ve LPG fiyatlarını
                karşılaştırın.</p>
        </div>
        <div class="col-md-4 text-md-end d-none d-md-block">
            <div class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 shadow-sm">
                <i class="material-symbols-outlined align-middle me-1">update</i>
                Son Güncelleme: <?= date('d.m.Y') ?>
            </div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="card border-0 shadow-sm rounded-4 mb-5 animate__animated animate__fadeIn">
        <div class="card-body p-4">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">Şehir Seçin</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i
                                class="material-symbols-outlined fs-5">location_city</i></span>
                        <select name="city" class="form-select border-0 bg-light" onchange="this.form.submit()">
                            <option value="">Tüm Türkiye</option>
                            <?php foreach ($cities as $c): ?>
                                <option value="<?= e($c['city']) ?>" <?= $city === $c['city'] ? 'selected' : '' ?>>
                                    <?= e($c['city']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">İstasyon Ara</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i
                                class="material-symbols-outlined fs-5">search</i></span>
                        <input type="text" name="q" class="form-control border-0 bg-light"
                            placeholder="İsim veya ilçe..." value="<?= e($search) ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">Sıralama Kriteri</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i
                                class="material-symbols-outlined fs-5">sort</i></span>
                        <select name="sort" class="form-select border-0 bg-light" onchange="this.form.submit()">
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>En Ucuz Mazot</option>
                            <option value="near_me" <?= $sort === 'near_me' ? 'selected' : '' ?>>Yakınımdaki En Ucuzlar
                            </option>
                            <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>En Yeni Güncellenen
                            </option>
                            <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Müşteri Puanı
                            </option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2">
                        <i class="material-symbols-outlined fs-5 align-middle me-1">filter_alt</i> Sonuçları Getir
                    </button>
                </div>
            </form>
        </div>
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
                    $pDiesel = ['visible' => formatPrice($station['diesel_price']) . ' ₺', 'blurred' => ''];
                    $pGas = ['visible' => formatPrice($station['gasoline_price']) . ' ₺', 'blurred' => ''];
                    $pLpg = ['visible' => formatPrice($station['lpg_price']) . ' ₺', 'blurred' => ''];
                }
                ?>
                <div class="col-12">
                    <div class="card station-card rounded-4 border-0 shadow-sm overflow-hidden mb-3">
                        <div class="card-body p-4">
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
                                        <div class="price-box primary-price">
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
                                        <div class="price-box">
                                            <span class="fuel-type">Benzin</span>
                                            <span class="price-val">
                                                <?= $pGas['visible'] ?><span class="blurred"><?= $pGas['blurred'] ?></span>
                                            </span>
                                        </div>
                                        <!-- LPG -->
                                        <div class="price-box">
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
        const sortSelect = document.querySelector('select[name="sort"]');

        sortSelect.addEventListener('change', function (e) {
            if (this.value === 'near_me') {
                e.preventDefault();
                getUserLocation(function (pos) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('sort', 'near_me');
                    url.searchParams.set('lat', pos.lat);
                    url.searchParams.set('lng', pos.lng);
                    url.searchParams.delete('city'); // Mesafe bazlı aramada şehir fitresini kaldır
                    window.location.href = url.toString();
                });
            }
        });
    });
</script>