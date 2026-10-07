<?php
// Oturumu başlat
session_start();
$yol = "../";
$menu = "arac_kayit";
require_once '../db_config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// --- FİRMA BİLGİSİNİ ÇEK ---
$firma_adi = 'YEDİEMİN OTOPARK İŞLETMESİ'; // Varsayılan
$sql_f = "SELECT firma_adi FROM firma_bilgileri WHERE id = 1";
if($res_f = $conn->query($sql_f)){
    if($row_f = $res_f->fetch_assoc()){
        if(!empty($row_f['firma_adi'])) $firma_adi = $row_f['firma_adi'];
    }
}

// --- KAYDETME İŞLEMİ ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'ayar_kaydet') {
    $rapor_adi = 'hasar_tespit_formu';
    
    $logo_url = isset($_POST['mevcut_logo']) ? $_POST['mevcut_logo'] : '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed)) {
            $new_name = "logo_" . $rapor_adi . "." . $file_ext;
            $target_file = $upload_dir . $new_name;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                $logo_url = $target_file . "?v=" . time();
            }
        }
    }

    $blocks = [
        'logo', 'baslik', 'alt_baslik', 'tarih', 
        'info_arac', 'info_teslim_eden', 'info_teslim_alan', 
        'check_list', 'notlar', 'footer'
    ];
    
    $ayarlar = [
        'baslik' => $_POST['baslik'], 
        'alt_baslik' => $_POST['alt_baslik'], 
        'logo_url' => $logo_url
    ];

    foreach($blocks as $b) {
        $ayarlar['style_'.$b] = [
            'top' => $_POST['pos_'.$b.'_top'],
            'left' => $_POST['pos_'.$b.'_left'],
            'width' => $_POST['pos_'.$b.'_width'],
            'height' => $_POST['pos_'.$b.'_height']
        ];
        
        if($b != 'logo') {
            $ayarlar['style_'.$b]['font'] = $_POST['style_'.$b.'_font'];
            $ayarlar['style_'.$b]['size'] = $_POST['style_'.$b.'_size'];
            $ayarlar['style_'.$b]['color'] = $_POST['style_'.$b.'_color'];
        }

        $ayarlar['goster_'.$b] = isset($_POST['goster_'.$b]) ? 1 : 0;
    }
    
    $json_ayarlar = json_encode($ayarlar, JSON_UNESCAPED_UNICODE);
    
    $sql_check = "SELECT id FROM rapor_ayarlari WHERE rapor_adi = '$rapor_adi'";
    $check_res = $conn->query($sql_check);

    if ($check_res->num_rows > 0) {
        $sql = "UPDATE rapor_ayarlari SET ayarlar = ? WHERE rapor_adi = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $json_ayarlar, $rapor_adi);
    } else {
        $sql = "INSERT INTO rapor_ayarlari (rapor_adi, ayarlar) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $rapor_adi, $json_ayarlar);
    }
    $stmt->execute();
    header("Location: bos_arac_hasar_formu.php");
    exit;
}

// --- AYARLARI ÇEK ---
$varsayilan = [
    'baslik' => $firma_adi, // <-- VERİTABANINDAN GELEN İSİM
    'alt_baslik' => 'ARAÇ HASAR TESPİT FORMU', 
    'logo_url' => '',
    
    'style_logo' => ['top'=>20, 'left'=>20, 'width'=>100, 'height'=>'auto'], 'goster_logo'=>1,
    'style_baslik' => ['font'=>'Arial', 'size'=>24, 'color'=>'#000', 'top'=>30, 'left'=>150, 'width'=>400, 'height'=>'auto'], 'goster_baslik'=>1,
    'style_tarih' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>30, 'left'=>600, 'width'=>150, 'height'=>'auto'], 'goster_tarih'=>1,
    'style_alt_baslik' => ['font'=>'Arial', 'size'=>16, 'color'=>'#000', 'top'=>80, 'left'=>20, 'width'=>750, 'height'=>'auto'], 'goster_alt_baslik'=>1,
    
    'style_info_arac' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>130, 'left'=>20, 'width'=>350, 'height'=>'auto'], 'goster_info_arac'=>1,
    'style_info_teslim_eden' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>130, 'left'=>400, 'width'=>350, 'height'=>'auto'], 'goster_info_teslim_eden'=>1,
    'style_info_teslim_alan' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>250, 'left'=>20, 'width'=>350, 'height'=>'auto'], 'goster_info_teslim_alan'=>1,
    
    'style_check_list' => ['font'=>'Arial', 'size'=>11, 'color'=>'#000', 'top'=>350, 'left'=>20, 'width'=>750, 'height'=>'auto'], 'goster_check_list'=>1,
    'style_notlar' => ['font'=>'Arial', 'size'=>11, 'color'=>'#000', 'top'=>650, 'left'=>20, 'width'=>750, 'height'=>'auto'], 'goster_notlar'=>1,
    'style_footer' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>800, 'left'=>20, 'width'=>750, 'height'=>'auto'], 'goster_footer'=>1
];

