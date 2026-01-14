# UCUZMAZOT.COM
## Akaryakıt Fiyat Karşılaştırma & Rekabet Platformu
---

## 1. PROJE ÖZETİ

Ucuzmazot.com;  
Türkiye genelindeki akaryakıt istasyonlarının **manuel olarak fiyat girdiği**,  
şoförlerin ise **en ucuz ve avantajlı istasyonu harita üzerinden anında görebildiği**  
rekabet odaklı bir platformdur.

Ana hedef:
- Tır şoförleri ve nakliyecilerin **yakıt maliyetini düşürmek**
- Akaryakıt istasyonlarının **rekabet ederek müşteri çekmesini sağlamak**

---

## 2. HEDEF KİTLE

### Birincil
- Tır şoförleri
- Nakliyeciler
- Uzun yol şoförleri

### İkincil
- Akaryakıt istasyonu sahipleri
- Lojistik firmaları
- Filo yöneticileri

---

## 3. TEMEL PRENSİP

> **Gerçek rekabet = istasyonun kendi fiyatını manuel girmesi**

- Resmi fiyat API’leri (EPGİS vb.) **referans amaçlıdır**
- Kullanıcıya gösterilen fiyatlar **istasyon sahipleri tarafından girilir**
- Amaç:  
  - İstasyonları rekabete zorlamak  
  - Şoförlere gerçek avantaj sunmak  

---

## 4. FİYAT KAYNAK MODELİ (HİBRİT)

### 4.1 Referans Fiyat (Arka Plan)
- EPGİS / resmi tavan fiyat
- Kullanıcıya gösterilmez
- Amaç:
  - Aşırı uçuk fiyatları filtrelemek
  - Kontrol ve güvenlik

### 4.2 Ana Fiyat (Gösterilen)
İstasyon sahibi panelden girer:
- Mazot
- Benzin
- LPG (opsiyonel)
- **TIR’a özel fiyat** (kritik özellik)

---

## 5. İSTASYONLAR NASIL REKABET EDER?

### 5.1 Fiyat Rekabeti
Harita üzerinde renkler:
- 🟢 En ucuz
- 🟡 Ortalama
- 🔴 Pahalı

### 5.2 Premium Öne Çıkma (Gelir Modeli)
- Bölgede öne çık
- “En Ucuz” rozeti
- “TIR Dostu İstasyon” etiketi

### 5.3 Kampanyalar
İstasyon şunları girebilir:
- Saatlik / günlük indirim
- Gece indirimi
- Duş – çay – park avantajları

---

## 6. FİYAT GÜNCELLİK KURALLARI

- Son güncelleme bilgisi gösterilir:
  - “5 dk önce”
  - “2 saat önce”
  - “Dün”

### Otomatik Kurallar
- 24 saati geçen fiyatlar:
  - Sıralamada düşer
  - “Güncel değil” uyarısı alır

Amaç:
- İstasyonu **güncel fiyat girmeye zorlamak**

---

## 7. ŞOFÖR TARAFI GÜVEN SİSTEMİ

### 7.1 Fiyat Onayı
- “Bu fiyattan aldım” butonu
- 3–5 onay → fiyat güvenilir

### 7.2 Şikayet
- “Fiyat tutmadı”
- “Kasada farklı çıktı”

### Yaptırım
- Çok şikayet alan istasyon:
  - Sıralamada düşer
  - Geçici olarak gizlenebilir

---

## 8. İSTASYON PANELİ (UX PRENSİPLERİ)

- Mobil uyumlu
- Tek ekran
- Büyük rakam giriş alanları
- Tek buton: **GÜNCELLE**

Hedef:
> İstasyon sahibi 10 saniyede fiyat girebilmeli

---

## 9. PLATFORM ÖZELLİKLERİ

### Şoför
- Harita üzerinden en ucuz istasyon
- En yakın / en ucuz filtreleri
- Kampanya görüntüleme
- Güven oylaması

### İstasyon
- Manuel fiyat girişi
- Kampanya tanımı
- Premium satın alma
- Görüntülenme & tıklama istatistikleri

---

## 10. GELİR MODELLERİ

- Premium istasyon üyeliği
- Sponsorlu listeleme
- Bölgesel öne çıkarma
- Kurumsal / filo paketleri
- Reklam alanları

---

## 11. PAZARLAMA STRATEJİSİ (ÖZET)

### SEO
- “En ucuz mazot nerede”
- “İl + mazot fiyatları”
- “TIR’a özel mazot”

### Sosyal Medya
- Günlük en ucuz şehir paylaşımları
- Tırcı hikayeleri
- Kampanya duyuruları

### Reklam
- Google Ads (şehir + mazot)
- Instagram / TikTok (şoför hedefleme)

---

## 12. 90 GÜNLÜK YOL HARİTASI

### Ay 1 [TAMAMLANDI ✅]
- **UX & Harita Gelişmiş Özellikler**: Dinamik renkli marker sistemi (Ucuz/Pahalı).
- **İstasyon Paneli**: 10 saniye kuralına uygun hızlı fiyat güncelleme widget'ı.
- **Güven Sistemi**: Kullanıcı bazlı fiyat doğrulama (confirm) altyapısı.
- **TIR Özel Fiyatı**: Veritabanı ve tüm arayüzlerde tam destek.
- **Güncellik**: 24 saati geçen fiyatlar için görsel uyarılar.
- **Admin Kontrolü**: Gelişmiş filtreleme ve sıralama (Marka, Şehir, Fiyat).
- **SEO Altyapısı**: Dinamik `sitemap.php` ve `robots.txt` kurulumu.
- **Admin Panel Responsive Tasarım**: Tüm admin sayfaları mobil ve tablet uyumlu hale getirildi, "İşlemler" butonları dokunmatik cihazlar için optimize edildi.
- **Dijital Fiyat Tabelası**: Tüm sitedeki (Liste, Detay, Harita ve Admin) fiyatlar 7-segment LED tabela görünümüne kavuşturuldu.
- **Sıralama ve Filtreleme**: Admin fiyat listesinde ASC/DESC toggle ve gelişmiş filtre barı hataları giderildi.
- **Güvenlik & Profil**: Şoförler için şifreli giriş sistemi ve profil düzenleme altyapısı tamamlandı.

### Ay 2 [SIRADAKİ ⏳]
- Sosyal medya içerikleri
- İlk reklam testleri
- İlk premium istasyonlar
- Gelişmiş filtreleme (Haritada marka/yakıt türü bazlı)

### Ay 3
- Referans sistemi
- Sadakat / rozet sistemi
- Kurumsal görüşmeler

---

## 13. SONUÇ

Ucuzmazot.com;
- Bilgi sitesi değil
- **Canlı, rekabetçi, ticari bir pazar yeri**dir

Doğru uygulama ile:
- Şoför kazanır
- İstasyon kazanır
- Platform büyür
