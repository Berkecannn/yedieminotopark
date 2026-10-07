<?php
// Oturumu başlat
session_start();

// --- HEADER VE MENÜ AYARLARI ---
$yol = ""; 
$menu = "makbuz";

require_once 'db_config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// ============================================================================
// --- FİRMA BİLGİSİNİ ÇEK ---
// ============================================================================
$firma_adi = 'YEDİEMİN OTOPARK İŞLETMESİ'; // Varsayılan
$sql_f = "SELECT firma_adi FROM firma_bilgileri WHERE id = 1";
if($res_f = $conn->query($sql_f)){
    if($row_f = $res_f->fetch_assoc()){
        if(!empty($row_f['firma_adi'])) $firma_adi = $row_f['firma_adi'];
    }
}

// ============================================================================
// TASARIM AYARLARINI ÇEK
// ============================================================================
$varsayilan = [
    'baslik' => 'TAHSİLAT MAKBUZU', 'alt_baslik' => $firma_adi, 
    'logo_url' => '',
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

$sql_ayar = "SELECT ayarlar FROM rapor_ayarlari WHERE rapor_adi = 'para_makbuzu'";
$res_ayar = $conn->query($sql_ayar);
$ayarlar = ($res_ayar->num_rows > 0) ? array_replace_recursive($varsayilan, json_decode($res_ayar->fetch_assoc()['ayarlar'], true)) : $varsayilan;
$ayarlar['alt_baslik'] = $firma_adi;

// --- ARAÇTAN GELDİYSE BİLGİLERİ VE ÜCRETİ ÇEK ---
$linked_arac_id = null;
$prefill_sayin = "";
$prefill_aciklama = "";
$cekici_ucret_db = 0;
$val_otopark = "0.00"; // Varsayılan

if (isset($_GET['arac_id']) && is_numeric($_GET['arac_id'])) {
    $linked_arac_id = $_GET['arac_id'];
    $sql_arac = "SELECT * FROM araclar WHERE id = $linked_arac_id";
    $res_arac = $conn->query($sql_arac);
    if ($res_arac->num_rows > 0) {
        $arac_bilgi = $res_arac->fetch_assoc();
        $prefill_sayin = !empty($arac_bilgi['surucu_adi']) ? $arac_bilgi['surucu_adi'] : 'ARAÇ SAHİBİ';
        $cekici_ucret_db = isset($arac_bilgi['cekici_ucret']) ? (float)$arac_bilgi['cekici_ucret'] : 0;
        
        if ($cekici_ucret_db > 0) {
            $prefill_aciklama = $arac_bilgi['plaka'] . " PLAKALI ARAÇ OTOPARK VE ÇEKİCİ BEDELİ";
        } else {
            $prefill_aciklama = $arac_bilgi['plaka'] . " PLAKALI ARAÇ OTOPARK ÜCRETİ";
        }

        // --- YENİ: OTOMATİK OTOPARK ÜCRETİ HESAPLAMA ---
        $arac_cinsi = $arac_bilgi['cinsi'];
        $gunluk_ucret = 0;

        // 1. Tanımlı Günlük Ücreti Bul
        $sql_fiyat = "SELECT gunluk_ucret FROM tanim_arac_cins WHERE cins_adi = '$arac_cinsi'";
        if($res_fiyat = $conn->query($sql_fiyat)) {
            if($row_fiyat = $res_fiyat->fetch_assoc()) {
                $gunluk_ucret = floatval($row_fiyat['gunluk_ucret']);
            }
        }

        // 2. Gün Sayısını Hesapla (Giriş Tarihi - Bugün)
        if(!empty($arac_bilgi['giris_tarihi'])) {
            $giris_time = strtotime($arac_bilgi['giris_tarihi']);
            $bugun_time = time(); // Çıkış şu an yapılıyor varsayılır
            
            // Farkı güne çevir ve yukarı yuvarla (Örn: 1.2 gün -> 2 gün)
            $gun_farki = ceil(($bugun_time - $giris_time) / (60 * 60 * 24));
            
            // Eğer aynı gün girip çıkıyorsa en az 1 gün sayılır
            if ($gun_farki < 1) $gun_farki = 1;

            // 3. Hesaplama
            $hesaplanan_otopark = $gun_farki * $gunluk_ucret;
            $val_otopark = number_format($hesaplanan_otopark, 2, '.', '');
        }
    }
}

// --- İŞLEMLER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'kaydet') {
        $id = !empty($_POST['m_id']) ? $_POST['m_id'] : null;
        
        $tarih = $_POST['tarih'];
        $tutar = $_POST['tutar']; 
        $sayin = mb_strtoupper($_POST['sayin'], 'UTF-8');
        $teslim_eden = mb_strtoupper($_POST['teslim_eden'], 'UTF-8');
        $teslim_alan = mb_strtoupper($_POST['teslim_alan'], 'UTF-8');
        $aciklama = mb_strtoupper($_POST['aciklama'], 'UTF-8');
        $form_linked_arac_id = !empty($_POST['linked_arac_id']) ? $_POST['linked_arac_id'] : null;
        
        $tam_zaman = date('Y-m-d H:i:s');

        if ($id) {
            $sql = "UPDATE makbuzlar SET tarih=?, tutar=?, sayin=?, teslim_eden=?, teslim_alan=?, aciklama=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdssssi", $tarih, $tutar, $sayin, $teslim_eden, $teslim_alan, $aciklama, $id);
        } else {
            $sql = "INSERT INTO makbuzlar (tarih, tutar, sayin, teslim_eden, teslim_alan, aciklama) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdssss", $tarih, $tutar, $sayin, $teslim_eden, $teslim_alan, $aciklama);
        }
        
        if ($stmt->execute()) {
            $last_id = $id ? $id : $stmt->insert_id;
            if ($form_linked_arac_id) {
                $sql_update_arac = "UPDATE araclar SET durumu = 'CikisYapildi', cikis_tarihi = ?, ucret = ? WHERE id = ?";
                $stmt_arac = $conn->prepare($sql_update_arac);
                $stmt_arac->bind_param("sdi", $tam_zaman, $tutar, $form_linked_arac_id);
                $stmt_arac->execute();
                $stmt_arac->close();
            }
            header("location: makbuz.php?id=" . $last_id);
            exit;
        }
        $stmt->close();
    }
}

