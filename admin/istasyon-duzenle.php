<?php
/**
 * Admin Paneli - İstasyon Düzenle
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    redirect('/admin/istasyonlar.php');
}

$station = db()->fetchOne("SELECT * FROM stations WHERE id = ?", [$id]);
if (!$station) {
    setFlash('error', 'İstasyon bulunamadı.');
    redirect('/admin/istasyonlar.php');
}

// Kullanıcılar (Sahiplik için)
$users = db()->fetchAll("SELECT id, name, phone, email, role FROM users ORDER BY name ASC");
// Mevcut Fiyatlar (Son eklenen)
$lastPrice = db()->fetchOne("SELECT * FROM fuel_prices WHERE station_id = ? ORDER BY created_at DESC LIMIT 1", [$id]);

$errors = [];
$success = '';

// Form İşlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz istek.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. İstasyon Bilgileri Güncelleme
        if ($action === 'update_info') {
            $updateData = [
                'name' => $_POST['name'],
                'brand' => $_POST['brand'] ?: null,
                'city' => $_POST['city'],
                'district' => $_POST['district'] ?: null,
                'address' => $_POST['address'] ?: null,
                'phone' => $_POST['phone'] ?: null,
                'user_id' => $_POST['user_id'], // Sahiplik devri
                'lat' => $_POST['lat'],
                'lng' => $_POST['lng']
            ];

            try {
                db()->update('stations', $updateData, 'id = ?', [$id]);
                setFlash('success', 'İstasyon bilgileri güncellendi.');
                redirect('?id=' . $id);
            } catch (Exception $e) {
                $errors[] = 'Hata: ' . $e->getMessage();
            }
        }

        // 2. Fiyat Güncelleme
        elseif ($action === 'add_price') {
            $diesel = $_POST['diesel_price'] ?: null;
            $gasoline = $_POST['gasoline_price'] ?: null;
            $lpg = $_POST['lpg_price'] ?: null;

            if ($diesel || $gasoline || $lpg) {
                try {
                    db()->insert('fuel_prices', [
                        'station_id' => $id,
                        'updated_by' => currentUserId(),
                        'diesel_price' => $diesel,
                        'gasoline_price' => $gasoline,
                        'lpg_price' => $lpg,
                        'is_approved' => 1
                    ]);
                    setFlash('success', 'Fiyatlar güncellendi.');
                    redirect('?id=' . $id);
                } catch (Exception $e) {
                    $errors[] = 'Fiyat eklenirken hata: ' . $e->getMessage();
                }
            } else {
                $errors[] = 'En az bir fiyat girmelisiniz.';
            }
        }
    }
}

$pageTitle = 'İstasyon Düzenle - Admin Paneli';
require_once __DIR__ . '/includes/header.php';
?>

<div class="panel-header">
    <div class="d-flex align-items-center gap-3">
        <a href="istasyonlar.php" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1>İstasyon Düzenle</h1>
    </div>
</div>

<div class="content-grid">
    <?php if ($flash = getFlash()): ?>
        <div class="alert alert-<?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li>
                        <?= e($err) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row" style="display: flex; gap: 20px; flex-wrap: wrap;">
        <!-- Sol Kolon: İstasyon Bilgileri -->
        <div class="col-lg-8" style="flex: 2; min-width: 300px;">
            <div class="card h-100">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> İstasyon Bilgileri</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="update_info">

                        <!-- Sahiplik -->
                        <div class="form-group mb-4 p-3 bg-gray-50 rounded border">
                            <label class="form-label font-bold text-primary">İstasyon Sahibi (Devir İşlemi)</label>
                            <select name="user_id" class="form-control select2">
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= $station['user_id'] == $u['id'] ? 'selected' : '' ?>>
                                        <?= e($u['name']) ?> (
                                        <?= e($u['phone']) ?>) -
                                        <?= $u['role'] == 'admin' ? '[Admin]' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-gray">İstasyonu başka bir kullanıcıya atamak için buradan seçim
                                yapın.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">İstasyon Adı</label>
                                <input type="text" name="name" class="form-control" value="<?= e($station['name']) ?>"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Marka</label>
                                <select name="brand" class="form-control select2">
                                    <option value="">Seçiniz</option>
                                    <?php
                                    $sortedBrands = FUEL_BRANDS;
                                    asort($sortedBrands, SORT_LOCALE_STRING);
                                    foreach ($sortedBrands as $brand):
                                        ?>
                                        <option value="<?= e($brand) ?>" <?= $station['brand'] === $brand ? 'selected' : '' ?>>
                                            <?= e($brand) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Şehir</label>
                                <select name="city" class="form-control select2" required>
                                    <?php foreach (TURKEY_CITIES as $city): ?>
                                        <option value="<?= e($city) ?>" <?= $station['city'] === $city ? 'selected' : '' ?>>
                                            <?= e($city) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">İlçe</label>
                                <input type="text" name="district" class="form-control"
                                    value="<?= e($station['district']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Adres</label>
                            <textarea name="address" class="form-control"
                                rows="2"><?= e($station['address']) ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="tel" name="phone" class="form-control" value="<?= e($station['phone']) ?>">
                            </div>
                        </div>

                        <!-- Harita -->
                        <div class="form-group mb-3">
                            <label class="form-label">Konum</label>
                            <div id="locationMap"
                                style="height: 300px; border-radius: var(--radius); border: 1px solid var(--gray-200);">
                            </div>
                            <input type="hidden" name="lat" id="latInput" value="<?= e($station['lat']) ?>">
                            <input type="hidden" name="lng" id="lngInput" value="<?= e($station['lng']) ?>">
                            <div class="mt-1 text-sm text-gray" id="locationText">
                                <?= $station['lat'] . ', ' . $station['lng'] ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Değişiklikleri Kaydet
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sağ Kolon: Fiyat Güncelleme -->
        <div class="col-lg-4" style="flex: 1; min-width: 300px;">
            <div class="card mb-4" style="position: sticky; top: 80px;">
                <div class="card-header bg-primary text-white">
                    <h3 class="text-white mb-0"><i class="fas fa-tags"></i> Fiyat Güncelle</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="add_price">

                        <div class="form-group mb-3">
                            <label class="form-label">Motorin (TL)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="diesel_price" class="form-control"
                                    value="<?= $lastPrice ? $lastPrice['diesel_price'] : '' ?>" placeholder="0.00">
                                <span class="input-group-text">₺</span>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">Benzin (TL)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="gasoline_price" class="form-control"
                                    value="<?= $lastPrice ? $lastPrice['gasoline_price'] : '' ?>" placeholder="0.00">
                                <span class="input-group-text">₺</span>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">LPG (TL)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="lpg_price" class="form-control"
                                    value="<?= $lastPrice ? $lastPrice['lpg_price'] : '' ?>" placeholder="0.00">
                                <span class="input-group-text">₺</span>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <small><i class="fas fa-info-circle"></i> Fiyatlar anında güncellenecek ve onaylı olarak
                                kaydedilecektir.</small>
                        </div>

                        <button type="submit" class="btn btn-success w-full">
                            <i class="fas fa-check"></i> Fiyatları Güncelle
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const initialLat = <?= $station['lat'] ?: '39.9334' ?>;
        const initialLng = <?= $station['lng'] ?: '32.8597' ?>;
        const zoom = 15;

        const map = L.map('locationMap').setView([initialLat, initialLng], zoom);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
        }).addTo(map);

        let marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);
        const latInput = document.getElementById('latInput');
        const lngInput = document.getElementById('lngInput');
        const locationText = document.getElementById('locationText');

        marker.on('dragend', function () {
            const pos = marker.getLatLng();
            latInput.value = pos.lat.toFixed(8);
            lngInput.value = pos.lng.toFixed(8);
            locationText.textContent = pos.lat.toFixed(6) + ', ' + pos.lng.toFixed(6);
        });

        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            latInput.value = e.latlng.lat.toFixed(8);
            lngInput.value = e.latlng.lng.toFixed(8);
            locationText.textContent = e.latlng.lat.toFixed(6) + ', ' + e.latlng.lng.toFixed(6);
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>