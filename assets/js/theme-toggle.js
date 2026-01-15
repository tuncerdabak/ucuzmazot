/**
 * UcuzMazot.com - Theme Toggle Script
 */
document.addEventListener('DOMContentLoaded', () => {
    const themeToggles = document.querySelectorAll('.theme-toggle-btn');
    const htmlElement = document.documentElement;

    // Başlangıç temasını ayarla
    const savedTheme = localStorage.getItem('theme') || 'dark';

    // UI'ı güncelle
    updateThemeUI(savedTheme);

    themeToggles.forEach(btn => {
        btn.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeUI(newTheme);
        });
    });

    function updateThemeUI(theme) {
        htmlElement.setAttribute('data-theme', theme);

        themeToggles.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }

            // Eğer butonun içinde yazı varsa onu da güncelleyebiliriz (opsiyonel)
            const span = btn.querySelector('span');
            if (span && btn.closest('.mobile-menu')) {
                span.textContent = theme === 'dark' ? 'Aydınlık Mod' : 'Koyu Mod';
            }
        });

        // PWA Theme Color Güncelleme
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', theme === 'dark' ? '#111827' : '#2563eb');
        }
    }
});
