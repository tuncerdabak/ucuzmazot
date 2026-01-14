<?php
/**
 * Kullanıcı Profili
 */

require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

requireLogin();

$userId = currentUserId();
$user = db()->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

if (!$user) {
    redirect('/logout.php');
}

// Migration check (Columnekle)
try {
    $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'is_password_set'");
    if (empty($columns)) {
        db()->query("ALTER TABLE users ADD COLUMN is_password_set TINYINT(1) DEFAULT 1 AFTER is_verified");
        // Mevcut şoförler için 0 yapalım (hızlı kayıt olmuş olabilirler)
        db()->query("UPDATE users SET is_password_set = 0 WHERE role = 'driver' AND (password_hash IS NOT NULL AND password_hash != '')");
    }
} catch (Exception $e) {
    // Sessiz geç
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        if (empty($name))
            throw new Exception('Ad soyad boş bırakılamaz.');

        $updateData = [
            'name' => $name,
            'email' => $email
        ];

        if (!empty($password)) {
            if (strlen($password) < 6)
                throw new Exception('Şifre en az 6 karakter olmalıdır.');
            $updateData['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
            $updateData['is_password_set'] = 1;
            $_SESSION['is_password_set'] = 1;
        }

        db()->update('users', $updateData, 'id = ?', [$userId]);
        $_SESSION['user_name'] = $name;

        $success = 'Profiliniz başarıyla güncellendi.';
        $user = db()->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = 'Profilim - ' . SITE_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-8">
    <div class="max-w-2xl mx-auto">
        <header class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Profilim</h1>
                <p class="text-gray-500">Kişisel bilgilerinizi ve şifrenizi buradan güncelleyebilirsiniz.</p>
            </div>
            <a href="<?= url('/') ?>" class="btn btn-sm btn-outline">
                <i class="fas fa-arrow-left mr-1"></i> Ana Sayfa
            </a>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success mb-6">
                <i class="fas fa-check-circle"></i>
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-6">
                <i class="fas fa-exclamation-circle"></i>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!($user['is_password_set'] ?? 1)): ?>
            <div class="alert alert-warning mb-8">
                <i class="fas fa-key"></i>
                <div>
                    <strong>Şifre Belirleyin!</strong>
                    <p class="text-sm mt-1">Gelecekte farklı cihazlardan giriş yapabilmeniz için lütfen kendinize bir şifre
                        belirleyin.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="form-group">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Telefon Numarası</label>
                        <input type="text" class="form-control" value="<?= e($user['phone']) ?>" disabled>
                        <p class="form-text">Telefon numarası değiştirilemez.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">E-posta Adresi</label>
                        <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>"
                            placeholder="ornek@mail.com">
                    </div>

                    <div class="border-t my-8 pt-8">
                        <h3 class="text-lg font-semibold mb-4">Şifre Değiştir</h3>
                        <div class="form-group">
                            <label class="form-label">Yeni Şifre</label>
                            <input type="password" name="password" class="form-control"
                                placeholder="Değiştirmek istemiyorsanız boş bırakın">
                            <p class="form-text">En az 6 karakter olmalıdır.</p>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-full md:w-auto">
                        <i class="fas fa-save mr-2"></i> Değişiklikleri Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .max-w-2xl {
        max-width: 42rem;
    }

    .mx-auto {
        margin-left: auto;
        margin-right: auto;
    }

    .py-8 {
        padding-top: 2rem;
        padding-bottom: 2rem;
    }

    .mb-8 {
        margin-bottom: 2rem;
    }

    .mb-6 {
        margin-bottom: 1.5rem;
    }

    .mt-4 {
        margin-top: 1rem;
    }

    .text-2xl {
        font-size: 1.5rem;
        line-height: 2rem;
    }

    .text-lg {
        font-size: 1.125rem;
        line-height: 1.75rem;
    }

    .font-bold {
        font-weight: 700;
    }

    .font-semibold {
        font-weight: 600;
    }

    .text-gray-500 {
        color: var(--gray-500);
    }

    .text-sm {
        font-size: 0.875rem;
        line-height: 1.25rem;
    }

    .mt-1 {
        margin-top: 0.25rem;
    }

    .w-full {
        width: 100%;
    }

    .border-t {
        border-top: 1px solid var(--gray-200);
    }

    .my-8 {
        margin-top: 2rem;
        margin-bottom: 2rem;
    }

    .pt-8 {
        padding-top: 2rem;
    }

    .flex {
        display: flex;
    }

    .justify-between {
        justify-content: space-between;
    }

    .items-center {
        align-items: center;
    }

    .mr-1 {
        margin-right: 0.25rem;
    }

    .mr-2 {
        margin-right: 0.5rem;
    }

    @media (min-width: 768px) {
        .md\:w-auto {
            width: auto;
        }
    }
</style>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>