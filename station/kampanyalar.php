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
$editCampaign = null;

// Düzenleme modu
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editCampaign = db()->fetchOne(
        "SELECT * FROM campaigns WHERE id = ? AND station_id = ?",
        [$editId, $station['id']]
    );
}

// Kampanya Silme
if (isset($_GET['delete'])) {
    $campaignId = (int) $_GET['delete'];
    // Sadece bu istasyona ait kampanyayı sil
    db()->delete('campaigns', 'id = ? AND station_id = ?', [$campaignId, $station['id']]);
    setFlash('success', 'Kampanya silindi.');
    redirect('/station/kampanyalar.php');
}

// Kampanya Ekleme/Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz istek.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $endDate = $_POST['end_date'] ?? null;
        $campaignId = (int) ($_POST['campaign_id'] ?? 0);

        if (empty($title)) {
            $error = 'Kampanya başlığı zorunludur.';
        } else {
            if ($campaignId > 0) {
                // Güncelleme
                db()->update('campaigns', [
                    'title' => $title,
                    'description' => $description ?: null,
                    'end_date' => $endDate ?: null
                ], 'id = ? AND station_id = ?', [$campaignId, $station['id']]);
                $success = 'Kampanya başarıyla güncellendi!';
            } else {
                // Yeni ekleme
                db()->insert('campaigns', [
                    'station_id' => $station['id'],
                    'title' => $title,
                    'description' => $description ?: null,
                    'end_date' => $endDate ?: null,
                    'is_active' => 1
                ]);
                $success = 'Kampanya başarıyla oluşturuldu!';
            }
            $editCampaign = null; // Formu temizle
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
    <!-- Kampanya Formu (Ekle/Düzenle) -->
    <div class="card">
        <div class="card-header">
            <?php if ($editCampaign): ?>
                <h3><i class="fas fa-edit"></i> Kampanyayı Düzenle</h3>
            <?php else: ?>
                <h3><i class="fas fa-plus-circle"></i> Yeni Kampanya Ekle</h3>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="campaign_id" value="<?= $editCampaign['id'] ?? 0 ?>">

                <div class="form-group">
                    <label class="form-label">Kampanya Başlığı</label>
                    <input type="text" name="title" class="form-control" required placeholder="Örn: %10 İndirim Fırsatı"
                        value="<?= e($editCampaign['title'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control" rows="3"
                        placeholder="Kampanya detaylarını buraya yazın..."><?= e($editCampaign['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Bitiş Tarihi (Opsiyonel)</label>
                    <input type="date" name="end_date" class="form-control"
                        value="<?= $editCampaign['end_date'] ?? '' ?>">
                </div>

                <?php if ($editCampaign): ?>
                    <div class="btn-group" style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                            <i class="fas fa-save"></i> Değişiklikleri Kaydet
                        </button>
                        <a href="kampanyalar.php" class="btn btn-outline btn-lg">
                            <i class="fas fa-times"></i> İptal
                        </a>
                    </div>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary btn-lg w-full">
                        <i class="fas fa-check"></i> Kampanyayı Yayınla
                    </button>
                <?php endif; ?>
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
                            <div class="camp-actions" style="display: flex; gap: 8px;">
                                <a href="?edit=<?= $camp['id'] ?>" class="btn btn-sm btn-outline" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?= $camp['id'] ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Bu kampanyayı silmek istediğinize emin misiniz?')" title="Sil">
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