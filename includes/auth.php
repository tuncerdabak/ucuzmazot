<?php
/**
 * Kimlik Doğrulama Fonksiyonları
 */

require_once __DIR__ . '/db.php';

/**
 * Kullanıcı giriş yap
 */
function login($phone, $password, $role = null)
{
    $phone = normalizePhone($phone);

    $sql = "SELECT * FROM users WHERE phone = ? AND is_active = 1";
    $params = [$phone];

    if ($role) {
        $sql .= " AND role = ?";
        $params[] = $role;
    }

    $user = db()->fetchOne($sql, $params);

    if (!$user) {
        return ['success' => false, 'error' => 'Kullanıcı bulunamadı'];
    }

    // Şoförler için şifre kontrolü yapma (Eğer sadece telefonla giriş isteniyorsa)
    // Ancak güvenlik için şoförlerin de bir şifresi olmalı, biz burayı "kolay giriş" için
    // ayrı bir loginByPhone fonksiyonunda halledeceğiz.
    // Standart login fonksiyonu şifre sormaya devam etsin.
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Şifre hatalı'];
    }

    // Oturum başlat
    createSession($user);

    return ['success' => true, 'user' => $user];
}


/**
 * Şoför Hızlı Kayıt (Ad + Telefon)
 */
function registerDriver($name, $phone)
{
    $phone = normalizePhone($phone);

    // Telefon kontrolü
    $exists = db()->fetchColumn("SELECT COUNT(*) FROM users WHERE phone = ?", [$phone]);
    if ($exists > 0) {
        return ['success' => false, 'error' => 'Bu numara zaten kayıtlı. Lütfen giriş yapın.'];
    }

    // Rastgele güçlü bir şifre oluştur (kullanıcı bilmeyecek, sadece arkada dursun)
    $randomPassword = bin2hex(random_bytes(8));
    $passwordHash = password_hash($randomPassword, PASSWORD_BCRYPT);

    $userId = db()->insert('users', [
        'phone' => $phone,
        'password_hash' => $passwordHash,
        'role' => 'driver',
        'name' => $name,
        'is_active' => 1,
        'is_verified' => 1,
        'is_password_set' => 0 // Hızlı kayıt olanlar şifresini henüz belirlemedi
    ]);

    $user = db()->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    createSession($user);

    return ['success' => true, 'user' => $user];
}

/**
 * Oturum verilerini oluştur
 */
function createSession($user)
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_phone'] = $user['phone'];
    $_SESSION['is_password_set'] = $user['is_password_set'] ?? 1;
    $_SESSION['logged_in_at'] = time();

    // Son giriş zamanını güncelle
    db()->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

    // Beni hatırla (Cookie)
    setRememberCookie($user['id']);
}

/**
 * Beni Hatırla Token Oluştur ve Cookie Yaz
 */
function setRememberCookie($userId)
{
    $token = bin2hex(random_bytes(32)); // 64 karakter

    // Tokeni veritabanına kaydet
    db()->update('users', ['remember_token' => $token], 'id = ?', [$userId]);

    // Cookie'yi 1 yıl geçerli yap
    $expiry = time() + (365 * 24 * 60 * 60);
    setcookie('remember_token', $token, $expiry, '/', '', false, true); // HttpOnly
}

/**
 * Cookie kontrolü ile otomatik giriş
 */
function checkRememberMe()
{
    // Infinite Loop Fix: Do not call isLoggedIn() here.
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
        return;

    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $user = db()->fetchOne("SELECT * FROM users WHERE remember_token = ?", [$token]);

        if ($user) {
            // Token doğru, oturum aç
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['logged_in_at'] = time();

            // Son giriş zamanını güncelle
            db()->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        }
    }
}

/**
 * Kullanıcı kayıt
 */
function register($data)
{
    $phone = normalizePhone($data['phone']);

    // Telefon kontrolü
    $exists = db()->fetchColumn("SELECT COUNT(*) FROM users WHERE phone = ?", [$phone]);
    if ($exists > 0) {
        return ['success' => false, 'error' => 'Bu telefon numarası zaten kayıtlı'];
    }

    $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);

    $userId = db()->insert('users', [
        'phone' => $phone,
        'password_hash' => $passwordHash,
        'role' => $data['role'] ?? 'driver',
        'name' => $data['name'] ?? null,
        'email' => $data['email'] ?? null,
        'is_active' => 1,
        'is_verified' => 0
    ]);

    return ['success' => true, 'user_id' => $userId];
}

/**
 * Çıkış yap
 */
function logout()
{
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Remember me cookie sil
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        // Veritabanından sil
        db()->query("UPDATE users SET remember_token = NULL WHERE remember_token = ?", [$token]);
        setcookie('remember_token', '', time() - 3600, '/');
    }

    session_destroy();
}

function isLoggedIn()
{
    // Session yoksa cookie kontrol et
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
        checkRememberMe();
    }
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function currentUserId()
{
    if (!isLoggedIn())
        return null;
    return $_SESSION['user_id'] ?? null;
}

function currentUserRole()
{
    return $_SESSION['user_role'] ?? null;
}

function isAdmin()
{
    return isLoggedIn() && currentUserRole() === 'admin';
}

function isStation()
{
    return isLoggedIn() && currentUserRole() === 'station';
}

function requireLogin($redirectUrl = null)
{
    if (!isLoggedIn()) {
        $redirectUrl = $redirectUrl ?? '/';
        setFlash('warning', 'Giriş yapmanız gerekiyor.');
        redirect($redirectUrl);
    }
}

function requireAdmin()
{
    if (!isAdmin()) {
        setFlash('error', 'Erişim yetkiniz yok.');
        redirect('/admin/login.php');
    }
}

function requireStation()
{
    if (!isStation()) {
        setFlash('error', 'Erişim yetkiniz yok.');
        redirect('/station/login.php');
    }
}

function getCurrentStation()
{
    if (!isStation())
        return null;
    return db()->fetchOne("SELECT * FROM stations WHERE user_id = ?", [currentUserId()]);
}

function normalizePhone($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 12 && substr($phone, 0, 2) === '90') {
        $phone = '0' . substr($phone, 2);
    } elseif (strlen($phone) === 10 && $phone[0] !== '0') {
        $phone = '0' . $phone;
    }
    return $phone;
}
