</main>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script src="<?= asset('js/app.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('stationMenuToggle');
        const closeBtn = document.getElementById('sidebarClose');
        const sidebar = document.querySelector('.panel-sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (toggle && sidebar) {
            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
            });
        }

        if (closeBtn && sidebar) {
            closeBtn.addEventListener('click', function () {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            });
        }

        if (overlay && sidebar) {
            overlay.addEventListener('click', function () {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            });
        }
    });
</script>
</body>

</html>