<?php
/**
 * Admin API: Şifre Sıfırlama Bağlantısı Oluştur
 */

require_once dirname(dirname(__DIR__)) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

// Güvenlik kontrolü
if (!isAdmin()) {
    jsonResponse(['success' => false, 'error' => 'Yetkisiz erişim']);
}

// self-healing DB: Tablo yoksa oluştur
try {
    db()->query("CREATE TABLE IF NOT EXISTS `password_resets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `token` VARCHAR(100) NOT NULL UNIQUE,
        `is_used` TINYINT(1) DEFAULT 0,
        `expires_at` DATETIME NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {
    // Tablo oluşumu hatasını logla ama devam etmeyi dene (belki zaten vardır)
}

$userId = (int) ($_GET['user_id'] ?? 0);

if (!$userId) {
    jsonResponse(['success' => false, 'error' => 'Geçersiz kullanıcı ID']);
}

// Kullanıcıyı bul
$user = db()->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

if (!$user) {
    jsonResponse(['success' => false, 'error' => 'Kullanıcı bulunamadı']);
}

// Eski aktif tokenleri iptal et
db()->update('password_resets', ['is_used' => 1], 'user_id = ? AND is_used = 0', [$userId]);

// Yeni token oluştur
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

db()->insert('password_resets', [
    'user_id' => $userId,
    'token' => $token,
    'expires_at' => $expiresAt
]);

// Bağlantı oluştur
$resetUrl = SITE_URL . "/sifre-sifirla.php?token=" . $token;

// WhatsApp Mesajı
$message = "Merhaba " . ($user['name'] ?: 'Kullanıcımız') . ",\n\nUcuzMazot.com hesabınızın şifresini yenilemek için aşağıdaki bağlantıyı kullanabilirsiniz:\n\n" . $resetUrl . "\n\nBu bağlantı 24 saat geçerlidir.";

// WhatsApp Linki (wa.me)
$waPhone = preg_replace('/[^0-9]/', '', $user['phone']);
if (substr($waPhone, 0, 1) === '0') {
    $waPhone = '90' . substr($waPhone, 1);
}

$waUrl = "https://wa.me/" . $waPhone . "?text=" . urlencode($message);

jsonResponse([
    'success' => true,
    'token' => $token,
    'reset_url' => $resetUrl,
    'wa_url' => $waUrl
]);
