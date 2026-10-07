<?php
// Oturumu başlat
session_start();

// --- HEADER VE MENÜ AYARLARI ---
$yol = ""; 
$menu = "personel";

require_once 'db_config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// ============================================================================
// --- FİRMA BİLGİSİNİ ÇEK (YENİ EKLENDİ) ---
// ============================================================================
$firma_adi = 'YEDİEMİN OTOPARK İŞLETMESİ'; // Varsayılan
$sql_f = "SELECT firma_adi FROM firma_bilgileri WHERE id = 1";
if($res_f = $conn->query($sql_f)){
    if($row_f = $res_f->fetch_assoc()){
        if(!empty($row_f['firma_adi'])) $firma_adi = $row_f['firma_adi'];
    }
}

// ============================================================================
// 1. AYARLARI ÇEK: PERSONEL LİSTESİ ($ayarlar_list)
// ============================================================================
$varsayilan_list = [
    'baslik' => 'PERSONEL LİSTESİ', 'alt_baslik' => $firma_adi, // GÜNCELLENDİ: Değişken atandı
    'logo_url' => '',
    'goster_logo'=>1, 'imza_alani'=>1, 'goster_footer'=>1,
    'goster_ad'=>1, 'goster_gorev'=>1, 'goster_tel'=>1, 'goster_maas'=>1, 'goster_tc'=>1, 'goster_ise_giris'=>1,
    'style_logo' => ['top'=>20, 'left'=>20, 'width'=>100, 'height'=>'auto'],
    'style_baslik' => ['font'=>'Arial', 'size'=>24, 'color'=>'#000', 'top'=>30, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_alt_baslik' => ['font'=>'Arial', 'size'=>14, 'color'=>'#555', 'top'=>70, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_table' => ['font'=>'Arial', 'size'=>11, 'color'=>'#000', 'top'=>150, 'left'=>20, 'width'=>750, 'height'=>'auto'],
    'style_footer' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>800, 'left'=>20, 'width'=>750, 'height'=>'auto']
];
$sql_list = "SELECT ayarlar FROM rapor_ayarlari WHERE rapor_adi = 'personel_listesi'";
$res_list = $conn->query($sql_list);
$db_ayarlar_list = ($res_list->num_rows > 0) ? json_decode($res_list->fetch_assoc()['ayarlar'], true) : [];
$ayarlar_list = array_replace_recursive($varsayilan_list, is_array($db_ayarlar_list) ? $db_ayarlar_list : []);

// *** GÜNCEL FİRMA ADINI ZORLA (Tasarım dosyasıyla eşleşmesi için) ***
$ayarlar_list['alt_baslik'] = $firma_adi;

// ============================================================================
// 2. AYARLARI ÇEK: PERSONEL BİLGİ KARTI ($ayarlar_info)
// ============================================================================
$varsayilan_info = [
    'baslik' => 'PERSONEL BİLGİ FORMU', 'alt_baslik' => $firma_adi, // GÜNCELLENDİ: Değişken atandı
    'logo_url' => '',
    'goster_logo'=>1, 'goster_baslik'=>1, 'goster_alt_baslik'=>1, 'goster_footer'=>1,
    'goster_row_tc'=>1, 'goster_row_ad'=>1, 'goster_row_gorev'=>1, 'goster_row_sigorta'=>1, 'goster_row_ucret'=>1,
    'goster_row_baslama'=>1, 'goster_row_ayrilma'=>1, 'goster_row_medeni'=>1, 'goster_row_kan'=>1,
    'goster_row_cep'=>1, 'goster_row_ev'=>1, 'goster_row_adres'=>1, 'goster_row_not'=>1,
    'style_logo' => ['top'=>20, 'left'=>20, 'width'=>100, 'height'=>'auto'],
    'style_baslik' => ['font'=>'Arial', 'size'=>24, 'color'=>'#000', 'top'=>30, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_alt_baslik' => ['font'=>'Arial', 'size'=>14, 'color'=>'#555', 'top'=>70, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_footer' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>800, 'left'=>20, 'width'=>750, 'height'=>'auto'],
    'style_row_tc' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>150, 'left'=>50, 'width'=>300, 'height'=>'auto'],
    'style_row_ad' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>180, 'left'=>50, 'width'=>300, 'height'=>'auto'],
    'style_row_gorev' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>210, 'left'=>50, 'width'=>300, 'height'=>'auto'],
    'style_row_sigorta' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>240, 'left'=>50, 'width'=>300, 'height'=>'auto'],
    'style_row_ucret' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>270, 'left'=>50, 'width'=>300, 'height'=>'auto'],
    'style_row_baslama' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>300, 'left'=>50, 'width'=>300, 'height'=>'auto'],
    'style_row_ayrilma' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>330, 'left'=>50, 'width'=>300, 'height'=>'auto'],
    'style_row_medeni' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>360, 'left'=>50, 'width'=>300, 'height'=>'auto'],
    'style_row_kan' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>390, 'left'=>50, 'width'=>300, 'height'=>'auto'],
    'style_row_cep' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>150, 'left'=>400, 'width'=>300, 'height'=>'auto'],
    'style_row_ev' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>180, 'left'=>400, 'width'=>300, 'height'=>'auto'],
    'style_row_adres' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>210, 'left'=>400, 'width'=>300, 'height'=>'auto'],
    'style_row_not' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>300, 'left'=>400, 'width'=>300, 'height'=>'auto']
];
$sql_info = "SELECT ayarlar FROM rapor_ayarlari WHERE rapor_adi = 'personel_bilgileri'";
$res_info = $conn->query($sql_info);
$db_ayarlar_info = ($res_info->num_rows > 0) ? json_decode($res_info->fetch_assoc()['ayarlar'], true) : [];
$ayarlar_info = array_replace_recursive($varsayilan_info, is_array($db_ayarlar_info) ? $db_ayarlar_info : []);

