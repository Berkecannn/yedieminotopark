<?php
// Oturumu başlat
session_start();
$yol = "../"; 
$menu = "yazici";
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

// --- AYARLARI KAYDETME ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'ayar_kaydet') {
    
    $rapor_adi = 'para_makbuzu';
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

    $blocks = ['logo', 'baslik', 'alt_baslik', 'tarih_no', 'row_sayin', 'row_tutar', 'row_aciklama', 'row_teslim_eden', 'footer'];
    
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
    header("Location: tasarim_para_makbuzu.php");
    exit;
}

// --- MEVCUT AYARLARI ÇEK ---
$varsayilan = [
    'baslik' => 'TAHSİLAT MAKBUZU', 'alt_baslik' => $firma_adi, 'logo_url' => '',
    
    'goster_logo'=>1, 'goster_baslik'=>1, 'goster_alt_baslik'=>1, 'goster_tarih_no'=>1,
    'goster_row_sayin'=>1, 'goster_row_tutar'=>1, 'goster_row_aciklama'=>1, 'goster_row_teslim_eden'=>1, 'goster_footer'=>1,

    'style_logo' => ['top'=>20, 'left'=>20, 'width'=>100, 'height'=>'auto'],
    'style_baslik' => ['font'=>'Arial', 'size'=>24, 'color'=>'#000', 'top'=>30, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_alt_baslik' => ['font'=>'Arial', 'size'=>14, 'color'=>'#555', 'top'=>70, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_tarih_no' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>100, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    
    'style_row_sayin' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>150, 'left'=>20, 'width'=>750, 'height'=>'auto'],
    'style_row_tutar' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>190, 'left'=>20, 'width'=>750, 'height'=>'auto'],
    'style_row_aciklama' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>230, 'left'=>20, 'width'=>750, 'height'=>'auto'],
    'style_row_teslim_eden' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>270, 'left'=>20, 'width'=>750, 'height'=>'auto'],
    
    'style_footer' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>400, 'left'=>20, 'width'=>750, 'height'=>'auto']
];

$sql_get = "SELECT ayarlar FROM rapor_ayarlari WHERE rapor_adi = 'para_makbuzu'";
$res_get = $conn->query($sql_get);
if($res_get->num_rows > 0) {
    $db_ayarlar = json_decode($res_get->fetch_assoc()['ayarlar'], true);
    $ayarlar = array_replace_recursive($varsayilan, $db_ayarlar);
} else {
    $ayarlar = $varsayilan;
}

