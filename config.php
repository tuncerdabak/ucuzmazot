<?php
/**
 * UcuzMazot.com - Ana Konfigürasyon Dosyası
 * 
 * Bu dosyayı sunucuya yüklemeden önce veritabanı bilgilerini güncelleyin!
 */

// Hata raporlama (Geliştirme için açık, üretimde kapatın)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Karakter seti
mb_internal_encoding('UTF-8');

// =====================================================
// VERİTABANI AYARLARI
// =====================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'tuncerda_ucuzmazot');
define('DB_USER', 'tuncerda_mazotcu');
define('DB_PASS', 'Td3492549*');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// SİTE AYARLARI
// =====================================================
// define('SITE_NAME', 'UcuzMazot'); // Aşağıda DB'den çekilecek
// define('SITE_TITLE', 'UcuzMazot - En Ucuz Mazot Fiyatları'); // Aşağıda DB'den çekilecek

// Dinamik URL Algılama
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'ucuzmazot.com';
define('SITE_URL', $protocol . '://' . $host);

// define('SITE_EMAIL', 'info@ucuzmazot.com'); // Aşağıda DB'den çekilecek

// Dizin yolları
define('ROOT_PATH', dirname(__FILE__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// URL yolları
define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOADS_URL', SITE_URL . '/uploads');

// =====================================================
// GÜVENLİK AYARLARI
// =====================================================
define('SESSION_NAME', 'ucuzmazot_session');
define('SESSION_LIFETIME', 86400);          // 24 saat
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_COST', 12);                // bcrypt cost

// JWT ayarları (API için)
define('JWT_SECRET', 'GUCLU_BIR_SECRET_KEY_GIRIN_BURAYA_EN_AZ_32_KARAKTER');
define('JWT_EXPIRY', 604800);               // 7 gün

// Versiyon Kontrolü (CSS/JS Cache Busting için)
define('ASSET_VERSION', '1.0.6');
define('SYSTEM_VERSION', 'v1.0.4');

// =====================================================
// FİYAT KONTROL AYARLARI
// =====================================================
// =====================================================
// FİYAT KONTROL AYARLARI
// =====================================================
// Veritabanı bağlantısı henüz kurulmadığı için varsayılan değerleri tanımlıyoruz.
// DB bağlantısı `includes/db.php` içinde yapılıyor, ancak config dosyası genellikle önce dahil edilir.
// Bu yüzden dinamik ayarları config.php'nin en sonunda veya functions.php yüklendikten sonra almalıyız.
// Ancak pratiklik açısından, burada varsayılanları tanımlayıp, functions.php içinde override edebiliriz 
// veya db.php'de bu işlemi yapabiliriz. 
// En temiz yöntem: Bu sabitleri "varsayılan" olarak tanımlamak ve gerekli yerlerde getSetting() fonksiyonu kullanmaktır.
// Ancak mevcut kod yapısını (sabit kullanımı) bozmamak için, DB bağlantısını burada yapıp sabitleri tanımlayacağız.

define('DEFAULT_DIESEL_MIN_PRICE', 40.00);
define('DEFAULT_DIESEL_MAX_PRICE', 50.00);
define('DEFAULT_GASOLINE_MIN_PRICE', 40.00);
define('DEFAULT_GASOLINE_MAX_PRICE', 50.00);
define('DEFAULT_LPG_MIN_PRICE', 20.00);
define('DEFAULT_LPG_MAX_PRICE', 30.00);
define('PRICE_CHANGE_LIMIT', 5.00);

// DB'den ayarları çekmek için basit bir PDO bağlantısı (db.php'den bağımsız, çünkü db.php config'i require ediyor)
try {
    $tempDb = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $tempDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $tempDb->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('min_price_alert', 'max_price_alert', 'contact_email', 'contact_phone', 'site_name', 'site_title')");
    $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    define('DIESEL_MIN_PRICE', (float) ($dbSettings['min_price_alert'] ?? DEFAULT_DIESEL_MIN_PRICE));
    define('DIESEL_MAX_PRICE', (float) ($dbSettings['max_price_alert'] ?? DEFAULT_DIESEL_MAX_PRICE));

    // İletişim ve Genel Ayarları tanımla
    define('SITE_NAME', $dbSettings['site_name'] ?? 'UcuzMazot');
    define('SITE_TITLE', $dbSettings['site_title'] ?? 'UcuzMazot - En Ucuz Mazot Fiyatları');
    define('SITE_EMAIL', $dbSettings['contact_email'] ?? 'info@ucuzmazot.com');
    define('SITE_CONTACT_PHONE', $dbSettings['contact_phone'] ?? '');

    // Diğerleri için şimdilik varsayılan
    define('GASOLINE_MIN_PRICE', DEFAULT_GASOLINE_MIN_PRICE);
    define('GASOLINE_MAX_PRICE', DEFAULT_GASOLINE_MAX_PRICE);
    define('LPG_MIN_PRICE', DEFAULT_LPG_MIN_PRICE);
    define('LPG_MAX_PRICE', DEFAULT_LPG_MAX_PRICE);

    $tempDb = null;
} catch (PDOException $e) {
    // DB hatası olursa varsayılanları kullan
    define('DIESEL_MIN_PRICE', DEFAULT_DIESEL_MIN_PRICE);
    define('DIESEL_MAX_PRICE', DEFAULT_DIESEL_MAX_PRICE);
    define('GASOLINE_MIN_PRICE', DEFAULT_GASOLINE_MIN_PRICE);
    define('GASOLINE_MAX_PRICE', DEFAULT_GASOLINE_MAX_PRICE);
    define('LPG_MIN_PRICE', DEFAULT_LPG_MIN_PRICE);
    define('LPG_MAX_PRICE', DEFAULT_LPG_MAX_PRICE);
    define('SITE_NAME', 'UcuzMazot');
    define('SITE_TITLE', 'UcuzMazot - En Ucuz Mazot Fiyatları');
    define('SITE_EMAIL', 'info@ucuzmazot.com');
    define('SITE_CONTACT_PHONE', '');
}

// =====================================================
// HARİTA AYARLARI
// =====================================================
define('MAP_DEFAULT_LAT', 41.0082);         // İstanbul
define('MAP_DEFAULT_LNG', 28.9784);
define('MAP_DEFAULT_ZOOM', 10);
define('SEARCH_RADIUS_KM', 50);             // Yakındaki istasyon arama yarıçapı

// =====================================================
// SMS API AYARLARI (Sonra yapılandırılacak)
// =====================================================
define('SMS_API_ENABLED', false);
define('SMS_API_URL', '');
define('SMS_API_KEY', '');
define('SMS_API_SECRET', '');
define('SMS_SENDER_ID', 'UCUZMAZOT');

// =====================================================
// RATE LIMIT AYARLARI
// =====================================================
define('LOGIN_ATTEMPTS_LIMIT', 5);          // Maksimum giriş denemesi
define('LOGIN_LOCKOUT_TIME', 900);          // Kilitleme süresi (15 dakika)
define('API_RATE_LIMIT', 100);              // API istek limiti (dakika başına)

// =====================================================
// DOSYA YÜKLEME AYARLARI
// =====================================================
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// =====================================================
// TÜRKİYE ŞEHİRLERİ
// =====================================================
define('TURKEY_CITIES', [
    'Adana',
    'Adıyaman',
    'Afyonkarahisar',
    'Ağrı',
    'Aksaray',
    'Amasya',
    'Ankara',
    'Antalya',
    'Ardahan',
    'Artvin',
    'Aydın',
    'Balıkesir',
    'Bartın',
    'Batman',
    'Bayburt',
    'Bilecik',
    'Bingöl',
    'Bitlis',
    'Bolu',
    'Burdur',
    'Bursa',
    'Çanakkale',
    'Çankırı',
    'Çorum',
    'Denizli',
    'Diyarbakır',
    'Düzce',
    'Edirne',
    'Elazığ',
    'Erzincan',
    'Erzurum',
    'Eskişehir',
    'Gaziantep',
    'Giresun',
    'Gümüşhane',
    'Hakkari',
    'Hatay',
    'Iğdır',
    'Isparta',
    'İstanbul',
    'İzmir',
    'Kahramanmaraş',
    'Karabük',
    'Karaman',
    'Kars',
    'Kastamonu',
    'Kayseri',
    'Kırıkkale',
    'Kırklareli',
    'Kırşehir',
    'Kilis',
    'Kocaeli',
    'Konya',
    'Kütahya',
    'Malatya',
    'Manisa',
    'Mardin',
    'Mersin',
    'Muğla',
    'Muş',
    'Nevşehir',
    'Niğde',
    'Ordu',
    'Osmaniye',
    'Rize',
    'Sakarya',
    'Samsun',
    'Şanlıurfa',
    'Siirt',
    'Sinop',
    'Şırnak',
    'Sivas',
    'Tekirdağ',
    'Tokat',
    'Trabzon',
    'Tunceli',
    'Uşak',
    'Van',
    'Yalova',
    'Yozgat',
    'Zonguldak'
]);

// =====================================================
// YAKIT MARKALARI
// =====================================================
define('FUEL_BRANDS', [
    '7 Kıta',
    'Alpet',
    'Aytemiz',
    'Bpet',
    'Bestoil',
    'Bluepet',
    'BP',
    'Classoil',
    'Daspet',
    'Energy',
    'Epic',
    'Es Es',
    'Euroil',
    'Fox',
    'Go',
    'Gulf',
    'Hypco',
    'Kadoil',
    'Long',
    'Lukoil',
    'Memoil',
    'Moil',
    'Norm Gaz',
    'Opet',
    'Parkoil',
    'Petline',
    'Petrol Ofisi',
    'Qplus',
    'Rpet',
    'Shell',
    'Socar',
    'Soil',
    'Sunpet',
    'SahhOil',
    'Teco',
    'Termopet',
    'Total',
    'Türkiye Petrolleri',
    'Uspet',
    'Diğer'
]);

// =====================================================
// İSTASYON OLANAKLARI
// =====================================================
define('STATION_FACILITIES', [
    'tir_park' => 'TIR Parkı',
    'dus' => 'Duş',
    'wc' => 'Tuvalet',
    'restoran' => 'Restoran',
    'market' => 'Market',
    'wifi' => 'Ücretsiz WiFi',
    'atm' => 'ATM',
    'oto_yikama' => 'Oto Yıkama',
    'lastik_tamiri' => 'Lastik Tamiri',
    'servis' => 'Servis',
    '7_24' => '7/24 Açık'
]);

// =====================================================
// OTURUM BAŞLAT
// =====================================================
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// =====================================================
// YARDIMCI FONKSİYONLAR
// =====================================================

/**
 * Güvenli çıktı için HTML escape
 */
function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Asset URL oluştur
 */
function asset($path)
{
    return ASSETS_URL . '/' . ltrim($path, '/') . '?v=' . ASSET_VERSION;
}

/**
 * Site URL oluştur
 */
function url($path = '')
{
    return SITE_URL . '/' . ltrim($path, '/');
}

/**
 * Yönlendirme
 */
function redirect($url, $statusCode = 302)
{
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * JSON yanıt gönder
 */
function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * CSRF token oluştur
 */
function csrfToken()
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * CSRF token doğrula
 */
function verifyCsrfToken($token)
{
    return isset($_SESSION[CSRF_TOKEN_NAME]) &&
        hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Flash mesaj ayarla
 */
function setFlash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Flash mesaj al ve temizle
 */
function getFlash()
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
