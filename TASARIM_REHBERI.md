# 🎨 UcuzMazot Premium Tasarım Rehberi

**Bu dosya projenin kök dizininde saklanmalıdır.**  
**Kesinti durumunda bu dosyayı okuyun.**

---

## 📌 Proje Durumu

| Öğe | Durum |
|-----|-------|
| **Mevcut Versiyon** | v1.3.0 |
| **Hedef Versiyon** | v2.0.0 |
| **Başlangıç** | 16.01.2026 |
| **Tamamlanan** | Faz 1, 2, 3, 5, 6, 7 |

---

## 🎯 Tasarım Sistemi

### Renk Paleti
```css
/* Primary */
--primary: #2563eb
--primary-dark: #1d4ed8
--primary-light: #3b82f6

/* Accent */
--accent: #f59e0b (turuncu)
--success: #10b981 (yeşil)
--danger: #ef4444 (kırmızı)
```

### Gradient'ler
```css
/* Kart arka planları */
Light: linear-gradient(145deg, #ffffff, #f8fafc)
Dark: linear-gradient(145deg, rgba(31, 41, 55, 0.98), rgba(17, 24, 39, 0.95))

/* Butonlar */
Primary: linear-gradient(135deg, #2563eb, #1d4ed8)

/* Hover Glow */
box-shadow: 0 20px 40px -10px rgba(59, 130, 246, 0.25)
```

### Tasarım Prensipleri
1. **Glassmorphism**: Yarı şeffaf arka planlar + blur
2. **Gradient**: Düz renk yerine yumuşak geçişler
3. **Glow**: Hover'da mavi parlaklık
4. **Animation**: Subtle hover transform'ları

---

## 📂 Dosya Yapısı

```
sunucuya_gidecek_dosyalar/
├── assets/
│   └── css/
│       ├── style.css      <- ANA STİL DOSYASI
│       └── home.css       <- Ana sayfa özel stiller
├── includes/
│   ├── header.php         <- Üst menü
│   └── footer.php         <- Alt kısım
├── index.php              <- Ana sayfa
├── fiyatlar.php           <- ✅ Tamamlandı
├── sehir.php              <- ✅ Header tamamlandı
├── markalar.php           <- ✅ Tamamlandı
├── hakkimizda.php         <- ⏳ Bekliyor
├── iletisim.php           <- ⏳ Bekliyor
└── istasyon-detay.php     <- ⏳ Bekliyor
```

---

## 🔄 Kesinti Durumunda

1. Bu dosyayı okuyun
2. `brain/task.md` dosyasındaki checklist'i kontrol edin
3. Son tamamlanan [x] öğeden sonraki göreve bakın
4. O görevden devam edin

---

## ✅ Premium CSS Class'ları

| Class | Kullanım |
|-------|----------|
| `.page-header-premium` | Sayfa başlıkları |
| `.filter-card-premium` | Filtre/info kartları |
| `.filter-btn-premium` | Premium butonlar |
| `.update-badge` | Yeşil badge'ler |
| `.station-card` | İstasyon kartları |
| `.price-box` | Fiyat kutuları |
| `.brand-item` | Marka kartları |
