<?php
/**
 * İstasyon Paneli - Profil Düzenle
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

requireStation();
$station = getCurrentStation();

if (!$station) {
    redirect('/station/login.php');
}

$error = '';
$success = '';

// Form İşleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz istek.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $brand = $_POST['brand'] ?? '';
        $city = $_POST['city'] ?? '';
        $district = trim($_POST['district'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = normalizePhone($_POST['phone'] ?? '');
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);
        
        $selectedFacilities = $_POST['facilities'] ?? [];
        $facilitiesJson = json_encode($selectedFacilities);
        
        if (empty($name) || empty($brand) || empty($city) || empty($lat) || empty($lng)) {
            $error = 'Lütfen zorunlu alanları doldurun (İsim, Marka, Şehir, Konum).';
        } else {
            db()->update('stations', [
                'name' => $name,
                'brand' => $brand,
                'city' => $city,
                'district' => $district,
                'address' => $address,
                'phone' => $phone,
                'lat' => $lat,
                'lng' => $lng,
                'facilities' => $facilitiesJson,
                'description' => trim($_POST['description'] ?? '')
            ], 'id = ?', [$station['id']]);
            
            $success = 'Profil bilgileriniz güncellendi.';
            
            // Güncel veriyi al
            $station = getCurrentStation();
        }
    }
}

$currentFacilities = $station['facilities'] ? json_decode($station['facilities'], true) : [];

$pageTitle = 'İstasyon Profili - İstasyon Paneli';
require_once __DIR__ . '/includes/header.php';
?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #mapPicker { height: 300px; width: 100%; border-radius: var(--radius); margin-top: var(--space-2); }
        .facilities-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: var(--space-3); }
        .facility-option { display: flex; align-items: center; gap: var(--space-2); padding: var(--space-2); border: 1px solid var(--gray-200); border-radius: var(--radius); cursor: pointer; transition: all 0.2s; }
        .facility-option:hover { background: var(--gray-50); }
        .facility-option input { width: 18px; height: 18px; }
    </style>

            <header class="panel-header">
                <h1>İstasyon Profili Düzenle</h1>
            </header>
            
            <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= e($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="content-grid two-column">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                
                <!-- Temel Bilgiler -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Temel Bilgiler</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">İstasyon Adı</label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?= e($station['name']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Marka</label>
                            <select name="brand" class="form-control" required>
                                <option value="">Seçiniz</option>
                                <?php foreach (FUEL_BRANDS as $brand): ?>
                                    <option value="<?= $brand ?>" <?= $station['brand'] === $brand ? 'selected' : '' ?>>
                                        <?= $brand ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label class="form-label">Şehir</label>
                                <select name="city" class="form-control" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach (TURKEY_CITIES as $city): ?>
                                        <option value="<?= $city ?>" <?= $station['city'] === $city ? 'selected' : '' ?>>
                                            <?= $city ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">İlçe</label>
                                <input type="text" name="district" class="form-control"
                                       value="<?= e($station['district']) ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Açık Adres</label>
                            <textarea name="address" class="form-control" rows="3"><?= e($station['address']) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">İstasyon Telefonu</label>
                            <input type="tel" name="phone" class="form-control"
                                   placeholder="0212..."
                                   value="<?= e($station['phone']) ?>">
                        </div>
                        
                         <div class="form-group">
                            <label class="form-label">Açıklama / Hakkımızda</label>
                            <textarea name="description" class="form-control" rows="3" 
                                      placeholder="Müşterileriniz için kısa bir açıklama..."><?= e($station['description']) ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Konum ve Olanaklar -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-map-marker-alt"></i> Konum ve Olanaklar</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Harita Konumu</label>
                            <div class="form-text mb-2">Konumunuzu değiştirmek için harita üzerinde işaretçiyi sürükleyin.</div>
                            <div id="mapPicker"></div>
                            <input type="hidden" name="lat" id="lat" value="<?= $station['lat'] ?>">
                            <input type="hidden" name="lng" id="lng" value="<?= $station['lng'] ?>">
                        </div>
                        
                        <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--gray-200);">
                        
                        <div class="form-group">
                            <label class="form-label mb-3">İstasyon Olanakları</label>
                            <div class="facilities-grid">
                                <?php foreach (STATION_FACILITIES as $key => $label): ?>
                                    <label class="facility-option">
                                        <input type="checkbox" name="facilities[]" value="<?= $key ?>"
                                            <?= in_array($key, $currentFacilities) ? 'checked' : '' ?>>
                                        <span><?= $label ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-full mt-4">
                            <i class="fas fa-save"></i>
                            Değişiklikleri Kaydet
                        </button>
                    </div>
                </div>
            </form>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= asset('js/map.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Haritayı başlat
    const map = initMap('mapPicker', {
        center: [<?= $station['lat'] ?>, <?= $station['lng'] ?>],
        zoom: 15
    });
    
    // Mevcut marker'ı ekle ve sürüklenebilir yap
    const marker = L.marker([<?= $station['lat'] ?>, <?= $station['lng'] ?>], {
        draggable: true
    }).addTo(map);
    
    // Global değişkene ata (enableLocationPicker fonksiyonu da kullanabilir ama burada manuel yapıyoruz)
    window.pickerMarker = marker;
    
    // Sürükleme bitince inputları güncelle
    marker.on('dragend', function() {
        const pos = marker.getLatLng();
        document.getElementById('lat').value = pos.lat.toFixed(6);
        document.getElementById('lng').value = pos.lng.toFixed(6);
    });
    
    // Haritaya tıklayınca marker taşı
    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        document.getElementById('lat').value = e.latlng.lat.toFixed(6);
        document.getElementById('lng').value = e.latlng.lng.toFixed(6);
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
