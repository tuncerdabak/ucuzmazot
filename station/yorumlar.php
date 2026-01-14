<?php
/**
 * İstasyon Paneli - Yorumlar
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

// İstatistikler
$stats = db()->fetchOne("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive_reviews,
        SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as negative_reviews
    FROM reviews 
    WHERE station_id = ? AND is_visible = 1
", [$station['id']]);

// Yorumları Listele
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalReviews = $stats['total_reviews'];
$totalPages = ceil($totalReviews / $perPage);

$reviews = db()->fetchAll("
    SELECT r.*, u.name as user_name 
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.station_id = ? AND r.is_visible = 1
    ORDER BY r.created_at DESC
    LIMIT $perPage OFFSET $offset
", [$station['id']]);

$pageTitle = 'Yorumlar - İstasyon Paneli';
require_once __DIR__ . '/includes/header.php';
?>

<header class="panel-header">
    <h1>Müşteri Değerlendirmeleri</h1>
</header>

<!-- İstatistik Kartları -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-info">
            <span class="label">Ortalama Puan</span>
            <span class="value">
                <?= number_format($stats['avg_rating'] ?? 0, 1) ?>
            </span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
            <i class="fas fa-thumbs-up"></i>
        </div>
        <div class="stat-info">
            <span class="label">Olumlu Yorum</span>
            <span class="value">
                <?= $stats['positive_reviews'] ?? 0 ?>
            </span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
            <i class="fas fa-thumbs-down"></i>
        </div>
        <div class="stat-info">
            <span class="label">Olumsuz Yorum</span>
            <span class="value">
                <?= $stats['negative_reviews'] ?? 0 ?>
            </span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(107, 114, 128, 0.1); color: #6b7280;">
            <i class="fas fa-comment-dots"></i>
        </div>
        <div class="stat-info">
            <span class="label">Toplam Yorum</span>
            <span class="value">
                <?= $stats['total_reviews'] ?? 0 ?>
            </span>
        </div>
    </div>
</div>

<!-- Yorum Listesi -->
<div class="card mt-5">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Son Yorumlar</h3>
    </div>
    <div class="card-body">
        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-comment-slash"></i>
                <p>Henüz değerlendirme yapılmamış.</p>
            </div>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="user-info">
                                <div class="avatar-circle">
                                    <?= strtoupper(substr($review['user_name'] ?? 'A', 0, 1)) ?>
                                </div>
                                <div class="user-meta">
                                    <span class="name">
                                        <?= e($review['user_name'] ?? 'Anonim') ?>
                                    </span>
                                    <span class="date">
                                        <?= timeAgo($review['created_at']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="rating-display">
                                <?= renderStars($review['rating']) ?>
                            </div>
                        </div>
                        <div class="review-body">
                            <?php if ($review['comment']): ?>
                                <p>
                                    <?= nl2br(e($review['comment'])) ?>
                                </p>
                            <?php else: ?>
                                <p class="text-gray italic">Yorum yapılmamış, sadece puan verilmiş.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Sayfalama -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="page-link <?= $page === $i ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-4);
        margin-bottom: var(--space-6);
    }

    .stat-card {
        background: white;
        padding: var(--space-4);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        gap: var(--space-4);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .stat-info {
        display: flex;
        flex-direction: column;
    }

    .stat-info .label {
        font-size: 0.8rem;
        color: var(--gray-500);
    }

    .stat-info .value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--gray-800);
    }

    .review-item {
        border-bottom: 1px solid var(--gray-100);
        padding: var(--space-4) 0;
    }

    .review-item:last-child {
        border-bottom: none;
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--space-3);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: var(--space-3);
    }

    .avatar-circle {
        width: 40px;
        height: 40px;
        background: var(--primary);
        color: white;
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }

    .user-meta {
        display: flex;
        flex-direction: column;
    }

    .user-meta .name {
        font-weight: 500;
        font-size: 0.9rem;
    }

    .user-meta .date {
        font-size: 0.75rem;
        color: var(--gray-400);
    }

    .review-body p {
        color: var(--gray-700);
        line-height: 1.5;
        margin: 0;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: var(--space-2);
        margin-top: var(--space-4);
    }

    .page-link {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius);
        color: var(--gray-600);
        transition: all 0.2s;
    }

    .page-link.active,
    .page-link:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>