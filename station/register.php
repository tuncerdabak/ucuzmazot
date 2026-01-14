<?php
/**
 * İstasyon Paneli - Kayıt Sayfası
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

if (isStation()) {
    redirect('/station/');
}

$errors = [];
$formData = [
    'phone' => '',
    'name' => '',
    'email' => '',
    'station_name' => '',
    'brand' => '',
    'city' => '',
    'district' => '',
    'address' => '',
    'station_phone' => '',
    'lat' => '',
    'lng' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz istek.';
    } else {
        $formData = array_merge($formData, $_POST);

        // Validasyon
        if (empty($formData['phone']))
            $errors[] = 'Telefon numarası zorunludur.';
        if (empty($formData['password']))
            $errors[] = 'Şifre zorunludur.';
        if (strlen($formData['password'] ?? '') < 6)
            $errors[] = 'Şifre en az 6 karakter olmalı.';
        if ($formData['password'] !== $formData['password_confirm'])
            $errors[] = 'Şifreler eşleşmiyor.';
        if (empty($formData['name']))
            $errors[] = 'İsim zorunludur.';
        if (empty($formData['station_name']))
            $errors[] = 'İstasyon adı zorunludur.';
        if (empty($formData['city']))
            $errors[] = 'Şehir zorunludur.';
        if (empty($formData['lat']) || empty($formData['lng']))
            $errors[] = 'Lütfen haritadan konum seçin.';

        if (empty($errors)) {
            try {
                db()->beginTransaction();

                // Kullanıcı oluştur
                $userResult = register([
                    'phone' => $formData['phone'],
                    'password' => $formData['password'],
                    'role' => 'station',
                    'name' => $formData['name'],
                    'email' => $formData['email']
                ]);

                if (!$userResult['success']) {
                    throw new Exception($userResult['error']);
                }

                // İstasyon oluştur
                $stationId = db()->insert('stations', [
                    'user_id' => $userResult['user_id'],
                    'name' => $formData['station_name'],
                    'brand' => $formData['brand'] ?: null,
                    'address' => $formData['address'] ?: null,
                    'city' => $formData['city'],
                    'district' => $formData['district'] ?: null,
                    'lat' => $formData['lat'],
                    'lng' => $formData['lng'],
                    'phone' => $formData['station_phone'] ?: null,
                    'email' => $formData['email'] ?: null,
                    'is_approved' => 0,
                    'is_active' => 1
                ]);

                db()->commit();

                setFlash('success', 'Kayıt başarılı! İstasyonunuz onay bekliyor. Giriş yapabilirsiniz.');
                redirect('/station/login.php');

            } catch (Exception $e) {
                db()->rollback();
                $errors[] = $e->getMessage();
            }
        }
    }
}

$pageTitle = 'İstasyon Kaydı - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= e($pageTitle) ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>

<body class="auth-page register-page">
    <div class="register-container">
        <div class="register-card glass-card">
            <div class="auth-logo">
                <a href="<?= url('/') ?>">
                    <i class="fas fa-gas-pump"></i>
                    <span>
                        <?= SITE_NAME ?>
                    </span>
                </a>
            </div>

            <h1>İstasyon Kaydı</h1>
            <p class="auth-subtitle">İstasyonunuzu sisteme ekleyin</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul style="margin:0;padding-left:20px;">
                        <?php foreach ($errors as $err): ?>
                            <li>
                                <?= e($err) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" data-validate>
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <!-- Kişisel Bilgiler -->
                <fieldset class="form-section">
                    <legend><i class="fas fa-user"></i> Kişisel Bilgiler</legend>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Ad Soyad *</label>
                            <input type="text" name="name" class="form-control" required
                                value="<?= e($formData['name']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-control" value="<?= e($formData['email']) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Telefon *</label>
                            <input type="tel" name="phone" class="form-control" required placeholder="05XX XXX XX XX"
                                value="<?= e($formData['phone']) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Şifre *</label>
                            <input type="password" name="password" class="form-control" required minlength="6"
                                placeholder="En az 6 karakter">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Şifre Tekrar *</label>
                            <input type="password" name="password_confirm" class="form-control" required>
                        </div>
                    </div>
                </fieldset>

                <!-- İstasyon Bilgileri -->
                <fieldset class="form-section">
                    <legend><i class="fas fa-store"></i> İstasyon Bilgileri</legend>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">İstasyon Adı *</label>
                            <input type="text" name="station_name" class="form-control" required
                                value="<?= e($formData['station_name']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Marka</label>
                            <select name="brand" class="form-control">
                                <option value="">Seçiniz</option>
                                <?php foreach (FUEL_BRANDS as $brand): ?>
                                    <option value="<?= e($brand) ?>" <?= $formData['brand'] === $brand ? 'selected' : '' ?>>
                                        <?= e($brand) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Şehir *</label>
                            <select name="city" class="form-control" required id="citySelect">
                                <option value="">Seçiniz</option>
                                <?php foreach (TURKEY_CITIES as $city): ?>
                                    <option value="<?= e($city) ?>" <?= $formData['city'] === $city ? 'selected' : '' ?>>
                                        <?= e($city) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">İlçe</label>
                            <input type="text" name="district" class="form-control"
                                value="<?= e($formData['district']) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Adres</label>
                        <textarea name="address" class="form-control" rows="2"><?= e($formData['address']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">İstasyon Telefonu</label>
                        <input type="tel" name="station_phone" class="form-control"
                            value="<?= e($formData['station_phone']) ?>">
                    </div>
                </fieldset>

                <!-- Konum Seçimi -->
                <fieldset class="form-section">
                    <legend><i class="fas fa-map-marker-alt"></i> Konum Seçimi *</legend>
                    <p class="form-text mb-3">Haritaya tıklayarak istasyonunuzun konumunu işaretleyin.</p>

                    <div id="locationMap"
                        style="height: 300px; border-radius: var(--radius-lg); margin-bottom: var(--space-4);"></div>

                    <input type="hidden" name="lat" id="latInput" value="<?= e($formData['lat']) ?>">
                    <input type="hidden" name="lng" id="lngInput" value="<?= e($formData['lng']) ?>">

                    <div class="selected-location" id="selectedLocation"
                        style="display: <?= $formData['lat'] ? 'block' : 'none' ?>;">
                        <i class="fas fa-check-circle text-success"></i>
                        Konum seçildi: <span id="locationText">
                            <?= $formData['lat'] ?>,
                            <?= $formData['lng'] ?>
                        </span>
                    </div>
                </fieldset>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg w-full">
                        <i class="fas fa-check"></i>
                        Kayıt Ol
                    </button>
                </div>
            </form>

            <div class="auth-links">
                <a href="login.php">Zaten hesabınız var mı? Giriş yapın</a>
                <br>
                <a href="<?= url('/') ?>">← Ana Sayfaya Dön</a>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const map = L.map('locationMap').setView([39.9334, 32.8597], 6); // Türkiye merkezi
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
            }).addTo(map);

            let marker = null;
            const latInput = document.getElementById('latInput');
            const lngInput = document.getElementById('lngInput');
            const locationText = document.getElementById('locationText');
            const selectedLocation = document.getElementById('selectedLocation');

            // Mevcut konum varsa marker ekle
            if (latInput.value && lngInput.value) {
                const lat = parseFloat(latInput.value);
                const lng = parseFloat(lngInput.value);
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                map.setView([lat, lng], 14);

                marker.on('dragend', function () {
                    const pos = marker.getLatLng();
                    updateLocation(pos.lat, pos.lng);
                });
            }

            // Haritaya tıklama
            map.on('click', function (e) {
                const { lat, lng } = e.latlng;

                if (marker) {
                    marker.setLatLng([lat, lng]);
                } else {
                    marker = L.marker([lat, lng], { draggable: true }).addTo(map);

                    marker.on('dragend', function () {
                        const pos = marker.getLatLng();
                        updateLocation(pos.lat, pos.lng);
                    });
                }

                updateLocation(lat, lng);
            });

            function updateLocation(lat, lng) {
                latInput.value = lat.toFixed(8);
                lngInput.value = lng.toFixed(8);
                locationText.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
                selectedLocation.style.display = 'block';
            }
        });
    </script>

    <style>
        .register-page {
            padding: var(--space-8) var(--space-4);
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('../banner.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
        }

        .register-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .register-card {
            padding: var(--space-6);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-xl);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .form-section {
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
            margin-bottom: var(--space-5);
        }

        .form-section legend {
            font-weight: 600;
            padding: 0 var(--space-2);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4);
        }

        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .selected-location {
            padding: var(--space-3);
            background: var(--gray-50);
            border-radius: var(--radius);
            font-size: 0.875rem;
        }
    </style>
</body>

</html>