<?php
/**
 * Admin Paneli - İstasyon Ekle
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

requireAdmin();

// Tüm kullanıcıları getir (sahip seçimi için)
$users = db()->fetchAll("SELECT id, name, phone, email FROM users ORDER BY name ASC");

$errors = [];
$formData = [
    'user_id' => currentUserId(), // Varsayılan: Kendisi
    'name' => '',
    'brand' => '',
    'city' => 'İstanbul',
    'district' => '',
    'address' => '',
    'phone' => '',
    'email' => '',
    'lat' => '',
    'lng' => '',
    'diesel_price' => '',
    'gasoline_price' => '',
    'lpg_price' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz istek.';
    } else {
        $formData = array_merge($formData, $_POST);

        if (empty($formData['name']))
            $errors[] = 'İstasyon adı zorunludur.';
        if (empty($formData['city']))
            $errors[] = 'Şehir zorunludur.';
        if (empty($formData['lat']) || empty($formData['lng']))
            $errors[] = 'Lütfen konum seçin.';
        if (empty($formData['user_id']))
            $errors[] = 'İstasyon sahibi seçilmelidir.';

        if (empty($errors)) {
            try {
                db()->beginTransaction();

                $stationId = db()->insert('stations', [
                    'user_id' => $formData['user_id'],
                    'name' => $formData['name'],
                    'brand' => $formData['brand'] ?: null,
                    'address' => $formData['address'] ?: null,
                    'city' => $formData['city'],
                    'district' => $formData['district'] ?: null,
                    'lat' => $formData['lat'],
                    'lng' => $formData['lng'],
                    'phone' => $formData['phone'] ?: null,
                    'email' => $formData['email'] ?: null,
                    'is_approved' => 1, // Admin eklediği için onaylı
                    'is_active' => 1,
                    'approved_at' => date('Y-m-d H:i:s'),
                    'approved_by' => currentUserId()
                ]);

                // Fiyat bilgisi girilmişse kaydet
                if (!empty($formData['diesel_price']) || !empty($formData['gasoline_price']) || !empty($formData['lpg_price'])) {
                    db()->insert('fuel_prices', [
                        'station_id' => $stationId,
                        'diesel_price' => !empty($formData['diesel_price']) ? str_replace(',', '.', $formData['diesel_price']) : null,
                        'gasoline_price' => !empty($formData['gasoline_price']) ? str_replace(',', '.', $formData['gasoline_price']) : null,
                        'lpg_price' => !empty($formData['lpg_price']) ? str_replace(',', '.', $formData['lpg_price']) : null,
                        'updated_by' => currentUserId(),
                        'is_approved' => 1
                    ]);
                }

                db()->commit();

                setFlash('success', 'İstasyon ve fiyat bilgileri başarıyla oluşturuldu.');
                redirect('/admin/istasyonlar.php');
            } catch (Exception $e) {
                if (db()->inTransaction()) db()->rollBack();
                $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Yeni İstasyon Ekle - Admin Paneli';
require_once __DIR__ . '/includes/header.php';
?>

<div class="panel-header">
    <h1>Yeni İstasyon Ekle</h1>
    <a href="istasyonlar.php" class="btn btn-outline btn-sm">
        <i class="fas fa-arrow-left"></i> Geri Dön
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mb-4">
                <ul class="mb-0 pl-3">
                    <?php foreach ($errors as $err): ?>
                        <li>
                            <?= e($err) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <!-- İstasyon Sahibi -->
            <div class="form-group mb-4">
                <label class="form-label">İstasyon Sahibi</label>
                <select name="user_id" class="form-control select2">
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $formData['user_id'] == $u['id'] ? 'selected' : '' ?>>
                            <?= e($u['name']) ?> (
                            <?= e($u['phone']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-gray">İstasyonu yönetecek kullanıcıyı seçin.</small>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label">İstasyon Adı *</label>
                        <input type="text" name="name" class="form-control" required
                            value="<?= e($formData['name']) ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
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
            </div>

            <!-- Fiyat Bilgileri -->
            <div class="row bg-light p-3 rounded mb-4 mx-0">
                <div class="col-12 mb-2">
                    <h5 class="mb-0">Açılış Fiyatları</h5>
                    <small class="text-gray">İstasyon için başlangıç fiyatlarını girebilirsiniz.</small>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="form-label">Mazot Fiyatı</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="diesel_price" class="form-control" placeholder="0.00" value="<?= e($formData['diesel_price']) ?>">
                            <span class="input-group-text">TL</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="form-label">Benzin Fiyatı</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="gasoline_price" class="form-control" placeholder="0.00" value="<?= e($formData['gasoline_price']) ?>">
                            <span class="input-group-text">TL</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="form-label">LPG Fiyatı</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="lpg_price" class="form-control" placeholder="0.00" value="<?= e($formData['lpg_price']) ?>">
                            <span class="input-group-text">TL</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
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
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label">İlçe</label>
                        <input type="text" name="district" class="form-control" value="<?= e($formData['district']) ?>">
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label">Adres</label>
                <textarea name="address" class="form-control" rows="2"><?= e($formData['address']) ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="tel" name="phone" class="form-control" value="<?= e($formData['phone']) ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" value="<?= e($formData['email']) ?>">
                    </div>
                </div>
            </div>

            <!-- Harita -->
            <div class="form-group mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Konum *</label>
                    <button type="button" id="btnGetLocation" class="btn btn-sm btn-info">
                        <i class="fas fa-map-marker-alt"></i> Konumumu Bul
                    </button>
                </div>
                <div id="locationMap"
                    style="height: 400px; border-radius: var(--radius); border: 1px solid var(--gray-200);"></div>
                <input type="hidden" name="lat" id="latInput" value="<?= e($formData['lat']) ?>">
                <input type="hidden" name="lng" id="lngInput" value="<?= e($formData['lng']) ?>">
                <div class="mt-2 text-sm text-gray" id="locationText">
                    <?= $formData['lat'] ? $formData['lat'] . ', ' . $formData['lng'] : 'Haritadan konum seçin.' ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Kaydet ve Oluştur
            </button>
        </form>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Harita Başlangıcı
        const initialLat = <?= $formData['lat'] ?: '39.9334' ?>;
        const initialLng = <?= $formData['lng'] ?: '32.8597' ?>;
        const zoom = <?= $formData['lat'] ? '15' : '6' ?>;

        const map = L.map('locationMap').setView([initialLat, initialLng], zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let marker = null;
        if (<?= $formData['lat'] ? 'true' : 'false' ?>) {
            marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);
            marker.on('dragend', function () {
                const pos = marker.getLatLng();
                updateInputs(pos.lat, pos.lng);
            });
        }

        const latInput = document.getElementById('latInput');
        const lngInput = document.getElementById('lngInput');
        const locationText = document.getElementById('locationText');
        const btnGetLocation = document.getElementById('btnGetLocation');

        function updateMarker(lat, lng, zoomTo = false) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', function () {
                    const pos = marker.getLatLng();
                    updateInputs(pos.lat, pos.lng);
                });
            }
            if (zoomTo) {
                map.setView([lat, lng], 17);
            }
            updateInputs(lat, lng);
        }

        function updateInputs(lat, lng) {
            latInput.value = lat.toFixed(8);
            lngInput.value = lng.toFixed(8);
            locationText.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
        }

        map.on('click', function (e) {
            updateMarker(e.latlng.lat, e.latlng.lng);
        });

        // Konumumu Bul
        btnGetLocation.addEventListener('click', function() {
            if (navigator.geolocation) {
                btnGetLocation.disabled = true;
                btnGetLocation.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aranıyor...';
                
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    updateMarker(lat, lng, true);
                    
                    btnGetLocation.disabled = false;
                    btnGetLocation.innerHTML = '<i class="fas fa-map-marker-alt"></i> Konumumu Bul';
                }, function(error) {
                    alert('Konum alınamadı: ' + error.message);
                    btnGetLocation.disabled = false;
                    btnGetLocation.innerHTML = '<i class="fas fa-map-marker-alt"></i> Konumumu Bul';
                }, {
                    enableHighAccuracy: true
                });
            } else {
                alert('Tarayıcınız konum özelliğini desteklemiyor.');
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>