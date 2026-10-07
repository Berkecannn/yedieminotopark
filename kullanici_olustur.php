<?php
require_once 'db_config.php';

// Oluşturulacak test kullanıcısının bilgileri
$kullanici_adi = 'admin';
$sifre_plain = '123456'; // Test için basit bir şifre

// Şifreyi PHP'nin en güvenli fonksiyonu ile şifrele (hash'le)
$hashlenmis_sifre = password_hash($sifre_plain, PASSWORD_DEFAULT);

// SQL Injection'a karşı korumalı olarak veritabanına ekle
$sql = "INSERT INTO yoneticiler (kullanici_adi, sifre) VALUES (?, ?)";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ss", $kullanici_adi, $hashlenmis_sifre);
    
    if ($stmt->execute()) {
        echo "<h2>Kullanıcı Başarıyla Oluşturuldu!</h2>";
        echo "<p><strong>Kullanıcı Adı:</strong> " . htmlspecialchars($kullanici_adi) . "</p>";
        echo "<p>Giriş yaparak sistemi test edebilirsiniz.</p>";
        echo "<p><strong>Güvenlik Uyarısı:</strong> Bu dosyayı işiniz bittikten sonra sunucudan silin.</p>";
    } else {
        echo "<h2>Hata!</h2> <p>Kullanıcı oluşturulamadı. Muhtemelen bu kullanıcı adı zaten mevcut.</p>";
    }
    $stmt->close();
}
$conn->close();
?>