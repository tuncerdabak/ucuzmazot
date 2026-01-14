<?php
require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek']);
    exit;
}

$action = $_POST['action'] ?? '';
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Telefon numarası zorunludur']);
    exit;
}

try {
    if ($action === 'register') {
        if (empty($name)) {
            throw new Exception('Ad soyad zorunludur');
        }
        $result = registerDriver($name, $phone);
    } elseif ($action === 'login') {
        if (empty($password)) {
            throw new Exception('Şifre zorunludur');
        }
        $result = login($phone, $password, 'driver');
    } else {
        throw new Exception('Geçersiz işlem');
    }

    if ($result['success']) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
