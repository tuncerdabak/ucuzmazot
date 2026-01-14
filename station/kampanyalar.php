<?php
/**
 * İstasyon Paneli - Kampanyalar
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

// Kampanya Silme
if (isset($_GET['delete'])) {
    $campaignId = (int) $_GET['delete'];
    // Sadece bu istasyona ait kampanyayı sil
    db()->delete('campaigns', 'id = ? AND station_id = ?', [$campaignId, $station['id']]);
    setFlash('success', 'Kampanya silindi.');
    redirect('/station/kampanyalar.php');
}

// Kampanya Ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz istek.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $endDate = $_POST['end_date'] ?? null;

        if (empty($title)) {
            $error = 'Kampanya başlığı zorunludur.';
        } else {
            db()->insert('campaigns', [
                'station_id' => $station['id'],
                'title' => $title,
                'description' => $description ?: null,
                'end_date' => $endDate ?: null,
                'is_active' => 1
            ]);

            $success = 'Kampanya başarıyla oluşturuldu!';
        }
    }
}

// Kampanyaları Listele
$campaigns = db()->fetchAll(
    "SELECT * FROM campaigns WHERE station_id = ? ORDER BY created_at DESC",
    [$station['id']]
);

$pageTitle = 'Kampanyalar - İstasyon Paneli';
require_once __DIR__ . '/includes/header.php';
?>

<header class="panel-header">
    <h1>Kampanya Yönetimi</h1>
</header>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i>
        <?= e($error) ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i>
        <?= e($success) ?>
    </div>
<?php endif; ?>

<?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?= $flash['type'] == 'error' ? 'danger' : 'success' ?>">
        <i class="fas fa-info-circle"></i>
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="content-grid two-column">
    <!-- Yeni Kampanya Formu -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle"></i> Yeni Kampanya Ekle</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="form-group">
                    <label class="form-label">Kampanya Başlığı</label>
                    <input type="text" name="title" class="form-control" required
                        placeholder="Örn: %10 İndirim Fırsatı">
                </div>

                <div class="form-group">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control" rows="3"
                        placeholder="Kampanya detaylarını buraya yazın..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Bitiş Tarihi (Opsiyonel)</label>
                    <input type="date" name="end_date" class="form-control">
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-full">
                    <i class="fas fa-check"></i>
                    Kampanyayı Yayınla
                </button>
            </form>
        </div>
    </div>

    <!-- Kampanya Listesi -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-bullhorn"></i> Aktif Kampanyalar</h3>
        </div>
        <div class="card-body">
            <?php if (empty($campaigns)): ?>
                <div class="empty-state">
                    <i class="fas fa-tag"></i>
                    <p>Henüz kampanya oluşturmadınız.</p>
                </div>
            <?php else: ?>
                <div class="campaign-list">
                    <?php foreach ($campaigns as $camp): ?>
                        <div class="campaign-item">
                            <div class="camp-content">
                                <h4>
                                    <?= e($camp['title']) ?>
                                </h4>
                                <?php if ($camp['description']): ?>
                                    <p>
                                        <?= e($camp['description']) ?>
                                    </p>
                                <?php endif; ?>
                                <div class="camp-meta">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php if ($camp['end_date']): ?>
                                        Bitiş:
                                        <?= formatDate($camp['end_date']) ?>
                                    <?php else: ?>
                                        Süresiz
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="camp-actions">
                                <a href="?delete=<?= $camp['id'] ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Bu kampanyayı silmek istediğinize emin misiniz?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .campaign-item {
        display: flex;
        justify-content: space-between;
        align-items: start;
        padding: var(--space-4);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius);
        margin-bottom: var(--space-3);
        background: var(--gray-50);
    }

    .camp-content h4 {
        margin: 0 0 var(--space-2) 0;
        color: var(--primary);
    }

    .camp-content p {
        font-size: 0.9rem;
        color: var(--gray-600);
        margin-bottom: var(--space-2);
    }

    .camp-meta {
        font-size: 0.8rem;
        color: var(--gray-500);
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>