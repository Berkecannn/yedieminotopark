<?php
// Oturumu başlat
session_start();

// --- MENÜ YOL AYARI ---
$yol = "../";

// --- AKTİF MENÜ AYARI ---
$menu = "arac_kayit"; 

// --- GLOBAL AYAR DOSYASINI DAHİL ET ---
require_once '../db_config.php';

// Kullanıcı giriş yapmamışsa, login sayfasına geri yönlendir
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ============================================================================
// AJAX İŞLEMLERİ (EKLEME VE VERİ ÇEKME)
// ============================================================================
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'İşlem başarısız'];

    // 1. MARKA EKLEME
    if ($_POST['ajax_action'] == 'marka_ekle') {
        $marka = mb_strtoupper(trim($_POST['deger']), 'UTF-8');
        if (!empty($marka)) {
            $conn->query("INSERT IGNORE INTO tanim_marka (marka_adi) VALUES ('$marka')");
            $response = ['status' => 'success', 'message' => 'Marka tanımlara eklendi.'];
        }
    } 
    // 2. MODEL EKLEME
    elseif ($_POST['ajax_action'] == 'model_ekle') {
        $marka_adi = mb_strtoupper(trim($_POST['ust_deger']), 'UTF-8');
        $model_adi = mb_strtoupper(trim($_POST['deger']), 'UTF-8');
        
        if (!empty($marka_adi) && !empty($model_adi)) {
            $res = $conn->query("SELECT id FROM tanim_marka WHERE marka_adi = '$marka_adi'");
            if ($res->num_rows > 0) {
                $marka_id = $res->fetch_assoc()['id'];
            } else {
                $conn->query("INSERT INTO tanim_marka (marka_adi) VALUES ('$marka_adi')");
                $marka_id = $conn->insert_id;
            }
            $conn->query("INSERT INTO tanim_model (marka_id, model_adi) VALUES ($marka_id, '$model_adi')");
            $response = ['status' => 'success', 'message' => 'Model tanımlara eklendi.'];
        } else {
            $response = ['status' => 'error', 'message' => 'Model eklemek için önce Marka bilgisini giriniz.'];
        }
    }
    // 3. MARKAYA GÖRE MODELLERİ GETİR
    elseif ($_POST['ajax_action'] == 'get_modeller') {
        $marka_adi = mb_strtoupper(trim($_POST['marka_adi']), 'UTF-8');
        $modeller = [];
        if (!empty($marka_adi)) {
            $res = $conn->query("SELECT id FROM tanim_marka WHERE marka_adi = '$marka_adi'");
            if ($res->num_rows > 0) {
                $marka_id = $res->fetch_assoc()['id'];
                $res_m = $conn->query("SELECT model_adi FROM tanim_model WHERE marka_id = $marka_id ORDER BY model_adi ASC");
                while($row = $res_m->fetch_assoc()) {
                    $modeller[] = $row['model_adi'];
                }
            }
        }
        echo json_encode(['status' => 'success', 'models' => $modeller]);
        exit;
    }
    // 4. MEN NEDENİ EKLEME
    elseif ($_POST['ajax_action'] == 'men_nedeni_ekle') {
        $neden = mb_strtoupper(trim($_POST['deger']), 'UTF-8');
        if (!empty($neden)) {
            $conn->query("INSERT IGNORE INTO tanim_men_nedeni (men_nedeni) VALUES ('$neden')");
            $response = ['status' => 'success', 'message' => 'Men nedeni tanımlara eklendi.'];
        }
    }
    // 5. ARAÇ CİNSİ EKLEME
    elseif ($_POST['ajax_action'] == 'cins_ekle') {
        $cins = mb_strtoupper(trim($_POST['deger']), 'UTF-8');
        if (!empty($cins)) {
            $conn->query("INSERT IGNORE INTO tanim_arac_cins (cins_adi, gunluk_ucret) VALUES ('$cins', 0)");
            $response = ['status' => 'success', 'message' => 'Araç cinsi tanımlara eklendi.'];
        }
    }
    // 6. KURUM EKLEME
    elseif ($_POST['ajax_action'] == 'kurum_ekle') {
        $kurum = mb_strtoupper(trim($_POST['deger']), 'UTF-8');
        if (!empty($kurum)) {
            $conn->query("INSERT IGNORE INTO tanim_kurum (kurum_adi) VALUES ('$kurum')");
            $response = ['status' => 'success', 'message' => 'Kurum tanımlara eklendi.'];
        }
    }
    // 7. HACİZ KURUM EKLEME
    elseif ($_POST['ajax_action'] == 'haciz_kurum_ekle') {
        $hkurum = mb_strtoupper(trim($_POST['deger']), 'UTF-8');
        if (!empty($hkurum)) {
            $conn->query("INSERT IGNORE INTO tanim_haciz_kurum (kurum_adi) VALUES ('$hkurum')");
            $response = ['status' => 'success', 'message' => 'Haciz yetkili kurumu tanımlara eklendi.'];
        }
    }
    // 8. ÇEKİCİ FİRMA EKLEME
    elseif ($_POST['ajax_action'] == 'cekici_ekle') {
        $firma = mb_strtoupper(trim($_POST['deger']), 'UTF-8');
        if (!empty($firma)) {
            // Yeni eklenen firmanın ücreti varsayılan olarak 0 olur
            $conn->query("INSERT IGNORE INTO tanim_cekici (firma_adi, ucret) VALUES ('$firma', 0)");
            $response = ['status' => 'success', 'message' => 'Çekici firma tanımlara eklendi.'];
        }
    }
    // 9. ÇEKİCİ ÜCRETİ GETİR
    elseif ($_POST['ajax_action'] == 'get_cekici_ucret') {
        $firma = mb_strtoupper(trim($_POST['firma_adi']), 'UTF-8');
        $ucret = 0;
        if (!empty($firma)) {
            $stmt = $conn->prepare("SELECT ucret FROM tanim_cekici WHERE firma_adi = ?");
            $stmt->bind_param("s", $firma);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $ucret = floatval($row['ucret']);
            }
        }
        echo json_encode(['status' => 'success', 'ucret' => $ucret]);
        exit;
    }
    
    echo json_encode($response);
    exit;
}

