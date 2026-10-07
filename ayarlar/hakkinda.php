<?php
// Oturumu başlat
session_start();

// --- HEADER VE MENÜ AYARLARI ---
$yol = "../"; 
$menu = "ayar"; 

require_once '../db_config.php';

// Güvenlik: Oturum açmamışsa login'e at
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// --- LİSANS VE FİRMA BİLGİSİ ---
// Veritabanından çekme işlemi kaldırıldı. Statik olarak tanımlandı.
$lisans_sahibi = "Yediemin Otopark İşletmesi"; 

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hakkında - Yediemin Otopark Sistemi</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .about-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            max-width: 800px;
            margin: 40px auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
            text-align: center;
        }
        
        /* Header Rengi için CSS Değişkeni Kullanımı (Varsa) */
        .about-header {
            background: linear-gradient(135deg, var(--ana-renk, #2196F3), #1e88e5);
            padding: 40px 20px;
            color: #fff;
        }
        
        .app-icon {
            font-size: 64px;
            margin-bottom: 15px;
            color: rgba(255,255,255,0.9);
        }
        
        .app-name {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .app-version {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 5px;
            font-family: monospace;
        }
        
        .about-body {
            padding: 30px;
            text-align: left;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-box {
            background: #f8f9fa;
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 6px;
        }
        
        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }
        
        .info-value {
            font-size: 15px;
            font-weight: 500;
            color: #333;
        }
        
        .tech-details {
            font-size: 12px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 20px;
            margin-top: 20px;
            text-align: center;
        }
        
        .copyright {
            background: #f1f3f5;
            padding: 15px;
            font-size: 13px;
            color: #555;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-content">
        
        <div class="about-card">
            <div class="about-header">
                <i class="fas fa-parking app-icon"></i>
                <h1 class="app-name">Yediemin Otopark Otomasyonu</h1>
                <div class="app-version">Sürüm 1.0.0 (Kararlı)</div>
            </div>
            
            <div class="about-body">
                <p style="text-align:center; color:#555; margin-bottom:30px; font-size:15px;">
                    Bu yazılım, Yediemin otopark süreçlerinin dijital ortamda güvenli, hızlı ve hatasız bir şekilde yönetilmesi amacıyla geliştirilmiştir.
                </p>

                <div class="info-grid">
                    <div class="info-box">
                        <span class="info-label">Lisans Sahibi</span>
                        <div class="info-value">
                            <i class="fas fa-building" style="color:var(--ana-renk, #2196F3);"></i> 
                            <?php echo htmlspecialchars($lisans_sahibi); ?>
                        </div>
                    </div>
                    
                    <div class="info-box">
                        <span class="info-label">Lisans Durumu</span>
                        <div class="info-value" style="color:#28a745;">
                            <i class="fas fa-check-circle"></i> Aktif / Geçerli
                        </div>
                    </div>

                    <div class="info-box">
                        <span class="info-label">Son Güncelleme</span>
                        <div class="info-value">
                            <?php echo date("d.m.Y"); ?>
                        </div>
                    </div>

                    <div class="info-box">
                        <span class="info-label">Destek & İletişim</span>
                        <div class="info-value">
                            <a href="mailto:destek@yazilim.com" style="text-decoration:none; color:#333;">destek@yazilim.com</a>
                        </div>
                    </div>
                </div>

                <div class="tech-details">
                    <strong>Sistem Bilgileri:</strong> 
                    PHP v<?php echo phpversion(); ?> | 
                    Server: <?php echo $_SERVER['SERVER_SOFTWARE']; ?> | 
                    DB: MySQL
                </div>
            </div>

            <div class="copyright">
                &copy; <?php echo date("Y"); ?> Tüm Hakları Saklıdır. İzinsiz kopyalanması ve dağıtılması yasaktır.
            </div>
        </div>

    </div>

</body>
</html>