// *** GÜNCEL FİRMA ADINI ZORLA ***
$ayarlar_info['alt_baslik'] = $firma_adi;

// ============================================================================
// 3. AYARLARI ÇEK: PERSONEL ÖDEMELERİ ($ayarlar_payments)
// ============================================================================
$varsayilan_payments = [
    'baslik' => 'PERSONEL ÖDEME RAPORU', 'alt_baslik' => $firma_adi, // GÜNCELLENDİ: Değişken atandı
    'logo_url' => '',
    'goster_logo'=>1, 'goster_baslik'=>1, 'goster_alt_baslik'=>1, 'goster_footer'=>1,
    'goster_tarih'=>1, 'goster_tur'=>1, 'goster_sekil'=>1, 'goster_aciklama'=>1, 'goster_tutar'=>1,
    'style_logo' => ['top'=>20, 'left'=>20, 'width'=>100, 'height'=>'auto'],
    'style_baslik' => ['font'=>'Arial', 'size'=>24, 'color'=>'#000', 'top'=>30, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_alt_baslik' => ['font'=>'Arial', 'size'=>14, 'color'=>'#555', 'top'=>70, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_table' => ['font'=>'Arial', 'size'=>11, 'color'=>'#000', 'top'=>150, 'left'=>20, 'width'=>750, 'height'=>'auto'],
    'style_footer' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>800, 'left'=>20, 'width'=>750, 'height'=>'auto']
];
$sql_payments = "SELECT ayarlar FROM rapor_ayarlari WHERE rapor_adi = 'personel_odemeleri'";
$res_payments = $conn->query($sql_payments);
$db_ayarlar_payments = ($res_payments->num_rows > 0) ? json_decode($res_payments->fetch_assoc()['ayarlar'], true) : [];
$ayarlar_payments = array_replace_recursive($varsayilan_payments, is_array($db_ayarlar_payments) ? $db_ayarlar_payments : []);

// *** GÜNCEL FİRMA ADINI ZORLA ***
$ayarlar_payments['alt_baslik'] = $firma_adi;

// ============================================================================
// YARDIMCI FONKSİYON
// ============================================================================
function renderRowInfo($kisa_key, $etiket, $deger) {
    global $ayarlar_info;
    $full_key = 'row_' . $kisa_key;
    if (empty($ayarlar_info['goster_' . $full_key])) return; 
    echo "<div id='print-info-{$full_key}' class='print-el info-row'><div class='info-label'>{$etiket}</div><div class='info-val'>{$deger}</div></div>";
}

