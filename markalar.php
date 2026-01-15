<?php
/**
 * Markalar ve En Yakın İstasyon Bulucu
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

// Tüm markaları çek (config.php'deki listeden)
$brandsList = array_filter(FUEL_BRANDS, function ($b) {
    return $b !== 'Diğer';
});
asort($brandsList, SORT_LOCALE_STRING);
$brands = array_map(function ($b) {
    return ['brand' => $b];
}, $brandsList);

$pageTitle = 'Markalar - ' . SITE_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="brands-page">
    <div class="container">
        <!-- Premium Page Header -->
        <div class="page-header-premium animate__animated animate__fadeIn text-center" style="margin-bottom: 32px;">
            <h1 style="margin-bottom: 12px;">Markalar</h1>
            <p class="subtitle">Size en yakın favori marka istasyonunuzu bulun.</p>
        </div>

        <!-- Brand Grid -->
        <div class="brands-grid">
            <?php foreach ($brands as $b):
                $brandLogo = getBrandLogo($b['brand']);
                ?>
                <div class="brand-item" onclick="findNearestStation('<?= e($b['brand']) ?>', this)">
                    <div class="brand-icon <?= $brandLogo ? 'has-logo' : '' ?>">
                        <?php if ($brandLogo): ?>
                            <img src="<?= $brandLogo ?>" alt="<?= e($b['brand']) ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block'; this.parentElement.classList.remove('has-logo');">
                            <i class="fas fa-gas-pump" style="display: none;"></i>
                        <?php else: ?>
                            <i class="fas fa-gas-pump"></i>
                        <?php endif; ?>
                    </div>
                    <span class="brand-name">
                        <?= e($b['brand']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Result Section (Hidden by default) -->
        <div id="resultSection" class="result-section" style="display: none;">
            <div id="resultCard" class="result-card glass-card">
                <div class="result-header">
                    <div class="result-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="result-info">
                        <span class="result-label">Size En Yakın <span id="selectedBrandName"></span></span>
                        <h2 id="foundStationName">İstasyon Adı</h2>
                        <p id="foundStationDistance" class="result-distance">0.0 km uzakta</p>
                    </div>
                </div>

                <div class="result-prices">
                    <div class="price-mini">
                        <span>Motorin</span>
                        <div class="price-tag" id="foundDiesel">-</div>
                    </div>
                    <div class="price-mini">
                        <span>Benzin</span>
                        <div class="price-tag" id="foundGasoline">-</div>
                    </div>
                    <div class="price-mini">
                        <span>LPG</span>
                        <div class="price-tag" id="foundLpg">-</div>
                    </div>
                </div>

                <div class="result-actions">
                    <a id="navigateBtn" href="#" target="_blank" class="btn btn-primary btn-lg w-full">
                        <i class="fas fa-directions"></i> Yol Tarifi Al
                    </a>
                </div>
            </div>

            <div id="loadingState" class="text-center" style="display: none;">
                <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                <p class="mt-3">En yakın istasyon aranıyor...</p>
            </div>

            <div id="errorState" class="text-center text-danger" style="display: none;">
                <i class="fas fa-exclamation-circle fa-3x"></i>
                <p class="mt-3" id="errorMessage">Hata oluştu.</p>
            </div>
        </div>
    </div>
</div>

<style>
    .brands-page {
        padding: var(--space-8) 0;
    }

    .brands-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: var(--space-4);
        margin-bottom: var(--space-8);
    }

    .brand-item {
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        border: 2px solid rgba(229, 231, 235, 0.8);
        border-radius: 16px;
        padding: var(--space-5);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--space-3);
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        text-align: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .brand-item:hover {
        border-color: rgba(59, 130, 246, 0.4);
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 12px 30px rgba(37, 99, 235, 0.15);
    }

    .brand-item.active {
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(59, 130, 246, 0.12));
        border-color: rgba(59, 130, 246, 0.5);
        box-shadow: 0 8px 25px rgba(37, 99, 235, 0.2);
    }

    [data-theme="dark"] .brand-item {
        background: linear-gradient(145deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.9));
        border: 2px solid rgba(75, 85, 99, 0.5);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    [data-theme="dark"] .brand-item:hover {
        border-color: rgba(59, 130, 246, 0.5);
        box-shadow: 0 12px 30px rgba(59, 130, 246, 0.25), 0 0 20px rgba(59, 130, 246, 0.1);
    }

    [data-theme="dark"] .brand-item.active {
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.2), rgba(59, 130, 246, 0.15));
        border-color: rgba(59, 130, 246, 0.6);
    }

    .brand-icon {
        font-size: 1.5rem;
        color: var(--gray-400);
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(145deg, #f3f4f6, #e5e7eb);
        border-radius: 12px;
        transition: all 0.25s ease;
    }

    [data-theme="dark"] .brand-icon {
        background: linear-gradient(145deg, rgba(55, 65, 81, 0.8), rgba(31, 41, 55, 0.8));
    }

    .brand-icon.has-logo {
        width: 64px;
        height: 64px;
        background: transparent;
    }

    .brand-icon img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .brand-item:hover .brand-icon,
    .brand-item.active .brand-icon {
        color: var(--primary);
        transform: scale(1.1);
    }

    .brand-name {
        font-weight: 700;
        font-size: 0.9rem;
        color: #374151;
    }

    [data-theme="dark"] .brand-name {
        color: #e5e7eb;
    }

    /* Result Card - Premium */
    .result-section {
        max-width: 500px;
        margin: 0 auto;
    }

    .result-card {
        padding: var(--space-6);
        animation: slideUp 0.3s ease;
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        border-radius: 20px;
        border: 1px solid rgba(229, 231, 235, 0.8);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    }

    [data-theme="dark"] .result-card {
        background: linear-gradient(145deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.9));
        border: 1px solid rgba(75, 85, 99, 0.5);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .result-header {
        display: flex;
        gap: var(--space-4);
        margin-bottom: var(--space-6);
    }

    .result-icon {
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
    }

    .result-label {
        font-size: 0.875rem;
        color: #6b7280;
        display: block;
        margin-bottom: 4px;
    }

    [data-theme="dark"] .result-label {
        color: #9ca3af;
    }

    .result-info h2 {
        font-size: 1.25rem;
        margin-bottom: 4px;
        line-height: 1.2;
        color: #1f2937;
    }

    [data-theme="dark"] .result-info h2 {
        color: #f3f4f6;
    }

    .result-distance {
        color: #2563eb;
        font-weight: 700;
        font-size: 0.9rem;
    }

    [data-theme="dark"] .result-distance {
        color: #60a5fa;
    }

    .result-prices {
        display: flex;
        justify-content: space-between;
        background: linear-gradient(145deg, #f3f4f6, #e5e7eb);
        padding: var(--space-4);
        border-radius: 12px;
        margin-bottom: var(--space-6);
    }

    [data-theme="dark"] .result-prices {
        background: linear-gradient(145deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.6));
    }

    .price-mini {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }

    .price-mini span {
        font-size: 0.75rem;
        color: var(--gray-500);
        text-transform: uppercase;
    }

    .price-mini .price-tag {
        font-size: 1.25rem;
        color: var(--gray-900);
    }

    .price-mini .currency {
        font-size: 0.75rem;
    }

    #loadingState,
    #errorState {
        padding: var(--space-8);
    }
