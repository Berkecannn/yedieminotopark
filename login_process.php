<?php
session_start();
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $kullanici_adi = trim($_POST['kullanici_adi']);
    $girilen_sifre = $_POST['sifre'];

    // 1. ADIM: YÖNETİCİLER TABLOSUNDA ARA (Full Yetki)
    $sql_admin = "SELECT id, kullanici_adi, sifre FROM yoneticiler WHERE kullanici_adi = ?";
    
    if ($stmt = $conn->prepare($sql_admin)) {
        $stmt->bind_param("s", $kullanici_adi);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $username, $hashed_password);
            $stmt->fetch();
            
            // Yönetici şifresi doğrulama (Eğer hashliyse verify, düz metinse direkt kontrol)
            // Not: Güvenlik için hash kullanmanızı öneririm ama eski sistem düz ise ona göre çalışır.
            // Burada password_verify varsayıyoruz.
            if (password_verify($girilen_sifre, $hashed_password)) {
                session_regenerate_id();
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $id;
                $_SESSION["username"] = $username;
                $_SESSION["is_admin"] = true; // YÖNETİCİ OLDUĞUNU BELİRT
                
                header("location: index.php");
                exit;
            }
        }
        $stmt->close();
    }

    // 2. ADIM: EĞER YÖNETİCİ DEĞİLSE, USERS (PERSONEL) TABLOSUNDA ARA
    $sql_user = "SELECT id, username, password, yetkiler FROM users WHERE username = ?";
    
    if ($stmt = $conn->prepare($sql_user)) {
        $stmt->bind_param("s", $kullanici_adi);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($uid, $u_username, $u_password, $u_yetkiler);
            $stmt->fetch();

            if (password_verify($girilen_sifre, $u_password)) {
                session_regenerate_id();
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $uid;
                $_SESSION["username"] = $u_username;
                $_SESSION["is_admin"] = false; // PERSONEL OLDUĞUNU BELİRT
                $_SESSION["yetkiler"] = $u_yetkiler; // Yetkileri Session'a at

                header("location: index.php");
                exit;
            }
        }
        $stmt->close();
    }
    
    // İki tabloda da yoksa hata ver
    header("location: login.php?error=1");
    exit;
}
?>