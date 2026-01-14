<?php
/**
 * Admin Paneli - Yorum Yönetimi
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

requireAdmin();

// İşlem yap
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $reviewId = (int) ($_POST['review_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'hide':
            db()->update('reviews', ['is_visible' => 0], 'id = ?', [$reviewId]);
            setFlash('success', 'Yorum gizlendi.');
            break;

        case 'show':
            db()->update('reviews', ['is_visible' => 1], 'id = ?', [$reviewId]);
            setFlash('success', 'Yorum gösterildi.');
            break;

        case 'delete':
            db()->delete('reviews', 'id = ?', [$reviewId]);
            setFlash('success', 'Yorum silindi.');
            break;
    }

    redirect('/admin/yorumlar.php');
}

// Filtreler
$filter = $_GET['filter'] ?? 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = "1=1";
if ($filter === 'hidden')
    $where .= " AND r.is_visible = 0";
elseif ($filter === 'visible')
    $where .= " AND r.is_visible = 1";
elseif ($filter === 'low')
    $where .= " AND r.rating <= 2";

$total = db()->fetchColumn("SELECT COUNT(*) FROM reviews r WHERE $where");
$offset = ($page - 1) * $perPage;

$reviews = db()->fetchAll("
    SELECT r.*, u.name as user_name, u.phone as user_phone, s.name as station_name
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN stations s ON r.station_id = s.id
    WHERE $where
    ORDER BY r.created_at DESC
    LIMIT $perPage OFFSET $offset
");

$pageTitle = 'Yorum Yönetimi - Admin Paneli';
require_once __DIR__ . '/includes/header.php';
?>
<header class="panel-header">
    <h1>Yorum Yönetimi</h1>
    <span class="text-gray"><?= $total ?> yorum</span>
</header>

<?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <i class="fas fa-check-circle"></i>
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Filtreler -->
<div class="filter-bar">
    <select class="form-control" onchange="location.href='?filter='+this.value">
        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Tümü</option>
        <option value="visible" <?= $filter === 'visible' ? 'selected' : '' ?>>Görünür</option>
        <option value="hidden" <?= $filter === 'hidden' ? 'selected' : '' ?>>Gizli</option>
        <option value="low" <?= $filter === 'low' ? 'selected' : '' ?>>Düşük Puan (1-2)</option>
    </select>
</div>

<!-- Yorumlar -->
<div class="card">
    <div class="card-body">
        <?php if (empty($reviews)): ?>
            <p class="text-center text-gray">Yorum bulunamadı.</p>
        <?php else: ?>
            <div class="reviews-admin-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-admin-item <?= !$review['is_visible'] ? 'hidden-review' : '' ?>">
                        <div class="review-admin-header">
                            <div class="review-admin-user">
                                <strong><?= e($review['user_name'] ?? 'Anonim') ?></strong>
                                <span class="text-gray"><?= e($review['user_phone']) ?></span>
                            </div>
                            <div class="review-admin-rating">
                                <?= renderStars($review['rating']) ?>
                            </div>
                        </div>

                        <div class="review-admin-station">
                            <i class="fas fa-gas-pump text-gray"></i>
                            <?= e($review['station_name']) ?>
                        </div>

                        <?php if ($review['comment']): ?>
                            <p class="review-admin-comment"><?= nl2br(e($review['comment'])) ?></p>
                        <?php endif; ?>

                        <div class="review-admin-footer">
                            <span class="review-admin-date"><?= formatDate($review['created_at']) ?></span>

                            <div class="review-admin-actions">
                                <?php if ($review['is_visible']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                        <input type="hidden" name="action" value="hide">
                                        <button type="submit" class="btn btn-sm btn-outline" title="Gizle">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                        <input type="hidden" name="action" value="show">
                                        <button type="submit" class="btn btn-sm btn-success" title="Göster">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" style="display:inline;"
                                    onsubmit="return confirm('Yorumu silmek istediğinize emin misiniz?')">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($total > $perPage): ?>
    <div class="mt-4">
        <?= paginate($total, $perPage, $page, '/admin/yorumlar.php') ?>
    </div>
<?php endif; ?>

<style>
    .reviews-admin-list {
        display: flex;
        flex-direction: column;
        gap: var(--space-4);
    }

    .review-admin-item {
        padding: var(--space-4);
        background: var(--gray-50);
        border-radius: var(--radius-lg);
    }

    .review-admin-item.hidden-review {
        opacity: 0.6;
        border: 1px dashed var(--gray-300);
    }

    .review-admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--space-2);
    }

    .review-admin-user {
        display: flex;
        flex-direction: column;
    }

    .review-admin-station {
        font-size: 0.875rem;
        color: var(--gray-500);
        margin-bottom: var(--space-3);
    }

    .review-admin-comment {
        margin: 0 0 var(--space-3) 0;
        color: var(--gray-700);
    }

    .review-admin-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .review-admin-date {
        font-size: 0.8125rem;
        color: var(--gray-400);
    }

    .review-admin-actions {
        display: flex;
        gap: var(--space-2);
    }

    @media (max-width: 480px) {
        .review-admin-header {
            flex-direction: column;
            align-items: flex-start;
            gap: var(--space-2);
        }

        .review-admin-footer {
            flex-direction: column;
            align-items: flex-start;
            gap: var(--space-3);
        }

        .review-admin-actions {
            width: 100%;
            justify-content: flex-end;
        }
    }
</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>