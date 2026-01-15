/**
 * UcuzMazot.com - Harita İşlevleri
 * Leaflet.js ile harita ve istasyon işaretçileri
 */

let map = null;
let markers = [];
let userMarker = null;
let userLocation = null;

/**
 * Haritayı başlat
 */
function initMap(elementId, options = {}) {
    const defaults = {
        center: [41.0082, 28.9784], // İstanbul
        zoom: 10,
        minZoom: 6,
        maxZoom: 18
    };

    const config = { ...defaults, ...options };

    map = L.map(elementId, {
        zoomControl: false
    }).setView(config.center, config.zoom);

    // Harita tile layer
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
    }).addTo(map);

    // Zoom control sağ alta
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    // Kullanıcı konumu butonu
    addLocationButton();

    return map;
}

/**
 * Konum butonu ekle
 */
function addLocationButton() {
    const locationBtn = L.control({ position: 'bottomright' });

    locationBtn.onAdd = function () {
        const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
        div.innerHTML = `
            <a href="#" class="location-btn" title="Konumum">
                <i class="fas fa-crosshairs"></i>
            </a>
        `;
        div.style.cssText = 'background: white; border-radius: 4px;';
        div.querySelector('a').style.cssText = `
            display: flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            color: #374151;
            text-decoration: none;
        `;

        div.querySelector('a').addEventListener('click', function (e) {
            e.preventDefault();
            getUserLocation();
        });

        return div;
    };

    locationBtn.addTo(map);
}

/**
 * Kullanıcı konumunu al
 */
function getUserLocation(callback) {
    if (!navigator.geolocation) {
        showToast('Tarayıcınız konum servisini desteklemiyor', 'error');
        return;
    }

    showLoading('Konum alınıyor...');

    navigator.geolocation.getCurrentPosition(
        function (position) {
            hideLoading();
            userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };

            // Haritayı konuma taşı
            map.setView([userLocation.lat, userLocation.lng], 13);

            // Kullanıcı marker
            if (userMarker) {
                userMarker.setLatLng([userLocation.lat, userLocation.lng]);
            } else {
                userMarker = L.marker([userLocation.lat, userLocation.lng], {
                    icon: createUserIcon(),
                    zIndexOffset: 10000
                }).addTo(map);
            }

            if (callback) callback(userLocation);
        },
        function (error) {
            hideLoading();
            let message = 'Konum alınamadı';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Konum izni reddedildi';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Konum bilgisi alınamıyor';
                    break;
                case error.TIMEOUT:
                    message = 'Konum isteği zaman aşımına uğradı';
                    break;
            }
            showToast(message, 'error');
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        }
    );
}

/**
 * Kullanıcı ikonu oluştur
 */
function createUserIcon() {
    return L.divIcon({
        className: 'user-marker',
        html: `
            <div class="user-marker-pulse">
                <div style="
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 20px;
                    height: 20px;
                    background: #3b82f6;
                    border: 3px solid white;
                    border-radius: 50%;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                    z-index: 2;
                "></div>
            </div>
        `,
        iconSize: [20, 20],
        iconAnchor: [10, 10]
    });
}

/**
 * İstasyon ikonu oluştur
 */
function createStationIcon(station, isSelected = false) {
    // Fiyat Seviyesine Göre Renk Belirleme
    let bgColor = '#2563eb'; // Varsayılan Mavi

    if (station.price_level === 'cheap') {
        bgColor = '#10b981'; // Yeşil (Ucuz)
    } else if (station.price_level === 'expensive') {
        bgColor = '#ef4444'; // Kırmızı (Pahalı)
    } else if (station.price_level === 'average') {
        bgColor = '#f59e0b'; // Sarı (Ortalama)
    }

    // Fiyat Metni Belirleme
    let priceText;

    if (station.locked) {
        // Teaser Modu (Örn: 4...)
        if (station.diesel_teaser && station.diesel_teaser !== '-') {
            priceText = `<span style="opacity:1">${station.diesel_teaser}</span><span style="opacity:0.5; filter:blur(1px);">...</span>`;
        } else {
            priceText = '<i class="fas fa-lock"></i>';
        }
    } else {
        // Normal Mod
        const price = station.diesel_price;
        if (price && price > 0) {
            const formatted = price.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            priceText = `<span class="digital-price" style="font-size: 1.1rem; color: #fff; text-shadow: none;">${formatted}</span> <span style="font-size: 0.7rem; opacity: 0.9;">TL</span>`;
        } else {
            priceText = '-';
        }
    }

    return L.divIcon({
        className: 'station-marker',
        html: `
            <div style="
                background: ${bgColor};
                color: white;
                padding: 6px 10px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                white-space: nowrap;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                transform: ${isSelected ? 'scale(1.1)' : 'scale(1)'};
                transition: transform 0.2s;
                display: flex; align-items: center; justify-content: center;
                border: 2px solid white;
            ">
                ${priceText}
            </div>
        `,
        iconSize: [80, 30],
        iconAnchor: [40, 15]
    });
}

