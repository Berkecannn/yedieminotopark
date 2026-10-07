<?php
// Oturumu başlat
session_start();
$yol = "../"; 
$menu = "tanimlar";

require_once '../db_config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

function tr_strtoupper($text) {
    $search = array("ç", "i", "ı", "ğ", "ö", "ş", "ü");
    $replace = array("Ç", "İ", "I", "Ğ", "Ö", "Ş", "Ü");
    $text = str_replace($search, $replace, $text);
    return mb_strtoupper($text, 'UTF-8');
}

$aktif_tab = isset($_GET['tab']) ? $_GET['tab'] : 'gelir';
$secili_tur_id = isset($_GET['tur_id']) ? $_GET['tur_id'] : null;
$secili_tur_adi = "";

if ($aktif_tab == 'gelir') {
    $tbl_tur = "tanim_gelir_turleri";
    $tbl_ad  = "tanim_gelir_adlari";
    $baslik  = "GELİR";
    $renk    = "green";
} else {
    $tbl_tur = "tanim_gider_turleri";
    $tbl_ad  = "tanim_gider_adlari";
    $baslik  = "GİDER";
    $renk    = "red";
}

// --- İŞLEMLER ---

// TÜR EKLEME / GÜNCELLEME
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // Tür Ekle
    if ($_POST['action'] == 'tur_ekle') {
        $deger = tr_strtoupper(trim($_POST['tur_adi']));
        if(!empty($deger)) {
            $conn->query("INSERT IGNORE INTO $tbl_tur (tanim_adi) VALUES ('$deger')");
            header("Location: tanim_gelir_gider.php?tab=$aktif_tab"); exit;
        }
    }
    // YENİ: Tür Güncelle
    if ($_POST['action'] == 'tur_guncelle') {
        $id = $_POST['tur_id'];
        $deger = tr_strtoupper(trim($_POST['tur_adi']));
        if(!empty($deger) && !empty($id)) {
            $conn->query("UPDATE $tbl_tur SET tanim_adi = '$deger' WHERE id = $id");
            header("Location: tanim_gelir_gider.php?tab=$aktif_tab"); exit;
        }
    }

    // Ad Ekle
    if ($_POST['action'] == 'ad_ekle') {
        $deger = tr_strtoupper(trim($_POST['tanim_adi']));
        $tur_id = $_POST['tur_id'];
        if(!empty($deger) && !empty($tur_id)) {
            $conn->query("INSERT INTO $tbl_ad (tur_id, tanim_adi) VALUES ($tur_id, '$deger')");
            header("Location: tanim_gelir_gider.php?tab=$aktif_tab&tur_id=$tur_id"); exit;
        }
    }
    // YENİ: Ad Güncelle
    if ($_POST['action'] == 'ad_guncelle') {
        $id = $_POST['ad_id'];
        $deger = tr_strtoupper(trim($_POST['tanim_adi']));
        $tur_id = $_POST['tur_id'];
        if(!empty($deger) && !empty($id)) {
            $conn->query("UPDATE $tbl_ad SET tanim_adi = '$deger' WHERE id = $id");
            header("Location: tanim_gelir_gider.php?tab=$aktif_tab&tur_id=$tur_id"); exit;
        }
    }
}

// SİLME
if (isset($_GET['sil_tur'])) {
    $id = $_GET['sil_tur'];
    $conn->query("DELETE FROM $tbl_tur WHERE id = $id");
    header("Location: tanim_gelir_gider.php?tab=$aktif_tab"); exit;
}
if (isset($_GET['sil_ad']) && isset($_GET['tur_id'])) {
    $id = $_GET['sil_ad'];
    $tid = $_GET['tur_id'];
    $conn->query("DELETE FROM $tbl_ad WHERE id = $id");
    header("Location: tanim_gelir_gider.php?tab=$aktif_tab&tur_id=$tid"); exit;
}

