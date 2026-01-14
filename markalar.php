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
        <header class="page-header text-center mb-8">
            <h1>Markalar</h1>
            <p class="text-gray">Size en yakın favori marka istasyonunuzu bulun.</p>
        </header>

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
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-lg);
        padding: var(--space-5);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--space-3);
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }

    .brand-item:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .brand-item.active {
        background: var(--primary-light);
        border-color: var(--primary);
        color: var(--primary);
    }

    .brand-icon {
        font-size: 1.5rem;
        color: var(--gray-400);
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .brand-icon.has-logo {
        width: 64px;
        height: 64px;
    }

    .brand-icon img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .brand-item:hover .brand-icon,
    .brand-item.active .brand-icon {
        color: var(--primary);
    }

    .brand-name {
        font-weight: 600;
        font-size: 0.9rem;
    }

    /* Result Card */
    .result-section {
        max-width: 500px;
        margin: 0 auto;
    }

    .result-card {
        padding: var(--space-6);
        animation: slideUp 0.3s ease;
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
        background: var(--primary);
        color: white;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .result-label {
        font-size: 0.875rem;
        color: var(--gray-500);
        display: block;
        margin-bottom: 4px;
    }

    .result-info h2 {
        font-size: 1.25rem;
        margin-bottom: 4px;
        line-height: 1.2;
    }

    .result-distance {
        color: var(--primary);
        font-weight: 600;
        font-size: 0.9rem;
    }

    .result-prices {
        display: flex;
        justify-content: space-between;
        background: var(--gray-50);
        padding: var(--space-4);
        border-radius: var(--radius);
        margin-bottom: var(--space-6);
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