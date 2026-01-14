-- =====================================================
-- UcuzMazot.com Veritabanı Şeması
-- Oluşturma Tarihi: 2026-01-08
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";
SET NAMES utf8mb4;

-- =====================================================
-- 1. KULLANICILAR TABLOSU
-- Şoförler, istasyon yetkilileri ve adminler
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(15) UNIQUE NOT NULL COMMENT 'Telefon numarası (giriş için)',
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Şifrelenmiş parola',
    `role` ENUM('driver', 'station', 'admin') DEFAULT 'driver' COMMENT 'Kullanıcı rolü',
    `name` VARCHAR(100) COMMENT 'Ad soyad',
    `email` VARCHAR(100) COMMENT 'E-posta adresi',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Aktif mi?',
    `is_verified` TINYINT(1) DEFAULT 0 COMMENT 'Telefon doğrulandı mı?',
    `last_login` DATETIME COMMENT 'Son giriş zamanı',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_phone` (`phone`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. İSTASYONLAR TABLOSU
-- Akaryakıt istasyonları
-- =====================================================
CREATE TABLE IF NOT EXISTS `stations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT COMMENT 'İstasyon sahibi/yetkilisi',
    `name` VARCHAR(150) NOT NULL COMMENT 'İstasyon adı',
    `brand` VARCHAR(100) COMMENT 'Marka (Shell, BP, Opet vb.)',
    `address` TEXT COMMENT 'Açık adres',
    `city` VARCHAR(50) NOT NULL COMMENT 'Şehir',
    `district` VARCHAR(50) COMMENT 'İlçe',
    `lat` DECIMAL(10, 8) NOT NULL COMMENT 'Enlem',
    `lng` DECIMAL(11, 8) NOT NULL COMMENT 'Boylam',
    `phone` VARCHAR(15) COMMENT 'İstasyon telefonu',
    `email` VARCHAR(100) COMMENT 'İstasyon e-postası',
    `image` VARCHAR(255) COMMENT 'İstasyon görseli',
    `description` TEXT COMMENT 'Açıklama',
    `facilities` JSON COMMENT 'Olanaklar (tır parkı, duş, restoran vb.)',
    `is_approved` TINYINT(1) DEFAULT 0 COMMENT 'Admin tarafından onaylandı mı?',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Aktif mi?',
    `approved_at` DATETIME COMMENT 'Onay tarihi',
    `approved_by` INT COMMENT 'Onaylayan admin',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_city` (`city`),
    INDEX `idx_location` (`lat`, `lng`),
    INDEX `idx_approved` (`is_approved`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. YAKIT FİYATLARI TABLOSU
-- Güncel mazot fiyatları
-- =====================================================
CREATE TABLE IF NOT EXISTS `fuel_prices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `station_id` INT NOT NULL COMMENT 'İstasyon ID',
    `diesel_price` DECIMAL(6, 2) DEFAULT NULL COMMENT 'Mazot fiyatı (TL)',
    `gasoline_price` DECIMAL(6, 2) DEFAULT NULL COMMENT 'Benzin fiyatı (TL)',
    `lpg_price` DECIMAL(6, 2) DEFAULT NULL COMMENT 'LPG fiyatı (TL)',
    `updated_by` INT COMMENT 'Güncelleyen kullanıcı',
    `is_approved` TINYINT(1) DEFAULT 1 COMMENT 'Onaylı mı?',
    `note` VARCHAR(255) COMMENT 'Not/açıklama',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`station_id`) REFERENCES `stations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_station` (`station_id`),
    INDEX `idx_created` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. FİYAT GEÇMİŞİ TABLOSU (LOG)
-- Fiyat değişiklik kayıtları
-- =====================================================
CREATE TABLE IF NOT EXISTS `price_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `station_id` INT NOT NULL,
    `old_price` DECIMAL(6, 2) COMMENT 'Eski mazot fiyatı',
    `new_price` DECIMAL(6, 2) COMMENT 'Yeni mazot fiyatı',
    `gasoline_old` DECIMAL(6, 2) COMMENT 'Eski benzin fiyatı',
    `gasoline_new` DECIMAL(6, 2) COMMENT 'Yeni benzin fiyatı',
    `lpg_old` DECIMAL(6, 2) COMMENT 'Eski LPG fiyatı',
    `lpg_new` DECIMAL(6, 2) COMMENT 'Yeni LPG fiyatı',
    `changed_by` INT COMMENT 'Değiştiren kullanıcı',
    `ip_address` VARCHAR(45) COMMENT 'IP adresi',
    `changed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`station_id`) REFERENCES `stations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_station_date` (`station_id`, `changed_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. YORUMLAR TABLOSU
-- Kullanıcı değerlendirmeleri
-- =====================================================
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL COMMENT 'Yorum yapan kullanıcı',
    `station_id` INT NOT NULL COMMENT 'İstasyon',
    `rating` TINYINT NOT NULL COMMENT 'Puan (1-5)',
    `comment` TEXT COMMENT 'Yorum metni',
    `is_visible` TINYINT(1) DEFAULT 1 COMMENT 'Görünür mü?',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`station_id`) REFERENCES `stations`(`id`) ON DELETE CASCADE,
    INDEX `idx_station` (`station_id`),
    INDEX `idx_user` (`user_id`),
    CONSTRAINT `chk_rating` CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. KAMPANYALAR TABLOSU
-- İstasyon kampanyaları
-- =====================================================
CREATE TABLE IF NOT EXISTS `campaigns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `station_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL COMMENT 'Kampanya başlığı',
    `description` TEXT COMMENT 'Kampanya açıklaması',
    `discount_amount` DECIMAL(6, 2) COMMENT 'İndirim tutarı',
    `discount_percent` TINYINT COMMENT 'İndirim yüzdesi',
    `start_date` DATE COMMENT 'Başlangıç tarihi',
    `end_date` DATE COMMENT 'Bitiş tarihi',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`station_id`) REFERENCES `stations`(`id`) ON DELETE CASCADE,
    INDEX `idx_station` (`station_id`),
    INDEX `idx_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. SİTE AYARLARI TABLOSU
-- Genel site yapılandırması
-- =====================================================
CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) UNIQUE NOT NULL,
    `setting_value` TEXT,
    `setting_type` ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    `description` VARCHAR(255),
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. AKTİVİTE LOGLARı TABLOSU
-- Sistem aktivite kayıtları
-- =====================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT COMMENT 'İşlemi yapan kullanıcı',
    `action` VARCHAR(100) NOT NULL COMMENT 'Aksiyon tipi',
    `entity_type` VARCHAR(50) COMMENT 'Etkilenen varlık tipi',
    `entity_id` INT COMMENT 'Etkilenen varlık ID',
    `details` JSON COMMENT 'Detay bilgileri',
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VARSAYILAN VERİLER
-- =====================================================

