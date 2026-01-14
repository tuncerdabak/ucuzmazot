<?php
/**
 * Şifre Sıfırlama Sayfası
 */

require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$success = false;
$error = '';

if (!$token) {
    redirect('/');
}

// Token kontrolü
$reset = db()->fetchOne("
    SELECT * FROM password_resets 
    WHERE token = ? AND is_used = 0 AND expires_at > NOW()
", [$token]);

if (!$reset) {
    $error = "Geçersiz veya süresi dolmuş bağlantı. Lütfen yeni bir bağlantı talep edin.";
}

// Şifre güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 6) {
        $error = "Şifre en az 6 karakter olmalıdır.";
    } elseif ($password !== $passwordConfirm) {
        $error = "Şifreler uyuşmuyor.";
    } else {
        try {
            db()->beginTransaction();

            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);

            // Şifreyi güncelle
            db()->update('users', ['password_hash' => $passwordHash], 'id = ?', [$reset['user_id']]);

            // Tokeni kullanıldı yap
            db()->update('password_resets', ['is_used' => 1], 'id = ?', [$reset['id']]);

            db()->commit();
            $success = true;
        } catch (Exception $e) {
            db()->rollback();
            $error = "Bir hata oluştu. Lütfen tekrar deneyin.";
        }
    }
}

$pageTitle = 'Şifre Sıfırla - ' . SITE_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="auth-page">
    <div class="container">
        <div class="auth-card glass-card">
            <div class="auth-header">
                <i class="fas fa-key"></i>
                <h1>Şifre Sıfırla</h1>
                <p>Güvenliğiniz için yeni bir şifre belirleyin.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Şifreniz başarıyla güncellendi. Artık yeni şifrenizle giriş yapabilirsiniz.
                    <div class="mt-4">
                        <a href="/?login=1" class="btn btn-primary w-full">Giriş Yap</a>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($reset): ?>
                    <form method="POST" class="auth-form" id="resetForm">
                        <input type="hidden" name="token" value="<?= e($token) ?>">

                        <div class="form-group">
                            <label>Yeni Şifre</label>
                            <input type="password" name="password" class="form-control" placeholder="En az 6 karakter" required
                                minlength="6">
                        </div>

                        <div class="form-group">
                            <label>Yeni Şifre (Yeniden)</label>
                            <input type="password" name="password_confirm" class="form-control"
                                placeholder="Şifrenizi doğrulayın" required minlength="6">
                        </div>

                        <button type="submit" class="btn btn-primary w-full btn-lg">
                            <i class="fas fa-save"></i> Şifreyi Güncelle
                        </button>
                    </form>
                <?php else: ?>
                    <div class="text-center mt-4">
                        <a href="/" class="btn btn-outline">Ana Sayfaya Dön</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .auth-page {
        padding: 60px 0;
        min-height: calc(100vh - 300px);
        display: flex;
        align-items: center;
    }

    .auth-card {
        max-width: 450px;
        margin: 0 auto;
        padding: var(--space-8);
    }

    .auth-header {
        text-align: center;
        margin-bottom: var(--space-8);
    }

    .auth-header i {
        font-size: 3rem;
        color: var(--primary);
        margin-bottom: var(--space-4);
    }

    .auth-header h1 {
        font-size: 1.75rem;
        margin-bottom: var(--space-2);
    }

    .auth-header p {
        color: var(--gray-500);
    }

    .mt-4 {
        margin-top: var(--space-4);
    }
</style>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>