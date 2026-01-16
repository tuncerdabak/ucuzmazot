---
description: Yapılan değişiklikleri GitHub'a yedekler (push)
---

1. Değişiklikleri Git'e ekle:
// turbo
- `git add .`

2. Commit oluştur (kullanıcı mesaj belirtmediyse genel bir mesaj kullan):
// turbo
- `git commit -m "Güncelleme: Sistem iyileştirmeleri ve UI düzeltmeleri"`

3. GitHub'a gönder (Not: Bu işlem artık sunucuya dağıtım yapmaz, sadece yedekler):
// turbo
- `git push origin main`

4. İşlem bittiğinde GitHub Action durumunun kontrol edilebileceğini (https://github.com/tuncerdabak/ucuzmazot/actions) bildir.