// ... (Geri kalan işlemler) ...
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'personel_kayit') {
        $id = !empty($_POST['p_id']) ? $_POST['p_id'] : null;
        $tc = $_POST['tc_no']; $ad = mb_strtoupper($_POST['ad_soyad'], 'UTF-8');
        $medeni = $_POST['medeni_hal']; $kan = $_POST['kan_grubu']; $cep = $_POST['cep_tel']; $ev = $_POST['ev_tel'];
        $gorev = mb_strtoupper($_POST['gorevi'], 'UTF-8'); $sigorta = $_POST['sigorta_no'];
        $ucret = !empty($_POST['ucret']) ? $_POST['ucret'] : 0;
        $adres = mb_strtoupper($_POST['adres'], 'UTF-8'); $baslama = !empty($_POST['ise_baslama']) ? $_POST['ise_baslama'] : NULL;
        $ayrilma = !empty($_POST['isten_ayrilma']) ? $_POST['isten_ayrilma'] : NULL; $not = $_POST['personel_not'];

        if ($id) {
            $stmt = $conn->prepare("UPDATE personeller SET tc_no=?, ad_soyad=?, medeni_hal=?, kan_grubu=?, cep_tel=?, ev_tel=?, gorevi=?, sigorta_no=?, ucret=?, adres=?, ise_baslama=?, isten_ayrilma=?, personel_not=? WHERE id=?");
            $stmt->bind_param("ssssssssdssssi", $tc, $ad, $medeni, $kan, $cep, $ev, $gorev, $sigorta, $ucret, $adres, $baslama, $ayrilma, $not, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO personeller (tc_no, ad_soyad, medeni_hal, kan_grubu, cep_tel, ev_tel, gorevi, sigorta_no, ucret, adres, ise_baslama, isten_ayrilma, personel_not) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssdssss", $tc, $ad, $medeni, $kan, $cep, $ev, $gorev, $sigorta, $ucret, $adres, $baslama, $ayrilma, $not);
        }
        $stmt->execute(); $last_id = $id ? $id : $stmt->insert_id; $stmt->close();
        header("location: personeller.php?id=" . $last_id); exit;
    }
    
    if ($_POST['action'] == 'hareket_kayit') {
        $h_id = !empty($_POST['hareket_id']) ? $_POST['hareket_id'] : null;
        $p_id = $_POST['personel_id']; $tarih = $_POST['islem_tarihi']; $tur = $_POST['islem_turu']; 
        $tutar = $_POST['tutar']; $aciklama = mb_strtoupper($_POST['aciklama'], 'UTF-8');
        $odeme_sekli = ($tur == 'Odeme' || $tur == 'Avans') ? $_POST['odeme_sekli'] : NULL;

        if ($h_id) {
            $stmt = $conn->prepare("UPDATE personel_hareketleri SET islem_tarihi=?, islem_turu=?, odeme_sekli=?, tutar=?, aciklama=? WHERE id=?");
            $stmt->bind_param("sssdsi", $tarih, $tur, $odeme_sekli, $tutar, $aciklama, $h_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO personel_hareketleri (personel_id, islem_tarihi, islem_turu, odeme_sekli, tutar, aciklama) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssds", $p_id, $tarih, $tur, $odeme_sekli, $tutar, $aciklama);
        }
        $stmt->execute(); $stmt->close();
        header("location: personeller.php?id=" . $p_id); exit;
    }
}

if (isset($_GET['sil_id'])) { $conn->query("DELETE FROM personeller WHERE id = {$_GET['sil_id']}"); header("location: personeller.php"); exit; }
if (isset($_GET['sil_hareket_id'])) { $conn->query("DELETE FROM personel_hareketleri WHERE id = {$_GET['sil_hareket_id']}"); header("location: personeller.php?id={$_GET['pid']}"); exit; }

$personel_listesi = $conn->query("SELECT * FROM personeller ORDER BY id DESC");
$secili_personel = null; $hakedisler = []; $odemeler = []; $toplam_hakedis = 0; $toplam_odenen = 0;

if (isset($_GET['id'])) {
    $secili_id = $_GET['id'];
    $secili_personel = $conn->query("SELECT * FROM personeller WHERE id = $secili_id")->fetch_assoc();
    if($secili_personel) {
        $res_hareket = $conn->query("SELECT * FROM personel_hareketleri WHERE personel_id = $secili_id ORDER BY islem_tarihi DESC");
        while($row = $res_hareket->fetch_assoc()) {
            if ($row['islem_turu'] == 'Hakedis' || $row['islem_turu'] == 'Prim') { $hakedisler[] = $row; $toplam_hakedis += $row['tutar']; } 
            else { $odemeler[] = $row; $toplam_odenen += $row['tutar']; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Personel Yönetimi</title>
    <link rel="stylesheet" href="<?php echo $yol; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .personel-layout { display: grid; grid-template-columns: 450px 1fr; grid-template-rows: auto auto; gap: 20px; margin-top: 20px; }
        .panel-header { background-color: #f8f9fa; border-bottom: 1px solid #e9ecef; padding: 12px 15px; font-weight: 700; color: #495057; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
        .form-section-title { font-size: 12px; font-weight: 800; color: var(--ana-mavi); border-bottom: 2px solid #eee; padding-bottom: 5px; margin: 15px 0 10px 0; text-transform: uppercase; }
        .form-compact-row { display: flex; align-items: center; margin-bottom: 8px; }
        .form-compact-row label { width: 100px; font-size: 12px; font-weight: 600; color: #555; flex-shrink: 0; }
        .form-compact-row input, .form-compact-row select, .form-compact-row textarea { flex-grow: 1; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; width: 100%; }
        .bottom-panels { grid-column: span 2; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .table-scroll-container { height: 250px; overflow-y: auto; border-bottom: 1px solid #eee; }
        .total-box { text-align: right; padding: 10px; background-color: #e9ecef; font-weight: bold; font-size: 13px; }
        .row-actions { display: flex; gap: 5px; justify-content: center; }
        .btn-icon-mini { width: 24px; height: 24px; border-radius: 4px; border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; }
        .btn-icon-mini.blue { background-color: #17a2b8; }
        .btn-icon-mini.red { background-color: #dc3545; }
        
        #printable-info-design, #printable-list-design, #printable-payments-design { display: none; }

        /* YAZDIRMA MODU */
        @media print {
            @page { margin: 0; size: A4; }
            body * { visibility: hidden; }
            .main-header, .toolbar-container, .personel-layout, .no-print { display: none !important; }

            /* A. BİLGİ KARTI YAZDIRMA */
            body.print-printable-info #printable-info-design, body.print-printable-info #printable-info-design * { visibility: visible; }
            body.print-printable-info #printable-info-design { display: block !important; position: absolute; left: 0; top: 0; width: 210mm; height: 297mm; background-color: white; }

            /* B. ÖDEMELER YAZDIRMA */
            body.print-printable-payments #printable-payments-design, body.print-printable-payments #printable-payments-design * { visibility: visible; }
            body.print-printable-payments #printable-payments-design { display: block !important; position: absolute; left: 0; top: 0; width: 210mm; height: 297mm; background-color: white; }

            /* C. LİSTE YAZDIRMA (ARTIK ÖZEL CLASS İLE ÇALIŞIYOR) */
            body.print-printable-list #printable-list-design, body.print-printable-list #printable-list-design * { visibility: visible; }
            body.print-printable-list #printable-list-design { display: block !important; position: absolute; left: 0; top: 0; width: 210mm; height: 297mm; background-color: white; }

            .print-el { position: absolute; overflow: hidden; }
            img.print-img { width: 100%; height: 100%; object-fit: contain; }
            .info-row { display: flex; align-items: baseline; width: 100%; box-sizing:border-box; }
            .info-label { font-weight: bold; width: 120px; flex-shrink: 0; }
            .info-val { flex-grow: 1; border-bottom: 1px dotted #000; padding-left: 10px; }
            .sign-wrapper { display: flex; justify-content: space-between; width: 100%; }
            .sign-box { text-align: center; width: 45%; }
            .sign-line { border-top: 1px solid currentColor; margin-top: 30px; }
            .print-table { width: 100%; border-collapse: collapse; font-size: inherit; }
            .print-table th, .print-table td { border: 1px solid #ccc; padding: 5px; text-align: left; }
            .print-table th { background-color: #f0f0f0; font-weight: bold; }

            /* Dinamik CSS Injection */
            <?php 
            // 1. Bilgi Kartı Stilleri
            foreach(['row_tc','row_ad','row_gorev','row_sigorta','row_ucret','row_baslama','row_ayrilma','row_medeni','row_kan','row_cep','row_ev','row_adres','row_not'] as $key) {
                if(isset($ayarlar_info['style_'.$key])) {
                    $s = $ayarlar_info['style_'.$key];
                    echo "#print-info-{$key} { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; font-family: {$s['font']}; font-size: {$s['size']}px; color: {$s['color']}; display: ".($ayarlar_info['goster_'.$key]?'flex':'none')." !important; }";
                }
            }
            $s = $ayarlar_info['style_logo']; echo "#print-info-logo { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; height: " . ($s['height']!='auto' ? $s['height'].'px' : 'auto') . "; }";
            $s = $ayarlar_info['style_baslik']; echo "#print-info-baslik { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; font-family: {$s['font']}; font-size: {$s['size']}px; color: {$s['color']}; text-align:center; font-weight:800; text-transform:uppercase; margin:0; }";
            $s = $ayarlar_info['style_alt_baslik']; echo "#print-info-alt-baslik { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; font-family: {$s['font']}; font-size: {$s['size']}px; color: {$s['color']}; text-align:center; margin:0; }";
            $s = $ayarlar_info['style_footer']; echo "#print-info-footer { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; font-family: {$s['font']}; font-size: {$s['size']}px; color: {$s['color']}; display: ".($ayarlar_info['goster_footer']?'block':'none')." !important; }";

            // 2. Liste Raporu Stilleri
            $s = $ayarlar_list['style_logo']; echo "#print-list-logo { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; height: " . ($s['height']!='auto' ? $s['height'].'px' : 'auto') . "; }";
            $s = $ayarlar_list['style_baslik']; echo "#print-list-baslik { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; font-family: {$s['font']}; font-size: {$s['size']}px; color: {$s['color']}; text-align:center; font-weight:800; text-transform:uppercase; margin:0; }";
            $s = $ayarlar_list['style_alt_baslik']; echo "#print-list-alt-baslik { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; font-family: {$s['font']}; font-size: {$s['size']}px; color: {$s['color']}; text-align:center; margin:0; }";
            $s = $ayarlar_list['style_table']; echo "#print-list-table { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; font-family: {$s['font']}; font-size: {$s['size']}px; color: {$s['color']}; }";
            $s = $ayarlar_list['style_footer']; echo "#print-list-footer { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; font-family: {$s['font']}; font-size: {$s['size']}px; color: {$s['color']}; display: ".($ayarlar_list['goster_footer']?'block':'none')." !important; }";

            // 3. Ödemeler Raporu Stilleri
            $s = $ayarlar_payments['style_logo']; echo "#print-pay-logo { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; height: " . ($s['height']!='auto' ? $s['height'].'px' : 'auto') . "; }";
            $s = $ayarlar_payments['style_baslik']; echo "#print-pay-baslik { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; font-family: {$s['font']}; font-size: {$s['size']}px; color: {$s['color']}; text-align:center; font-weight:800; text-transform:uppercase; margin:0; }";
            $s = $ayarlar_payments['style_alt_baslik']; echo "#print-pay-alt-baslik { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; font-family: {$s['font']}; font-size: {$s['size']}px; color: {$s['color']}; text-align:center; margin:0; }";
            $s = $ayarlar_payments['style_table']; echo "#print-pay-table { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; font-family: {$s['font']}; font-size: {$s['size']}px; color: {$s['color']}; }";
            $s = $ayarlar_payments['style_footer']; echo "#print-pay-footer { top: {$s['top']}px; left: {$s['left']}px; width: {$s['width']}px; font-family: {$s['font']}; font-size: {$s['size']}px; color: {$s['color']}; display: ".($ayarlar_payments['goster_footer']?'block':'none')." !important; }";
            ?>
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="main-content">
        <div class="toolbar-container no-print">
            <div class="toolbar-left">
                <a href="personeller.php" class="btn"><i class="fas fa-user-plus"></i> Yeni Personel</a>
                <button type="submit" form="personelKayitForm" class="btn btn-success"><i class="fas fa-save"></i> Kaydet</button>
                <?php if(isset($secili_id)): ?>
                    <button type="submit" form="personelKayitForm" class="btn btn-primary"><i class="fas fa-edit"></i> Güncelle</button>
                    <a href="personeller.php?sil_id=<?php echo $secili_id; ?>" onclick="return confirm('Silmek istediğinize emin misiniz?')" class="btn btn-danger"><i class="fas fa-trash"></i> Sil</a>
                <?php endif; ?>
                <a href="personeller.php" class="btn btn-danger"><i class="fas fa-times-circle"></i> Vazgeç</a>
            </div>
            <div class="toolbar-right">
                <button class="btn" onclick="printSpecificArea('printable-list')"><i class="fas fa-list"></i> Listeyi Yazdır</button> 
                <button class="btn" onclick="printSpecificArea('printable-info')"><i class="fas fa-file-invoice"></i> Bilgileri Yazdır</button>
                <button class="btn" onclick="printSpecificArea('printable-payments')"><i class="fas fa-money-check"></i> Ödemeleri Yazdır</button>
            </div>
        </div>

        <div class="personel-layout">
            <div class="form-card" style="margin-bottom: 0;">
                <div class="panel-header"><i class="fas fa-user-edit"></i> Personel Bilgi Kartı</div>
                <div class="form-card-body" style="height: 350px; overflow-y: auto;">
                    <form id="personelKayitForm" method="POST">
                        <input type="hidden" name="action" value="personel_kayit">
                        <input type="hidden" name="p_id" value="<?php echo $secili_personel['id'] ?? ''; ?>">
                        <div class="form-section-title" style="margin-top:0;">KAYIT BİLGİLERİ</div>
                        <div class="form-compact-row"><label>TC Kimlik No</label><input type="text" name="tc_no" class="numeric-only" maxlength="11" value="<?php echo $secili_personel['tc_no'] ?? ''; ?>" required></div>
                        <div class="form-compact-row"><label>Adı Soyadı</label><input type="text" name="ad_soyad" class="force-uppercase" value="<?php echo $secili_personel['ad_soyad'] ?? ''; ?>" required></div>
                        <div class="form-compact-row"><label>Görevi</label><input type="text" name="gorevi" class="force-uppercase" value="<?php echo $secili_personel['gorevi'] ?? ''; ?>"></div>
                        <div class="form-compact-row"><label>Sigorta No</label><input type="text" name="sigorta_no" class="numeric-only" value="<?php echo $secili_personel['sigorta_no'] ?? ''; ?>"></div>
                        <div class="form-compact-row"><label>Ücreti (Maaş)</label><input type="number" name="ucret" step="0.01" min="0" oninput="validity.valid||(value='');" value="<?php echo $secili_personel['ucret'] ?? ''; ?>"></div>
                        <div class="form-compact-row"><label>İşe Başlama</label><input type="date" name="ise_baslama" value="<?php echo $secili_personel['ise_baslama'] ?? date('Y-m-d'); ?>"></div>
                        <div class="form-compact-row"><label>İşten Ayrılma</label><input type="date" name="isten_ayrilma" value="<?php echo $secili_personel['isten_ayrilma'] ?? ''; ?>"></div>
                        <div class="form-section-title">KİMLİK VE DİĞER BİLGİLER</div>
                        <div class="form-compact-row"><label>Medeni Hali</label><select name="medeni_hal"><option value="Bekar" <?php if(($secili_personel['medeni_hal']??'')=='Bekar') echo 'selected'; ?>>Bekar</option><option value="Evli" <?php if(($secili_personel['medeni_hal']??'')=='Evli') echo 'selected'; ?>>Evli</option></select></div>
                        <div class="form-compact-row"><label>Kan Grubu</label><select name="kan_grubu"><option value="">Seçiniz</option><option value="A Rh+" <?php if(($secili_personel['kan_grubu']??'')=='A Rh+') echo 'selected'; ?>>A Rh+</option><option value="B Rh+" <?php if(($secili_personel['kan_grubu']??'')=='B Rh+') echo 'selected'; ?>>B Rh+</option><option value="0 Rh+" <?php if(($secili_personel['kan_grubu']??'')=='0 Rh+') echo 'selected'; ?>>0 Rh+</option></select></div>
                        <div class="form-compact-row"><label>Cep Telefonu</label><input type="text" name="cep_tel" class="phone-mask numeric-only" value="<?php echo $secili_personel['cep_tel'] ?? ''; ?>"></div>
                        <div class="form-compact-row"><label>Ev Telefonu</label><input type="text" name="ev_tel" class="phone-mask numeric-only" value="<?php echo $secili_personel['ev_tel'] ?? ''; ?>"></div>
                        <div class="form-compact-row"><label>Adres</label><textarea name="adres" rows="2" class="force-uppercase"><?php echo $secili_personel['adres'] ?? ''; ?></textarea></div>
                        <div class="form-compact-row"><label>Not</label><textarea name="personel_not" rows="2" class="force-uppercase"><?php echo $secili_personel['personel_not'] ?? ''; ?></textarea></div>
                    </form>
                </div>
            </div>

            <div class="form-card" style="margin-bottom: 0;">
                <div class="panel-header"><i class="fas fa-list"></i> Kayıtlı Personeller Listesi</div>
                <div class="table-scroll-container" style="height: 385px;">
                    <table class="custom-table">
                        <thead><tr><th>Adı Soyadı</th><th>Görevi</th><th>Telefon</th><th>Maaş</th></tr></thead>
                        <tbody>
                            <?php $personel_listesi->data_seek(0); while($p = $personel_listesi->fetch_assoc()): $isSelected = (isset($_GET['id']) && $_GET['id'] == $p['id']); ?>
                            <tr onclick="window.location.href='personeller.php?id=<?php echo $p['id']; ?>'" style="cursor:pointer; <?php echo $isSelected ? 'background-color:#d1ecf1;' : ''; ?>">
                                <td><?php echo htmlspecialchars($p['ad_soyad']); ?></td><td><?php echo htmlspecialchars($p['gorevi']); ?></td><td><?php echo htmlspecialchars($p['cep_tel']); ?></td><td style="text-align:right;"><?php echo number_format($p['ucret'], 2, ',', '.'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bottom-panels">
                <div class="form-card" style="margin-bottom: 0;">
                    <div class="panel-header"><span><i class="fas fa-money-check"></i> Hakedişler</span><button class="btn-icon-small no-print" onclick="openModal('hakedis')"><i class="fas fa-plus"></i></button></div>
                    <div class="table-scroll-container" style="height: 200px;">
                        <table class="custom-table"><thead><tr><th>Tarih</th><th>Açıklama</th><th style="text-align:right;">Tutar</th><th class="no-print">İşlem</th></tr></thead>
                            <tbody><?php foreach($hakedisler as $h): ?><tr><td><?php echo date("d.m.Y", strtotime($h['islem_tarihi'])); ?></td><td><?php echo htmlspecialchars($h['aciklama']); ?></td><td style="text-align:right;"><?php echo number_format($h['tutar'], 2, ',', '.'); ?></td><td class="no-print"><div class="row-actions"><button class="btn-icon-mini blue" onclick="editHareket(<?php echo htmlspecialchars(json_encode($h)); ?>)"><i class="fas fa-edit"></i></button><a href="personeller.php?id=<?php echo $secili_id; ?>&sil_hareket_id=<?php echo $h['id']; ?>&pid=<?php echo $secili_id; ?>" onclick="return confirm('Sil')" class="btn-icon-mini red"><i class="fas fa-trash"></i></a></div></td></tr><?php endforeach; ?></tbody>
                        </table>
                    </div><div class="total-box">Toplam: <?php echo number_format($toplam_hakedis, 2, ',', '.'); ?> TL</div>
                </div>
                <div class="form-card" style="margin-bottom: 0;">
                    <div class="panel-header"><span><i class="fas fa-hand-holding-usd"></i> Ödemeler</span><button class="btn-icon-small no-print" onclick="openModal('odeme')"><i class="fas fa-plus"></i></button></div>
                    <div class="table-scroll-container" style="height: 200px;">
                        <table class="custom-table"><thead><tr><th>Tarih</th><th>Tür</th><th>Şekil</th><th style="text-align:right;">Tutar</th><th class="no-print">İşlem</th></tr></thead>
                            <tbody><?php foreach($odemeler as $o): ?><tr><td><?php echo date("d.m.Y", strtotime($o['islem_tarihi'])); ?></td><td><?php echo htmlspecialchars($o['islem_turu']); ?></td><td><?php echo htmlspecialchars($o['odeme_sekli']); ?></td><td style="text-align:right;"><?php echo number_format($o['tutar'], 2, ',', '.'); ?></td><td class="no-print"><div class="row-actions"><button class="btn-icon-mini blue" onclick="editHareket(<?php echo htmlspecialchars(json_encode($o)); ?>)"><i class="fas fa-edit"></i></button><a href="personeller.php?id=<?php echo $secili_id; ?>&sil_hareket_id=<?php echo $o['id']; ?>&pid=<?php echo $secili_id; ?>" onclick="return confirm('Sil')" class="btn-icon-mini red"><i class="fas fa-trash"></i></a></div></td></tr><?php endforeach; ?></tbody>
                        </table>
                    </div><div class="total-box">Toplam: <?php echo number_format($toplam_odenen, 2, ',', '.'); ?> TL</div>
                </div>
            </div>
        </div>
    </div>

    <?php if($secili_personel): ?>
    <div id="printable-info-design">
        <?php if(!empty($ayarlar_info['logo_url']) && !empty($ayarlar_info['goster_logo'])): ?><div id="print-info-logo" class="print-el"><img src="<?php echo $ayarlar_info['logo_url']; ?>" class="print-img"></div><?php endif; ?>
        <?php if(!empty($ayarlar_info['goster_baslik'])): ?><div id="print-info-baslik" class="print-el"><?php echo htmlspecialchars($ayarlar_info['baslik']); ?></div><?php endif; ?>
        <?php if(!empty($ayarlar_info['goster_alt_baslik'])): ?><div id="print-info-alt-baslik" class="print-el"><?php echo htmlspecialchars($ayarlar_info['alt_baslik']); ?></div><?php endif; ?>
        <?php 
        renderRowInfo('tc', 'TC KİMLİK', $secili_personel['tc_no']); renderRowInfo('ad', 'ADI SOYADI', $secili_personel['ad_soyad']);
        renderRowInfo('gorev', 'GÖREVİ', $secili_personel['gorevi']); renderRowInfo('sigorta', 'SİGORTA NO', $secili_personel['sigorta_no']);
        renderRowInfo('ucret', 'MAAŞ', number_format($secili_personel['ucret'], 2, ',', '.') . ' TL'); renderRowInfo('cep', 'CEP TEL', $secili_personel['cep_tel']);
        renderRowInfo('ev', 'EV TEL', $secili_personel['ev_tel']); renderRowInfo('adres', 'ADRES', $secili_personel['adres']);
        renderRowInfo('baslama', 'İŞE BAŞLAMA', date("d.m.Y", strtotime($secili_personel['ise_baslama']))); renderRowInfo('ayrilma', 'İŞTEN AYRILMA', !empty($secili_personel['isten_ayrilma']) ? date("d.m.Y", strtotime($secili_personel['isten_ayrilma'])) : '-');
        renderRowInfo('medeni', 'MEDENİ HAL', $secili_personel['medeni_hal']); renderRowInfo('kan', 'KAN GRUBU', $secili_personel['kan_grubu']); renderRowInfo('not', 'NOT', $secili_personel['personel_not']);
        ?>
        <div id="print-info-footer" class="print-el"><div class="sign-wrapper"><div class="sign-box"><strong>YÖNETİCİ</strong><div class="sign-line"></div></div><div class="sign-box"><strong>PERSONEL</strong><div class="sign-line"></div></div></div></div>
    </div>
    <?php endif; ?>

    <div id="printable-list-design">
        <?php if(!empty($ayarlar_list['logo_url']) && !empty($ayarlar_list['goster_logo'])): ?><div id="print-list-logo" class="print-el"><img src="<?php echo $ayarlar_list['logo_url']; ?>" class="print-img"></div><?php endif; ?>
        <div id="print-list-baslik" class="print-el"><?php echo htmlspecialchars($ayarlar_list['baslik']); ?></div>
        <div id="print-list-alt-baslik" class="print-el"><?php echo htmlspecialchars($ayarlar_list['alt_baslik']); ?></div>
        <div id="print-list-table" class="print-el">
            <table class="print-table">
                <thead><tr>
                    <?php if($ayarlar_list['goster_ad']): ?><th>ADI SOYADI</th><?php endif; ?>
                    <?php if($ayarlar_list['goster_tc']): ?><th>TC KİMLİK</th><?php endif; ?>
                    <?php if($ayarlar_list['goster_gorev']): ?><th>GÖREVİ</th><?php endif; ?>
                    <?php if($ayarlar_list['goster_tel']): ?><th>TELEFON</th><?php endif; ?>
                    <?php if($ayarlar_list['goster_maas']): ?><th style="text-align:right;">MAAŞ</th><?php endif; ?>
                    <?php if($ayarlar_list['goster_ise_giris']): ?><th>İŞE GİRİŞ</th><?php endif; ?>
                </tr></thead>
                <tbody>
                    <?php $personel_listesi->data_seek(0); while($row = $personel_listesi->fetch_assoc()): ?>
                    <tr>
                        <?php if($ayarlar_list['goster_ad']): ?><td><?php echo htmlspecialchars($row['ad_soyad']); ?></td><?php endif; ?>
                        <?php if($ayarlar_list['goster_tc']): ?><td><?php echo htmlspecialchars($row['tc_no']); ?></td><?php endif; ?>
                        <?php if($ayarlar_list['goster_gorev']): ?><td><?php echo htmlspecialchars($row['gorevi']); ?></td><?php endif; ?>
                        <?php if($ayarlar_list['goster_tel']): ?><td><?php echo htmlspecialchars($row['cep_tel']); ?></td><?php endif; ?>
                        <?php if($ayarlar_list['goster_maas']): ?><td style="text-align:right;"><?php echo number_format($row['ucret'], 2, ',', '.') . ' ₺'; ?></td><?php endif; ?>
                        <?php if($ayarlar_list['goster_ise_giris']): ?><td><?php echo date("d.m.Y", strtotime($row['ise_baslama'])); ?></td><?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div id="print-list-footer" class="print-el"><div class="sign-wrapper"><div class="sign-box"><strong>MUHASEBE</strong><div class="sign-line"></div></div><div class="sign-box"><strong>ONAYLAYAN</strong><div class="sign-line"></div></div></div></div>
    </div>

    <div id="printable-payments-design">
        <?php if(!empty($ayarlar_payments['logo_url']) && !empty($ayarlar_payments['goster_logo'])): ?><div id="print-pay-logo" class="print-el"><img src="<?php echo $ayarlar_payments['logo_url']; ?>" class="print-img"></div><?php endif; ?>
        <div id="print-pay-baslik" class="print-el"><?php echo htmlspecialchars($ayarlar_payments['baslik']); ?></div>
        <div id="print-pay-alt-baslik" class="print-el"><?php echo htmlspecialchars($ayarlar_payments['alt_baslik']); ?></div>
        <div id="print-pay-table" class="print-el">
            <table class="print-table">
                <thead><tr>
                    <?php if($ayarlar_payments['goster_tarih']): ?><th>TARİH</th><?php endif; ?>
                    <?php if($ayarlar_payments['goster_tur']): ?><th>TÜR</th><?php endif; ?>
                    <?php if($ayarlar_payments['goster_sekil']): ?><th>ŞEKİL</th><?php endif; ?>
                    <?php if($ayarlar_payments['goster_aciklama']): ?><th>AÇIKLAMA</th><?php endif; ?>
                    <?php if($ayarlar_payments['goster_tutar']): ?><th style="text-align:right;">TUTAR</th><?php endif; ?>
                </tr></thead>
                <tbody>
                    <?php 
                    $tum_hareketler = array_merge($hakedisler, $odemeler);
                    usort($tum_hareketler, function($a, $b) { return strtotime($b['islem_tarihi']) - strtotime($a['islem_tarihi']); });
                    if(!empty($tum_hareketler)): foreach($tum_hareketler as $h): ?>
                    <tr>
                        <?php if($ayarlar_payments['goster_tarih']): ?><td><?php echo date("d.m.Y", strtotime($h['islem_tarihi'])); ?></td><?php endif; ?>
                        <?php if($ayarlar_payments['goster_tur']): ?><td><?php echo htmlspecialchars($h['islem_turu']); ?></td><?php endif; ?>
                        <?php if($ayarlar_payments['goster_sekil']): ?><td><?php echo htmlspecialchars($h['odeme_sekli'] ?? '-'); ?></td><?php endif; ?>
                        <?php if($ayarlar_payments['goster_aciklama']): ?><td><?php echo htmlspecialchars($h['aciklama']); ?></td><?php endif; ?>
                        <?php if($ayarlar_payments['goster_tutar']): ?><td style="text-align:right;"><?php echo number_format($h['tutar'], 2, ',', '.') . ' ₺'; ?></td><?php endif; ?>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center;">Kayıt bulunamadı.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="print-pay-footer" class="print-el"><div class="sign-wrapper"><div class="sign-box"><strong>MUHASEBE</strong><div class="sign-line"></div></div><div class="sign-box"><strong>ONAYLAYAN</strong><div class="sign-line"></div></div></div></div>
    </div>

    <div id="islemModal" class="modal-overlay" style="display:none;"><div class="modal-window" style="width:400px;"><div class="modal-header"><h3 id="modalTitle">İşlem Ekle</h3><span class="close-modal" onclick="closeModal()"><i class="fas fa-times"></i></span></div><div class="modal-content"><form method="POST"><input type="hidden" name="action" value="hareket_kayit"><input type="hidden" name="personel_id" value="<?php echo $secili_id ?? ''; ?>"><input type="hidden" name="hareket_id" id="hareketIdInput"><div class="form-compact-row"><label>Tarih</label><input type="date" name="islem_tarihi" id="islemTarihiInput" value="<?php echo date('Y-m-d'); ?>"></div><div class="form-compact-row" id="turSecimiDiv"><label>İşlem Türü</label><select name="islem_turu" id="islemTuruInput"></select></div><div class="form-compact-row" id="odemeSekliDiv"><label>Ödeme Şekli</label><select name="odeme_sekli" id="odemeSekliInput"><option value="Nakit">Nakit</option><option value="Banka">Banka</option></select></div><div class="form-compact-row"><label>Tutar</label><input type="number" name="tutar" id="tutarInput" step="0.01" min="0" oninput="validity.valid||(value='');" required></div><div class="form-compact-row"><label>Açıklama</label><input type="text" name="aciklama" id="aciklamaInput" class="force-uppercase"></div><div style="text-align:right; margin-top:15px;"><button type="submit" class="btn btn-success">Kaydet</button></div></form></div></div></div>

    <script>
        function openModal(tur) {
            <?php if(!isset($secili_id)): ?>alert("Lütfen önce listeden bir personel seçiniz!"); return;<?php endif; ?>
            document.getElementById('hareketIdInput').value = ""; document.getElementById('tutarInput').value = ""; document.getElementById('aciklamaInput').value = "";
            var turSelect = document.getElementById('islemTuruInput'); turSelect.innerHTML = ""; 
            if(tur === 'hakedis') { document.getElementById('modalTitle').innerText = "Hakediş / Maaş Ekle"; turSelect.add(new Option("Maaş Tahakkuk", "Hakedis")); turSelect.add(new Option("Prim / Ekstra", "Prim")); document.getElementById('odemeSekliDiv').style.display = 'none'; } else { document.getElementById('modalTitle').innerText = "Ödeme / Avans Ekle"; turSelect.add(new Option("Maaş Ödemesi", "Odeme")); turSelect.add(new Option("Avans", "Avans")); document.getElementById('odemeSekliDiv').style.display = 'flex'; }
            document.getElementById('islemModal').style.display = 'flex';
        }
        function editHareket(data) {
            document.getElementById('hareketIdInput').value = data.id; document.getElementById('islemTarihiInput').value = data.islem_tarihi; document.getElementById('tutarInput').value = data.tutar; document.getElementById('aciklamaInput').value = data.aciklama;
            var turSelect = document.getElementById('islemTuruInput'); turSelect.innerHTML = ""; turSelect.add(new Option(data.islem_turu, data.islem_turu));
            if(data.islem_turu == 'Odeme' || data.islem_turu == 'Avans') { document.getElementById('odemeSekliDiv').style.display = 'flex'; document.getElementById('odemeSekliInput').value = data.odeme_sekli; } else { document.getElementById('odemeSekliDiv').style.display = 'none'; }
            document.getElementById('modalTitle').innerText = "İşlem Düzenle"; document.getElementById('islemModal').style.display = 'flex';
        }
        function closeModal() { document.getElementById('islemModal').style.display = 'none'; }
        
        function printSpecificArea(areaID) {
            // YENİ EKLENEN KONTROL MEKANİZMASI:
            // Eğer Bilgiler veya Ödemeler yazdırılacaksa ve URL'de 'id' yoksa uyarı ver.
            if (areaID === 'printable-info' || areaID === 'printable-payments') {
                const urlParams = new URLSearchParams(window.location.search);
                if (!urlParams.has('id')) {
                    alert("Bu işlemi yapabilmek için lütfen listeden bir personel seçiniz!");
                    return; // Fonksiyondan çık, yazdırmayı başlatma
                }
            }

            document.body.classList.add('printing-mode');
            document.body.classList.add('print-' + areaID);
            window.print();
            document.body.classList.remove('printing-mode');
            document.body.classList.remove('print-' + areaID);
        }

        document.querySelectorAll('.force-uppercase').forEach(function(input) { input.addEventListener('input', function() { this.value = this.value.toUpperCase(); }); });
        document.querySelectorAll('.numeric-only').forEach(function(input) { input.addEventListener('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); }); });
        document.querySelectorAll('.phone-mask').forEach(function(input) { input.addEventListener('input', function(e) { var x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/); e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? ' ' + x[3] : '') + (x[4] ? ' ' + x[4] : ''); }); });
    </script>

</body>
</html>