$turler = $conn->query("SELECT * FROM $tbl_tur ORDER BY tanim_adi ASC");
$adlar = [];
if($secili_tur_id) {
    $res = $conn->query("SELECT tanim_adi FROM $tbl_tur WHERE id = $secili_tur_id");
    if($res->num_rows > 0) $secili_tur_adi = $res->fetch_assoc()['tanim_adi'];
    $adlar = $conn->query("SELECT * FROM $tbl_ad WHERE tur_id = $secili_tur_id ORDER BY tanim_adi ASC");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Gelir ve Gider Tanımları</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .tab-container { display: flex; gap: 10px; padding: 20px 20px 0 20px; }
        .tab-btn { padding: 10px 20px; font-size: 14px; font-weight: bold; border: 1px solid #ddd; border-bottom: none; border-radius: 8px 8px 0 0; cursor: pointer; background: #f1f1f1; color: #555; text-decoration: none; display: inline-block; }
        .tab-btn.active-gelir { background: #28a745; color: white; border-color: #28a745; }
        .tab-btn.active-gider { background: #dc3545; color: white; border-color: #dc3545; }
        .def-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 20px; height: calc(100vh - 160px); }
        .def-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; }
        .def-header { background: #f8f9fa; padding: 15px; border-bottom: 1px solid #eee; font-weight: bold; display: flex; justify-content: space-between; align-items: center; text-transform: uppercase; color: #444; }
        .theme-green .def-header { border-top: 3px solid #28a745; color: #28a745; }
        .theme-red .def-header { border-top: 3px solid #dc3545; color: #dc3545; }
        .def-body { padding: 15px; overflow-y: auto; flex-grow: 1; }
        .def-list { list-style: none; padding: 0; margin: 0; }
        .def-list li { display: flex; justify-content: space-between; padding: 8px; border-bottom: 1px solid #eee; align-items: center; }
        .def-list li:hover { background-color: #f1f1f1; }
        .def-list li.active-row { background-color: #e8f5e9; border-left: 4px solid #28a745; font-weight: bold; }
        .theme-red .def-list li.active-row { background-color: #fdecea; border-left: 4px solid #dc3545; }
        .add-form { display: flex; gap: 10px; margin-bottom: 15px; }
        .add-form input { flex-grow: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-mini { padding: 5px 10px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; }
        .btn-red { background: #dc3545; color: white; }
        .btn-edit { background: #007bff; color: white; margin-right: 5px; }
        .btn-theme { color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .btn-cancel { background: #6c757d; color: #fff; border: none; padding: 8px 15px; cursor: pointer; border-radius: 4px; display:none; }
        .bg-green { background-color: #28a745; }
        .bg-red { background-color: #dc3545; }
        .bg-blue { background-color: #007bff; }
    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-content">
        <div class="tab-container">
            <a href="?tab=gelir" class="tab-btn <?php echo ($aktif_tab == 'gelir') ? 'active-gelir' : ''; ?>">
                <i class="fas fa-wallet"></i> GELİR TANIMLARI
            </a>
            <a href="?tab=gider" class="tab-btn <?php echo ($aktif_tab == 'gider') ? 'active-gider' : ''; ?>">
                <i class="fas fa-file-invoice-dollar"></i> GİDER TANIMLARI
            </a>
        </div>

        <div class="def-container <?php echo ($aktif_tab == 'gelir') ? 'theme-green' : 'theme-red'; ?>">
            
            <div class="def-card">
                <div class="def-header"><?php echo $baslik; ?> TÜRLERİ (KATEGORİ)</div>
                <div class="def-body">
                    <form method="POST" class="add-form" id="turForm">
                        <input type="hidden" name="action" id="turAction" value="tur_ekle">
                        <input type="hidden" name="tur_id" id="turId">
                        <input type="text" name="tur_adi" id="turInput" placeholder="Yeni <?php echo $baslik; ?> Türü..." class="force-uppercase" required>
                        <button type="submit" id="turBtn" class="btn-theme bg-<?php echo $renk; ?>"><i class="fas fa-plus"></i></button>
                        <button type="button" id="turCancel" class="btn-cancel" onclick="resetTurForm()"><i class="fas fa-times"></i></button>
                    </form>
                    <ul class="def-list">
                        <?php while($t = $turler->fetch_assoc()): ?>
                            <li class="<?php echo ($t['id'] == $secili_tur_id) ? 'active-row' : ''; ?>">
                                <a href="?tab=<?php echo $aktif_tab; ?>&tur_id=<?php echo $t['id']; ?>" style="text-decoration:none; color:inherit; flex-grow:1;">
                                    <?php echo htmlspecialchars($t['tanim_adi']); ?>
                                </a>
                                <div>
                                    <button type="button" class="btn-mini btn-edit" onclick="duzenleTur('<?php echo $t['id']; ?>', '<?php echo $t['tanim_adi']; ?>')"><i class="fas fa-edit"></i></button>
                                    <a href="?tab=<?php echo $aktif_tab; ?>&sil_tur=<?php echo $t['id']; ?>" onclick="return confirm('Bu türü silmek istediğinize emin misiniz?')" class="btn-mini btn-red"><i class="fas fa-trash"></i></a>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>

            <div class="def-card">
                <div class="def-header">
                    <?php echo $secili_tur_adi ? "$secili_tur_adi KALEMLERİ" : "TANIM LİSTESİ"; ?>
                    <?php if(!$secili_tur_id): ?><small style="font-weight:normal; opacity:0.7; text-transform:none;">(Soldan bir tür seçiniz)</small><?php endif; ?>
                </div>
                <div class="def-body">
                    <?php if($secili_tur_id): ?>
                        <form method="POST" class="add-form" id="adForm">
                            <input type="hidden" name="action" id="adAction" value="ad_ekle">
                            <input type="hidden" name="tur_id" value="<?php echo $secili_tur_id; ?>">
                            <input type="hidden" name="ad_id" id="adId">
                            <input type="text" name="tanim_adi" id="adInput" placeholder="Yeni <?php echo $baslik; ?> Adı..." class="force-uppercase" required>
                            <button type="submit" id="adBtn" class="btn-theme bg-<?php echo $renk; ?>"><i class="fas fa-plus"></i></button>
                            <button type="button" id="adCancel" class="btn-cancel" onclick="resetAdForm()"><i class="fas fa-times"></i></button>
                        </form>
                        <ul class="def-list">
                            <?php if($adlar->num_rows > 0): ?>
                                <?php while($ad = $adlar->fetch_assoc()): ?>
                                    <li>
                                        <span><?php echo htmlspecialchars($ad['tanim_adi']); ?></span>
                                        <div>
                                            <button type="button" class="btn-mini btn-edit" onclick="duzenleAd('<?php echo $ad['id']; ?>', '<?php echo $ad['tanim_adi']; ?>')"><i class="fas fa-edit"></i></button>
                                            <a href="?tab=<?php echo $aktif_tab; ?>&tur_id=<?php echo $secili_tur_id; ?>&sil_ad=<?php echo $ad['id']; ?>" onclick="return confirm('Silmek istediğinize emin misiniz?')" class="btn-mini btn-red"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li style="color:#999; justify-content:center;">Henüz bu türe ait tanım eklenmemiş.</li>
                            <?php endif; ?>
                        </ul>
                    <?php else: ?>
                        <div style="text-align:center; padding:50px; color:#999;">
                            <i class="fas fa-arrow-left"></i> <?php echo $baslik; ?> adlarını görmek veya eklemek için sol listeden bir kategori seçiniz.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.querySelectorAll('.force-uppercase').forEach(function(input) {
            input.addEventListener('input', function() {
                var start = this.selectionStart; var end = this.selectionEnd;
                this.value = this.value.toLocaleUpperCase('tr-TR'); 
                this.setSelectionRange(start, end);
            });
        });

        // TÜR DÜZENLEME JS
        function duzenleTur(id, adi) {
            document.getElementById('turAction').value = 'tur_guncelle';
            document.getElementById('turId').value = id;
            document.getElementById('turInput').value = adi;
            document.getElementById('turBtn').className = 'btn-theme bg-blue';
            document.getElementById('turBtn').innerHTML = '<i class="fas fa-save"></i>';
            document.getElementById('turCancel').style.display = 'block';
        }
        function resetTurForm() {
            document.getElementById('turAction').value = 'tur_ekle';
            document.getElementById('turId').value = '';
            document.getElementById('turInput').value = '';
            document.getElementById('turBtn').className = 'btn-theme bg-<?php echo $renk; ?>';
            document.getElementById('turBtn').innerHTML = '<i class="fas fa-plus"></i>';
            document.getElementById('turCancel').style.display = 'none';
        }

        // AD DÜZENLEME JS
        function duzenleAd(id, adi) {
            document.getElementById('adAction').value = 'ad_guncelle';
            document.getElementById('adId').value = id;
            document.getElementById('adInput').value = adi;
            document.getElementById('adBtn').className = 'btn-theme bg-blue';
            document.getElementById('adBtn').innerHTML = '<i class="fas fa-save"></i>';
            document.getElementById('adCancel').style.display = 'block';
        }
        function resetAdForm() {
            document.getElementById('adAction').value = 'ad_ekle';
            document.getElementById('adId').value = '';
            document.getElementById('adInput').value = '';
            document.getElementById('adBtn').className = 'btn-theme bg-<?php echo $renk; ?>';
            document.getElementById('adBtn').innerHTML = '<i class="fas fa-plus"></i>';
            document.getElementById('adCancel').style.display = 'none';
        }
    </script>
</body>
</html>