</style>

<script>
    async function findNearestStation(brand, element) {
        // Highlight active brand
        document.querySelectorAll('.brand-item').forEach(el => el.classList.remove('active'));
        if (element) element.classList.add('active');

        // UI Reset
        const resultSection = document.getElementById('resultSection');
        const resultCard = document.getElementById('resultCard');
        const loadingState = document.getElementById('loadingState');
        const errorState = document.getElementById('errorState');

        resultSection.style.display = 'block';
        resultCard.style.display = 'none';
        errorState.style.display = 'none';
        loadingState.style.display = 'block';

        // Scroll to result
        resultSection.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Get Location
        if (!navigator.geolocation) {
            showError("Tarayıcınız konum servisini desteklemiyor.");
            return;
        }

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                try {
                    const response = await fetch(`/api/get-nearest-brand.php?lat=${lat}&lng=${lng}&brand=${encodeURIComponent(brand)}`);
                    const data = await response.json();

                    loadingState.style.display = 'none';

                    if (data.success) {
                        displayResult(data.station, brand);
                    } else {
                        showError(data.error);
                    }
                } catch (err) {
                    loadingState.style.display = 'none';
                    showError("Bir bağlantı hatası oluştu.");
                    console.error(err);
                }
            },
            (err) => {
                loadingState.style.display = 'none';
                let msg = "Konum alınamadı.";
                if (err.code === 1) msg = "Lütfen konum izni verin.";
                showError(msg);
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    function displayResult(station, brandName) {
        document.getElementById('selectedBrandName').textContent = brandName;
        document.getElementById('foundStationName').textContent = station.name;
        document.getElementById('foundStationDistance').textContent = station.distance + ' km uzakta';

        // Prices (Handle partial logic if needed, but for simplicity showing what API returns)
        // Note: API returns raw prices. We should probably use the same obfuscation if guest.
        // For now, let's assume we show what backend gave. 
        // Backend currently gives full prices. 
        // We can do simple formatting or obfuscation here if needed.
        // Let's implement client-side simple formatting for now.

        document.getElementById('foundDiesel').innerHTML = formatPrice(station.diesel_price);
        document.getElementById('foundGasoline').innerHTML = formatPrice(station.gasoline_price);
        document.getElementById('foundLpg').innerHTML = formatPrice(station.lpg_price);

        document.getElementById('navigateBtn').href = `https://www.google.com/maps/dir/?api=1&destination=${station.lat},${station.lng}`;

        document.getElementById('resultCard').style.display = 'block';
    }

    function formatPrice(price) {
        if (!price || price <= 0) return '-';
        let formatted = parseFloat(price).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return `<span class="digital-price">${formatted}</span> <span class="currency">TL</span>`;
    }

    function showError(msg) {
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('errorState').style.display = 'block';
        document.getElementById('errorMessage').textContent = msg;
    }
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>