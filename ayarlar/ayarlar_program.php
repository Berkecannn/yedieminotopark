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

// YETKİ KONTROLÜ
$kullanici_yetkileri = isset($_SESSION['yetkiler']) ? json_decode($_SESSION['yetkiler'], true) : [];
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

if (!$is_admin && !in_array('ayar_program', $kullanici_yetkileri)) {
    header("location: ../index.php");
    exit;
}

// --- KAYDETME İŞLEMİ ---
$mesaj = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $liste_satir_sayisi = (int)$_POST['liste_satir_sayisi'];
    $site_tema = $_POST['site_tema'];
    $bakim_modu = isset($_POST['bakim_modu']) ? 1 : 0;

    // Tek bir kayıt olduğu için ID=1 güncelliyoruz
    $sql = "UPDATE program_ayarlari SET 
            liste_satir_sayisi=?, site_tema=?, bakim_modu=?
            WHERE id=1";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isi", $liste_satir_sayisi, $site_tema, $bakim_modu);
        if ($stmt->execute()) {
            $mesaj = '<div class="alert success"><i class="fas fa-check-circle"></i> Ayarlar başarıyla güncellendi. Sayfa yenileniyor...</div>';
            header("Refresh: 2"); // Değişikliğin header'da görünmesi için yenile
        } else {
            $mesaj = '<div class="alert error"><i class="fas fa-times-circle"></i> Hata oluştu: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// --- VERİLERİ ÇEK ---
$ayar = $conn->query("SELECT * FROM program_ayarlari WHERE id=1")->fetch_assoc();
if(!$ayar) {
    $ayar = ['liste_satir_sayisi' => 20, 'site_tema' => 'blue', 'bakim_modu' => 0];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Program Ayarları</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .settings-container { max-width: 600px; margin: 20px auto; background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        
        .panel-header { background: #f8f9fa; padding: 15px 25px; border-bottom: 1px solid #ddd; font-weight: bold; color: #444; font-size: 16px; display: flex; align-items: center; gap: 10px; }
        .panel-body { padding: 25px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #444; font-size: 13px; }
        .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        .form-group small { display: block; margin-top: 5px; color: #888; font-size: 11px; }
        
        /* Tema Seçimi Stili */
        .theme-options { display: flex; gap: 15px; }
        .theme-radio { display: none; }
        .theme-label { 
            flex: 1; border: 2px solid #eee; border-radius: 6px; padding: 10px; cursor: pointer; text-align: center; transition: all 0.2s;
            display: flex; flex-direction: column; align-items: center; gap: 5px;
        }
        .theme-color-box { width: 100%; height: 30px; border-radius: 4px; margin-bottom: 5px; }
        
        /* Renkler (Önizleme) */
        .t-blue .theme-color-box { background: linear-gradient(to right, #2196F3, #64B5F6); }
        .t-dark .theme-color-box { background: linear-gradient(to right, #343a40, #495057); }
        .t-red .theme-color-box { background: linear-gradient(to right, #dc3545, #e4606d); }
        .t-green .theme-color-box { background: linear-gradient(to right, #28a745, #5dd879); }

        .theme-radio:checked + .theme-label { border-color: #2196F3; background-color: #f0f7ff; color: #000; font-weight: bold; box-shadow: 0 0 5px rgba(33, 150, 243, 0.3); }

        /* Butonlar */
        .form-footer { padding: 20px; background: #f1f3f5; border-top: 1px solid #ddd; text-align: right; }
        .btn-save { background: #28a745; color: white; padding: 10px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }
        .btn-save:hover { background: #218838; }
        
        /* Alert */
        .alert { padding: 15px; margin: 20px 20px 0 20px; border-radius: 4px; font-size: 14px; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Toggle Switch */
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #dc3545; }
        input:checked + .slider:before { transform: translateX(26px); }
    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-content">
        <div class="page-header-row">
            <h2><i class="fas fa-cogs"></i> Program Ayarları</h2>
        </div>

        <?php echo $mesaj; ?>

        <form method="POST" class="settings-container">
            
            <div class="panel-header">
                <i class="fas fa-sliders-h"></i> Sistem Yapılandırması
            </div>

            <div class="panel-body">
                
                <div class="form-group">
                    <label>Arayüz Teması</label>
                    <div class="theme-options">
                        <label class="t-blue" style="flex:1;">
                            <input type="radio" name="site_tema" value="blue" class="theme-radio" <?php echo ($ayar['site_tema'] == 'blue') ? 'checked' : ''; ?>>
                            <div class="theme-label">
                                <div class="theme-color-box"></div>
                                <span>Klasik Mavi</span>
                            </div>
                        </label>

                        <label class="t-dark" style="flex:1;">
                            <input type="radio" name="site_tema" value="dark" class="theme-radio" <?php echo ($ayar['site_tema'] == 'dark') ? 'checked' : ''; ?>>
                            <div class="theme-label">
                                <div class="theme-color-box"></div>
                                <span>Karanlık Mod</span>
                            </div>
                        </label>

                        <label class="t-red" style="flex:1;">
                            <input type="radio" name="site_tema" value="red" class="theme-radio" <?php echo ($ayar['site_tema'] == 'red') ? 'checked' : ''; ?>>
                            <div class="theme-label">
                                <div class="theme-color-box"></div>
                                <span>Kırmızı</span>
                            </div>
                        </label>
                        
                        <label class="t-green" style="flex:1;">
                            <input type="radio" name="site_tema" value="green" class="theme-radio" <?php echo ($ayar['site_tema'] == 'green') ? 'checked' : ''; ?>>
                            <div class="theme-label">
                                <div class="theme-color-box"></div>
                                <span>Yeşil</span>
                            </div>
                        </label>
                    </div>
                    <small>Seçilen tema, menü çizgileri, butonlar ve vurgu renklerini değiştirecektir.</small>
                </div>

                <hr style="border:0; border-top:1px solid #eee; margin: 25px 0;">

                <div class="form-group">
                    <label>Listelerde Gösterilecek Satır Sayısı</label>
                    <select name="liste_satir_sayisi">
                        <option value="10" <?php echo ($ayar['liste_satir_sayisi'] == 10) ? 'selected' : ''; ?>>10 Satır</option>
                        <option value="20" <?php echo ($ayar['liste_satir_sayisi'] == 20) ? 'selected' : ''; ?>>20 Satır (Önerilen)</option>
                        <option value="50" <?php echo ($ayar['liste_satir_sayisi'] == 50) ? 'selected' : ''; ?>>50 Satır</option>
                        <option value="100" <?php echo ($ayar['liste_satir_sayisi'] == 100) ? 'selected' : ''; ?>>100 Satır</option>
                    </select>
                    <small>Tablolarda sayfalama yaparken bir sayfada kaç kayıt görüneceğini belirler.</small>
                </div>

                <div class="form-group" style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; border-radius: 4px;">
                    <label style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0; cursor:pointer;">
                        <span>
                            <i class="fas fa-tools"></i> <strong>Sistem Bakım Modu</strong><br>
                            <span style="font-weight:normal; font-size:12px; color:#666;">Aktif edildiğinde, <strong>Yöneticiler hariç</strong> hiçbir personel sisteme giriş yapamaz.</span>
                        </span>
                        <label class="switch">
                            <input type="checkbox" name="bakim_modu" <?php echo ($ayar['bakim_modu'] == 1) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </label>
                </div>

            </div>

            <div class="form-footer">
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Ayarları Kaydet</button>
            </div>
        </form>
    </div>

</body>
</html>