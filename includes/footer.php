</main>

<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand -->
            <div class="footer-brand">
                <a href="<?= url('/') ?>" class="logo">
                    <i class="fas fa-gas-pump"></i>
                    <span>
                        <?= SITE_NAME ?>
                    </span>
                </a>
                <p>Türkiye genelinde en ucuz mazot fiyatlarını bulun ve karşılaştırın.</p>
            </div>

            <!-- Links -->
            <div class="footer-links">
                <h4>Hızlı Linkler</h4>
                <ul>
                    <li><a href="<?= url('/') ?>">Ana Sayfa</a></li>
                    <li><a href="<?= url('/hakkimizda.php') ?>">Hakkımızda</a></li>
                    <li><a href="<?= url('/iletisim.php') ?>">İletişim</a></li>
                    <li><a href="<?= url('/station/register.php') ?>">İstasyon Kaydı</a></li>
                </ul>
            </div>

            <!-- Cities -->
            <div class="footer-links">
                <h4>Popüler Şehirler</h4>
                <ul>
                    <?php
                    $popularCities = ['İstanbul', 'Ankara', 'İzmir', 'Bursa', 'Antalya', 'Kocaeli', 'Adana', 'Gaziantep'];
                    foreach ($popularCities as $pCity):
                        ?>
                        <li><a href="<?= url('/sehir.php?slug=' . slugify($pCity . '-en-ucuz-mazot')) ?>"><?= $pCity ?> En
                                Ucuz Mazot</a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Legal -->
            <div class="footer-links">
                <h4>Yasal</h4>
                <ul>
                    <li><a href="<?= url('/kullanim-sartlari.php') ?>">Kullanım Şartları</a></li>
                    <li><a href="<?= url('/gizlilik-politikasi.php') ?>">Gizlilik Politikası</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="footer-contact">
                <h4>İletişim</h4>
                <p>
                    <i class="fas fa-envelope"></i>
                    <a href="mailto:<?= SITE_EMAIL ?>">
                        <?= SITE_EMAIL ?>
                    </a>
                </p>
                <?php if (!empty(SITE_CONTACT_PHONE)): ?>
                    <p>
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?= e(SITE_CONTACT_PHONE) ?>"><?= e(SITE_CONTACT_PHONE) ?></a>
                    </p>
                    <a href="https://wa.me/<?= cleanPhone(SITE_CONTACT_PHONE) ?>" class="btn btn-sm btn-success mt-4"
                        target="_blank">
                        <i class="fab fa-whatsapp"></i> WhatsApp Destek
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy;
                <?= date('Y') ?>
                <?= SITE_NAME ?>. Tüm hakları saklıdır.
            </p>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>

<?php if (isset($extraJs)): ?>
    <script src="<?= asset($extraJs) ?>"></script>
<?php endif; ?>

<?php if (isset($inlineJs)): ?>
    <script><?= $inlineJs ?></script>
<?php endif; ?>

<style>
    /* Footer Styles */
    .site-footer {
        background: var(--gray-900);
        color: var(--gray-300);
        padding: var(--space-6) 0 var(--space-3);
        margin-top: var(--space-4);
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: white;
        width: 90%;
        max-width: 400px;
        border-radius: var(--radius-xl);
        padding: var(--space-6);
        position: relative;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        transform: translateY(20px);
        transition: all 0.3s ease;
    }

    .modal-overlay.active .modal-content {
        transform: translateY(0);
    }

    .close-modal {
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: none;
        font-size: 1.25rem;
        color: var(--gray-400);
        cursor: pointer;
        transition: color 0.2s;
        z-index: 10;
    }

    .close-modal:hover {
        color: var(--gray-800);
    }

    .modal-header {
        text-align: center;
        margin-bottom: var(--space-6);
    }

    .modal-icon {
        width: 60px;
        height: 60px;
        background: var(--primary-light, #eff6ff);
        color: var(--primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin: 0 auto var(--space-3);
    }

    .modal-header h2 {
        font-size: 1.25rem;
        color: var(--gray-800);
        margin-bottom: var(--space-2);
    }

    .modal-header p {
        font-size: 0.875rem;
        color: var(--gray-500);
        line-height: 1.5;
    }

    /* Tabs */
    .auth-tabs {
        display: flex;
        background: var(--gray-100);
        padding: 4px;
        border-radius: var(--radius-lg);
        margin-bottom: var(--space-5);
    }

    .auth-tab {
        flex: 1;
        border: none;
        background: none;
        padding: 8px;
        font-size: 0.9375rem;
        font-weight: 500;
        color: var(--gray-600);
        border-radius: var(--radius);
        cursor: pointer;
        transition: all 0.2s;
    }

    .auth-tab.active {
        background: white;
        color: var(--primary);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* Form */
    .form-group {
        margin-bottom: var(--space-4);
    }

    .form-group label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--gray-700);
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-lg);
        font-size: 1rem;
        transition: all 0.2s;
    }

    .form-control:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .btn-lg {
        padding: 12px;
        font-size: 1rem;
        font-weight: 600;
    }

    .terms-text {
        font-size: 0.75rem;
        text-align: center;
        color: var(--gray-500);
        margin-top: var(--space-4);
    }

    .terms-text a {
        color: var(--primary);
        text-decoration: none;
    }

    /* Blur helper */
    .footer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: var(--space-6);
        padding-bottom: var(--space-4);
        border-bottom: 1px solid var(--gray-700);
    }

    .footer-brand .logo {
        color: var(--white);
        margin-bottom: var(--space-4);
    }

    .footer-brand p {
        color: var(--gray-400);
        font-size: 0.9375rem;
    }

    .footer-links h4,
    .footer-contact h4 {
        color: var(--white);
        font-size: 1rem;
        margin-bottom: var(--space-4);
    }

    .footer-links ul {
        list-style: none;
    }

    .footer-links li {
        margin-bottom: var(--space-2);
    }

    .footer-links a {
        color: var(--gray-400);
        font-size: 0.9375rem;
        transition: color var(--transition);
    }

    .footer-links a:hover {
        color: var(--white);
    }

    .footer-contact p {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        margin-bottom: var(--space-2);
        font-size: 0.9375rem;
    }

    .footer-contact a {
        color: var(--gray-400);
    }

    .footer-contact a:hover {
        color: var(--primary-light);
    }

    .footer-bottom {
        padding-top: var(--space-4);
        text-align: center;
        font-size: 0.875rem;
        color: var(--gray-500);
    }

    @media (max-width: 768px) {
        .footer-grid {
            grid-template-columns: 1fr;
            gap: var(--space-6);
        }
    }
