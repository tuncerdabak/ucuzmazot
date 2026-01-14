<?php
require_once 'config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

$slug = $_GET['slug'] ?? '';
$cityFound = null;

// Slug'dan şehri bul
foreach (TURKEY_CITIES as $city) {
    if (slugify($city . '-en-ucuz-mazot') === $slug) {
        $cityFound = $city;
        break;
    }
}

// Eğer slug eşleşmiyorsa veya boşsa 404
if (!$cityFound) {
    if (empty($slug)) {
        header('Location: ' . url());
        exit;
    }
    http_response_code(404);
    die('Sayfa bulunamadı.');
}

// Şehirdeki en ucuz istasyonları getir
$stations = db()->fetchAll("
    SELECT * FROM v_stations_with_prices 
    WHERE city = ? 
    ORDER BY diesel_price ASC 
    LIMIT 20
", [$cityFound]);

// Sayfa meta bilgileri
$pageTitle = "$cityFound En Ucuz Mazot Fiyatları - " . date('d.m.Y');
$pageDescription = "$cityFound ve ilçelerindeki güncel mazot, benzin ve LPG fiyatlarını karşılaştırın. En ucuz istasyonu haritada görün.";

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url(); ?>">Ana Sayfa</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo e($cityFound); ?></li>
        </ol>
    </nav>

    <div class="row mb-4 animate__animated animate__fadeIn">
        <div class="col-md-8">
            <h1 class="display-5 fw-bold text-gradient mb-2">
                <?php echo e($cityFound); ?> En Ucuz Mazot
            </h1>
            <p class="lead text-muted">
                Bugün (<?php echo date('d.m.Y'); ?>) tarihinde <?php echo e($cityFound); ?> ilindeki en uygun fiyatlı
                akaryakıt istasyonları listelenmektedir.
            </p>
        </div>
        <div class="col-md-4 text-md-end d-flex align-items-center justify-content-md-end">
            <a href="<?php echo url(); ?>#map" class="btn btn-primary btn-lg rounded-pill shadow-sm">
                <i class="material-symbols-outlined align-middle me-2">map</i>
                Haritada Gör
            </a>
        </div>
    </div>

    <!-- Filtreleme ve Bilgi Barı -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light overflow-hidden animate__animated animate__fadeInUp">
        <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center">
                <div class="icon-box bg-white rounded-3 p-2 me-3 shadow-sm text-primary">
                    <i class="material-symbols-outlined">info</i>
                </div>
                <span class="fw-semibold">Toplam <?php echo count($stations); ?> istasyon bulundu.</span>
            </div>
            <div class="small text-muted">
                <i class="material-symbols-outlined fs-6 align-middle">update</i>
                Fiyatlar istasyon sahipleri tarafından güncellenmektedir.
            </div>
        </div>
    </div>

    <div class="row g-4 animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
        <?php if (empty($stations)): ?>
            <div class="col-12 text-center py-5">
                <div class="mb-4">
                    <i class="material-symbols-outlined display-1 text-muted">proximity_alert</i>
                </div>
                <h3>Üzgünüz, bu şehirde henüz kayıtlı istasyon bulunmuyor.</h3>
                <p class="text-muted">Yakındaki diğer şehirleri kontrol edebilir veya istasyon sahibiyseniz
                    kaydolabilirsiniz.</p>
                <a href="<?php echo url('station/register.php'); ?>"
                    class="btn btn-outline-primary rounded-pill mt-3">İstasyon Ekle</a>
            </div>
        <?php else: ?>
            <?php foreach ($stations as $index => $station):
                $logo = getBrandLogo($station['brand']);
                $isGuest = !isLoggedIn();
                if ($isGuest) {
                    $pDiesel = formatObfuscatedPrice($station['diesel_price']);
                } else {
                    $pDiesel = ['visible' => formatPrice($station['diesel_price']) . ' ₺', 'blurred' => ''];
                }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card station-card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-body p-4">
                            <?php if ($isGuest && $index === 0): ?>
                                <div class="alert alert-info border-0 rounded-3 small mb-3 py-2">
                                    <i class="material-symbols-outlined fs-6 align-middle me-1">info</i>
                                    Fiyatı tam görmek için <a href="<?= url('login.php') ?>" class="fw-bold">Giriş yapın</a>.
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="station-logo-wrapper">
                                    <?php if ($logo): ?>
                                        <img src="<?= $logo ?>" alt="<?= e($station['brand']) ?>" class="station-logo-img">
                                    <?php else: ?>
                                        <div class="station-logo-placeholder">
                                            <i class="material-symbols-outlined text-primary">local_gas_station</i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 small">En
                                        Ucuz</span>
                                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                        <?= timeAgo($station['price_updated_at']) ?>
                                    </div>
                                </div>
                            </div>

                            <h5 class="fw-bold mb-1">
                                <a href="<?= url('istasyon-detay.php?id=' . $station['id']) ?>"
                                    class="text-dark text-decoration-none hover-primary">
                                    <?= e($station['name']) ?>
                                </a>
                            </h5>
                            <p class="text-muted small mb-3">
                                <i class="material-symbols-outlined fs-6 align-middle me-1">location_on</i>
                                <?= e($station['district']) ?>, <?= e($station['city']) ?>
                            </p>

                            <div class="price-box primary-price mb-4">
                                <span class="fuel-type">Güncel Mazot Fiyatı</span>
                                <span class="price-val h3 mb-0">
                                    <?= $pDiesel['visible'] ?><span class="blurred"><?= $pDiesel['blurred'] ?></span>
                                </span>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="<?= url('istasyon-detay.php?id=' . $station['id']) ?>"
                                    class="btn btn-light rounded-pill flex-grow-1 border-0">Detay</a>
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $station['lat'] ?>,<?= $station['lng'] ?>"
                                    target="_blank" class="btn btn-primary rounded-pill flex-grow-1">Yol Tarifi</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Schema.org Verisi -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "itemListElement": [
    <?php foreach ($stations as $index => $station): ?>
                            {
                              "@type": "ListItem",
                              "position": <?php echo $index + 1; ?>,
                              "item": {
                                "@type": "LocalBusiness",
                                "name": "<?php echo e($station['name']); ?>",
                                "address": {
                                  "@type": "PostalAddress",
                                  "addressLocality": "<?php echo e($station['city']); ?>",
                                  "addressRegion": "<?php echo e($station['district']); ?>",
                                  "streetAddress": "<?php echo e($station['address']); ?>",
                                  "addressCountry": "TR"
                                },
                                "telephone": "<?php echo e($station['phone']); ?>",
                                "priceRange": "$$",
                                "image": "<?php echo $station['image'] ? url($station['image']) : asset('img/default-station.jpg'); ?>"
                              }
                            }<?php echo $index < count($stations) - 1 ? ',' : ''; ?>
    <?php endforeach; ?>
  ]
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>