// ============================================================================
// VERİTABANINDAN VERİLERİ ÇEK
// ============================================================================
$db_markalar = $conn->query("SELECT * FROM tanim_marka ORDER BY marka_adi ASC");
$db_men_nedenleri = $conn->query("SELECT * FROM tanim_men_nedeni ORDER BY men_nedeni ASC");
$db_cinsler = $conn->query("SELECT * FROM tanim_arac_cins ORDER BY cins_adi ASC");
$db_kurumlar = $conn->query("SELECT * FROM tanim_kurum ORDER BY kurum_adi ASC");
$db_haciz_kurumlar = $conn->query("SELECT * FROM tanim_haciz_kurum ORDER BY kurum_adi ASC");
$db_cekiciler = $conn->query("SELECT * FROM tanim_cekici ORDER BY firma_adi ASC");

// KONUM VERİLERİNİ ÇEK (Ada, Parsel, Sıra)
$db_parseller = $conn->query("SELECT DISTINCT parsel_no FROM tanim_otopark_konum ORDER BY parsel_no ASC");
$db_adalar    = $conn->query("SELECT DISTINCT ada_no FROM tanim_otopark_konum ORDER BY ada_no ASC");
$db_siralar   = $conn->query("SELECT DISTINCT park_sira_no FROM tanim_otopark_konum ORDER BY park_sira_no ASC");

// --- DÜZENLEME MODU KONTROLÜ ---
$is_edit = false; 
$arac = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM araclar WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $arac = $result->fetch_assoc();
            $is_edit = true; 
        }
    }
}

// ============================================================================
// DOLULUK KONTROLÜ VE KAPASİTE
// ============================================================================
$sql_kapasite = "SELECT COUNT(*) as toplam FROM tanim_otopark_konum";
$sql_dolu     = "SELECT COUNT(*) as dolu FROM araclar WHERE durumu = 'Otoparkta'";

$res_kapasite = $conn->query($sql_kapasite);
$res_dolu     = $conn->query($sql_dolu);

$toplam_kapasite = ($res_kapasite->num_rows > 0) ? $res_kapasite->fetch_assoc()['toplam'] : 100;
$dolu_arac_sayisi = ($res_dolu->num_rows > 0) ? $res_dolu->fetch_assoc()['dolu'] : 0;
$bos_yer_sayisi   = $toplam_kapasite - $dolu_arac_sayisi;
if($bos_yer_sayisi < 0) $bos_yer_sayisi = 0;

// Otopark Dolu Mu? (Eğer yeni kayıt yapılacaksa ve yer yoksa true olur)
$otopark_dolu = false;
if (!$is_edit && $dolu_arac_sayisi >= $toplam_kapasite) {
    $otopark_dolu = true;
}

