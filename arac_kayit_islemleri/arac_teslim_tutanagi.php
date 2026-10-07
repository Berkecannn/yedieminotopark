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
    $rapor_adi = 'arac_teslim_tutanagi';
    
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
        'info_arac', 'info_teslim_alan', 
        'beyan_metni', 'chk_malzemeler', 
        'footer'
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
    header("Location: arac_teslim_tutanagi.php");
    exit;
}

// --- AYARLARI ÇEK (KOORDİNATLAR GÜNCELLENDİ) ---
$varsayilan = [
    'baslik' => $firma_adi, // <-- VERİTABANINDAN GELEN İSİM
    'alt_baslik' => 'ARAÇ TESLİM TUTANAĞI', 
    'logo_url' => '',
    
    // Üst Bölüm (Aralıklar Açıldı)
    'style_logo' => ['top'=>20, 'left'=>20, 'width'=>100, 'height'=>'auto'], 'goster_logo'=>1,
    'style_tarih' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>25, 'left'=>600, 'width'=>150, 'height'=>'auto'], 'goster_tarih'=>1,
    
    'style_baslik' => ['font'=>'Arial', 'size'=>24, 'color'=>'#000', 'top'=>30, 'left'=>140, 'width'=>450, 'height'=>'auto'], 'goster_baslik'=>1,
    'style_alt_baslik' => ['font'=>'Arial', 'size'=>16, 'color'=>'#000', 'top'=>80, 'left'=>140, 'width'=>450, 'height'=>'auto'], 'goster_alt_baslik'=>1,
    
    // Bilgi Blokları (Aşağı İndirildi)
    'style_info_arac' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>150, 'left'=>20, 'width'=>350, 'height'=>'auto'], 'goster_info_arac'=>1,
    'style_info_teslim_alan' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>150, 'left'=>400, 'width'=>350, 'height'=>'auto'], 'goster_info_teslim_alan'=>1,
    
    // Alt Bölümler (Daha aşağı çekildi)
    'style_chk_malzemeler' => ['font'=>'Arial', 'size'=>11, 'color'=>'#000', 'top'=>400, 'left'=>20, 'width'=>750, 'height'=>'auto'], 'goster_chk_malzemeler'=>1,
    'style_beyan_metni' => ['font'=>'Arial', 'size'=>11, 'color'=>'#000', 'top'=>600, 'left'=>20, 'width'=>750, 'height'=>'auto'], 'goster_beyan_metni'=>1,
    'style_footer' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>750, 'left'=>20, 'width'=>750, 'height'=>'auto'], 'goster_footer'=>1
];

$sql_get = "SELECT ayarlar FROM rapor_ayarlari WHERE rapor_adi = 'arac_teslim_tutanagi'";
$res_get = $conn->query($sql_get);
if($res_get->num_rows > 0) {
    $db_ayarlar = json_decode($res_get->fetch_assoc()['ayarlar'], true);
    $ayarlar = array_replace_recursive($varsayilan, $db_ayarlar);
} else {
    $ayarlar = $varsayilan;
}

