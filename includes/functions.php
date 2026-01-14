<?php
/**
 * Yardımcı Fonksiyonlar
 */

/**
 * Haversine formülü ile iki nokta arası mesafe (km)
 */
function calculateDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLng / 2) * sin($dLng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

/**
 * Fiyatı formatla
 */
function formatPrice($price)
{
    return number_format($price, 2, ',', '.') . ' ₺';
}

/**
 * Tarihi formatla
 */
function formatDate($date, $format = 'd.m.Y H:i')
{
    if (empty($date))
        return '-';
    return date($format, strtotime($date));
}

/**
 * Zaman farkını insan dostu formatta göster
 */
function timeAgo($datetime)
{
    if (empty($datetime))
        return 'Henüz güncellenmedi';
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60)
        return 'Az önce';
    if ($diff < 3600)
        return floor($diff / 60) . ' dakika önce';
    if ($diff < 86400)
        return floor($diff / 3600) . ' saat önce';
    if ($diff < 604800)
        return floor($diff / 86400) . ' gün önce';

    return formatDate($datetime, 'd.m.Y');
}

/**
 * Mesafeyi formatla
 */
function formatDistance($km)
{
    if ($km < 1) {
        return round($km * 1000) . ' m';
    }
    return number_format($km, 1, ',', '.') . ' km';
}

/**
 * Puan yıldızlarını HTML olarak döndür
 */
function renderStars($rating, $max = 5)
{
    $html = '';
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;

    for ($i = 1; $i <= $max; $i++) {
        if ($i <= $fullStars) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($halfStar && $i == $fullStars + 1) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-muted"></i>';
        }
    }

    return $html;
}

/**
 * Slug oluştur
 */
function slugify($text)
{
    $text = mb_strtolower($text, 'UTF-8');
    $replacements = [
        'ş' => 's',
        'ğ' => 'g',
        'ü' => 'u',
        'ı' => 'i',
        'ö' => 'o',
        'ç' => 'c',
        'Ş' => 's',
        'Ğ' => 'g',
        'Ü' => 'u',
        'İ' => 'i',
        'Ö' => 'o',
        'Ç' => 'c'
    ];
    $text = strtr($text, $replacements);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * Güvenli dosya adı oluştur
 */
function safeFilename($filename)
{
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $name = pathinfo($filename, PATHINFO_FILENAME);
    return slugify($name) . '-' . time() . '.' . strtolower($ext);
}

/**
 * Dosya yükle
 */
function uploadFile($file, $targetDir, $allowedTypes = null)
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Dosya yükleme hatası'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'Dosya çok büyük'];
    }

    $allowedTypes = $allowedTypes ?? ALLOWED_IMAGE_TYPES;
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Geçersiz dosya tipi'];
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = safeFilename($file['name']);
    $targetPath = $targetDir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $targetPath];
    }

    return ['success' => false, 'error' => 'Dosya kaydedilemedi'];
}

/**
 * Sayfalama oluştur
 */
function paginate($total, $perPage, $currentPage, $baseUrl)
{
    $totalPages = ceil($total / $perPage);
    $html = '<nav><ul class="pagination justify-content-center">';

    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">&laquo;</a></li>';
    }

    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }

    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">&raquo;</a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Fiyat anormallik kontrolü
 */
function isPriceAbnormal($price)
{
    // config.php'deki dinamik veya varsayılan değerleri kullan
    return $price < DIESEL_MIN_PRICE || $price > DIESEL_MAX_PRICE;
}

/**
 * Fiyatı kısmen gizleyerek formatla (örn: 4x,xx ₺)
 * İlk basamak açık, geri kalanı gizli.
 */
function formatObfuscatedPrice($price)
{
    if (!$price)
        return ['visible' => '-', 'blurred' => ''];

    $formatted = number_format($price, 2, ',', '.'); // Örn: 45,90
    $visible = substr($formatted, 0, 1); // 4
    $blurred = substr($formatted, 1); // 5,90

    return [
        'visible' => $visible,
        'blurred' => $blurred . ' ₺'
    ];
}
/**
 * Marka logosu URL'ini döner. Bulamazsa null döner.
 */
function getBrandLogo($brand)
{
    if (empty($brand))
        return null;

    $brandSlug = strtolower(str_replace([' ', '.'], ['-', ''], $brand));
    $logoFile = "img/brands/{$brandSlug}.png"; // asset() zaten 'assets/' ekliyor
    $serverPath = ASSETS_PATH . '/' . $logoFile;

    if (file_exists($serverPath)) {
        return asset($logoFile);
    }

    return null;
}

/**
 * Telefon numarasını sadece rakamlardan oluşacak şekilde temizler.
 * WhatsApp linkleri için uygundur.
 */
function cleanPhone($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Eğer 0 ile başlıyorsa (örn: 0532...), 0'ı kaldırıp 90 ekleyelim
    if (strpos($phone, '0') === 0) {
        $phone = '90' . substr($phone, 1);
    }
    // Eğer 10 haneliyse (örn: 532...), başına 90 ekleyelim
    elseif (strlen($phone) === 10) {
        $phone = '90' . $phone;
    }

    return $phone;
}

/**
 * Aktivite günlüğü kaydet
 */
function logActivity($action, $entityType = null, $entityId = null, $details = null)
{
    try {
        db()->insert('activity_logs', [
            'user_id' => currentUserId(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details ? json_encode($details) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Loglama hatası ana akışı bozmasın
    }
}

/**
 * Basit Rate Limit kontrolü (Session tabanlı)
 */
function checkRateLimit($key, $limit = 60, $period = 60)
{
    $now = time();
    $sessionKey = "rate_limit_$key";

    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = ['count' => 1, 'start' => $now];
        return true;
    }

    $data = &$_SESSION[$sessionKey];

    if ($now - $data['start'] > $period) {
        $data = ['count' => 1, 'start' => $now];
        return true;
    }

    if ($data['count'] >= $limit) {
        return false;
    }

    $data['count']++;
    return true;
}
