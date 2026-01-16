<?php
/**
 * UcuzMazot - Akıllı FTP Dağıtım Scripti (Smart Sync v2.0)
 * 
 * Özellikler:
 * - .gitignore kurallarına uyar.
 * - Sadece yerelde değişen dosyaları gönderir (Hızlı).
 * - İlk çalışmada her şeyi gönderir, sonraki çalışmalarda sadece farkları.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = dirname(__DIR__);
$envPath = $root . '/.env';
$statePath = $root . '/.deploy_state.json';

// .env yükle
if (!file_exists($envPath)) {
    die("Hata: .env dosyası bulunamadı!\n");
}

$env = parse_ini_file($envPath);
$host = $env['FTP_HOST'] ?? '';
$user = $env['FTP_USER'] ?? '';
$pass = $env['FTP_PASS'] ?? '';
$remotePath = $env['FTP_PATH'] ?? '/';

// Mevcut durumu yükle
$state = file_exists($statePath) ? json_decode(file_get_contents($statePath), true) : [];
$newState = [];

echo "🚀 Akıllı Dağıtım başlatılıyor: $host\n";

// FTP Bağlantısı
$conn = ftp_connect($host);
if (!$conn || !ftp_login($conn, $user, $pass)) {
    die("Hata: FTP bağlantısı kurulamadı!\n");
}
ftp_pasv($conn, true);

// Atlanacaklar
$ignoreList = ['.git', '.github', '.agent', '.env', 'scripts/deploy.php', '.deploy_state.json', 'node_modules', 'desktop.ini'];
if (file_exists($root . '/.gitignore')) {
    $gitIgnore = file($root . '/.gitignore', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($gitIgnore as $line) {
        if ($line && strpos(trim($line), '#') !== 0)
            $ignoreList[] = trim($line, '/');
    }
}
$ignoreList = array_unique($ignoreList);

$uploadedCount = 0;
$skippedCount = 0;

function uploadSmart($conn, $localPath, $remotePath, $ignoreList, $root, &$state, &$newState, &$uploadedCount, &$skippedCount)
{
    if (!is_dir($localPath))
        return;
    $files = scandir($localPath);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..')
            continue;

        $localFile = $localPath . '/' . $file;
        $relativeFile = str_replace($root . '/', '', $localFile);

        foreach ($ignoreList as $ignore) {
            if ($ignore && strpos($relativeFile, $ignore) === 0)
                continue 2;
        }

        if (is_dir($localFile)) {
            @ftp_mkdir($conn, $remotePath . '/' . $file);
            uploadSmart($conn, $localFile, $remotePath . '/' . $file, $ignoreList, $root, $state, $newState, $uploadedCount, $skippedCount);
        } else {
            $mtime = filemtime($localFile);
            $newState[$relativeFile] = $mtime;

            if (isset($state[$relativeFile]) && $state[$relativeFile] === $mtime) {
                $skippedCount++;
                continue;
            }

            echo "📦 Gönderiliyor: $relativeFile ... ";
            if (ftp_put($conn, $remotePath . '/' . $file, $localFile, FTP_BINARY)) {
                echo "✅\n";
                $uploadedCount++;
            } else {
                echo "❌ HATA\n";
            }
        }
    }
}

uploadSmart($conn, $root, $remotePath, $ignoreList, $root, $state, $newState, $uploadedCount, $skippedCount);

// Durumu kaydet
file_put_contents($statePath, json_encode($newState, JSON_PRETTY_PRINT));
ftp_close($conn);

echo "\n✨ İşlem bitti! Yüklenen: $uploadedCount, Atlanan: $skippedCount\n";
