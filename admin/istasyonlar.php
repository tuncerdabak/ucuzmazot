<?php
/**
 * Admin Paneli - İstasyon Yönetimi
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

requireAdmin();

// İşlem yap
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $stationId = (int) ($_POST['station_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'approve':
            db()->update('stations', [
                'is_approved' => 1,
                'approved_at' => date('Y-m-d H:i:s'),
                'approved_by' => currentUserId()
            ], 'id = ?', [$stationId]);
            setFlash('success', 'İstasyon onaylandı.');
            break;

        case 'reject':
            db()->update('stations', ['is_approved' => 0], 'id = ?', [$stationId]);
            setFlash('success', 'İstasyon reddedildi.');
            break;

        case 'activate':
            db()->update('stations', ['is_active' => 1], 'id = ?', [$stationId]);
            setFlash('success', 'İstasyon aktifleştirildi.');
            break;

        case 'deactivate':
            db()->update('stations', ['is_active' => 0], 'id = ?', [$stationId]);
            setFlash('success', 'İstasyon pasifleştirildi.');
            break;
    }

    redirect('/admin/istasyonlar.php');
}

// Filtreler
$filter = $_GET['filter'] ?? 'all';
$city = $_GET['city'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

// Query oluştur
$where = "1=1";
$params = [];

if ($filter === 'pending') {
    $where .= " AND s.is_approved = 0";
} elseif ($filter === 'approved') {
    $where .= " AND s.is_approved = 1";
} elseif ($filter === 'inactive') {
    $where .= " AND s.is_active = 0";
}

if ($city) {
    $where .= " AND s.city = ?";
    $params[] = $city;
}

if ($search) {
    $where .= " AND (s.name LIKE ? OR s.brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Toplam sayı
$total = db()->fetchColumn("SELECT COUNT(*) FROM stations s WHERE $where", $params);

// İstasyonlar
$offset = ($page - 1) * $perPage;
$stations = db()->fetchAll("
    SELECT s.*, u.name as owner_name, u.phone as owner_phone,
        (SELECT diesel_price FROM fuel_prices WHERE station_id = s.id ORDER BY created_at DESC LIMIT 1) as current_price
    FROM stations s
    LEFT JOIN users u ON s.user_id = u.id
    WHERE $where
    ORDER BY s.created_at DESC
    LIMIT $perPage OFFSET $offset
", $params);

// Şehirler
$cities = db()->fetchAll("SELECT DISTINCT city FROM stations ORDER BY city");

$pageTitle = 'İstasyon Yönetimi - Admin Paneli';
require_once __DIR__ . '/includes/header.php';
?>
<header class="panel-header">
    <h1>İstasyon Yönetimi</h1>
    <div class="header-actions">
        <span class="text-gray mr-3">
            <?= $total ?> istasyon
        </span>
        <a href="istasyon-ekle.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Yeni Ekle
        </a>
    </div>
</header>

<div class="content-grid">
    <?php if ($flash = getFlash()): ?>
        <div class="alert alert-<?= e($flash['type']) ?>">
            <i class="fas fa-check-circle"></i>
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Filtreler -->
    <div class="filter-bar">
        <select class="form-control" onchange="applyFilter('filter', this.value)">
            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Tümü</option>
            <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Onay Bekliyor</option>
            <option value="approved" <?= $filter === 'approved' ? 'selected' : '' ?>>Onaylı</option>
            <option value="inactive" <?= $filter === 'inactive' ? 'selected' : '' ?>>Pasif</option>
        </select>

        <select class="form-control select2" onchange="applyFilter('city', this.value)">
            <option value="">Tüm Şehirler</option>
            <?php foreach ($cities as $c): ?>
                <option value="<?= e($c['city']) ?>" <?= $city === $c['city'] ? 'selected' : '' ?>>
                    <?= e($c['city']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" class="form-control search-input" placeholder="İstasyon ara..." value="<?= e($search) ?>"
            id="searchInput">
        <button onclick="applySearch()" class="btn btn-primary">
            <i class="fas fa-search"></i>
        </button>
    </div>

    <!-- Tablo -->
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>İstasyon</th>
                            <th>Şehir</th>
                            <th>Fiyat</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stations)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-gray p-5">
                                    İstasyon bulunamadı.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stations as $st): ?>
                                <tr>
                                    <td data-label="İstasyon">
                                        <strong>
                                            <?= e($st['name']) ?>
                                        </strong>
                                        <?php if ($st['brand']): ?>
                                            <br><small class="text-gray">
                                                <?= e($st['brand']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Şehir">
                                        <?= e($st['city']) ?>
                                    </td>
                                    <td data-label="Fiyat">
                                        <?= $st['current_price'] ? formatPrice($st['current_price']) : '-' ?>
                                    </td>
                                    <td data-label="Durum">
                                        <?php if (!$st['is_active']): ?>
                                            <span class="status-badge rejected">Pasif</span>
                                        <?php elseif (!$st['is_approved']): ?>
                                            <span class="status-badge pending">Onay Bekliyor</span>
                                        <?php else: ?>
                                            <span class="status-badge approved">Onaylı</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Tarih">
                                        <?= formatDate($st['created_at'], 'd.m.Y') ?>
                                    </td>
                                    <td data-label="İşlemler" class="actions">
                                        <?php if (!$st['is_approved']): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                                <input type="hidden" name="station_id" value="<?= $st['id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success" title="Onayla">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($st['is_active']): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                                <input type="hidden" name="station_id" value="<?= $st['id'] ?>">
                                                <input type="hidden" name="action" value="deactivate">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Pasifleştir">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                                <input type="hidden" name="station_id" value="<?= $st['id'] ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="btn btn-sm btn-success" title="Aktifleştir">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <a href="istasyon-duzenle.php?id=<?= $st['id'] ?>" class="btn btn-sm btn-primary"
                                            title="Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <a href="<?= url('/istasyon-detay.php?id=' . $st['id']) ?>" class="btn btn-sm btn-outline"
                                            target="_blank" title="Görüntüle">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sayfalama -->
    <?php if ($total > $perPage): ?>
        <div class="mt-4">
            <?= paginate($total, $perPage, $page, '/admin/istasyonlar.php') ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function applyFilter(key, value) {
        const url = new URL(window.location);
        if (value) {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function applySearch() {
        const search = document.getElementById('searchInput').value;
        applyFilter('search', search);
    }

    document.getElementById('searchInput').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') applySearch();
    });
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>