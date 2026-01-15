---
description: Yapılan değişiklikleri kontrol et, versiyonla ve GitHub'a gönder
---

Bu workflow, projedeki her güncellemeden sonra çalıştırılmalıdır.

1. **Görsel Kontrol**: 
    - `header.php` dosyasında `viewport-fit=cover` meta etiketinin durduğundan emin ol.
    - `style.css` dosyasında `env(safe-area-inset-bottom)` kuralının silinmediğini kontrol et.
    - Mobilde kritik olan banner veya butonların `safe-area` içinde kalıp kalmadığını kontrol et.

2. **Versiyon Güncelleme**:
    - `config.php` içindeki `SYSTEM_VERSION` değerini bir üst sürüme (örn: v1.0.7 -> v1.0.8) güncelle.

3. **Git İşlemleri**:
// turbo
    - `git add .` komutunu çalıştır.
// turbo
    - `git commit -m "fix/feat: [yapılan değişikliğin kısa özeti]"` mesajıyla commit oluştur.
// turbo
    - `git push` komutu ile değişiklikleri GitHub'a gönder.

4. **Kullanıcı Bilgilendirme**:
    - Kullanıcıya hangi dosyaların güncellendiğini ve versiyonun kaça çıktığını bildir.