// *** FİRMA ADINI GÜNCELLE ***
$ayarlar['alt_baslik'] = $firma_adi;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Para Makbuzu Tasarımı</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

    <style>
        /* A4 Kağıt Simülasyonu */
        .paper-container {
            background-color: #fff; width: 210mm; height: 297mm;
            margin: 0 auto; position: relative; box-shadow: 0 0 15px rgba(0,0,0,0.1); border: 1px solid #ddd;
            overflow: hidden;
        }

        /* Düzenlenebilir Öğeler */
        .print-el { position: absolute; box-sizing: border-box; }

        /* Edit Mode Stilleri */
        .edit-mode .print-el {
            border: 1px dashed #ccc;
            cursor: move;
            background: rgba(255, 255, 255, 0.8);
            z-index: 10;
        }
        .edit-mode .print-el:hover, .edit-mode .print-el.active-element {
            border: 1px dashed #2196F3;
            background: rgba(33, 150, 243, 0.1);
            z-index: 100;
        }
        .ui-resizable-handle { background: transparent; width: 10px; height: 10px; }

        /* Editör Paneli */
        #editor-panel {
            display: none; position: fixed; top: 80px; left: 20px; width: 300px;
            background: #fff; border: 1px solid #ccc; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            padding: 15px; border-radius: 8px; z-index: 9999; max-height: 85vh; overflow-y: auto;
        }
        .panel-section { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px; }
        .panel-title { font-weight: bold; font-size: 12px; color: #555; margin-bottom: 5px; text-transform: uppercase; }
        .panel-row { margin-bottom: 8px; display:flex; align-items:center; justify-content:space-between; }
        .panel-row label { font-size: 11px; margin: 0; }
        .panel-row input[type="text"], .panel-row input[type="number"], .panel-row select { width: 60%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; font-size: 11px; }
        .panel-row input[type="color"] { border: none; width: 30px; height: 25px; padding: 0; }

        /* Switch */
        .switch { position: relative; display: inline-block; width: 30px; height: 16px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 16px; }
        .slider:before { position: absolute; content: ""; height: 12px; width: 12px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #2196F3; }
        input:checked + .slider:before { transform: translateX(14px); }

        /* İçerik Stilleri (Makbuza Özel) */
        .receipt-row { display: flex; width: 100%; align-items: flex-end; }
        .receipt-label { font-weight: bold; width: 130px; flex-shrink: 0; }
        .receipt-val { flex-grow: 1; border-bottom: 1px dotted #000; padding-left: 10px; }
        .sign-wrapper { display: flex; justify-content: space-between; width: 100%; }
        .sign-box { text-align: center; width: 45%; }
        .sign-line { border-top: 1px solid #000; margin-top: 40px; }
    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-content">
        <div class="page-header-row">
            <h2><i class="fas fa-edit"></i> Para Makbuzu Dizaynı</h2>
            <div class="header-actions">
                <button id="btn-toggle-edit" class="btn btn-warning"><i class="fas fa-edit"></i> Tasarımı Düzenle</button>
            </div>
        </div>

        <div id="editor-panel">
            <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:5px;">Düzenleme Paneli</h4>
            <form id="saveForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="ayar_kaydet">
                <input type="hidden" name="mevcut_logo" value="<?php echo $ayarlar['logo_url']; ?>">
                
                <?php 
                $blocks = ['logo', 'baslik', 'alt_baslik', 'tarih_no', 'row_sayin', 'row_tutar', 'row_aciklama', 'row_teslim_eden', 'footer'];
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

                <div class="panel-section">
                    <div class="panel-title">Seçili Öğe: <span id="lbl-selected" style="color:#2196F3;">Yok</span></div>
                    <div class="panel-row">
                        <label>Font</label>
                        <select id="inp_font" disabled>
                            <option value="Arial">Arial</option><option value="'Times New Roman'">Times New Roman</option><option value="'Courier New'">Courier New</option><option value="'Roboto'">Roboto</option>
                        </select>
                    </div>
                    <div class="panel-row"><label>Boyut (px)</label><input type="number" id="inp_size" disabled></div>
                    <div class="panel-row"><label>Renk</label><input type="color" id="inp_color" disabled></div>
                </div>

                <div class="panel-section">
                    <div class="panel-title">Alan Görünürlükleri</div>
                    <?php 
                    $labels = [
                        'logo'=>'Logo', 'baslik'=>'Başlık', 'alt_baslik'=>'Alt Başlık', 'tarih_no'=>'Tarih / No',
                        'row_sayin'=>'Sayın Satırı', 'row_tutar'=>'Tutar Satırı', 
                        'row_aciklama'=>'Açıklama', 'row_teslim_eden'=>'Teslim Eden', 'footer'=>'İmza Alanı'
                    ];
                    foreach($blocks as $b): 
                    ?>
                    <div class="panel-row">
                        <label><?php echo $labels[$b]; ?></label>
                        <label class="switch"><input type="checkbox" name="goster_<?php echo $b; ?>" id="tog_<?php echo $b; ?>" <?php echo $ayarlar['goster_'.$b] ? 'checked' : ''; ?>><span class="slider"></span></label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="panel-section">
                    <div class="panel-title">İçerik</div>
                    <div class="panel-row"><label>Logo</label><input type="file" name="logo" id="inp_logo"></div>
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
                function renderBlock($id, $style, $content, $visible=true) {
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
            $logoContent = !empty($ayarlar['logo_url']) ? "<img src='{$ayarlar['logo_url']}' id='prev_logo_img' style='width:100%; height:100%; object-fit:contain;'>" : "<div style='width:100%; height:100%; border:1px dashed #ccc; display:flex; align-items:center; justify-content:center; color:#999;'>Logo Yok</div>";
            renderBlock('logo', $ayarlar['style_logo'], $logoContent, $ayarlar['goster_logo']); 
            ?>

            <?php renderBlock('baslik', $ayarlar['style_baslik'], "<div id='txt_baslik' style='font-weight:800; text-transform:uppercase; text-align:center;'>{$ayarlar['baslik']}</div>", $ayarlar['goster_baslik']); ?>

            <?php renderBlock('alt_baslik', $ayarlar['style_alt_baslik'], "<div id='txt_alt_baslik' style='text-align:center; color:#555;'>{$ayarlar['alt_baslik']}</div>", $ayarlar['goster_alt_baslik']); ?>

            <?php renderBlock('tarih_no', $ayarlar['style_tarih_no'], "<div style='text-align:center;'>Tarih: 01.01.2025 | No: 000123</div>", $ayarlar['goster_tarih_no']); ?>

            <?php ob_start(); ?>
            <div class="receipt-row">
                <div class="receipt-label">SAYIN</div>
                <div class="receipt-val">AHMET YILMAZ</div>
            </div>
            <?php $content = ob_get_clean();
            renderBlock('row_sayin', $ayarlar['style_row_sayin'], $content, $ayarlar['goster_row_sayin']); ?>

            <?php ob_start(); ?>
            <div class="receipt-row">
                <div class="receipt-label">TUTAR</div>
                <div class="receipt-val">1.500,00 TL</div>
            </div>
            <?php $content = ob_get_clean();
            renderBlock('row_tutar', $ayarlar['style_row_tutar'], $content, $ayarlar['goster_row_tutar']); ?>

            <?php ob_start(); ?>
            <div class="receipt-row">
                <div class="receipt-label">AÇIKLAMA</div>
                <div class="receipt-val">34 ABC 123 OTOPARK ÜCRETİ</div>
            </div>
            <?php $content = ob_get_clean();
            renderBlock('row_aciklama', $ayarlar['style_row_aciklama'], $content, $ayarlar['goster_row_aciklama']); ?>

            <?php ob_start(); ?>
            <div class="receipt-row">
                <div class="receipt-label">TESLİM EDEN</div>
                <div class="receipt-val">MEHMET DEMİR</div>
            </div>
            <?php $content = ob_get_clean();
            renderBlock('row_teslim_eden', $ayarlar['style_row_teslim_eden'], $content, $ayarlar['goster_row_teslim_eden']); ?>

            <?php ob_start(); ?>
            <div class="sign-wrapper">
                <div class="sign-box">
                    <strong>TESLİM EDEN</strong>
                    <div class="sign-line"></div>
                    <div style="margin-top:5px;">MEHMET DEMİR</div>
                </div>
                <div class="sign-box">
                    <strong>TESLİM ALAN</strong>
                    <div class="sign-line"></div>
                </div>
            </div>
            <?php $content = ob_get_clean();
            renderBlock('footer', $ayarlar['style_footer'], $content, $ayarlar['goster_footer']); ?>

        </div>
    </div>

    <script>
        var isEditMode = false;
        var activeElementId = null;

        // EDİTÖR AÇ/KAPA
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

        // DRAG & RESIZE
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

        // SEÇİLİ ÖĞE YÖNETİMİ
        function setActiveElement(el) {
            $(".print-el").removeClass("active-element");
            el.addClass("active-element");
            
            var id = el.attr('id').replace('el_', '');
            activeElementId = id;
            var type = el.data('type');

            $("#lbl-selected").text(id.toUpperCase().replace('_', ' '));

            var isLogo = (type === 'logo');
            $("#inp_font, #inp_size, #inp_color").prop('disabled', isLogo);

            if(!isLogo) {
                $("#inp_font").val( $("#style_" + id + "_font").val() );
                $("#inp_size").val( $("#style_" + id + "_size").val() );
                $("#inp_color").val( $("#style_" + id + "_color").val() );
            }
        }

        // ANLIK STİL GÜNCELLEME
        $("#inp_font, #inp_size, #inp_color").on('input change', function() {
            if(!activeElementId) return;
            var font = $("#inp_font").val();
            var size = $("#inp_size").val();
            var color = $("#inp_color").val();
            var el = $("#el_" + activeElementId);

            // Görünüm
            el.css({ 'font-family': font, 'font-size': size + 'px', 'color': color });
            
            // Eğer footer ise çizgi rengini de değiştir
            if(activeElementId === 'footer') {
                el.find('.sign-line').css('border-color', color);
            }

            // Hidden Input
            $("#style_" + activeElementId + "_font").val(font);
            $("#style_" + activeElementId + "_size").val(size);
            $("#style_" + activeElementId + "_color").val(color);
        });

        // KOORDİNAT GÜNCELLEME
        function updateInputs(el) {
            var id = el.attr('id').replace('el_', ''); 
            var pos = el.position();
            $('#pos_' + id + '_top').val(pos.top);
            $('#pos_' + id + '_left').val(pos.left);
            $('#pos_' + id + '_width').val(el.width());
            $('#pos_' + id + '_height').val(el.height());
        }

        // TOGGLE İŞLEMLERİ
        const toggles = ['logo', 'baslik', 'alt_baslik', 'tarih_no', 'row_sayin', 'row_tutar', 'row_aciklama', 'row_teslim_eden', 'footer'];
        toggles.forEach(key => {
            $("#tog_" + key).on('change', function() {
                var visible = this.checked;
                $("#el_" + key).toggle(visible);
            });
        });

        // METİN VE LOGO GÜNCELLEMELERİ
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

        // SAYFA YÜKLENİNCE GÖRÜNÜRLÜKLERİ AYARLA
        $(window).on('load', function() {
            toggles.forEach(key => {
                var checked = $("#tog_" + key).is(':checked');
                $("#el_" + key).toggle(checked);
            });
        });

        document.querySelectorAll('.force-uppercase').forEach(inp => inp.addEventListener('input', function(){ this.value = this.value.toUpperCase(); }));
    </script>
</body>
</html>