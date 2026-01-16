<?php
/**
 * İstasyon Paneli - Fiyat Güncelleme
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

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz istek.';
    } elseif (!checkRateLimit('price_update_' . $station['id'], 10, 3600)) {
        $error = 'Çok sık fiyat güncelliyorsunuz. Lütfen bir saat sonra tekrar deneyin.';
    } else {
        $newDiesel = floatval($_POST['diesel_price'] ?? 0);
        $newTruckDiesel = floatval($_POST['truck_diesel_price'] ?? 0);
        $newGasoline = floatval($_POST['gasoline_price'] ?? 0);
        $newLpg = floatval($_POST['lpg_price'] ?? 0);
        $note = trim($_POST['note'] ?? '');

        // Fiyat kontrol
        if ($newDiesel <= 0 && $newGasoline <= 0 && $newLpg <= 0) {
            $error = 'En az bir yakıt fiyatı giriniz.';
        } else {
            // Mevcut fiyatları al
            $currentData = db()->fetchOne(
                "SELECT * FROM fuel_prices WHERE station_id = ? ORDER BY created_at DESC LIMIT 1",
                [$station['id']]
            );

            // Anormallik kontrolü
            $isAbnormal = false;

            // Fiyat aralıkları (Config'den)
            if ($newDiesel > 0 && ($newDiesel < DIESEL_MIN_PRICE || $newDiesel > DIESEL_MAX_PRICE))
                $isAbnormal = true;
            if ($newGasoline > 0 && ($newGasoline < GASOLINE_MIN_PRICE || $newGasoline > GASOLINE_MAX_PRICE))
                $isAbnormal = true;
            if ($newLpg > 0 && ($newLpg < LPG_MIN_PRICE || $newLpg > LPG_MAX_PRICE))
                $isAbnormal = true;

            // Fiyat ekle
            db()->insert('fuel_prices', [
                'station_id' => $station['id'],
                'diesel_price' => $newDiesel > 0 ? $newDiesel : ($currentData['diesel_price'] ?? null),
                'truck_diesel_price' => $newTruckDiesel > 0 ? $newTruckDiesel : ($currentData['truck_diesel_price'] ?? null),
                'gasoline_price' => $newGasoline > 0 ? $newGasoline : ($currentData['gasoline_price'] ?? null),
                'lpg_price' => $newLpg > 0 ? $newLpg : ($currentData['lpg_price'] ?? null),
                'updated_by' => currentUserId(),
                'is_approved' => $isAbnormal ? 0 : 1,
                'note' => $note ?: null
            ]);

            // Fiyat geçmişi log
            db()->insert('price_history', [
                'station_id' => $station['id'],
                'old_price' => $currentData['diesel_price'] ?? null,
                'new_price' => $newDiesel > 0 ? $newDiesel : ($currentData['diesel_price'] ?? null),
                'gasoline_old' => $currentData['gasoline_price'] ?? null,
                'gasoline_new' => $newGasoline > 0 ? $newGasoline : ($currentData['gasoline_price'] ?? null),
                'lpg_old' => $currentData['lpg_price'] ?? null,
                'lpg_new' => $newLpg > 0 ? $newLpg : ($currentData['lpg_price'] ?? null),
                'changed_by' => currentUserId(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            logActivity('price_update', 'station', $station['id'], [
                'diesel' => $newDiesel,
                'truck_diesel' => $newTruckDiesel,
                'gasoline' => $newGasoline,
                'lpg' => $newLpg
            ]);

            if ($isAbnormal) {
                $success = 'Fiyat kaydedildi ancak onay bekliyor (normal aralık dışında).';
            } else {
                $success = 'Fiyat başarıyla güncellendi!';
            }
        }
    }
}

// Mevcut fiyat
$currentData = db()->fetchOne(
    "SELECT * FROM fuel_prices WHERE station_id = ? ORDER BY created_at DESC LIMIT 1",
    [$station['id']]
);

// Fiyat geçmişi
$priceHistory = db()->fetchAll(
    "SELECT ph.*, u.name as changed_by_name 
     FROM price_history ph 
     LEFT JOIN users u ON ph.changed_by = u.id 
     WHERE ph.station_id = ? 
     ORDER BY ph.changed_at DESC 
     LIMIT 20",
    [$station['id']]
);

$pageTitle = 'Fiyat Güncelle - İstasyon Paneli';
require_once __DIR__ . '/includes/header.php';
?>

<header class="panel-header">
    <h1>Fiyat Güncelleme</h1>
</header>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= e($success) ?></div>
<?php endif; ?>

<div class="content-grid two-column">
    <!-- Fiyat Güncelleme Formu -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-gas-pump"></i> Güncel Fiyatları Girin</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <!-- MAZOT -->
                <div class="form-group">
                    <label class="form-label d-flex justify-content-between">
                        <span>Motorin (Mazot)</span>
                        <span class="text-sm text-gray">Mevcut:
                            <?= formatPrice($currentData['diesel_price'] ?? 0) ?></span>
                    </label>
                    <div class="price-input-group">
                        <input type="number" name="diesel_price" step="0.01" min="0" max="100" class="form-control"
                            placeholder="Örn: 42.50" value="<?= $currentData ? $currentData['diesel_price'] : '' ?>">
                        <span class="currency">₺</span>
                    </div>
                    <div class="price-range-info">
                        Normal Aralık: <?= formatPrice(DIESEL_MIN_PRICE) ?> -
                        <?= formatPrice(DIESEL_MAX_PRICE) ?>
                    </div>
                </div>

                <!-- TIR ÖZEL MAZOT -->
                <div class="form-group"
                    style="background: #fdf2f2; border-radius: var(--radius); padding: var(--space-3); border: 1px dashed #ef4444; margin-bottom: var(--space-4);">
                    <label class="form-label d-flex justify-content-between">
                        <strong style="color: #b91c1c;"><i class="fas fa-truck"></i> TIR ÖZEL FİYATI
                            (Mazot)</strong>
                        <span class="text-sm text-gray">Mevcut:
                            <?= formatPrice($currentData['truck_diesel_price'] ?? 0) ?></span>
                    </label>
                    <div class="price-input-group">
                        <input type="number" name="truck_diesel_price" step="0.01" min="0" max="100"
                            class="form-control" placeholder="Örn: 41.50" style="border-color: #f87171;"
                            value="<?= $currentData ? $currentData['truck_diesel_price'] : '' ?>">
                        <span class="currency">₺</span>
                    </div>
                    <small class="text-gray d-block mt-1">Sadece tır şoförlerine özel fiyatınız varsa
                        girin.</small>
                </div>

                <!-- BENZİN -->
                <div class="form-group">
                    <label class="form-label d-flex justify-content-between">
                        <span>Kurşunsuz Benzin</span>
                        <span class="text-sm text-gray">Mevcut:
                            <?= formatPrice($currentData['gasoline_price'] ?? 0) ?></span>
                    </label>
                    <div class="price-input-group">
                        <input type="number" name="gasoline_price" step="0.01" min="0" max="100" class="form-control"
                            placeholder="Örn: 43.50" value="<?= $currentData ? $currentData['gasoline_price'] : '' ?>">
                        <span class="currency">₺</span>
                    </div>
                    <div class="price-range-info">
                        Normal Aralık: <?= formatPrice(GASOLINE_MIN_PRICE) ?> -
                        <?= formatPrice(GASOLINE_MAX_PRICE) ?>
                    </div>
                </div>

                <!-- LPG -->
                <div class="form-group">
                    <label class="form-label d-flex justify-content-between">
                        <span>LPG (Otogaz)</span>
                        <span class="text-sm text-gray">Mevcut:
                            <?= formatPrice($currentData['lpg_price'] ?? 0) ?></span>
                    </label>
                    <div class="price-input-group">
                        <input type="number" name="lpg_price" step="0.01" min="0" max="100" class="form-control"
                            placeholder="Örn: 22.50" value="<?= $currentData ? $currentData['lpg_price'] : '' ?>">
                        <span class="currency">₺</span>
                    </div>
                    <div class="price-range-info">
                        Normal Aralık: <?= formatPrice(LPG_MIN_PRICE) ?> - <?= formatPrice(LPG_MAX_PRICE) ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Not (opsiyonel)</label>
                    <input type="text" name="note" class="form-control" placeholder="Örn: Kampanya fiyatı">
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-full">
                    <i class="fas fa-save"></i>
                    Fiyatları Güncelle
                </button>
            </form>
        </div>
    </div>

    <!-- Fiyat Geçmişi -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Son Değişiklikler</h3>
        </div>
        <div class="card-body">
            <?php if (empty($priceHistory)): ?>
                <p class="text-center text-gray">Henüz fiyat geçmişi yok.</p>
            <?php else: ?>
                <div class="price-history-list">
                    <?php foreach ($priceHistory as $history): ?>
                        <div class="history-item">
                            <div class="history-header">
                                <span class="history-date"><?= formatDate($history['changed_at']) ?></span>
                                <span class="history-user"><?= e($history['changed_by_name'] ?? 'Sistem') ?></span>
                            </div>
                            <div class="history-details">
                                <!-- Mazot -->
                                <div class="fuel-change">
                                    <span class="fuel-type">Mazot:</span>
                                    <span class="fuel-val"><?= formatPrice($history['new_price']) ?> ₺</span>
                                </div>
                                <!-- Benzin -->
                                <?php if ($history['gasoline_new']): ?>
                                    <div class="fuel-change">
                                        <span class="fuel-type">Benzin:</span>
                                        <span class="fuel-val"><?= formatPrice($history['gasoline_new']) ?> ₺</span>
                                    </div>
                                <?php endif; ?>
                                <!-- LPG -->
                                <?php if ($history['lpg_new']): ?>
                                    <div class="fuel-change">
                                        <span class="fuel-type">LPG:</span>
                                        <span class="fuel-val"><?= formatPrice($history['lpg_new']) ?> ₺</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .price-input-group {
        position: relative;
    }

    .price-input-group input {
        padding-right: 30px;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .price-input-group .currency {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-500);
        font-weight: 600;
    }

    .price-range-info {
        font-size: 0.75rem;
        color: var(--gray-500);
        margin-top: 4px;
    }

    .history-item {
        border-bottom: 1px solid var(--gray-100);
        padding: var(--space-3) 0;
    }

    .history-header {
        display: flex;
        justify-content: space-between;
        font-size: 0.8125rem;
        color: var(--gray-500);
        margin-bottom: var(--space-2);
    }

    .history-details {
        display: flex;
        gap: var(--space-3);
        flex-wrap: wrap;
    }

    .fuel-change {
        background: var(--gray-50);
        padding: 2px 6px;
        border-radius: var(--radius-sm);
        font-size: 0.875rem;
    }

    .fuel-type {
        color: var(--gray-600);
        margin-right: 4px;
    }

    .fuel-val {
        font-weight: 600;
        color: var(--primary);
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>