function val($key) {
    global $arac, $is_edit;
    return $is_edit && isset($arac[$key]) ? htmlspecialchars($arac[$key]) : '';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_edit ? 'Araç Düzenle' : 'Detaylı Araç Kayıt'; ?> - Yediemin Otopark</title>
    <link rel="stylesheet" href="<?php echo $yol; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-content">
        
        <?php if($otopark_dolu): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 20px; font-weight: bold; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 5px;"></i><br>
            DİKKAT: OTOPARK KAPASİTESİ DOLU! (<?php echo $toplam_kapasite; ?> / <?php echo $toplam_kapasite; ?>)<br>
            <span style="font-weight: normal; font-size: 14px;">Yeni araç kaydı yapabilmek için mevcut araçlardan çıkış yapmalısınız veya kapasite tanımlarını artırmalısınız.</span>
        </div>
        <?php endif; ?>

        <div class="toolbar-container">
            <div class="toolbar-left">
                <?php if($is_edit): ?>
                    <span class="badge badge-warning" style="font-size:14px; padding:8px;">DÜZENLEME MODU (ID: <?php echo $arac['id']; ?>)</span>
                <?php else: ?>
                    <span class="badge badge-success" style="font-size:14px; padding:8px;">YENİ KAYIT MODU</span>
                <?php endif; ?>
                <a href="arac_kayit_detayli.php" class="btn"><i class="fas fa-plus"></i> Temizle / Yeni</a>
            </div>
            <div class="toolbar-right">
                <button type="submit" form="aracKayitFormu" class="btn btn-success" <?php echo $otopark_dolu ? 'disabled style="opacity:0.5; cursor:not-allowed;" title="Otopark dolu olduğu için kayıt yapılamaz"' : ''; ?>>
                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Güncelle' : 'Kaydet'; ?>
                </button>
                <a href="arac_sorgula.php" class="btn btn-danger"><i class="fas fa-times"></i> Vazgeç</a>
            </div>
        </div>

        <form id="aracKayitFormu" action="kayit_islemi.php" method="POST">
            <input type="hidden" name="id" value="<?php echo val('id'); ?>">

            <div class="date-row-wrapper">
                <div class="date-item">
                    <label>Kayıt Tarihi:</label>
                    <input type="datetime-local" name="kayit_tarihi" value="<?php echo $is_edit ? date('Y-m-d\TH:i', strtotime($arac['kayit_tarihi'])) : date('Y-m-d\TH:i'); ?>">
                </div>
                <div class="date-item">
                    <label>Giriş Tarihi:</label>
                    <input type="datetime-local" name="giris_tarihi" value="<?php echo $is_edit ? date('Y-m-d\TH:i', strtotime($arac['giris_tarihi'])) : date('Y-m-d\TH:i'); ?>">
                </div>
                
                <?php if($is_edit && !empty($arac['cikis_tarihi'])): ?>
                <div class="date-item">
                    <label>Çıkış Tarihi:</label>
                    <input type="datetime-local" name="cikis_tarihi_gosterim" value="<?php echo date('Y-m-d\TH:i', strtotime($arac['cikis_tarihi'])); ?>" readonly style="background-color:#f8d7da; color:#721c24; border-color:#f5c6cb;">
                </div>
                <?php endif; ?>

                <div class="date-item">
                    <label>Fiş No:</label>
                    <input type="text" name="fis_no" class="force-uppercase no-space" value="<?php echo val('fis_no'); ?>">
                </div>
                <div class="date-item">
                    <label>Cilt No:</label>
                    <input type="text" name="cilt_no" class="force-uppercase" value="<?php echo val('cilt_no'); ?>">
                </div>
            </div>

            <div class="form-grid">

                <div class="grid-column">
                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-car"></i> Araç Bilgileri</div>
                        <div class="form-card-body">
                            
                            <div class="form-field">
                                <label>Plakası:</label>
                                <div class="input-with-toggle">
                                    <div class="labeled-switch-wrapper">
                                        <span class="switch-label yok">YOK</span>
                                        <div class="toggle-wrapper small-toggle">
                                            <input type="checkbox" id="plaka_var" name="plaka_var" class="toggle-checkbox" onchange="togglePlakaDurumu()" <?php echo (!$is_edit || val('plaka') != 'PLAKASIZ') ? 'checked' : ''; ?>>
                                            <label for="plaka_var" class="toggle-label"></label>
                                        </div>
                                        <span class="switch-label var">VAR</span>
                                    </div>
                                    <input type="text" id="plakaInput" name="plaka" required class="force-uppercase no-space" style="margin-left: 10px; <?php echo ($is_edit && val('plaka') == 'PLAKASIZ') ? 'display:none;' : ''; ?>" value="<?php echo ($is_edit && val('plaka') != 'PLAKASIZ') ? val('plaka') : ''; ?>">
                                </div>
                            </div>

                            <div class="form-field">
                                <label>Cinsi:</label>
                                <div class="input-group-button">
                                    <input type="text" list="cinsListesi" id="cinsInput" name="cinsi" class="force-uppercase" placeholder="Seç veya Yaz" value="<?php echo val('cinsi'); ?>">
                                    <datalist id="cinsListesi">
                                        <?php 
                                        if($db_cinsler->num_rows > 0) {
                                            $db_cinsler->data_seek(0);
                                            while($c = $db_cinsler->fetch_assoc()) { echo "<option value='".htmlspecialchars($c['cins_adi'])."'>"; }
                                        }
                                        ?>
                                    </datalist>
                                    <button type="button" class="btn-icon" onclick="yeniOgeEkle('cinsInput', 'cinsListesi')" title="Ekle"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label>Marka / Model:</label>
                                <div class="dual-input">
                                    <div class="input-group-button">
                                        <input type="text" list="markaListesi" id="markaInput" name="marka" class="force-uppercase" placeholder="Marka Seç/Yaz" value="<?php echo val('marka'); ?>" autocomplete="off">
                                        <datalist id="markaListesi">
                                            <?php 
                                            if($db_markalar->num_rows > 0) {
                                                $db_markalar->data_seek(0);
                                                while($m = $db_markalar->fetch_assoc()) { echo "<option value='".htmlspecialchars($m['marka_adi'])."'>"; }
                                            }
                                            ?>
                                        </datalist>
                                        <button type="button" class="btn-icon" onclick="yeniOgeEkle('markaInput', 'markaListesi')" title="Ekle"><i class="fas fa-plus"></i></button>
                                    </div>
                                    
                                    <div class="input-group-button">
                                        <input type="text" list="modelListesi" id="modelInput" name="model" class="force-uppercase" placeholder="Model Seç/Yaz" value="<?php echo val('model'); ?>" autocomplete="off">
                                        <datalist id="modelListesi"></datalist>
                                        <button type="button" class="btn-icon" onclick="yeniOgeEkle('modelInput', 'modelListesi')" title="Ekle"><i class="fas fa-plus"></i></button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-field">
                                <label>Yıl / KM:</label>
                                <div class="dual-input">
                                    <select name="yil">
                                        <option value="">Yıl Seç</option>
                                        <?php $currentYear = date("Y"); for ($i = $currentYear; $i >= $currentYear - 50; $i--) { $selected = ($is_edit && $arac['yil'] == $i) ? 'selected' : ''; echo "<option value='$i' $selected>$i</option>"; } ?>
                                    </select>
                                    <input type="number" name="km" placeholder="KM" min="0" value="<?php echo val('km'); ?>">
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label>Rengi:</label>
                                <input type="text" list="renkListesi" id="renkInput" name="renk" class="force-uppercase" value="<?php echo val('renk'); ?>">
                            </div>

                            <div class="form-field">
                                <label>Motor No:</label>
                                <input type="text" name="motor_no" class="force-uppercase no-space" value="<?php echo val('motor_no'); ?>">
                            </div>
                            <div class="form-field">
                                <label>Şasi No:</label>
                                <input type="text" name="sasi_no" class="force-uppercase no-space" value="<?php echo val('sasi_no'); ?>">
                            </div>
                            <div class="form-field">
                                <label>Durumu:</label>
                                <select name="durumu" style="font-weight:bold; color: <?php echo ($is_edit && $arac['durumu'] == 'CikisYapildi') ? 'red' : 'green'; ?>;">
                                    <option value="Otoparkta" <?php echo ($is_edit && $arac['durumu'] == 'Otoparkta') ? 'selected' : ''; ?>>Otoparkta</option>
                                    <option value="CikisYapildi" <?php echo ($is_edit && $arac['durumu'] == 'CikisYapildi') ? 'selected' : ''; ?>>Çıkış Yapıldı</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-building"></i> Teslim Eden Kurum</div>
                        <div class="form-card-body">
                            <div class="form-field">
                                <label>Kurum Adı:</label>
                                <div class="input-group-button">
                                    <input type="text" list="kurumListesi" id="kurumInput" name="kurum_adi" class="force-uppercase" placeholder="Seç veya Yaz" value="<?php echo val('kurum_adi'); ?>">
                                    <datalist id="kurumListesi">
                                        <?php 
                                        if($db_kurumlar->num_rows > 0) {
                                            $db_kurumlar->data_seek(0);
                                            while($k = $db_kurumlar->fetch_assoc()) { echo "<option value='".htmlspecialchars($k['kurum_adi'])."'>"; }
                                        }
                                        ?>
                                    </datalist>
                                    <button type="button" class="btn-icon" onclick="yeniOgeEkle('kurumInput', 'kurumListesi')" title="Ekle"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Men Nedeni:</label>
                                <div class="input-group-button">
                                    <input type="text" list="menNedenleri" id="menNedeniInput" name="men_nedeni" class="force-uppercase" placeholder="Seç veya Yaz" value="<?php echo val('men_nedeni'); ?>">
                                    <datalist id="menNedenleri">
                                        <?php 
                                        if($db_men_nedenleri->num_rows > 0) {
                                            $db_men_nedenleri->data_seek(0);
                                            while($mn = $db_men_nedenleri->fetch_assoc()) { echo "<option value='".htmlspecialchars($mn['men_nedeni'])."'>"; }
                                        }
                                        ?>
                                    </datalist>
                                    <button type="button" class="btn-icon" onclick="yeniOgeEkle('menNedeniInput', 'menNedenleri')"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Men No:</label>
                                <input type="text" name="men_no" class="force-uppercase" value="<?php echo val('men_no'); ?>">
                            </div>
                            <div class="form-field">
                                <label>Ekip Kodu:</label>
                                <input type="text" name="ekip_kodu" class="force-uppercase" value="<?php echo val('ekip_kodu'); ?>">
                            </div>
                            <div class="form-field">
                                <label>Men Yeri/Açık.:</label>
                                <textarea name="men_yeri_aciklama" rows="3" class="force-uppercase"><?php echo val('men_yeri_aciklama'); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid-column">
                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-user"></i> Sürücü / Sahibi</div>
                        <div class="form-card-body">
                            <div class="form-field">
                                <label>T.C. / Vergi:</label>
                                <input type="text" name="tc_no" maxlength="11" class="numeric-only" value="<?php echo val('tc_no'); ?>">
                            </div>
                            <div class="form-field">
                                <label>Adı Soyadı:</label>
                                <input type="text" name="surucu_adi" class="force-uppercase" value="<?php echo val('surucu_adi'); ?>">
                            </div>
                            <div class="form-field">
                                <label>Baba Adı:</label>
                                <input type="text" name="baba_adi" class="force-uppercase" value="<?php echo val('baba_adi'); ?>">
                            </div>
                            <div class="form-field">
                                <label>D. Yeri / Yılı:</label>
                                <div class="dual-input">
                                    <input type="text" name="dogum_yeri" placeholder="Doğum Yeri" class="force-uppercase" value="<?php echo val('dogum_yeri'); ?>">
                                    <select name="dogum_yili">
                                        <option value="">Yıl Seç</option>
                                        <?php for ($i = $currentYear; $i >= $currentYear - 90; $i--) { $selected = ($is_edit && $arac['dogum_yili'] == $i) ? 'selected' : ''; echo "<option value='$i' $selected>$i</option>"; } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Telefonlar:</label>
                                <div class="dual-input">
                                    <input type="text" name="tel1" class="phone-mask numeric-only" value="<?php echo val('tel1'); ?>">
                                    <input type="text" name="tel2" class="phone-mask numeric-only" value="<?php echo val('tel2'); ?>">
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Adres:</label>
                                <textarea name="adres" rows="2" class="force-uppercase"><?php echo val('adres'); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-truck-pickup"></i> Çekici Bilgileri</div>
                        <div class="form-card-body">
                            <div class="form-field">
                                <label>Ünvanı:</label>
                                <div class="input-group-button">
                                    <input type="text" list="cekiciListesi" id="cekiciInput" name="cekici_unvan" class="force-uppercase" placeholder="Firma Seç/Yaz" value="<?php echo val('cekici_unvan'); ?>">
                                    <datalist id="cekiciListesi">
                                        <?php 
                                        if($db_cekiciler->num_rows > 0) {
                                            $db_cekiciler->data_seek(0);
                                            while($ck = $db_cekiciler->fetch_assoc()) { echo "<option value='".htmlspecialchars($ck['firma_adi'])."'>"; }
                                        }
                                        ?>
                                    </datalist>
                                    <button type="button" class="btn-icon" onclick="yeniOgeEkle('cekiciInput', 'cekiciListesi')" title="Ekle"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Plaka / Şoför:</label>
                                <div class="dual-input">
                                    <input type="text" list="cekiciPlakaListesi" id="cekiciPlakaInput" name="cekici_plaka" class="force-uppercase no-space" placeholder="Plaka" value="<?php echo val('cekici_plaka'); ?>">
                                    
                                    <input type="text" list="soforListesi" id="soforInput" name="cekici_sofor" class="force-uppercase" placeholder="Şoför" value="<?php echo val('cekici_sofor'); ?>">
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Ücreti:</label>
                                <input type="number" name="cekici_ucret" step="0.01" min="0" value="<?php echo val('cekici_ucret'); ?>">
                            </div>
                            <div class="form-field">
                                <label>Not:</label>
                                <textarea name="cekici_not" rows="2" class="force-uppercase"><?php echo val('cekici_not'); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid-column">
                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-map-marker-alt"></i> Konum ve Anahtar</div>
                        <div class="form-card-body">
                            <div class="arazi-row">
                                <div class="mini-input">
                                    <label>Ada No</label>
                                    <input type="text" list="adaListesi" name="ada_no" class="force-uppercase" value="<?php echo val('ada_no'); ?>">
                                    <datalist id="adaListesi">
                                        <?php if($db_adalar->num_rows > 0) {
                                            $db_adalar->data_seek(0);
                                            while($a = $db_adalar->fetch_assoc()) { echo "<option value='".htmlspecialchars($a['ada_no'])."'>"; }
                                        } ?>
                                    </datalist>
                                </div>
                                <div class="mini-input">
                                    <label>Parsel No</label>
                                    <input type="text" list="parselListesi" name="parsel_no" class="force-uppercase" value="<?php echo val('parsel_no'); ?>">
                                    <datalist id="parselListesi">
                                        <?php if($db_parseller->num_rows > 0) {
                                            $db_parseller->data_seek(0);
                                            while($p = $db_parseller->fetch_assoc()) { echo "<option value='".htmlspecialchars($p['parsel_no'])."'>"; }
                                        } ?>
                                    </datalist>
                                </div>
                                <div class="mini-input">
                                    <label>Park S. No</label>
                                    <input type="text" list="siraListesi" name="park_sira_no" class="force-uppercase" value="<?php echo val('park_sira_no'); ?>">
                                    <datalist id="siraListesi">
                                        <?php if($db_siralar->num_rows > 0) {
                                            $db_siralar->data_seek(0);
                                            while($s = $db_siralar->fetch_assoc()) { echo "<option value='".htmlspecialchars($s['park_sira_no'])."'>"; }
                                        } ?>
                                    </datalist>
                                </div>
                            </div>
                            <hr class="form-divider">
                            <div class="konum-flex-container">
                                <div class="chart-wrapper"><canvas id="dolulukGrafigi"></canvas><span class="chart-label">Otopark</span></div>
                                <div class="anahtar-dolap-wrapper">
                                    <div class="form-field-row no-border">
                                        <div class="switch-container">
                                            <label style="font-size:11px; display:block; margin-bottom:2px; text-align:center; color:#666;">Anahtar</label>
                                            <div class="labeled-switch-wrapper">
                                                <span class="switch-label yok">YOK</span>
                                                <div class="toggle-wrapper small-toggle">
                                                    <input type="checkbox" id="anahtar_var" name="anahtar_var" class="toggle-checkbox" <?php echo (!$is_edit || $arac['anahtar_var'] == 1) ? 'checked' : ''; ?>>
                                                    <label for="anahtar_var" class="toggle-label"></label>
                                                </div>
                                                <span class="switch-label var">VAR</span>
                                            </div>
                                        </div>
                                        <div class="dolap-sira-inputs">
                                            <div class="mini-input-group"><label>Dolap</label><input type="number" name="dolap_no" min="0" value="<?php echo val('dolap_no'); ?>" style="width: 60px;"></div>
                                            <div class="mini-input-group"><label>Sıra</label><input type="number" name="sira_no" min="0" value="<?php echo val('sira_no'); ?>" style="width: 60px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-card">
                        <div class="form-card-header"><i class="fas fa-file-contract"></i> Haciz Bilgileri</div>
                        <div class="form-card-body">
                            
                            <div id="hacizListesiGosterim" style="margin-bottom:15px; display:none;">
                                <label style="font-size:11px; font-weight:bold; color:#007bff;">EKLENEN HACİZLER:</label>
                                <ul id="hacizUl" style="list-style:none; padding:0; margin:0; font-size:12px; border:1px solid #eee; border-radius:4px;"></ul>
                            </div>

                            <input type="hidden" name="haciz_eden" id="real_haciz_eden" value="<?php echo val('haciz_eden'); ?>">
                            <input type="hidden" name="dosya_no" id="real_dosya_no" value="<?php echo val('dosya_no'); ?>">

                            <div class="form-field">
                                <label>Haciz Eden:</label>
                                <div class="input-group-button">
                                    <input type="text" list="hacizListesi" id="temp_haciz_eden" class="force-uppercase" placeholder="Seç veya Yaz">
                                    <datalist id="hacizListesi">
                                        <?php 
                                        if($db_haciz_kurumlar->num_rows > 0) {
                                            $db_haciz_kurumlar->data_seek(0);
                                            while($hk = $db_haciz_kurumlar->fetch_assoc()) { echo "<option value='".htmlspecialchars($hk['kurum_adi'])."'>"; }
                                        }
                                        ?>
                                    </datalist>
                                    <button type="button" class="btn-icon" onclick="yeniOgeEkle('temp_haciz_eden', 'hacizListesi')"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Dosya No:</label>
                                <input type="text" id="temp_dosya_no" class="force-uppercase no-space">
                            </div>
                            <div class="form-field">
                                <label>Açıklama:</label>
                                <textarea name="haciz_aciklama" rows="2" class="force-uppercase"><?php echo val('haciz_aciklama'); ?></textarea>
                            </div>
                            
                            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                                <button type="button" class="btn btn-sm btn-danger" onclick="hacizTemizle()"><i class="fas fa-trash"></i> Temizle</button>
                                <button type="button" class="btn btn-sm btn-success" onclick="hacizListeyeEkle()"><i class="fas fa-plus"></i> Ekle</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script>
        document.querySelectorAll('.force-uppercase').forEach(function(input) {
            input.addEventListener('input', function() {
                var start = this.selectionStart; var end = this.selectionEnd;
                this.value = this.value.toUpperCase(); this.setSelectionRange(start, end);
            });
        });
        document.querySelectorAll('.no-space').forEach(function(input) {
            input.addEventListener('keydown', function(e) { if (e.key === " ") e.preventDefault(); });
            input.addEventListener('input', function() { this.value = this.value.replace(/\s/g, ''); });
        });
        document.querySelectorAll('.numeric-only').forEach(function(input) {
            input.addEventListener('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); });
        });
        document.querySelectorAll('.phone-mask').forEach(function(input) {
            input.addEventListener('input', function(e) {
                var x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
                e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? ' ' + x[3] : '') + (x[4] ? ' ' + x[4] : '');
            });
        });
        function togglePlakaDurumu() {
            var checkbox = document.getElementById('plaka_var');
            var input = document.getElementById('plakaInput');
            if (checkbox.checked) {
                input.style.display = 'block'; input.setAttribute('required', 'required');
            } else {
                input.style.display = 'none'; input.value = ''; input.removeAttribute('required');
            }
        }
        window.addEventListener('load', togglePlakaDurumu);

        // ==========================================================
        // YENİ: MARKA-MODEL, MEN NEDENİ, CİNS, KURUM, HACİZ VE ÇEKİCİ İŞLEMLERİ
        // ==========================================================
        
        // 1. Marka değişince Modelleri getir
        document.getElementById('markaInput').addEventListener('change', function() {
            modelleriGuncelle(this.value);
        });

        // YENİ: ÇEKİCİ FİRMA SEÇİLİNCE ÜCRETİ GETİR
        document.getElementById('cekiciInput').addEventListener('change', function() {
            var firmaAdi = this.value;
            var ucretInput = document.querySelector('input[name="cekici_ucret"]');
            
            if(!firmaAdi) return;

            var formData = new FormData();
            formData.append('ajax_action', 'get_cekici_ucret');
            formData.append('firma_adi', firmaAdi);

            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    // Eğer veritabanında bir ücret tanımlıysa (0'dan büyükse) kutuya yaz
                    if(data.ucret > 0) {
                        ucretInput.value = data.ucret.toFixed(2);
                    } else {
                        // Tanımlı değilse boş bırakalım ki kullanıcı manuel girebilsin
                        ucretInput.value = ''; 
                    }
                }
            })
            .catch(error => console.error('Hata:', error));
        });

        window.addEventListener('load', function() {
            var mevcutMarka = document.getElementById('markaInput').value;
            if(mevcutMarka) {
                modelleriGuncelle(mevcutMarka, true);
            }
        });

        function modelleriGuncelle(markaAdi, koru = false) {
            var modelInput = document.getElementById('modelInput');
            var datalist = document.getElementById('modelListesi');
            
            datalist.innerHTML = "";
            if(!koru) modelInput.value = "";

            if(!markaAdi) return;

            var formData = new FormData();
            formData.append('ajax_action', 'get_modeller');
            formData.append('marka_adi', markaAdi);

            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    data.models.forEach(function(mod) {
                        var option = document.createElement('option');
                        option.value = mod;
                        datalist.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Hata:', error));
        }

        // 2. YENİ ÖGE EKLEME
        function yeniOgeEkle(inputId, datalistId) {
            var input = document.getElementById(inputId);
            var yeniDeger = input.value.trim();
            if(yeniDeger === "") return;

            var formData = new FormData();
            formData.append('deger', yeniDeger);
            var isAjax = false;

            if (inputId === 'markaInput') {
                formData.append('ajax_action', 'marka_ekle');
                isAjax = true;
            } else if (inputId === 'modelInput') {
                var markaVal = document.getElementById('markaInput').value.trim();
                if(markaVal === "") { alert("Model eklemek için önce Marka bilgisini giriniz."); return; }
                formData.append('ajax_action', 'model_ekle');
                formData.append('ust_deger', markaVal); 
                isAjax = true;
            } else if (inputId === 'menNedeniInput') {
                formData.append('ajax_action', 'men_nedeni_ekle');
                isAjax = true;
            } else if (inputId === 'cinsInput') {
                formData.append('ajax_action', 'cins_ekle');
                isAjax = true;
            } else if (inputId === 'kurumInput') {
                formData.append('ajax_action', 'kurum_ekle');
                isAjax = true;
            } else if (inputId === 'temp_haciz_eden') {
                formData.append('ajax_action', 'haciz_kurum_ekle');
                isAjax = true;
            } else if (inputId === 'cekiciInput') { // YENİ: ÇEKİCİ FİRMA EKLEME
                formData.append('ajax_action', 'cekici_ekle');
                isAjax = true;
            }

            if (isAjax) {
                fetch(window.location.href, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        var dataList = document.getElementById(datalistId);
                        var option = document.createElement('option');
                        option.value = yeniDeger;
                        dataList.appendChild(option);
                        alert(data.message);
                    } else {
                        alert("Hata: " + data.message);
                    }
                })
                .catch(error => console.error('Hata:', error));
            } else {
                var dataList = document.getElementById(datalistId);
                var option = document.createElement('option');
                option.value = yeniDeger;
                dataList.appendChild(option);
            }
        }

        // --- KONUM GRAFİĞİ ---
        const ctx = document.getElementById('dolulukGrafigi').getContext('2d');
        new Chart(ctx, { 
            type: 'doughnut', 
            data: { 
                labels: ['Dolu', 'Boş'], 
                datasets: [{ 
                    data: [<?php echo $dolu_arac_sayisi; ?>, <?php echo $bos_yer_sayisi; ?>], 
                    backgroundColor: ['#dc3545', '#28a745'], 
                    borderWidth: 0 
                }] 
            }, 
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                cutout: '65%', 
                plugins: { legend: { display: false } } 
            } 
        });

        window.addEventListener('load', function() {
            const realEden = document.getElementById('real_haciz_eden').value;
            const realDosya = document.getElementById('real_dosya_no').value;
            if(realEden) {
                const edenArr = realEden.split(' | ');
                const dosyaArr = realDosya.split(' | ');
                for(let i=0; i<edenArr.length; i++) { addHacizRow(edenArr[i], dosyaArr[i] || ''); }
            }
        });

        function hacizListeyeEkle() {
            const eden = document.getElementById('temp_haciz_eden').value.trim();
            const dosya = document.getElementById('temp_dosya_no').value.trim();
            if(!eden) { alert("Lütfen Haciz Eden bilgisini giriniz."); return; }
            addHacizRow(eden, dosya); hacizTemizle(); updateRealInputs();
        }
        function hacizTemizle() { document.getElementById('temp_haciz_eden').value = ''; document.getElementById('temp_dosya_no').value = ''; }
        function addHacizRow(eden, dosya) {
            const ul = document.getElementById('hacizUl'); const div = document.getElementById('hacizListesiGosterim');
            const li = document.createElement('li');
            li.style.cssText = 'padding:5px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;';
            li.innerHTML = `<span><strong>${eden}</strong> (${dosya})</span> <button type="button" onclick="removeHacizRow(this)" style="background:none; border:none; color:red; cursor:pointer;"><i class="fas fa-times"></i></button>`;
            ul.appendChild(li); div.style.display = 'block';
        }
        function removeHacizRow(btn) { btn.parentElement.remove(); updateRealInputs(); if(document.getElementById('hacizUl').children.length === 0) { document.getElementById('hacizListesiGosterim').style.display = 'none'; } }
        function updateRealInputs() {
            const ul = document.getElementById('hacizUl'); const items = ul.querySelectorAll('li span');
            let edenArr = []; let dosyaArr = [];
            items.forEach(item => { let text = item.innerText; let parts = text.split(' ('); edenArr.push(parts[0]); let d = parts[1] ? parts[1].replace(')', '') : ''; dosyaArr.push(d); });
            document.getElementById('real_haciz_eden').value = edenArr.join(' | '); document.getElementById('real_dosya_no').value = dosyaArr.join(' | ');
        }
    </script>
</body>
</html>