$sql_get = "SELECT ayarlar FROM rapor_ayarlari WHERE rapor_adi = 'hasar_tespit_formu'";
$res_get = $conn->query($sql_get);
if($res_get->num_rows > 0) {
    $db_ayarlar = json_decode($res_get->fetch_assoc()['ayarlar'], true);
    $ayarlar = array_replace_recursive($varsayilan, $db_ayarlar);
} else {
    $ayarlar = $varsayilan;
}

// *** FİRMA ADINI GÜNCELLE ***
// Eğer daha önce kaydedilmişse bile, sayfa her açıldığında güncel firma adı görünsün
$ayarlar['baslik'] = $firma_adi;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Boş Araç Hasar Durum Formu</title>
    <link rel="stylesheet" href="<?php echo $yol; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

    <style>
        /* A4 ALANI */
        .paper-container { 
            background-color: #fff; width: 210mm; height: 297mm; 
            margin: 0 auto; position: relative; /* Absolute öğeler için */
            box-shadow: 0 0 15px rgba(0,0,0,0.1); border: 1px solid #ddd; 
            overflow: hidden; 
        }

        /* YAZDIRILABİLİR ÖĞELER (ABSOLUTE) */
        .print-el { position: absolute; box-sizing: border-box; }

        /* DÜZENLEME MODU GÖRÜNÜMÜ */
        .edit-mode .print-el { 
            border: 1px dashed #999; 
            cursor: move; 
            background: rgba(255, 255, 255, 0.9); 
            z-index: 10; 
        }
        .edit-mode .print-el:hover, .edit-mode .print-el.active-element { 
            border: 1px solid #2196F3; 
            background: rgba(33, 150, 243, 0.1); 
            z-index: 100; 
        }
        .ui-resizable-handle { background: transparent; width: 10px; height: 10px; }

        /* SOL EDİTÖR PANELİ */
        #editor-panel { 
            display: none; position: fixed; top: 80px; left: 20px; width: 280px; 
            background: #fff; border: 1px solid #ccc; box-shadow: 0 5px 15px rgba(0,0,0,0.2); 
            padding: 15px; border-radius: 8px; z-index: 9999; max-height: 85vh; overflow-y: auto; 
        }
        .panel-row { margin-bottom: 8px; display:flex; align-items:center; justify-content:space-between; }
        .panel-row label { font-size: 11px; margin: 0; font-weight:600; }
        .panel-row input[type="text"], .panel-row input[type="number"], .panel-row select { width: 60%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; font-size: 11px; }
        
        /* Switch */
        .switch { position: relative; display: inline-block; width: 30px; height: 16px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 16px; }
        .slider:before { position: absolute; content: ""; height: 12px; width: 12px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #2196F3; }
        input:checked + .slider:before { transform: translateX(14px); }

        /* YAZDIRMA AYARLARI */
        @media print {
            @page { margin: 0; size: A4; }
            body { margin: 0; background: white; }
            .main-header, .page-header-row, #editor-panel, .no-print { display: none !important; }
            .paper-container { box-shadow: none; border: none; margin: 0; }
            .print-el { border: none !important; background: transparent !important; }
            .edit-mode .print-el { border: none !important; background: transparent !important; } /* Edit modda yazdırılırsa temiz görünsün */
        }

        /* --- FORM İÇERİK STİLLERİ (Orijinalinden) --- */
        .info-row { display: flex; align-items: flex-end; margin-bottom: 5px; width: 100%; } 
        .info-label { font-weight: 700; width: 110px; flex-shrink: 0; font-size: inherit; }
        .info-dots { flex-grow: 1; border-bottom: 1px dotted #333; height: 14px; }

        .group-title { font-weight: 700; text-decoration: underline; margin-bottom: 5px; text-transform: uppercase; font-size: inherit; }
        .check-table { width: 100%; border-collapse: collapse; font-size: <?php echo $ayarlar['chk_content_size']; ?>px; font-family: <?php echo $ayarlar['chk_content_font']; ?>; }
        .check-table td { padding: 2px 0; vertical-align: middle; border-bottom: 1px solid #eee; }
        .check-name { width: 40%; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .check-options { display: flex; gap: 8px; justify-content: flex-end; font-size: 0.9em; }
        .cb-item { display: flex; align-items: center; gap: 3px; cursor: pointer; }
        .opt-saglam { color: #28a745; font-weight: bold; }
        .opt-hasarli { color: #dc3545; font-weight: bold; }
        .opt-yok { color: #666; }
        
        .sign-wrapper { display: flex; justify-content: space-between; width: 100%; }
        .sign-box { text-align: center; width: 45%; }
        .sign-line { border-top: 1px solid #000; margin-top: 40px; }
    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-content">
        
        <div class="page-header-row no-print">
            <h2><i class="fas fa-file-alt"></i> Boş Araç Hasar Durum Formu</h2>
            <div class="header-actions">
                <button id="btn-toggle-edit" class="btn btn-warning"><i class="fas fa-edit"></i> Tasarımı Düzenle</button>
                <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Yazdır</button>
            </div>
        </div>

        <div id="editor-panel">
            <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:5px;">Düzenleme Paneli</h4>
            <form id="saveForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="ayar_kaydet">
                <input type="hidden" name="mevcut_logo" value="<?php echo $ayarlar['logo_url']; ?>">
                
                <?php 
                $blocks = ['logo', 'baslik', 'alt_baslik', 'tarih', 'info_genel', 'info_sahip', 'chk_avadanlik', 'chk_isik', 'chk_cam', 'chk_yurur', 'chk_kaporta', 'footer'];
                foreach($blocks as $b) {
                    $s = $ayarlar['style_'.$b];
                    echo "<input type='hidden' name='pos_{$b}_top' id='pos_{$b}_top' value='{$s['top']}'>";
                    echo "<input type='hidden' name='pos_{$b}_left' id='pos_{$b}_left' value='{$s['left']}'>";
                    echo "<input type='hidden' name='pos_{$b}_width' id='pos_{$b}_width' value='{$s['width']}'>";
                    echo "<input type='hidden' name='pos_{$b}_height' id='pos_{$b}_height' value='{$s['height']}'>";
                    
                    if($b != 'logo') {
                        echo "<input type='hidden' name='style_{$b}_font' id='style_{$b}_font' value='{$s['font']}'>";
                        echo "<input type='hidden' name='style_{$b}_size' id='style_{$b}_size' value='{$s['size']}'>";
                        echo "<input type='hidden' name='style_{$b}_color' id='style_{$b}_color' value='{$s['color']}'>";
                    }
                }
                ?>
                <input type="hidden" name="chk_content_font" id="chk_content_font" value="<?php echo $ayarlar['chk_content_font']; ?>">
                <input type="hidden" name="chk_content_size" id="chk_content_size" value="<?php echo $ayarlar['chk_content_size']; ?>">

                <div class="panel-section" style="border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:10px;">
                    <div style="font-weight:bold; color:#2196F3; margin-bottom:5px;">Seçili: <span id="lbl-selected">Yok</span></div>
                    <div class="panel-row">
                        <label>Font</label>
                        <select id="inp_font" disabled>
                            <option value="Arial">Arial</option><option value="'Times New Roman'">Times New Roman</option><option value="'Courier New'">Courier New</option><option value="'Segoe UI'">Segoe UI</option>
                        </select>
                    </div>
                    <div class="panel-row"><label>Boyut</label><input type="number" id="inp_size" disabled></div>
                    <div class="panel-row"><label>Renk</label><input type="color" id="inp_color" style="width:30px; border:none;" disabled></div>
                </div>

                <div class="panel-section" style="border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:10px;">
                    <div style="font-weight:bold; margin-bottom:5px;">Görünürlük</div>
                    <?php 
                    $labels = [
                        'logo'=>'Logo', 'baslik'=>'Firma Adı', 'alt_baslik'=>'Rapor Başlığı', 'tarih'=>'Tarih',
                        'info_genel'=>'Genel Bilgiler', 'info_sahip'=>'Araç Sahibi',
                        'chk_avadanlik'=>'Avadanlıklar', 'chk_isik'=>'Işıklar', 'chk_cam'=>'Camlar',
                        'chk_yurur'=>'Yürür Aksam', 'chk_kaporta'=>'Kaporta', 'footer'=>'İmza'
                    ];
                    foreach($blocks as $b): 
                        $checked = $ayarlar['goster_'.$b] ? 'checked' : '';
                    ?>
                    <div class="panel-row">
                        <label><?php echo $labels[$b]; ?></label>
                        <label class="switch"><input type="checkbox" name="goster_<?php echo $b; ?>" id="tog_<?php echo $b; ?>" <?php echo $checked; ?>><span class="slider"></span></label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="panel-section">
                    <div class="panel-row"><label>Logo</label><input type="file" name="logo"></div>
                    <div class="panel-row"><label>Firma</label><input type="text" name="baslik" id="inp_baslik_text" value="<?php echo $ayarlar['baslik']; ?>" class="force-uppercase"></div>
                    <div class="panel-row"><label>Başlık</label><input type="text" name="alt_baslik" id="inp_alt_baslik_text" value="<?php echo $ayarlar['alt_baslik']; ?>" class="force-uppercase"></div>
                </div>

                <div style="margin-top:10px;">
                    <button type="submit" class="btn btn-sm btn-success" style="width:100%;">Kaydet</button>
                    <button type="button" id="btn-cancel-edit" class="btn btn-sm btn-danger" style="width:100%; margin-top:5px;">Kapat</button>
                </div>
            </form>
        </div>

        <div id="paper-area" class="paper-container">
            
            <?php 
                function renderBlock($id, $style, $content, $visible) {
                    $display = $visible ? 'block' : 'none';
                    $font = isset($style['font']) ? "font-family:{$style['font']};" : "";
                    $size = isset($style['size']) ? "font-size:{$style['size']}px;" : "";
                    $color = isset($style['color']) ? "color:{$style['color']};" : "";
                    // Yazdırırken border çıkmaması için inline css'e eklemiyoruz, class ile yöneteceğiz
                    $css = "top:{$style['top']}px; left:{$style['left']}px; width:{$style['width']}px; height:{$style['height']}px; display:{$display}; $font $size $color";
                    
                    $type = (strpos($id, 'chk_') !== false) ? 'checklist' : (($id=='logo')?'logo':'text');
                    
                    echo "<div id='el_{$id}' class='print-el' data-type='$type' style='$css'>$content</div>";
                }
            ?>

            <?php 
            $logoContent = !empty($ayarlar['logo_url']) ? "<img src='{$ayarlar['logo_url']}' style='width:100%; height:100%; object-fit:contain;'>" : "<div style='width:100%; height:100%; border:1px dashed #ccc; display:flex; align-items:center; justify-content:center; color:#999;'>Logo Yok</div>";
            renderBlock('logo', $ayarlar['style_logo'], $logoContent, $ayarlar['goster_logo']); 
            ?>

            <?php renderBlock('baslik', $ayarlar['style_baslik'], "<div id='txt_baslik' style='font-weight:800; text-transform:uppercase; text-align:center;'>{$ayarlar['baslik']}</div>", $ayarlar['goster_baslik']); ?>

            <?php renderBlock('tarih', $ayarlar['style_tarih'], "<div style='text-align:right; font-weight:600;'>Tarih: ".date('d.m.Y')."</div>", $ayarlar['goster_tarih']); ?>

            <?php renderBlock('alt_baslik', $ayarlar['style_alt_baslik'], "<div id='txt_alt_baslik' style='text-align:center; font-weight:700; text-transform:uppercase; border:1px solid #000; padding:3px; background:#f9f9f9;'>{$ayarlar['alt_baslik']}</div>", $ayarlar['goster_alt_baslik']); ?>

            <?php ob_start(); ?>
            <div style="font-weight:700; margin-bottom:5px; text-decoration:underline; font-size:inherit;">GENEL BİLGİLER</div>
            <?php foreach(["PLAKASI", "CİNSİ", "MARKASI", "MODELİ", "MODEL YILI", "RENGİ", "ŞASİ NO", "MOTOR NO", "AÇIKLAMA"] as $l) 
                echo '<div class="info-row"><div class="info-label">'.$l.'</div><div class="info-dots"></div></div>'; ?>
            <?php $c=ob_get_clean(); renderBlock('info_genel', $ayarlar['style_info_genel'], $c, $ayarlar['goster_info_genel']); ?>

            <?php ob_start(); ?>
            <div style="font-weight:700; margin-bottom:5px; text-decoration:underline; font-size:inherit;">ARAÇ SAHİBİ BİLGİLERİ</div>
            <?php foreach(["ARAÇ SAHİBİ", "T.C. NUMARASI", "BABA ADI", "DOĞUM YERİ", "DOĞUM YILI", "TELEFONU", "ADRESİ"] as $l) 
                echo '<div class="info-row"><div class="info-label">'.$l.'</div><div class="info-dots"></div></div>'; ?>
            <?php $c=ob_get_clean(); renderBlock('info_sahip', $ayarlar['style_info_sahip'], $c, $ayarlar['goster_info_sahip']); ?>

            <?php ob_start(); ?>
            <div class="group-title">AVADANLIKLAR</div>
            <table class="check-table">
                <?php foreach(["RUHSAT", "STEPNE", "KRİKO", "ANAHTAR", "RADYO-TEYP"] as $i) 
                    echo '<tr><td class="check-name">'.$i.'</td><td><div class="check-options"><label class="cb-item"><input type="checkbox"> VAR</label><label class="cb-item"><input type="checkbox"> YOK</label></div></td></tr>'; ?>
            </table>
            <?php $c=ob_get_clean(); renderBlock('chk_avadanlik', $ayarlar['style_chk_avadanlik'], $c, $ayarlar['goster_chk_avadanlik']); ?>

            <?php ob_start(); ?>
            <div class="group-title">IŞIKLANDIRMALAR</div>
            <table class="check-table">
                <?php foreach(["SAĞ ÖN IŞIKLAR", "SOL ÖN IŞIKLAR", "SAĞ ARKA IŞIKLAR", "SOL ARKA IŞIKLAR"] as $i) 
                    echo '<tr><td class="check-name">'.$i.'</td><td><div class="check-options"><label class="cb-item"><input type="checkbox"><span class="opt-saglam">SAĞLAM</span></label><label class="cb-item"><input type="checkbox"><span class="opt-hasarli">HASARLI</span></label><label class="cb-item"><input type="checkbox"><span class="opt-yok">YOK</span></label></div></td></tr>'; ?>
            </table>
            <?php $c=ob_get_clean(); renderBlock('chk_isik', $ayarlar['style_chk_isik'], $c, $ayarlar['goster_chk_isik']); ?>

            <?php ob_start(); ?>
            <div class="group-title">CAM AKSAMLARI</div>
            <table class="check-table">
                <?php foreach(["ÖN CAM", "ARKA CAM", "SOL A. KAPI CAMI", "SAĞ A. KAPI CAMI", "SOL Ö. KAPI CAMI", "SAĞ Ö. KAPI CAMI", "SAĞ A. KELEBEK", "SOL A. KELEBEK", "SAĞ Ö. KELEBEK", "SOL Ö. KELEBEK", "SUNROOF"] as $i) 
                    echo '<tr><td class="check-name">'.$i.'</td><td><div class="check-options"><label class="cb-item"><input type="checkbox"><span class="opt-saglam">SAĞLAM</span></label><label class="cb-item"><input type="checkbox"><span class="opt-hasarli">HASARLI</span></label><label class="cb-item"><input type="checkbox"><span class="opt-yok">YOK</span></label></div></td></tr>'; ?>
            </table>
            <?php $c=ob_get_clean(); renderBlock('chk_cam', $ayarlar['style_chk_cam'], $c, $ayarlar['goster_chk_cam']); ?>

            <?php ob_start(); ?>
            <div class="group-title">YÜRÜR & DİĞER AKSAMLAR</div>
            <table class="check-table">
                <?php foreach(["SOL ÖN TEKER", "SAĞ ÖN TEKER", "SOL ARKA TEKER", "SAĞ ARKA TEKER", "MOTOR", "DİFERANSİYEL", "ŞANZUMAN", "AKÜMÜLATÖR"] as $i) 
                    echo '<tr><td class="check-name">'.$i.'</td><td><div class="check-options"><label class="cb-item"><input type="checkbox"><span class="opt-saglam">SAĞLAM</span></label><label class="cb-item"><input type="checkbox"><span class="opt-hasarli">HASARLI</span></label><label class="cb-item"><input type="checkbox"><span class="opt-yok">YOK</span></label></div></td></tr>'; ?>
            </table>
            <?php $c=ob_get_clean(); renderBlock('chk_yurur', $ayarlar['style_chk_yurur'], $c, $ayarlar['goster_chk_yurur']); ?>

            <?php ob_start(); ?>
            <div class="group-title">KAPORTA AKSAMLARI</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <table class="check-table">
                    <?php foreach(["MOTOR KAPUTU", "BAGAJ KAPAĞI", "İÇ AYNA", "SAĞ DİKİZ AYNASI", "SOL DİKİZ AYNASI", "SPOILER", "SAĞ MARŞPİYEL", "SOL MARŞPİYEL", "ARKA TAMPON"] as $i) 
                        echo '<tr><td class="check-name">'.$i.'</td><td><div class="check-options"><label class="cb-item"><input type="checkbox"><span class="opt-saglam">SAĞLAM</span></label><label class="cb-item"><input type="checkbox"><span class="opt-hasarli">HASARLI</span></label><label class="cb-item"><input type="checkbox"><span class="opt-yok">YOK</span></label></div></td></tr>'; ?>
                </table>
                <table class="check-table">
                    <?php foreach(["ÖN TAMPON", "SAĞ A. ÇAMURLUK", "SOL A. ÇAMURLUK", "SAĞ Ö. ÇAMURLUK", "SOL Ö. ÇAMURLUK", "SAĞ ARKA KAPI", "SOL ARKA KAPI", "SAĞ ÖN KAPI", "SOL ÖN KAPI"] as $i) 
                        echo '<tr><td class="check-name">'.$i.'</td><td><div class="check-options"><label class="cb-item"><input type="checkbox"><span class="opt-saglam">SAĞLAM</span></label><label class="cb-item"><input type="checkbox"><span class="opt-hasarli">HASARLI</span></label><label class="cb-item"><input type="checkbox"><span class="opt-yok">YOK</span></label></div></td></tr>'; ?>
                </table>
            </div>
            <?php $c=ob_get_clean(); renderBlock('chk_kaporta', $ayarlar['style_chk_kaporta'], $c, $ayarlar['goster_chk_kaporta']); ?>

            <?php ob_start(); ?>
            <div class="sign-wrapper">
                <div class="sign-box"><strong>TESLİM EDEN</strong><div class="sign-line"></div></div>
                <div class="sign-box"><strong>TESLİM ALAN</strong><div class="sign-line"></div></div>
            </div>
            <?php $c=ob_get_clean(); renderBlock('footer', $ayarlar['style_footer'], $c, $ayarlar['goster_footer']); ?>

        </div>
    </div>

    <script>
        var isEditMode = false;
        var activeElementId = null;

        // --- EDİTÖR AÇ/KAPA ---
        $("#btn-toggle-edit, #btn-cancel-edit").click(function() {
            isEditMode = !isEditMode;
            if(isEditMode) {
                $("#paper-area").addClass("edit-mode");
                $("#editor-panel").show();
                $("#btn-toggle-edit").hide();
                enableDragResize();
            } else {
                $("#paper-area").removeClass("edit-mode");
                $("#editor-panel").hide();
                $("#btn-toggle-edit").show();
                disableDragResize();
            }
        });

        function enableDragResize() {
            $(".print-el").draggable({
                containment: "#paper-area",
                start: function() { setActiveElement($(this)); },
                stop: function(event, ui) { updateInputs($(this)); }
            }).resizable({
                containment: "#paper-area",
                handles: "all",
                start: function() { setActiveElement($(this)); },
                stop: function(event, ui) { updateInputs($(this)); }
            });
            $(".print-el").on("mousedown", function() { setActiveElement($(this)); });
        }

        function disableDragResize() {
            if ($(".print-el").data("ui-draggable")) $(".print-el").draggable("destroy");
            if ($(".print-el").data("ui-resizable")) $(".print-el").resizable("destroy");
            $(".print-el").off("mousedown").removeClass("active-element");
        }

        // --- SEÇİLİ ÖĞE VE AYARLARI ---
        function setActiveElement(el) {
            $(".print-el").removeClass("active-element");
            el.addClass("active-element");
            
            var id = el.attr('id').replace('el_', '');
            activeElementId = id;
            var type = el.data('type');

            $("#lbl-selected").text(id.toUpperCase());

            var isLogo = (type === 'logo');
            $("#inp_font, #inp_size, #inp_color").prop('disabled', isLogo);

            if(!isLogo) {
                // Checklist ise tablonun içindeki stili okuyamayabiliriz, hidden inputtan al
                if(type === 'checklist') {
                    // İçerik fontu
                    $("#inp_font").val( $("#chk_content_font").val() );
                    $("#inp_size").val( $("#chk_content_size").val() );
                    // Başlık stilini al (Checklist başlığı style_chk_... içinde kayıtlı)
                    $("#inp_color").val( $("#style_" + id + "_color").val() ); 
                } else {
                    $("#inp_font").val( $("#style_" + id + "_font").val() );
                    $("#inp_size").val( $("#style_" + id + "_size").val() );
                    $("#inp_color").val( $("#style_" + id + "_color").val() );
                }
            }
        }

        // --- ANLIK STİL GÜNCELLEME ---
        $("#inp_font, #inp_size, #inp_color").on('input change', function() {
            if(!activeElementId) return;
            
            var font = $("#inp_font").val();
            var size = $("#inp_size").val();
            var color = $("#inp_color").val();
            var el = $("#el_" + activeElementId);
            var type = el.data('type');

            // Görünümü güncelle
            el.css({ 'font-family': font, 'font-size': size + 'px', 'color': color });
            
            // Eğer checklist ise içindeki tablo fontunu da güncelle (daha küçük olabilir)
            if(type === 'checklist') {
                el.find('.check-table').css({ 'font-family': font, 'font-size': size + 'px' });
                // Checklistler için veritabanı inputlarını güncelle
                $("#chk_content_font").val(font);
                $("#chk_content_size").val(size);
            }

            // Hidden inputları güncelle (Seçili öğe için)
            $("#style_" + activeElementId + "_font").val(font);
            $("#style_" + activeElementId + "_size").val(size);
            $("#style_" + activeElementId + "_color").val(color);
        });

        // --- KOORDİNAT GÜNCELLEME ---
        function updateInputs(el) {
            var id = el.attr('id').replace('el_', ''); 
            var pos = el.position();
            $('#pos_' + id + '_top').val(pos.top);
            $('#pos_' + id + '_left').val(pos.left);
            $('#pos_' + id + '_width').val(el.width());
            $('#pos_' + id + '_height').val(el.height());
        }

        // --- GÖRÜNÜRLÜK (TOGGLE) ---
        <?php foreach($blocks as $b): ?>
        $("#tog_<?php echo $b; ?>").on('change', function() {
            var visible = $(this).is(':checked');
            $("#el_<?php echo $b; ?>").toggle(visible);
        });
        <?php endforeach; ?>

        $("#inp_baslik_text").on('input', function(){ $("#txt_baslik").text($(this).val()); });
        $("#inp_alt_baslik_text").on('input', function(){ $("#txt_alt_baslik").text($(this).val()); });
        
        document.getElementById('inp_logo').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) { document.getElementById('prev_logo_img').src = e.target.result; }
                reader.readAsDataURL(file);
            }
        });
        
        document.querySelectorAll('.force-uppercase').forEach(inp => inp.addEventListener('input', function(){ this.value = this.value.toUpperCase(); }));
    </script>
</body>
</html>