</style>
<!-- Hızlı Üyelik / Giriş Modalı -->
<div class="modal-overlay" id="authModal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeAuthModal()"><i class="fas fa-times"></i></button>

        <div class="modal-header">
            <div class="modal-icon">
                <i class="fas fa-gas-pump"></i>
            </div>
            <h2>Fiyatları Görmek İçin Devam Et</h2>
            <p>Sadece telefon numaranızla saniyeler içinde giriş yapın.</p>
        </div>

        <form id="quickAuthForm">
            <div class="auth-tabs">
                <button type="button" class="auth-tab active" data-action="register"
                    onclick="setAuthMode('register')">Hızlı Kayıt</button>
                <button type="button" class="auth-tab" data-action="login" onclick="setAuthMode('login')">Giriş
                    Yap</button>
            </div>

            <input type="hidden" name="action" id="authAction" value="register">

            <div class="form-group" id="nameGroup">
                <label>Ad Soyad</label>
                <input type="text" name="name" class="form-control" placeholder="Adınız Soyadınız" required>
            </div>

            <div class="form-group">
                <label>Telefon Numarası</label>
                <input type="tel" name="phone" class="form-control" placeholder="05XX XXX XX XX" required
                    style="font-size:1.2rem; letter-spacing:1px;">
            </div>

            <div class="form-group" id="passwordGroup" style="display:none;">
                <label>Şifre</label>
                <div class="password-input-wrapper" style="position:relative;">
                    <input type="password" name="password" id="authPassword" class="form-control"
                        placeholder="Şifreniz">
                    <button type="button" class="btn-toggle-password" onclick="toggleAuthPassword()"
                        style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--gray-400);">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full btn-lg">
                <i class="fas fa-user-plus"></i> Ücretsiz Kayıt Ol
            </button>

            <p class="terms-text">
                Uygulamayı kullanarak <a href="/kullanim-sartlari.php" target="_blank">Kullanım Şartları</a>'nı kabul
                etmiş sayılırsınız.
            </p>
        </form>
    </div>
</div>

<script>
    // Global Modal Functions
    function openAuthModal() {
        const m = document.getElementById('authModal');
        if (m) m.classList.add('active');
    }

    function closeAuthModal() {
        const m = document.getElementById('authModal');
        if (m) m.classList.remove('active');
    }

    function setAuthMode(mode) {
        document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
        document.querySelector(`.auth-tab[data-action="${mode}"]`).classList.add('active');
        document.getElementById('authAction').value = mode;

        const nameGroup = document.getElementById('nameGroup');
        const passwordGroup = document.getElementById('passwordGroup');
        const passwordInput = document.getElementById('authPassword');
        const btn = document.querySelector('#quickAuthForm button[type="submit"]');

        if (mode === 'login') {
            nameGroup.style.display = 'none';
            document.querySelector('#quickAuthForm input[name="name"]').removeAttribute('required');
            passwordGroup.style.display = 'block';
            passwordInput.setAttribute('required', 'required');
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Giriş Yap';
        } else {
            nameGroup.style.display = 'block';
            document.querySelector('#quickAuthForm input[name="name"]').setAttribute('required', 'required');
            passwordGroup.style.display = 'none';
            passwordInput.removeAttribute('required');
            btn.innerHTML = '<i class="fas fa-user-plus"></i> Ücretsiz Kayıt Ol';
        }
    }

    function toggleAuthPassword() {
        const passInput = document.getElementById('authPassword');
        const icon = document.querySelector('.btn-toggle-password i');
        if (passInput.type === 'password') {
            passInput.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passInput.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Modal Event Listeners
        const authModal = document.getElementById('authModal');

        // Dışarı tıklama kapatması
        if (authModal) {
            authModal.addEventListener('click', function (e) {
                if (e.target === authModal) {
                    closeAuthModal();
                }
            });
        }

        // Kart Tıklamaları (Blur için) - Global Delegated Event
        document.addEventListener('click', function (e) {
            if (e.target.closest('.auth-trigger')) {
                e.preventDefault();
                e.stopPropagation();
                openAuthModal();
            }
        });

        // Form Submit
        const authForm = document.getElementById('quickAuthForm');
        if (authForm) {
            authForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                const btn = this.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> İşleniyor...';
                btn.disabled = true;

                const formData = new FormData(this);

                try {
                    const response = await fetch('/api/driver-auth.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.location.reload();
                    } else {
                        alert(result.error || 'Bir hata oluştu');
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Bir bağlantı hatası oluştu. Lütfen tekrar deneyin.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        }
    });
</script>

</body>

</html>