if (isset($_GET['sil_id'])) {
    $sil_id = $_GET['sil_id'];
    $conn->query("DELETE FROM makbuzlar WHERE id = $sil_id");
    header("location: makbuz.php");
    exit;
}

$where_sql = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($_GET['ara'])) {
    $term = "%" . $_GET['ara'] . "%";
    $where_sql .= " AND (sayin LIKE ? OR teslim_eden LIKE ? OR aciklama LIKE ?)";
    $params[] = $term; $params[] = $term; $params[] = $term;
    $types .= "sss";
}

$sql = "SELECT * FROM makbuzlar $where_sql ORDER BY tarih DESC, id DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$secili_makbuz = null;
if (isset($_GET['id'])) {
    $secili_id = $_GET['id'];
    $res_secili = $conn->query("SELECT * FROM makbuzlar WHERE id = $secili_id");
    $secili_makbuz = $res_secili->fetch_assoc();
}

$val_cekici = number_format($cekici_ucret_db, 2, '.', '');
// Eğer makbuz düzenleme modundaysa, DB'deki tutarı al. Değilse hesaplanan otopark + çekici
$val_toplam = isset($secili_makbuz['tutar']) 
    ? number_format($secili_makbuz['tutar'], 2, '.', '') 
    : number_format((float)$val_otopark + (float)$val_cekici, 2, '.', '');

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Makbuz İşlemleri</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
        .makbuz-layout { display: grid; grid-template-columns: 400px 1fr; gap: 20px; margin-top: 20px; height: calc(100vh - 180px); }
        .panel-header { background-color: #f8f9fa; border-bottom: 1px solid #e9ecef; padding: 12px 15px; font-weight: 700; color: #495057; font-size: 14px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
        .form-card { display: flex; flex-direction: column; height: 100%; background: #fff; border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
        .form-card-body { padding: 15px; flex-grow: 1; overflow-y: auto; }
        .form-row { margin-bottom: 15px; }
        .form-row label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 5px; }
        .form-row input, .form-row textarea { width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        .table-container { height: 100%; overflow-y: auto; }
        .search-bar { padding: 10px; background: #fff; border-bottom: 1px solid #eee; display: flex; gap: 5px; flex-shrink: 0; }
        .search-bar input { flex-grow: 1; padding: 6px; border: 1px solid #ddd; border-radius: 4px; }
        .calc-wrapper { background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 10px; margin-bottom: 15px; }
        .calc-title { font-size: 10px; font-weight: 700; color: #6c757d; margin-bottom: 5px; text-transform: uppercase; border-bottom: 1px solid #dee2e6; padding-bottom: 3px; }
        .calc-equation-row { display: flex; align-items: flex-end; justify-content: space-between; }
        .calc-group { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .calc-group label { font-size: 11px; font-weight: 600; color: #495057; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: center; }
        .calc-group input { text-align: right; font-weight: bold; color: #333; width: 100%; padding: 8px 4px; font-size: 15px; box-sizing: border-box; }
        .calc-symbol { font-size: 20px; font-weight: 800; color: #6c757d; margin: 0 15px; padding-bottom: 5px; flex-shrink: 0; }
        #inp_toplam { background-color: #e9ecef !important; color: #28a745 !important; font-weight: 800 !important; cursor: not-allowed; border: 1px solid #ced4da; }
        #inp_cekici { background-color: #e9ecef !important; cursor: not-allowed; }

        /* YAZDIRMA ALANI GİZLİ */
        #printable-area { display: none; }

        /* YAZDIRMA MODU (DİNAMİK) */
        @media print {
            @page { margin: 0; size: A4; }
            body { margin: 0; padding: 0; background: white; }
            body * { visibility: hidden; }
            .makbuz-layout, .header-container, .toolbar-container { display: none !important; }
            
            #printable-area, #printable-area * { visibility: visible; }
            #printable-area { display: block !important; position: absolute; left: 0; top: 0; width: 210mm; height: 297mm; background-color: white; z-index: 9999; }
            
            .print-el { position: absolute; overflow: hidden; }

            /* MAKBUZ SATIR STİLLERİ */
            .receipt-row { display: flex; width: 100%; align-items: flex-end; }
            .receipt-label { font-weight: bold; width: 130px; flex-shrink: 0; }
            .receipt-val { flex-grow: 1; border-bottom: 1px dotted #000; padding-left: 10px; }
            
            /* İMZA ALANI STİLLERİ */
            .sign-wrapper { display: flex; justify-content: space-between; width: 100%; }
            .sign-box { text-align: center; width: 45%; }
            .sign-name { margin-top: 5px; margin-bottom: 0; font-weight: normal; text-transform: uppercase; }
            .sign-line { border-top: 1px solid; margin-top: 50px; }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="main-content">
        
        <div class="toolbar-container no-print">
            <div class="toolbar-left">
                <?php if($linked_arac_id): ?>
                    <span class="badge badge-success" style="padding:10px;"><i class="fas fa-check-circle"></i> OTOMATİK HESAPLAMA MODU</span>
                <?php endif; ?>
                <a href="makbuz.php" class="btn"><i class="fas fa-plus-circle"></i> Yeni Kayıt</a>
                <button type="submit" form="makbuzForm" class="btn btn-success"><i class="fas fa-save"></i> <?php echo $linked_arac_id ? 'Kaydet & Çıkış Ver' : 'Kaydet / Güncelle'; ?></button>
                <?php if($secili_makbuz): ?>
                    <a href="makbuz.php?sil_id=<?php echo $secili_makbuz['id']; ?>" onclick="return confirm('Bu makbuzu silmek istediğinize emin misiniz?')" class="btn btn-danger"><i class="fas fa-trash"></i> Sil</a>
                    <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Makbuzu Yazdır</button>
                <?php endif; ?>
            </div>
            <div class="toolbar-right">
                <span style="font-size:12px; color:#666;">Toplam Kayıt: <strong><?php echo $result->num_rows; ?></strong></span>
            </div>
        </div>

        <div class="makbuz-layout no-print">
            
            <div class="form-card" style="margin-bottom: 0;">
                <div class="panel-header"><i class="fas fa-pen-nib"></i> Makbuz Bilgileri</div>
                <div class="form-card-body">
                    <form id="makbuzForm" method="POST">
                        <input type="hidden" name="action" value="kaydet">
                        <input type="hidden" name="m_id" value="<?php echo $secili_makbuz['id'] ?? ''; ?>">
                        <input type="hidden" name="linked_arac_id" value="<?php echo $linked_arac_id; ?>">

                        <div class="form-row">
                            <label>TARİH</label>
                            <input type="date" name="tarih" value="<?php echo $secili_makbuz['tarih'] ?? date('Y-m-d'); ?>" required>
                        </div>

                        <?php if($linked_arac_id): ?>
                        <div class="calc-wrapper">
                            <div class="calc-title">ÜCRET HESAPLAMA (<?php echo htmlspecialchars($arac_cinsi ?? ''); ?>)</div>
                            <div class="calc-equation-row">
                                <div class="calc-group"><label>Otopark</label><input type="number" id="inp_otopark" step="0.01" value="<?php echo $val_otopark; ?>" onchange="this.value=(parseFloat(this.value)||0).toFixed(2); hesaplaToplam();" oninput="hesaplaToplam()" autofocus></div>
                                <div class="calc-symbol">+</div>
                                <div class="calc-group"><label>Çekici</label><input type="number" id="inp_cekici" step="0.01" value="<?php echo $val_cekici; ?>" readonly></div>
                                <div class="calc-symbol">=</div>
                                <div class="calc-group"><label>TOPLAM</label><input type="number" id="inp_toplam" name="tutar" step="0.01" value="<?php echo $val_toplam; ?>" readonly></div>
                            </div>
                        </div>
                        <?php else: ?>
                            <div class="form-row">
                                <label>TUTAR (TL)</label>
                                <input type="number" name="tutar" step="0.01" style="font-weight:bold; color:#007bff;" value="<?php echo isset($secili_makbuz['tutar']) ? number_format($secili_makbuz['tutar'], 2, '.', '') : '0.00'; ?>" onchange="this.value=(parseFloat(this.value)||0).toFixed(2);" required>
                            </div>
                        <?php endif; ?>

                        <div class="form-row">
                            <label>SAYIN</label>
                            <input type="text" name="sayin" class="force-uppercase" value="<?php echo $secili_makbuz['sayin'] ?? $prefill_sayin; ?>" required>
                        </div>
                        <div class="form-row">
                            <label>TESLİM EDEN</label>
                            <input type="text" name="teslim_eden" class="force-uppercase" value="<?php echo $secili_makbuz['teslim_eden'] ?? ''; ?>">
                        </div>
                        <div class="form-row">
                            <label>TESLİM ALAN</label>
                            <input type="text" name="teslim_alan" class="force-uppercase" value="<?php echo $secili_makbuz['teslim_alan'] ?? $_SESSION["username"]; ?>">
                        </div>
                        <div class="form-row">
                            <label>AÇIKLAMA</label>
                            <textarea name="aciklama" rows="4" class="force-uppercase"><?php echo $secili_makbuz['aciklama'] ?? $prefill_aciklama; ?></textarea>
                        </div>
                    </form>
                </div>
            </div>

            <div class="form-card" style="margin-bottom: 0;">
                <div class="panel-header"><i class="fas fa-list"></i> Makbuz Kayıtları</div>
                <form method="GET" class="search-bar">
                    <input type="text" name="ara" class="force-uppercase" placeholder="Ara..." value="<?php echo $_GET['ara'] ?? ''; ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Ara</button>
                    <?php if(!empty($_GET['ara'])): ?><a href="makbuz.php" class="btn btn-sm btn-secondary">Temizle</a><?php endif; ?>
                </form>
                <div class="table-container">
                    <table class="custom-table">
                        <thead><tr><th>Tarih</th><th>Sayın</th><th>Teslim Eden</th><th>Teslim Alan</th><th style="text-align:right;">Tutar</th><th></th></tr></thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr onclick="window.location.href='makbuz.php?id=<?php echo $row['id']; ?>'" style="cursor:pointer; <?php echo (isset($secili_id) && $secili_id == $row['id']) ? 'background-color:#d1ecf1;' : ''; ?>">
                                        <td><?php echo date("d.m.Y", strtotime($row['tarih'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['sayin']); ?></td>
                                        <td><?php echo htmlspecialchars($row['teslim_eden']); ?></td>
                                        <td><?php echo htmlspecialchars($row['teslim_alan']); ?></td>
                                        <td style="text-align:right; font-weight:bold;"><?php echo number_format($row['tutar'], 2, ',', '.'); ?> ₺</td>
                                        <td><i class="fas fa-chevron-right" style="color:#ccc;"></i></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align:center; padding:20px; color:#999;">Kayıt bulunamadı.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if($secili_makbuz): ?>
    <div id="printable-area">
        
        <?php 
            function renderBlock($id, $style, $content, $visible=true) {
                $display = $visible ? 'block' : 'none';
                $font = isset($style['font']) ? "font-family:{$style['font']};" : "";
                $size = isset($style['size']) ? "font-size:{$style['size']}px;" : "";
                $color = isset($style['color']) ? "color:{$style['color']};" : "";
                $css = "top:{$style['top']}px; left:{$style['left']}px; width:{$style['width']}px; height:{$style['height']}px; display:{$display}; $font $size $color";
                echo "<div id='el_{$id}' class='print-el' style='$css'>$content</div>";
            }
        ?>

        <?php 
        $logoContent = !empty($ayarlar['logo_url']) ? "<img src='{$ayarlar['logo_url']}' style='width:100%; height:100%; object-fit:contain;'>" : "";
        renderBlock('logo', $ayarlar['style_logo'], $logoContent, $ayarlar['goster_logo']); 
        ?>

        <?php renderBlock('baslik', $ayarlar['style_baslik'], "<div style='font-weight:800; text-transform:uppercase; text-align:center;'>{$ayarlar['baslik']}</div>", $ayarlar['goster_baslik']); ?>

        <?php renderBlock('alt_baslik', $ayarlar['style_alt_baslik'], "<div style='text-align:center;'>{$ayarlar['alt_baslik']}</div>", $ayarlar['goster_alt_baslik']); ?>

        <?php renderBlock('tarih_no', $ayarlar['style_tarih_no'], "<div style='text-align:center;'>Tarih: ".date("d.m.Y", strtotime($secili_makbuz['tarih']))." | No: ".str_pad($secili_makbuz['id'], 6, '0', STR_PAD_LEFT)."</div>", $ayarlar['goster_tarih_no']); ?>

        <?php renderBlock('row_sayin', $ayarlar['style_row_sayin'], "<div class='receipt-row'><div class='receipt-label'>SAYIN</div><div class='receipt-val'>".htmlspecialchars($secili_makbuz['sayin'])."</div></div>", $ayarlar['goster_row_sayin']); ?>
        
        <?php renderBlock('row_tutar', $ayarlar['style_row_tutar'], "<div class='receipt-row'><div class='receipt-label'>TUTAR</div><div class='receipt-val'>".number_format($secili_makbuz['tutar'], 2, ',', '.')." TL</div></div>", $ayarlar['goster_row_tutar']); ?>
        
        <?php renderBlock('row_aciklama', $ayarlar['style_row_aciklama'], "<div class='receipt-row'><div class='receipt-label'>AÇIKLAMA</div><div class='receipt-val'>".htmlspecialchars($secili_makbuz['aciklama'])."</div></div>", $ayarlar['goster_row_aciklama']); ?>
        
        <?php renderBlock('row_teslim_eden', $ayarlar['style_row_teslim_eden'], "<div class='receipt-row'><div class='receipt-label'>TESLİM EDEN</div><div class='receipt-val'>".htmlspecialchars($secili_makbuz['teslim_eden'])."</div></div>", $ayarlar['goster_row_teslim_eden']); ?>

        <?php ob_start(); ?>
        <div class="sign-wrapper">
            <div class="sign-box">
                <strong>TESLİM EDEN</strong>
                <div class="sign-name"><?php echo htmlspecialchars($secili_makbuz['teslim_eden'] ?? ''); ?></div>
                <div class="sign-line" style="border-color:<?php echo $ayarlar['style_footer']['color'] ?? '#000'; ?>;"></div>
            </div>
            <div class="sign-box">
                <strong>TESLİM ALAN</strong>
                <div class="sign-name"><?php echo htmlspecialchars($secili_makbuz['teslim_alan'] ?? ''); ?></div>
                <div class="sign-line" style="border-color:<?php echo $ayarlar['style_footer']['color'] ?? '#000'; ?>;"></div>
            </div>
        </div>
        <?php $content = ob_get_clean();
        renderBlock('footer', $ayarlar['style_footer'], $content, $ayarlar['goster_footer']); ?>

    </div>
    <?php endif; ?>

    <script>
        document.querySelectorAll('.force-uppercase').forEach(function(input) {
            input.addEventListener('input', function() {
                var start = this.selectionStart; var end = this.selectionEnd;
                this.value = this.value.toUpperCase(); this.setSelectionRange(start, end);
            });
        });

        function hesaplaToplam() {
            var otopark = parseFloat(document.getElementById('inp_otopark').value) || 0;
            var cekici = parseFloat(document.getElementById('inp_cekici').value) || 0;
            var toplam = otopark + cekici;
            document.getElementById('inp_toplam').value = toplam.toFixed(2);
        }
    </script>

</body>
</html>