/**
 * İstasyonları haritaya ekle
 */
function addStationsToMap(stations, onSelect) {
    // Mevcut markerları temizle
    clearMarkers();

    stations.forEach(station => {
        // Z-Index Önceliği (Ucuz olanlar en üstte görünsün)
        let zIndex = 1000;
        if (station.price_level === 'cheap') zIndex = 2000;
        else if (station.price_level === 'expensive') zIndex = 500;

        const marker = L.marker([station.lat, station.lng], {
            icon: createStationIcon(station),
            zIndexOffset: zIndex
        }).addTo(map);

        // Popup
        marker.bindPopup(createStationPopup(station));

        // Click event
        marker.on('click', function () {
            if (onSelect) onSelect(station);
        });

        markers.push(marker);
    });

    // Tüm markerları göster
    if (markers.length > 0) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
}

/**
 * İstasyon popup içeriği
 */
function createStationPopup(station) {
    const formatDigital = (val) => {
        if (!val) return '-';
        const formatted = parseFloat(val).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return `<span class="digital-price" style="font-size: 1rem; color: inherit;">${formatted}</span> <span class="currency" style="font-size: 0.75rem;">TL</span>`;
    };

    const pricesHtml = `
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 8px; margin-bottom: 8px; font-size: 13px; align-items: center;">
            <div style="color: #6b7280;">Mazot:</div>
            <div style="text-align: right; color: #2563eb;">${formatDigital(station.diesel_price)}</div>

            ${station.truck_diesel_price ? `
                <div style="color: #b91c1c; font-weight: 600;">TIR Özel:</div>
                <div style="text-align: right; color: #b91c1c; background: #fee2e2; border-radius: 4px; padding: 2px 4px;">${formatDigital(station.truck_diesel_price)}</div>
            ` : ''}
            
            <div style="color: #6b7280;">Benzin:</div>
            <div style="text-align: right; color: #374151;">${formatDigital(station.gasoline_price)}</div>
            
            <div style="color: #6b7280;">LPG:</div>
            <div style="text-align: right; color: #374151;">${formatDigital(station.lpg_price)}</div>
        </div>
    `;

    return `
        <div class="station-popup">
            <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px;">
                ${station.name}
            </div>
            <div style="color: #6b7280; font-size: 12px; margin-bottom: 12px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">
                ${station.city} ${station.district ? '/ ' + station.district : ''}
            </div>
            ${pricesHtml}
            <a href="/istasyon-detay.php?id=${station.id}" 
               style="display: block; text-align: center; margin-top: 8px; color: white; background: #2563eb; padding: 6px; border-radius: 4px; font-size: 12px; text-decoration: none;">
                Detayları Gör
            </a>
        </div>
    `;
}

/**
 * Markerları temizle
 */
function clearMarkers() {
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];
}

/**
 * Harita üzerinde konum seç (form için)
 */
function enableLocationPicker(callback) {
    map.once('click', function (e) {
        const { lat, lng } = e.latlng;

        // Seçim marker
        if (window.pickerMarker) {
            window.pickerMarker.setLatLng([lat, lng]);
        } else {
            window.pickerMarker = L.marker([lat, lng], {
                draggable: true
            }).addTo(map);

            window.pickerMarker.on('dragend', function () {
                const pos = window.pickerMarker.getLatLng();
                if (callback) callback(pos.lat, pos.lng);
            });
        }

        if (callback) callback(lat, lng);
    });

    // Cursor değiştir
    map.getContainer().style.cursor = 'crosshair';
}

/**
 * Haversine mesafe hesabı
 */
function calculateDistance(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;

    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLng / 2) * Math.sin(dLng / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

/**
 * En yakın istasyonları sırala
 */
function sortByDistance(stations, userLat, userLng) {
    return stations.map(station => ({
        ...station,
        distance: calculateDistance(userLat, userLng, station.lat, station.lng)
    })).sort((a, b) => a.distance - b.distance);
}

/**
 * En ucuz istasyonları sırala
 */
function sortByPrice(stations) {
    return [...stations].sort((a, b) => (a.diesel_price || 999) - (b.diesel_price || 999));
}
