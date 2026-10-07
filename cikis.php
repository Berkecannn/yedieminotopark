<?php
// Oturumu başlat
session_start();

// Tüm oturum değişkenlerini temizle
$_SESSION = array();

// Oturumu sonlandır
session_destroy();

// GÜNCELLENEN SATIR
// Giriş sayfasına yönlendir
header("location: login.php");
exit;
?>