-- Admin kullanıcısı (şifre: admin123)
INSERT INTO `users` (`phone`, `password_hash`, `role`, `name`, `is_active`, `is_verified`) VALUES
('05001234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Site Admin', 1, 1);

-- Site varsayılan ayarları
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'UcuzMazot', 'text', 'Site adı'),
('site_title', 'UcuzMazot - En Ucuz Mazot Fiyatları', 'text', 'Site başlığı'),
('site_description', 'Türkiye genelinde en ucuz mazot fiyatlarını karşılaştırın', 'text', 'Site açıklaması'),
('contact_email', 'info@ucuzmazot.com', 'text', 'İletişim e-postası'),
('contact_phone', '', 'text', 'İletişim telefonu'),
('min_price_alert', '30.00', 'number', 'Minimum fiyat uyarı eşiği (TL)'),
('max_price_alert', '60.00', 'number', 'Maksimum fiyat uyarı eşiği (TL)'),
('default_city', 'İstanbul', 'text', 'Varsayılan şehir'),
('map_default_lat', '41.0082', 'text', 'Harita varsayılan enlem'),
('map_default_lng', '28.9784', 'text', 'Harita varsayılan boylam'),
('map_default_zoom', '10', 'number', 'Harita varsayılan zoom'),
('maintenance_mode', '0', 'boolean', 'Bakım modu');

-- Örnek istasyonlar (test için)
INSERT INTO `stations` (`user_id`, `name`, `brand`, `address`, `city`, `district`, `lat`, `lng`, `phone`, `is_approved`, `is_active`) VALUES
(1, 'Shell İstanbul Merkez', 'Shell', 'Fatih Mah. Ankara Cad. No:123', 'İstanbul', 'Fatih', 41.0082, 28.9784, '02121234567', 1, 1),
(1, 'Opet Ankara Yolu', 'Opet', 'Yıldırım Mah. E-5 Karayolu', 'İstanbul', 'Bağcılar', 41.0391, 28.8563, '02129876543', 1, 1),
(1, 'BP Gebze', 'BP', 'Organize Sanayi Bölgesi', 'Kocaeli', 'Gebze', 40.8027, 29.4307, '02627654321', 1, 1);

-- Örnek fiyatlar
INSERT INTO `fuel_prices` (`station_id`, `diesel_price`, `gasoline_price`, `lpg_price`, `updated_by`, `is_approved`) VALUES
(1, 42.50, 43.50, 22.50, 1, 1),
(2, 41.90, 42.90, 21.90, 1, 1),
(3, 43.25, 44.25, 23.25, 1, 1);

-- =====================================================
-- GÖRÜNÜMLER (VIEWS)
-- =====================================================

-- Güncel fiyatlarla istasyonlar
CREATE OR REPLACE VIEW `v_stations_with_prices` AS
SELECT 
    s.*,
    fp.diesel_price,
    fp.gasoline_price,
    fp.lpg_price,
    fp.created_at as price_updated_at,
    (SELECT AVG(rating) FROM reviews WHERE station_id = s.id AND is_visible = 1) as avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE station_id = s.id AND is_visible = 1) as review_count
FROM stations s
LEFT JOIN (
    SELECT station_id, diesel_price, gasoline_price, lpg_price, created_at
    FROM fuel_prices fp1
    WHERE created_at = (
        SELECT MAX(created_at) 
        FROM fuel_prices fp2 
        WHERE fp2.station_id = fp1.station_id
    )
) fp ON s.id = fp.station_id
WHERE s.is_active = 1 AND s.is_approved = 1;

-- =====================================================
-- NOT: Admin şifresi 'admin123' olarak ayarlanmıştır.
-- Üretim ortamında mutlaka değiştirin!
-- =====================================================
