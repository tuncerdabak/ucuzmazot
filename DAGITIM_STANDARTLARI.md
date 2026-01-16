# Proje Dağıtım ve Güncelleme Standartları (v1.0)

Bu doküman, projelerinizde kurduğumuz "Önce FTP (Gönder), Sonra GitHub (Güncelle)" yapısının diğer projelerinize nasıl uygulanacağını açıklar.

## 1. Dosya Yapısı
Her projenin kök dizininde şu yapı bulunmalıdır:
- `scripts/deploy.php`: Akıllı FTP gönderim scripti.
- `.env`: FTP bilgilerini içeren gizli dosya (GitHub'a gönderilmez).
- `.agent/workflows/gonder.md`: "gönder" komutu iş akışı.
- `.agent/workflows/guncelle.md`: "güncelle" komutu iş akışı.

## 2. Sistemin Kurulum Adımları

### Adım 1: .env Dosyasını Hazırlayın
Proje kök dizinine `.env` dosyası oluşturun:
```text
FTP_HOST=ftp.siteniz.com
FTP_USER=kullanici@siteniz.com
FTP_PASS=sifreniz
FTP_PATH=/public_html
IGNORE_GITIGNORE=true
```

### Adım 2: deploy.php Scriptini Kopyalayın
`scripts/deploy.php` dosyasını projeye ekleyin. Bu script:
- **Smart Sync v2.0:** Sadece değişen dosyaları gönderir.
- **Lokal Takip:** `.deploy_state.json` dosyası üzerinden dosya değişikliklerini (mtime) takip eder.
- **Hız:** Değişmeyen dosyaları milisaniyeler içinde atlayarak sadece farkları gönderir.
- `.gitignore` dosyasına bakarak `node_modules`, `.git` gibi kalabalık dosyaları atlar.

### Adım 3: .agent/workflows Klasörünü Yapılandırın
Komutların tetiklenmesi için `.agent/workflows/` altına `gonder.md` ve `guncelle.md` dosyalarını ekleyin.

## 3. Çalışma Mantığı (Best Practices)

1. **Lokal Test:** Değişiklikleri XAMPP/Localhost üzerinde yapın.
2. **"gönder" Komutu:** Değişikliği anında canlıya yansıtmak için kullanın. Smart Sync sayesinde sadece dokunduğunuz dosyalar saniyeler içinde gider.
3. **Canlıda Kontrol:** Sitenin canlı halini kontrol edin.
4. **"güncelle" Komutu:** Her şey yolundaysa, yapılan işi kalıcı olarak yedeklemek için GitHub'a pushlayın.

## 4. Güvenlik ve Önemli Notlar
- **.gitignore:** `.env` ve `.deploy_state.json` dosyalarını mutlaka `.gitignore` içine ekleyin! 
- **State Dosyası:** `.deploy_state.json` silinirse, bir sonraki `gonder` komutu her şeyi tekrar full olarak yükler.
- **Actions:** GitHub Actions üzerinden otomatik FTP dağıtımını iptal edin (çakışma olmaması için).

---
*Bu yapı, geliştirme sürecini hızlandırır ve hatalı kodun GitHub üzerinden otomatik olarak yayına girmesini engeller.*
