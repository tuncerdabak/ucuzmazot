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
$sort = $_GET['sort'] ?? 'fp.diesel_price';
$order = $_GET['order'] ?? 'ASC';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;

// Valid Sort Columns
$validSorts = ['station_name', 'city', 'brand', 'diesel_price', 'truck_diesel_price', 'gasoline_price', 'lpg_price', 'created_at', 'updater_name'];
if (!in_array($sort, $validSorts))
    $sort = 'fp.diesel_price';
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

// Toplam Kayıt (Sadece her istasyonun son fiyatı)
$sqlCount = "
    SELECT COUNT(DISTINCT s.id) 
    FROM fuel_prices fp
    JOIN stations s ON fp.station_id = s.id
    WHERE $where
";
$total = db()->fetchColumn($sqlCount, $params);

// Fiyatları Getir (Her istasyonun sadece en güncel kaydı)
$offset = ($page - 1) * $perPage;
$sql = "
    SELECT fp.*, s.name as station_name, s.brand, s.city, u.name as updater_name
    FROM fuel_prices fp
    INNER JOIN (
        SELECT station_id, MAX(id) as max_id
        FROM fuel_prices
        GROUP BY station_id
    ) latest ON fp.id = latest.max_id
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

$pageTitle = 'En Uygun Fiyatlar - Admin Paneli';
require_once __DIR__ . '/includes/header.php';
?>

<header class="panel-header">
    <h1>En Uygun Fiyatlı İstasyonlar</h1>
    <span class="text-gray">
        <?= $total ?> istasyon listeleniyor
    </span>
</header>

<div class="content-grid">
    <?php if ($flash = getFlash()): ?>
        <div class="alert alert-<?= e($flash['type']) ?>">
            <i class="fas fa-check-circle"></i>
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

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
        $orderField = $sort;
        if (strpos($sort, '.') !== false) {
            $parts = explode('.', $sort);
            $orderField = end($parts);
        }

        $newOrder = ($orderField === $col && $order === 'ASC') ? 'DESC' : 'ASC';
        $icon = '';
        if ($orderField === $col) {
            $icon = $order === 'ASC' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>';
        } else {
            $icon = ' <i class="fas fa-sort text-gray-400"></i>';
        }
        echo '<a href="javascript:void(0)" onclick="applyFilter(\'sort\', \'' . $col . '\', \'order\', \'' . $newOrder . '\')" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:5px;">' . $label . $icon . '</a>';
    }
    ?>

    <div class="card overflow-hidden">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="min-width: 150px;"><?php sortLink('station_name', 'İstasyon'); ?></th>
                        <th><?php sortLink('city', 'Şehir'); ?></th>
                        <th><?php sortLink('diesel_price', 'Motorin'); ?></th>
                        <th><?php sortLink('truck_diesel_price', 'TIR Özel'); ?></th>
                        <th><?php sortLink('gasoline_price', 'Benzin'); ?></th>
                        <th><?php sortLink('lpg_price', 'LPG'); ?></th>
                        <th class="text-right"><?php sortLink('created_at', 'Son Güncelleme'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prices)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-gray p-5">Kayıt bulunamadı.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($prices as $index => $p): ?>
                            <tr <?= ($index === 0 && $page === 1) ? 'style="background: rgba(16, 185, 129, 0.05);"' : '' ?>>
                                <td data-label="İstasyon">
                                    <div class="user-cell">
                                        <div class="user-info">
                                            <div class="user-name">
                                                <?= e($p['station_name']) ?>
                                                <?php if ($index === 0 && $page === 1): ?>
                                                    <span class="status-badge status-success"
                                                        style="font-size: 0.65rem; margin-left: 5px;">EN UCUZ</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-email">
                                                <?= e($p['brand']) ?>
                                            </div>
                                        </div>
                                    </div>
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
                                <td data-label="Tarih" class="text-right">
                                    <div class="user-info" style="align-items: flex-end;">
                                        <div class="user-name" style="font-size: 0.85rem;">
                                            <?= formatDate($p['created_at'], 'd.m.Y H:i') ?>
                                        </div>
                                        <div class="user-email" style="font-size: 0.75rem;">
                                            <?= e($p['updater_name'] ?: ($p['note'] ?: 'Sistem')) ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sayfalama -->
    <?php if ($total > $perPage): ?>
        <div class="mt-4">
            <?= paginate($total, $perPage, $page, '/admin/fiyatlar.php') ?>
        </div>
    <?php endif; ?>
</div>

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