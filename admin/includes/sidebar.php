<?php
/**
 * Admin Paneli - Sidebar
 */

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="panel-sidebar admin-sidebar">
    <div class="sidebar-header">
        <a href="<?= url('/') ?>" class="sidebar-logo">
            <i class="fas fa-gas-pump"></i>
            <span>
                <?= SITE_NAME ?>
            </span>
        </a>
        <span class="admin-badge">Admin</span>
    </div>

    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="istasyonlar.php" class="nav-item <?= $currentPage === 'istasyonlar.php' ? 'active' : '' ?>">
            <i class="fas fa-gas-pump"></i>
            <span>İstasyonlar</span>
        </a>
        <a href="kullanicilar.php" class="nav-item <?= $currentPage === 'kullanicilar.php' ? 'active' : '' ?>">
            <i class="fas fa-truck"></i>
            <span>Şoförler</span>
        </a>
        <a href="istasyon-sahipleri.php"
            class="nav-item <?= $currentPage === 'istasyon-sahipleri.php' ? 'active' : '' ?>">
            <i class="fas fa-user-tie"></i>
            <span>İstasyon Sahipleri</span>
        </a>
        <a href="fiyatlar.php" class="nav-item <?= $currentPage === 'fiyatlar.php' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i>
            <span>Fiyatlar</span>
        </a>
        <a href="yorumlar.php" class="nav-item <?= $currentPage === 'yorumlar.php' ? 'active' : '' ?>">
            <i class="fas fa-comments"></i>
            <span>Yorumlar</span>
        </a>
        <a href="ayarlar.php" class="nav-item <?= $currentPage === 'ayarlar.php' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i>
            <span>Ayarlar</span>
        </a>
        <a href="../sitemap.php" class="nav-item" target="_blank">
            <i class="fas fa-sitemap"></i>
            <span>Sitemap.php</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= url('/') ?>" class="nav-item" target="_blank">
            <i class="fas fa-external-link-alt"></i>
            <span>Siteyi Gör</span>
        </a>
        <a href="logout.php" class="nav-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Çıkış Yap</span>
        </a>
        <div class="sidebar-version">
            <?= SYSTEM_VERSION ?>
        </div>
    </div>
</aside>