// *** FİRMA ADINI GÜNCELLE ***
$ayarlar['baslik'] = $firma_adi;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Araç Teslim Tutanağı</title>
    <link rel="stylesheet" href="<?php echo $yol; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

    <style>
        /* A4 ALANI */
        .paper-container { 
            background-color: #fff; width: 210mm; height: 297mm; 
            margin: 0 auto; position: relative; 
            box-shadow: 0 0 15px rgba(0,0,0,0.1); border: 1px solid #ddd; 
            overflow: hidden; 
        }

        /* YAZDIRILABİLİR ÖĞELER */
        .print-el { position: absolute; box-sizing: border-box; line-height: 1.2; }

        /* DÜZENLEME MODU */
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
        .panel-row input[type="color"] { border: none; width: 30px; height: 25px; padding: 0; }
        
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
            .edit-mode .print-el { border: none !important; background: transparent !important; }
        }

        /* --- İÇERİK STİLLERİ --- */
        .info-row { display: flex; align-items: flex-end; margin-bottom: 8px; width: 100%; } 
        .info-label { font-weight: 700; width: 140px; flex-shrink: 0; font-size: inherit; }
        .info-dots { flex-grow: 1; border-bottom: 1px dotted #333; height: 16px; }

        .group-title { font-weight: 700; text-decoration: underline; margin-bottom: 8px; text-transform: uppercase; font-size: inherit; display: block; }
        .check-table { width: 100%; border-collapse: collapse; font-size: inherit; font-family: inherit; }
        .check-table td { padding: 5px 0; vertical-align: middle; border-bottom: 1px solid #eee; }
        .check-name { width: 50%; font-weight: 600; }
        .check-options { display: flex; gap: 20px; justify-content: flex-end; }
        .cb-item { display: flex; align-items: center; gap: 5px; cursor: pointer; }
        
        .sign-wrapper { display: flex; justify-content: space-between; width: 100%; padding-top: 20px; }
        .sign-box { text-align: center; width: 45%; }
        .sign-line { border-top: 1px solid #000; margin-top: 60px; }
        
        .legal-text { text-align: justify; line-height: 1.6; margin-top: 5px; }
    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-content">
        
        <div class="page-header-row no-print">
            <h2><i class="fas fa-file-contract"></i> Araç Teslim Tutanağı</h2>
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
                $blocks = ['logo', 'baslik', 'alt_baslik', 'tarih', 'info_arac', 'info_teslim_alan', 'beyan_metni', 'chk_malzemeler', 'footer'];
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
                        'info_arac'=>'Araç Bilgileri', 'info_teslim_alan'=>'Teslim Alan',
                        'beyan_metni'=>'Beyan Metni', 'chk_malzemeler'=>'Malzemeler', 'footer'=>'İmza'
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
                    $css = "top:{$style['top']}px; left:{$style['left']}px; width:{$style['width']}px; height:{$style['height']}px; display:{$display}; $font $size $color";
                    
                    $type = ($id=='logo') ? 'logo' : 'text';
                    echo "<div id='el_{$id}' class='print-el' data-type='$type' style='$css'>$content</div>";
                }
            ?>

            <?php 
            $logoContent = !empty($ayarlar['logo_url']) ? "<img src='{$ayarlar['logo_url']}' style='width:100%; height:100%; object-fit:contain;'>" : "<div style='width:100%; height:100%; border:1px dashed #ccc; display:flex; align-items:center; justify-content:center; color:#999;'>Logo Yok</div>";
            renderBlock('logo', $ayarlar['style_logo'], $logoContent, $ayarlar['goster_logo']); 
            ?>

            <?php renderBlock('baslik', $ayarlar['style_baslik'], "<div id='txt_baslik' style='font-weight:800; text-transform:uppercase; text-align:center;'>{$ayarlar['baslik']}</div>", $ayarlar['goster_baslik']); ?>
            <?php renderBlock('tarih', $ayarlar['style_tarih'], "<div style='text-align:right; font-weight:600;'>Tarih: ".date('d.m.Y')."</div>", $ayarlar['goster_tarih']); ?>
            <?php renderBlock('alt_baslik', $ayarlar['style_alt_baslik'], "<div id='txt_alt_baslik' style='text-align:center; font-weight:700; text-transform:uppercase; border-bottom:2px solid #000; padding:5px;'>{$ayarlar['alt_baslik']}</div>", $ayarlar['goster_alt_baslik']); ?>

            <?php ob_start(); ?>
            <div class="group-title">ARAÇ BİLGİLERİ</div>
            <?php foreach(["PLAKA", "MARKA", "MODEL", "RENGİ", "ŞASİ NO", "GİRİŞ TARİHİ", "ÇIKIŞ TARİHİ"] as $l) 
                echo '<div class="info-row"><div class="info-label">'.$l.'</div><div class="info-dots"></div></div>'; ?>
            <?php $c=ob_get_clean(); renderBlock('info_arac', $ayarlar['style_info_arac'], $c, $ayarlar['goster_info_arac']); ?>

            <?php ob_start(); ?>
            <div class="group-title">TESLİM ALAN BİLGİLERİ</div>
            <?php foreach(["ADI SOYADI", "TC KİMLİK NO", "TELEFON", "ADRESİ", "RUHSAT SAHİBİ"] as $l) 
                echo '<div class="info-row"><div class="info-label">'.$l.'</div><div class="info-dots"></div></div>'; ?>
            <?php $c=ob_get_clean(); renderBlock('info_teslim_alan', $ayarlar['style_info_teslim_alan'], $c, $ayarlar['goster_info_teslim_alan']); ?>

            <?php ob_start(); ?>
            <div class="group-title">TESLİM EDİLENLER</div>
            <table class="check-table">
                <?php foreach(["RUHSAT", "ANAHTAR", "STEPNE", "KRİKO / BİJON ANAHTARI", "RADYO / TEYP PANELLERİ"] as $i) 
                    echo '<tr><td class="check-name">'.$i.'</td><td><div class="check-options"><label class="cb-item"><input type="checkbox"> VAR</label><label class="cb-item"><input type="checkbox"> YOK</label></div></td></tr>'; ?>
            </table>
            <?php $c=ob_get_clean(); renderBlock('chk_malzemeler', $ayarlar['style_chk_malzemeler'], $c, $ayarlar['goster_chk_malzemeler']); ?>

            <?php ob_start(); ?>
            <div class="legal-text">
                <strong style="text-decoration:underline;">TESLİM ALANIN BEYANI:</strong><br>
                Yukarıda özellikleri belirtilen aracı, otoparka bıraktığım/teslim edildiği haliyle, içindeki eşyalarla birlikte eksiksiz ve hasarsız olarak teslim aldım. Otopark işletmesinden herhangi bir hak ve alacağım yoktur. Aracı teslim aldıktan sonra oluşabilecek her türlü sorumluluk tarafıma aittir.
            </div>
            <?php $c=ob_get_clean(); renderBlock('beyan_metni', $ayarlar['style_beyan_metni'], $c, $ayarlar['goster_beyan_metni']); ?>

            <?php ob_start(); ?>
            <div class="sign-wrapper">
                <div class="sign-box"><strong>TESLİM EDEN (YEDİEMİN)</strong><div class="sign-line"></div></div>
                <div class="sign-box"><strong>TESLİM ALAN (ARAÇ SAHİBİ/VEKİLİ)</strong><div class="sign-line"></div></div>
            </div>
            <?php $c=ob_get_clean(); renderBlock('footer', $ayarlar['style_footer'], $c, $ayarlar['goster_footer']); ?>

        </div>
    </div>

    <script>
        var isEditMode = false;
        var activeElementId = null;

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
                $("#inp_font").val( $("#style_" + id + "_font").val() );
                $("#inp_size").val( $("#style_" + id + "_size").val() );
                $("#inp_color").val( $("#style_" + id + "_color").val() );
            }
        }

        $("#inp_font, #inp_size, #inp_color").on('input change', function() {
            if(!activeElementId) return;
            var font = $("#inp_font").val();
            var size = $("#inp_size").val();
            var color = $("#inp_color").val();
            var el = $("#el_" + activeElementId);

            el.css({ 'font-family': font, 'font-size': size + 'px', 'color': color });
            
            $("#style_" + activeElementId + "_font").val(font);
            $("#style_" + activeElementId + "_size").val(size);
            $("#style_" + activeElementId + "_color").val(color);
        });

        function updateInputs(el) {
            var id = el.attr('id').replace('el_', ''); 
            var pos = el.position();
            $('#pos_' + id + '_top').val(pos.top);
            $('#pos_' + id + '_left').val(pos.left);
            $('#pos_' + id + '_width').val(el.width());
            $('#pos_' + id + '_height').val(el.height());
        }

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