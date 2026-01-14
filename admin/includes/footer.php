</main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if (isset($extraJs)): ?>
    <script src="<?= asset($extraJs) ?>"></script>
<?php endif; ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle Sidebar
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

        // Initialize Select2
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
            $('.select2').each(function () {
                $(this).select2({
                    width: '100%',
                    language: {
                        noResults: function () {
                            return "Sonuç bulunamadı";
                        },
                        searching: function () {
                            return "Aranıyor...";
                        }
                    }
                });
            });
        }
    });
</script>
</body>

</html>