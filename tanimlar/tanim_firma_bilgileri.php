<?php
// Oturumu başlat
session_start();

// --- YOL VE MENÜ AYARLARI ---
// Dosya "tanimlar" klasöründe olduğu için bir üst dizine çıkmamız lazım ("../")
$yol = "../"; 
$menu = "tanimlar";

// Veritabanı bağlantısı bir üst klasörde
require_once '../db_config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php"); // Login sayfası da bir üstte
    exit;
}

// --- KAYDETME İŞLEMİ ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'kaydet') {
    
    $firma_adi = mb_strtoupper($_POST['firma_adi'], 'UTF-8');
    $yetkili_adi = mb_strtoupper($_POST['yetkili_adi'], 'UTF-8');
    $telefon = $_POST['telefon'];
    $adres = mb_strtoupper($_POST['adres'], 'UTF-8');
    
    // ID=1 olan kaydı güncelle
    $sql = "UPDATE firma_bilgileri SET firma_adi=?, yetkili_adi=?, telefon=?, adres=? WHERE id=1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $firma_adi, $yetkili_adi, $telefon, $adres);
    
    if ($stmt->execute()) {
        $mesaj = "Firma bilgileri başarıyla güncellendi.";
        $mesaj_tur = "success";
    } else {
        $mesaj = "Hata oluştu: " . $conn->error;
        $mesaj_tur = "danger";
    }
}

// --- MEVCUT BİLGİLERİ ÇEK ---
$sql_get = "SELECT * FROM firma_bilgileri WHERE id = 1";
$result = $conn->query($sql_get);

$firma = $result->fetch_assoc();

// Eğer tablo boşsa varsayılan değerler
if (!$firma) {
    $firma = ['firma_adi' => 'YEDİEMİN OTOPARK İŞLETMESİ', 'yetkili_adi' => '', 'telefon' => '', 'adres' => ''];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Firma Bilgileri Tanımı</title>
    <link rel="stylesheet" href="<?php echo $yol; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .settings-container { max-width: 800px; margin: 30px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e9ecef; }
        .page-header-row h2 { margin: 0; color: #444; font-size: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #555; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; transition: 0.3s; }
        .form-control:focus { border-color: #007bff; outline: none; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn-save { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 15px; width: 100%; }
        .btn-save:hover { background-color: #218838; }
        .note { font-size: 12px; color: #888; margin-top: 5px; }
    </style>
</head>
<body>

    <?php include '../header.php'; // Header da bir üst klasörde ?>

    <div class="main-content">
        
        <div class="settings-container">
            <div class="page-header-row" style="border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:25px;">
                <h2><i class="fas fa-building"></i> Firma Bilgileri Tanımlama</h2>
            </div>

            <?php if(isset($mesaj)): ?>
                <div class="alert alert-<?php echo $mesaj_tur; ?>">
                    <?php echo $mesaj; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="kaydet">
                
                <div class="form-group">
                    <label>Firma / İşletme Adı</label>
                    <input type="text" name="firma_adi" class="form-control force-uppercase" value="<?php echo htmlspecialchars($firma['firma_adi']); ?>" required>
                    <div class="note"><i class="fas fa-info-circle"></i> Bu isim tüm rapor başlıklarında ve makbuzlarda otomatik olarak görünecektir.</div>
                </div>

                <div class="form-group">
                    <label>Yetkili Adı Soyadı</label>
                    <input type="text" name="yetkili_adi" class="form-control force-uppercase" value="<?php echo htmlspecialchars($firma['yetkili_adi']); ?>">
                </div>

                <div class="form-group">
                    <label>İletişim Telefonu</label>
                    <input type="text" name="telefon" class="form-control phone-mask" value="<?php echo htmlspecialchars($firma['telefon']); ?>">
                </div>

                <div class="form-group">
                    <label>Adres</label>
                    <textarea name="adres" class="form-control force-uppercase" rows="3"><?php echo htmlspecialchars($firma['adres']); ?></textarea>
                </div>

                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Bilgileri Güncelle</button>
            </form>
        </div>

    </div>

    <script>
        // Büyük harf zorunluluğu
        document.querySelectorAll('.force-uppercase').forEach(function(input) {
            input.addEventListener('input', function() {
                var start = this.selectionStart; var end = this.selectionEnd;
                this.value = this.value.toUpperCase(); this.setSelectionRange(start, end);
            });
        });
    </script>
</body>
</html>