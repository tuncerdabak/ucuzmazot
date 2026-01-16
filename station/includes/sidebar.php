<?php
/**
 * İstasyon Paneli - Sidebar
 */

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="panel-sidebar">
    <div class="sidebar-header">
        <a href="<?= url('/') ?>" class="sidebar-logo">
            <i class="fas fa-gas-pump"></i>
            <span>
                <?= SITE_NAME ?>
            </span>
        </a>
        <button id="sidebarClose" class="sidebar-close">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Kontrol Paneli</span>
        </a>
        <a href="fiyat-guncelle.php" class="nav-item <?= $currentPage === 'fiyat-guncelle.php' ? 'active' : '' ?>">
            <i class="fas fa-dollar-sign"></i>
            <span>Fiyat Güncelle</span>
        </a>
        <a href="kampanyalar.php" class="nav-item <?= $currentPage === 'kampanyalar.php' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i>
            <span>Kampanyalar</span>
        </a>
        <a href="yorumlar.php" class="nav-item <?= $currentPage === 'yorumlar.php' ? 'active' : '' ?>">
            <i class="fas fa-comments"></i>
            <span>Yorumlar</span>
        </a>
        <a href="profil.php" class="nav-item <?= $currentPage === 'profil.php' ? 'active' : '' ?>">
            <i class="fas fa-store"></i>
            <span>İstasyon Profili</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= url('/istasyon-detay.php?id=' . ($station['id'] ?? '')) ?>" class="nav-item" target="_blank">
            <i class="fas fa-external-link-alt"></i>
            <span>Sayfamı Gör</span>
        </a>
        <a href="logout.php" class="nav-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Çıkış Yap</span>
        </a>
    </div>
</aside>