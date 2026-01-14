<?php
require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/functions.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id)
    exit;

$station = db()->fetchOne("
    SELECT s.name, s.city, fp.diesel_price 
    FROM stations s 
    LEFT JOIN (SELECT * FROM fuel_prices WHERE station_id = ? ORDER BY created_at DESC LIMIT 1) fp ON s.id = fp.station_id 
    WHERE s.id = ?
", [$id, $id]);

if (!$station)
    exit;

// Template'i yükle
$templatePath = ASSETS_PATH . '/img/share-template.png';
if (!file_exists($templatePath)) {
    // Fallsafe: template yoksa boş resim oluştur
    $im = imagecreatetruecolor(1200, 630);
    $bg = imagecolorallocate($im, 37, 99, 235);
    imagefilledrectangle($im, 0, 0, 1200, 630, $bg);
} else {
    $im = imagecreatefrompng($templatePath);
}

// Renkler
$white = imagecolorallocate($im, 255, 255, 255);
$blue = imagecolorallocate($im, 37, 99, 235);

// Metinleri yaz (Font dosyası olmadığı için imagestring kullanılıyor, daha iyi sonuç için sunucuya ttf yüklenebilir)
// imagestring($im, font, x, y, string, color)
$stationName = mb_convert_encoding($station['name'], 'ISO-8859-9', 'UTF-8');
$priceText = number_format($station['diesel_price'], 2, ',', '.') . ' TL';

// Station Name
imagestring($im, 5, 200, 200, $stationName, $white);

// Price Label
imagestring($im, 5, 200, 250, "GUNCEL MAZOT FIYATI", $white);

// Price
imagestring($im, 5, 200, 300, $priceText, $white);

// Brand/URL
imagestring($im, 3, 200, 450, "ucuzmazot.com", $white);

// Header and output
header('Content-Type: image/png');
imagepng($im);
imagedestroy($im);
