<?php
/**
 * Admin Paneli - Fiyat Kontrol
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

requireAdmin();

// İşlem yap
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $priceId = (int) ($_POST['price_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'approve':
            db()->update('fuel_prices', ['is_approved' => 1], 'id = ?', [$priceId]);
            setFlash('success', 'Fiyat onaylandı.');
            break;

        case 'reject':
            db()->delete('fuel_prices', 'id = ?', [$priceId]);
            setFlash('success', 'Fiyat silindi.');
            break;
    }

    redirect('/admin/fiyat-kontrol.php');
}

// Onay bekleyen fiyatlar
$pendingPrices = db()->fetchAll("
    SELECT fp.*, s.name as station_name, s.city, u.name as updated_by_name
    FROM fuel_prices fp
    JOIN stations s ON fp.station_id = s.id
    LEFT JOIN users u ON fp.updated_by = u.id
    WHERE fp.is_approved = 0
    ORDER BY fp.created_at DESC
");

// Anormal fiyatlar (onaylı ama aralık dışı)
$abnormalPrices = db()->fetchAll("
    SELECT fp.*, s.name as station_name, s.city
    FROM fuel_prices fp
    JOIN stations s ON fp.station_id = s.id
    WHERE fp.is_approved = 1
    AND (fp.diesel_price < ? OR fp.diesel_price > ?)
    AND fp.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY fp.created_at DESC
", [DIESEL_MIN_PRICE, DIESEL_MAX_PRICE]);

$pageTitle = 'Fiyat Kontrol - Admin Paneli';
require_once __DIR__ . '/includes/header.php';
?>
<header class="panel-header">
    <h1>Fiyat Kontrol</h1>
</header>

<?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <i class="fas fa-check-circle"></i>
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Bilgi Kartları -->
<div class="stats-grid mb-5">
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--gradient-secondary);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Onay Bekleyen</span>
            <span class="stat-value">
                <?= count($pendingPrices) ?>
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Anormal Fiyat</span>
            <span class="stat-value">
                <?= count($abnormalPrices) ?>
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: var(--gradient-primary);">
            <i class="fas fa-info-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Fiyat Aralığı</span>
            <span class="stat-value" style="font-size: 1rem;">
                <?= formatPrice(defined('DIESEL_MIN_PRICE') ? DIESEL_MIN_PRICE : 40.00) ?> -
                <?= formatPrice(defined('DIESEL_MAX_PRICE') ? DIESEL_MAX_PRICE : 50.00) ?>
            </span>
        </div>
    </div>
</div>

<!-- Onay Bekleyen Fiyatlar -->
<div class="card mb-5">
    <div class="card-header">
        <h3><i class="fas fa-clock text-warning"></i> Onay Bekleyen Fiyatlar</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($pendingPrices)): ?>
            <p class="text-center text-gray p-5">Onay bekleyen fiyat yok.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>İstasyon</th>
                            <th>Şehir</th>
                            <th>Fiyat</th>
                            <th>Güncelleyen</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingPrices as $price): ?>
                            <tr>
                                <td><strong>
                                        <?= e($price['station_name']) ?>
                                    </strong></td>
                                <td>
                                    <?= e($price['city']) ?>
                                </td>
                                <td>
                                    <span class="<?= isPriceAbnormal($price['diesel_price']) ? 'text-danger' : '' ?>">
                                        <strong>
                                            <?= formatPrice($price['diesel_price']) ?>
                                        </strong>
                                    </span>
                                    <?php if (isPriceAbnormal($price['diesel_price'])): ?>
                                        <br><small class="text-danger">Aralık dışı!</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= e($price['updated_by_name'] ?? '-') ?>
                                </td>
                                <td>
                                    <?= formatDate($price['created_at']) ?>
                                </td>
                                <td class="actions">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="price_id" value="<?= $price['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-success" title="Onayla">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="price_id" value="<?= $price['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Anormal Fiyatlar -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-exclamation-triangle text-danger"></i> Anormal Fiyatlar (Son 7 Gün)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($abnormalPrices)): ?>
            <p class="text-center text-gray p-5">Anormal fiyat yok.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>İstasyon</th>
                            <th>Şehir</th>
                            <th>Fiyat</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($abnormalPrices as $price): ?>
                            <tr>
                                <td><strong>
                                        <?= e($price['station_name']) ?>
                                    </strong></td>
                                <td>
                                    <?= e($price['city']) ?>
                                </td>
                                <td>
                                    <span class="text-danger">
                                        <strong>
                                            <?= formatPrice($price['diesel_price']) ?>
                                        </strong>
                                    </span>
                                </td>
                                <td>
                                    <?= formatDate($price['created_at']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>