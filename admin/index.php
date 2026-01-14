<?php
/**
 * Admin Paneli - Dashboard
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

requireAdmin();

// İstatistikler
$stats = [
    'total_stations' => db()->fetchColumn("SELECT COUNT(*) FROM stations"),
    'approved_stations' => db()->fetchColumn("SELECT COUNT(*) FROM stations WHERE is_approved = 1"),
    'pending_stations' => db()->fetchColumn("SELECT COUNT(*) FROM stations WHERE is_approved = 0"),
    'total_users' => db()->fetchColumn("SELECT COUNT(*) FROM users WHERE role != 'admin'"),
    'total_reviews' => db()->fetchColumn("SELECT COUNT(*) FROM reviews"),
    'today_prices' => db()->fetchColumn("SELECT COUNT(*) FROM fuel_prices WHERE DATE(created_at) = CURDATE()"),
];

// Onay bekleyen istasyonlar
$pendingStations = db()->fetchAll("
    SELECT s.*, u.name as owner_name, u.phone as owner_phone
    FROM stations s
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.is_approved = 0
    ORDER BY s.created_at DESC
    LIMIT 5
");

// Anormal fiyatlar
$abnormalPrices = db()->fetchAll("
    SELECT fp.*, s.name as station_name
    FROM fuel_prices fp
    JOIN stations s ON fp.station_id = s.id
    WHERE fp.is_approved = 0
    ORDER BY fp.created_at DESC
    LIMIT 5
");

// Son aktiviteler
$recentActivity = db()->fetchAll("
    SELECT al.*, u.name as user_name
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
");

$pageTitle = 'Dashboard - Admin Paneli';
require_once __DIR__ . '/includes/header.php';
?>
<header class="panel-header">
    <h1>Dashboard</h1>
    <span class="welcome-text">Hoş geldin,
        <?= e($_SESSION['user_name']) ?>
    </span>
</header>

<?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'info-circle' ?>"></i>
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--gradient-primary);">
            <i class="fas fa-gas-pump"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Toplam İstasyon</span>
            <span class="stat-value">
                <?= $stats['total_stations'] ?>
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: var(--gradient-success);">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Onaylı</span>
            <span class="stat-value">
                <?= $stats['approved_stations'] ?>
            </span>
        </div>
    </div>

    <div class="stat-card warning">
        <div class="stat-icon" style="background: var(--gradient-secondary);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Onay Bekliyor</span>
            <span class="stat-value">
                <?= $stats['pending_stations'] ?>
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Kullanıcılar</span>
            <span class="stat-value">
                <?= $stats['total_users'] ?>
            </span>
        </div>
    </div>
</div>

<div class="content-grid">
    <!-- Onay Bekleyen İstasyonlar -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock text-warning"></i> Onay Bekleyen İstasyonlar</h3>
            <a href="istasyonlar.php?filter=pending" class="btn btn-sm btn-outline">Tümü</a>
        </div>
        <div class="card-body">
            <?php if (empty($pendingStations)): ?>
                <p class="text-center text-gray">Onay bekleyen istasyon yok.</p>
            <?php else: ?>
                <div class="pending-list">
                    <?php foreach ($pendingStations as $st): ?>
                        <div class="pending-item">
                            <div class="pending-info">
                                <strong>
                                    <?= e($st['name']) ?>
                                </strong>
                                <span>
                                    <?= e($st['city']) ?> -
                                    <?= e($st['owner_name']) ?>
                                </span>
                            </div>
                            <div class="pending-actions">
                                <form action="istasyonlar.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="station_id" value="<?= $st['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <a href="istasyonlar.php?id=<?= $st['id'] ?>" class="btn btn-sm btn-outline">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Anormal Fiyatlar -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-exclamation-triangle text-danger"></i> Anormal Fiyatlar</h3>
            <a href="fiyat-kontrol.php" class="btn btn-sm btn-outline">Tümü</a>
        </div>
        <div class="card-body">
            <?php if (empty($abnormalPrices)): ?>
                <p class="text-center text-gray">Anormal fiyat yok.</p>
            <?php else: ?>
                <div class="pending-list">
                    <?php foreach ($abnormalPrices as $fp): ?>
                        <div class="pending-item">
                            <div class="pending-info">
                                <strong>
                                    <?= e($fp['station_name']) ?>
                                </strong>
                                <span class="text-danger">
                                    <?= formatPrice($fp['diesel_price']) ?>
                                </span>
                            </div>
                            <div class="pending-actions">
                                <form action="fiyat-kontrol.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="price_id" value="<?= $fp['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>