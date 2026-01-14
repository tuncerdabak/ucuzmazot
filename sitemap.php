<?php
/**
 * UcuzMazot.com - Dinamik Sitemap Oluşturucu
 */

require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/functions.php';

header("Content-Type: application/xml; charset=utf-8");

$baseUrl = SITE_URL; // "https://ucuzmazot.com"
$now = date('Y-m-d\TH:i:sP');

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// 1. Ana Sayfalar
$pages = [
    '' => '1.0',
    'fiyatlar.php' => '0.8',
    'markalar.php' => '0.8',
    'hakkimizda.php' => '0.7',
    'iletisim.php' => '0.7'
];

foreach ($pages as $page => $priority) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . $baseUrl . '/' . $page . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . $now . '</lastmod>' . PHP_EOL;
    echo '    <priority>' . $priority . '</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

// 2. Şehirler
$cities = db()->fetchAll("SELECT DISTINCT city FROM stations WHERE is_approved = 1 AND city != '' ORDER BY city");
foreach ($cities as $c) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . $baseUrl . '/?city=' . urlencode($c['city']) . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . $now . '</lastmod>' . PHP_EOL;
    echo '    <priority>0.6</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

// 3. İstasyon Detayları
$stations = db()->fetchAll("
    SELECT s.id, s.updated_at, MAX(fp.created_at) as last_price_update
    FROM stations s
    LEFT JOIN fuel_prices fp ON s.id = fp.station_id
    WHERE s.is_approved = 1 AND s.is_active = 1
    GROUP BY s.id
    ORDER BY s.id DESC
");

foreach ($stations as $s) {
    $lastMod = $s['last_price_update'] ?: ($s['updated_at'] ?: date('Y-m-d H:i:s'));
    $formattedDate = date('Y-m-d\TH:i:sP', strtotime($lastMod));

    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . $baseUrl . '/istasyon-detay.php?id=' . $s['id'] . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . $formattedDate . '</lastmod>' . PHP_EOL;
    echo '    <priority>0.5</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

echo '</urlset>';
