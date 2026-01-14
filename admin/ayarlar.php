<?php
/**
 * Admin Paneli - Site Ayarları
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

requireAdmin();

// Ayarları kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $settings = $_POST['settings'] ?? [];

    foreach ($settings as $key => $value) {
        $exists = db()->fetchColumn("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?", [$key]);

        if ($exists) {
            db()->update('site_settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
        } else {
            db()->insert('site_settings', ['setting_key' => $key, 'setting_value' => $value]);
        }
    }

    setFlash('success', 'Ayarlar kaydedildi.');
    redirect('/admin/ayarlar.php');
}

// Mevcut ayarları al
$settings = [];
$rows = db()->fetchAll("SELECT setting_key, setting_value FROM site_settings");
foreach ($rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Site Ayarları - Admin Paneli';
require_once __DIR__ . '/includes/header.php';
?>
            <header class="panel-header">
                <h1>Site Ayarları</h1>
            </header>

            <?php if ($flash = getFlash()): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <i class="fas fa-check-circle"></i>
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <!-- Genel Ayarlar -->
                <div class="card mb-5">
                    <div class="card-header">
                        <h3><i class="fas fa-globe"></i> Genel Ayarlar</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Site Adı</label>
                            <input type="text" name="settings[site_name]" class="form-control"
                                value="<?= e($settings['site_name'] ?? SITE_NAME) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Site Başlığı</label>
                            <input type="text" name="settings[site_title]" class="form-control"
                                value="<?= e($settings['site_title'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Site Açıklaması</label>
                            <textarea name="settings[site_description]" class="form-control"
                                rows="2"><?= e($settings['site_description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">İletişim E-postası</label>
                            <input type="email" name="settings[contact_email]" class="form-control"
                                value="<?= e($settings['contact_email'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">İletişim Telefonu</label>
                            <input type="tel" name="settings[contact_phone]" class="form-control"
                                value="<?= e($settings['contact_phone'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Fiyat Ayarları -->
                <div class="card mb-5">
                    <div class="card-header">
                        <h3><i class="fas fa-lira-sign"></i> Fiyat Kontrol Ayarları</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row"
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-4);">
                            <div class="form-group">
                                <label class="form-label">Minimum Fiyat Uyarı Eşiği (₺)</label>
                                <input type="number" step="0.01" name="settings[min_price_alert]" class="form-control"
                                    value="<?= e($settings['min_price_alert'] ?? (defined('DIESEL_MIN_PRICE') ? DIESEL_MIN_PRICE : 40.00)) ?>">
                                <div class="form-text">Bu fiyatın altındaki değerler anormal kabul edilir.</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Maksimum Fiyat Uyarı Eşiği (₺)</label>
                                <input type="number" step="0.01" name="settings[max_price_alert]" class="form-control"
                                    value="<?= e($settings['max_price_alert'] ?? (defined('DIESEL_MAX_PRICE') ? DIESEL_MAX_PRICE : 50.00)) ?>">
                                <div class="form-text">Bu fiyatın üstündeki değerler anormal kabul edilir.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Harita Ayarları -->
                <div class="card mb-5">
                    <div class="card-header">
                        <h3><i class="fas fa-map"></i> Harita Ayarları</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row"
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-4);">
                            <div class="form-group">
                                <label class="form-label">Varsayılan Enlem</label>
                                <input type="text" name="settings[map_default_lat]" class="form-control"
                                    value="<?= e($settings['map_default_lat'] ?? MAP_DEFAULT_LAT) ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Varsayılan Boylam</label>
                                <input type="text" name="settings[map_default_lng]" class="form-control"
                                    value="<?= e($settings['map_default_lng'] ?? MAP_DEFAULT_LNG) ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Varsayılan Zoom</label>
                                <input type="number" name="settings[map_default_zoom]" class="form-control"
                                    value="<?= e($settings['map_default_zoom'] ?? MAP_DEFAULT_ZOOM) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Varsayılan Şehir</label>
                            <select name="settings[default_city]" class="form-control">
                                <?php foreach (TURKEY_CITIES as $city): ?>
                                    <option value="<?= e($city) ?>" <?= ($settings['default_city'] ?? 'İstanbul') === $city ? 'selected' : '' ?>>
                                        <?= e($city) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i>
                    Ayarları Kaydet
                </button>
            </form>
<?php require_once __DIR__ . '/includes/footer.php'; ?>