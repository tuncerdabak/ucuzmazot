<?php
/**
 * İstasyon Paneli - Giriş Sayfası
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';

// Zaten giriş yapmışsa dashboard'a yönlendir
if (isStation()) {
    redirect('/station/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';

    // CSRF kontrolü
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz istek. Lütfen sayfayı yenileyin.';
    } else {
        $result = login($phone, $password, 'station');

        if ($result['success']) {
            setFlash('success', 'Hoş geldiniz!');
            redirect('/station/');
        } else {
            $error = $result['error'];
        }
    }
}

$pageTitle = 'İstasyon Girişi - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= e($pageTitle) ?>
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card glass-card">
            <!-- Logo -->
            <div class="auth-logo">
                <a href="<?= url('/') ?>">
                    <i class="fas fa-gas-pump"></i>
                    <span>
                        <?= SITE_NAME ?>
                    </span>
                </a>
            </div>

            <h1>İstasyon Girişi</h1>
            <p class="auth-subtitle">İstasyonunuzu yönetmek için giriş yapın</p>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form" data-validate>
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="form-group">
                    <label class="form-label">Telefon Numarası</label>
                    <div class="input-icon">
                        <i class="fas fa-phone"></i>
                        <input type="tel" name="phone" class="form-control" placeholder="05XX XXX XX XX" required
                            value="<?= e($_POST['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Şifre</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg w-full">
                        <i class="fas fa-sign-in-alt"></i>
                        Giriş Yap
                    </button>
                </div>
            </form>

            <div class="auth-footer">
                <p>Henüz kayıtlı değil misiniz?</p>
                <a href="register.php" class="btn btn-outline w-full">
                    <i class="fas fa-store"></i>
                    İstasyon Kaydı Oluştur
                </a>
            </div>

            <div class="auth-links">
                <a href="<?= url('/') ?>">← Ana Sayfaya Dön</a>
            </div>
        </div>
    </div>

    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gradient-dark);
            padding: var(--space-4);
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
        }

        .auth-card {
            padding: var(--space-8);
            text-align: center;
        }

        .auth-logo {
            margin-bottom: var(--space-6);
        }

        .auth-logo a {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary);
        }

        .auth-logo i {
            font-size: 2rem;
        }

        .auth-card h1 {
            font-size: 1.5rem;
            margin-bottom: var(--space-2);
        }

        .auth-subtitle {
            color: var(--gray-500);
            margin-bottom: var(--space-6);
        }

        .auth-form {
            text-align: left;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: var(--space-4);
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
        }

        .input-icon input {
            padding-left: calc(var(--space-4) * 2 + 16px);
        }

        .auth-footer {
            margin-top: var(--space-6);
            padding-top: var(--space-6);
            border-top: 1px solid var(--gray-200);
        }

        .auth-footer p {
            color: var(--gray-500);
            margin-bottom: var(--space-3);
        }

        .auth-links {
            margin-top: var(--space-4);
        }

        .auth-links a {
            font-size: 0.875rem;
            color: var(--gray-500);
        }
    </style>
</body>

</html>