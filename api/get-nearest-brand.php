<?php
/**
 * API: En Yakın Marka İstasyonu Getir
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/functions.php';

header('Content-Type: application/json');

// Input Kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$lat = isset($_GET['lat']) ? (float) $_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float) $_GET['lng'] : null;
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : null;

if (!$lat || !$lng || !$brand) {
    http_response_code(400);
    echo json_encode(['error' => 'Eksik parametreler (lat, lng, brand)']);
    exit;
}

try {
    // Haversine Formülü ile En Yakın İstasyonu Bul
    $sql = "
        SELECT 
            *,
            (
                6371 * acos(
                    cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + 
                    sin(radians(?)) * sin(radians(lat))
                )
            ) AS distance,
            (SELECT diesel_price FROM fuel_prices WHERE station_id = stations.id ORDER BY created_at DESC LIMIT 1) as diesel_price,
            (SELECT gasoline_price FROM fuel_prices WHERE station_id = stations.id ORDER BY created_at DESC LIMIT 1) as gasoline_price,
            (SELECT lpg_price FROM fuel_prices WHERE station_id = stations.id ORDER BY created_at DESC LIMIT 1) as lpg_price
        FROM stations 
        WHERE 
            brand = ? 
            AND is_active = 1 
            AND is_approved = 1
        HAVING distance < 50
        ORDER BY distance ASC 
        LIMIT 1
    ";

    $station = db()->fetchOne($sql, [$lat, $lng, $lat, $brand]);

    if ($station) {
        echo json_encode([
            'success' => true,
            'station' => [
                'id' => $station['id'],
                'name' => $station['name'],
                'brand' => $station['brand'],
                'lat' => (float) $station['lat'],
                'lng' => (float) $station['lng'],
                'distance' => round($station['distance'], 2),
                'diesel_price' => $station['diesel_price'] ? (float) $station['diesel_price'] : null,
                'gasoline_price' => $station['gasoline_price'] ? (float) $station['gasoline_price'] : null,
                'lpg_price' => $station['lpg_price'] ? (float) $station['lpg_price'] : null,
                // Add address or city if needed
                'city' => $station['city'],
                'district' => $station['district']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Yakında bu markaya ait istasyon bulunamadı (50km).'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Sunucu hatası: ' . $e->getMessage()]);
}
