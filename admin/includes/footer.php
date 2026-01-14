</main>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
<?php if (isset($extraJs)): ?>
    <script src="<?= asset($extraJs) ?>"></script>
<?php endif; ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.panel-sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleMenu() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleMenu);
        }

        if (overlay) {
            overlay.addEventListener('click', toggleMenu);
        }
    });
</script>
</body>

</html>