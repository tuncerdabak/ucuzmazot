<?php
/**
 * Admin Paneli - İstasyon Sahibi Yönetimi
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

requireAdmin();

// İşlem yap
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($userId !== currentUserId()) { // Kendini engelleme
        switch ($action) {
            case 'activate':
                db()->update('users', ['is_active' => 1], 'id = ?', [$userId]);
                setFlash('success', 'Kullanıcı aktifleştirildi.');
                break;

            case 'deactivate':
                db()->update('users', ['is_active' => 0], 'id = ?', [$userId]);
                setFlash('success', 'Kullanıcı engellendi.');
                break;
        }
    }

    redirect('/admin/istasyon-sahipleri.php');
}

// Filtreler
$search = $_GET['search'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

// Query
$where = "role = 'station'";
$params = [];

if ($search) {
    $where .= " AND (name LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$total = db()->fetchColumn("SELECT COUNT(*) FROM users WHERE $where", $params);
$offset = ($page - 1) * $perPage;

$users = db()->fetchAll("
    SELECT u.*,
        (SELECT name FROM stations WHERE user_id = u.id LIMIT 1) as station_name,
        (SELECT COUNT(*) FROM reviews WHERE user_id = u.id) as review_count
    FROM users u
    WHERE $where
    ORDER BY u.created_at DESC
    LIMIT $perPage OFFSET $offset
", $params);

$pageTitle = 'İstasyon Sahipleri - Admin Paneli';
require_once __DIR__ . '/includes/header.php';
?>
<header class="panel-header">
    <h1>İstasyon Sahibi Yönetimi</h1>
    <span class="text-gray">
        <?= $total ?> istasyon sahibi
    </span>
</header>

<?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <i class="fas fa-check-circle"></i>
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Filtreler -->
<div class="filter-bar">
    <input type="text" class="form-control search-input" placeholder="İsim veya telefon ara..."
        value="<?= e($search) ?>" id="searchInput">
    <button onclick="applySearch()" class="btn btn-primary">
        <i class="fas fa-search"></i>
    </button>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>Telefon</th>
                    <th>İstasyon Adı</th>
                    <th>Kayıt Tarihi</th>
                    <th>Durum</th>
                    <th class="text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-info">
                                    <div class="user-name">
                                        <?= e($user['name']) ?>
                                    </div>
                                    <div class="user-email">
                                        <?= e($user['email']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?= e($user['phone']) ?>
                        </td>
                        <td>
                            <strong>
                                <?= e($user['station_name'] ?? '-') ?>
                            </strong>
                        </td>
                        <td>
                            <?= formatDate($user['created_at']) ?>
                        </td>
                        <td>
                            <span class="status-badge <?= $user['is_active'] ? 'status-success' : 'status-danger' ?>">
                                <?= $user['is_active'] ? 'Aktif' : 'Engelli' ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="action-buttons">
                                <button onclick="sendResetWhatsApp('<?= e($user['phone']) ?>', '<?= e($user['name']) ?>')"
                                    class="btn btn-sm btn-success" title="WhatsApp ile Şifre Sıfırla">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                                <?php if ($user['is_active']): ?>
                                    <form method="POST" style="display:inline"
                                        onsubmit="return confirm('Bu kullanıcıyı engellemek istediğinize emin misiniz?')">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="deactivate">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Engelle">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="activate">
                                        <button type="submit" class="btn btn-sm btn-success" title="Aktifleştir">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Sayfalama -->
    <?php if ($total > $perPage): ?>
        <div class="pagination">
            <?php
            $totalPages = ceil($total / $perPage);
            for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
                    class="page-link <?= $page === $i ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function applySearch() {
        const search = document.getElementById('searchInput').value;
        window.location.href = `?search=${encodeURIComponent(search)}`;
    }

    document.getElementById('searchInput').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') applySearch();
    });

    function sendResetWhatsApp(phone, name) {
        if (!confirm(`${name} kullanıcısına WhatsApp üzerinden şifre sıfırlama linki gönderilsin mi?`)) return;

        fetch('api/generate-reset-link.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `phone=${encodeURIComponent(phone)}&csrf_token=<?= csrfToken() ?>`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.open(data.whatsapp_url, '_blank');
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bir hata oluştu.');
            });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>