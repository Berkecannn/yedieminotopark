<?php
// 1. GLOBAL SAAT DİLİMİ AYARI
// Bu dosyanın dahil edildiği her yerde saat Türkiye saati olacak.
date_default_timezone_set('Europe/Istanbul');

// Türkçe tarih/zaman formatları için (İsteğe bağlı ama önerilir)
setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'tr', 'turkish');

// 2. VERİTABANI BİLGİLERİ
define('DB_SERVER', 'localhost:3306'); // Port numaran 3307 ise
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'otopark_db');

// 3. BAĞLANTIYI OLUŞTUR
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// 4. BAĞLANTI KONTROLÜ
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// 5. TÜRKÇE KARAKTER AYARI (Veritabanı için)
$conn->set_charset("utf8mb4");
?>