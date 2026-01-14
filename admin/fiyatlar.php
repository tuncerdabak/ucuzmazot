<?php
/**
 * Admin Paneli - Fiyat Yönetimi
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

requireAdmin();

// Başlangıç Filtreleri
$stationId = $_GET['station_id'] ?? '';
$city = $_GET['city'] ?? '';
$brand = $_GET['brand'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;

// Valid Sort Columns
$validSorts = ['station_name', 'city', 'brand', 'diesel_price', 'truck_diesel_price', 'gasoline_price', 'lpg_price', 'created_at', 'user_name'];
if (!in_array($sort, $validSorts))
    $sort = 'created_at';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Query
$where = "1=1";
$params = [];

if ($city) {
    $where .= " AND s.city = ?";
    $params[] = $city;
}

if ($brand) {
    $where .= " AND s.brand = ?";
    $params[] = $brand;
}

if ($search) {
    $where .= " AND (s.name LIKE ? OR s.brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Toplam Kayıt
$sqlCount = "
    SELECT COUNT(*) 
    FROM fuel_prices fp
    JOIN stations s ON fp.station_id = s.id
    WHERE $where
";
$total = db()->fetchColumn($sqlCount, $params);

// Fiyatları Getir
$offset = ($page - 1) * $perPage;
$sql = "
    SELECT fp.*, s.name as station_name, s.brand, s.city, u.name as updater_name
    FROM fuel_prices fp
    JOIN stations s ON fp.station_id = s.id
    LEFT JOIN users u ON fp.updated_by = u.id
    WHERE $where
    ORDER BY $sort $order
    LIMIT $perPage OFFSET $offset
";
$prices = db()->fetchAll($sql, $params);

// Şehir ve Marka Listesi
$cities = db()->fetchAll("SELECT DISTINCT city FROM stations WHERE city != '' ORDER BY city");
$brands = db()->fetchAll("SELECT DISTINCT brand FROM stations WHERE brand != '' ORDER BY brand");

$pageTitle = 'Fiyat Yönetimi - Admin Paneli';
require_once __DIR__ . '/includes/header.php';
?>

<header class="panel-header">
    <h1>Fiyat Geçmişi</h1>
    <span class="text-gray">
        <?= $total ?> fiyat kaydı
    </span>
</header>

<div class="filter-bar">
    <select class="form-control" onchange="applyFilter('city', this.value)">
        <option value="">Tüm Şehirler</option>
        <?php foreach ($cities as $c): ?>
            <option value="<?= e($c['city']) ?>" <?= $city === $c['city'] ? 'selected' : '' ?>>
                <?= e($c['city']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select class="form-control" onchange="applyFilter('brand', this.value)">
        <option value="">Tüm Markalar</option>
        <?php foreach ($brands as $b): ?>
            <option value="<?= e($b['brand']) ?>" <?= $brand === $b['brand'] ? 'selected' : '' ?>>
                <?= e($b['brand']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="text" class="form-control search-input" placeholder="İstasyon ara..." value="<?= e($search) ?>"
        id="searchInput">
    <button onclick="applySearch()" class="btn btn-primary">
        <i class="fas fa-search"></i>
    </button>
</div>

<?php
function sortLink($col, $label)
{
    global $sort, $order;
    $newOrder = ($sort === $col && $order === 'ASC') ? 'DESC' : 'ASC';
    $icon = '';
    if ($sort === $col) {
        $icon = $order === 'ASC' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>';
    } else {
        $icon = ' <i class="fas fa-sort text-gray-400"></i>';
    }
    echo '<a href="javascript:void(0)" onclick="applyFilter(\'sort\', \'' . $col . '\', \'order\', \'' . $newOrder . '\')" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:5px;">' . $label . $icon . '</a>';
}
?>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="min-width: 150px;"><?php sortLink('station_name', 'İstasyon'); ?></th>
                        <th><?php sortLink('city', 'Şehir'); ?></th>
                        <th><?php sortLink('diesel_price', 'Motorin'); ?></th>
                        <th><?php sortLink('truck_diesel_price', 'TIR Özel'); ?></th>
                        <th><?php sortLink('gasoline_price', 'Benzin'); ?></th>
                        <th><?php sortLink('lpg_price', 'LPG'); ?></th>
                        <th><?php sortLink('updater_name', 'Güncelleyen'); ?></th>
                        <th><?php sortLink('created_at', 'Tarih'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prices)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-gray p-5">Kayıt bulunamadı.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($prices as $p): ?>
                            <tr>
                                <td data-label="İstasyon">
                                    <strong>
                                        <?= e($p['station_name']) ?>
                                    </strong>
                                    <br><small class="text-gray">
                                        <?= e($p['brand']) ?>
                                    </small>
                                </td>
                                <td data-label="Şehir">
                                    <?= e($p['city']) ?>
                                </td>
                                <td data-label="Motorin">
                                    <?php if ($p['diesel_price']): ?>
                                        <div class="price-tag">
                                            <span class="digital-price"><?= number_format($p['diesel_price'], 2, ',', '.') ?></span>
                                            <span class="currency">TL</span>
                                        </div>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td data-label="TIR Özel">
                                    <?php if ($p['truck_diesel_price']): ?>
                                        <div class="price-tag" style="color: #b91c1c;">
                                            <span class="digital-price"
                                                style="color: inherit;"><?= number_format($p['truck_diesel_price'], 2, ',', '.') ?></span>
                                            <span class="currency" style="color: inherit;">TL</span>
                                        </div>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td data-label="Benzin">
                                    <?php if ($p['gasoline_price']): ?>
                                        <div class="price-tag">
                                            <span
                                                class="digital-price"><?= number_format($p['gasoline_price'], 2, ',', '.') ?></span>
                                            <span class="currency">TL</span>
                                        </div>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td data-label="LPG">
                                    <?php if ($p['lpg_price']): ?>
                                        <div class="price-tag">
                                            <span class="digital-price"><?= number_format($p['lpg_price'], 2, ',', '.') ?></span>
                                            <span class="currency">TL</span>
                                        </div>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td data-label="Güncelleyen">
                                    <span title="User ID: <?= $p['updated_by'] ?: 'System' ?>">
                                        <?= e($p['updater_name'] ?: 'Misafir/IP: ' . ($p['note'] ?? 'Sistem')) ?>
                                    </span>
                                </td>
                                <td data-label="Tarih">
                                    <?= formatDate($p['created_at'], 'd.m.Y H:i') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($total > $perPage): ?>
    <div class="mt-4">
        <?= paginate($total, $perPage, $page, '/admin/fiyatlar.php') ?>
    </div>
<?php endif; ?>

<script>
    function applyFilter(key, value, secondKey, secondValue) {
        const url = new URL(window.location);
        if (value) url.searchParams.set(key, value);
        else url.searchParams.delete(key);

        if (secondKey && secondValue) {
            url.searchParams.set(secondKey, secondValue);
        }

        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function applySearch() {
        const search = document.getElementById('searchInput').value;
        applyFilter('search', search);
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>