<?php
// Oturumu başlat
session_start();

// --- HEADER VE MENÜ AYARLARI ---
$yol = "../";
$menu = "arac_kayit";

require_once '../db_config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
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
// TASARIM AYARLARINI ÇEK
// ============================================================================
$varsayilan = [
    'baslik' => 'HACİZLİ ARAÇ LİSTESİ', 'alt_baslik' => $firma_adi, // GÜNCELLENDİ: Değişken atandı
    'logo_url' => '',
    'goster_logo'=>1, 'imza_alani'=>1,
    'goster_sira'=>1, 'goster_giris'=>1, 'goster_plaka'=>1, 'goster_cinsi'=>1, 
    'goster_marka'=>1, 'goster_haciz'=>1, 'goster_dosya'=>1, 'goster_durum'=>1, 'goster_gun'=>1,
    
    'style_logo' => ['top'=>20, 'left'=>20, 'width'=>100, 'height'=>'auto'],
    'style_baslik' => ['font'=>'Arial', 'size'=>24, 'color'=>'#000000', 'top'=>30, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_alt_baslik' => ['font'=>'Arial', 'size'=>14, 'color'=>'#555555', 'top'=>70, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_table' => ['font'=>'Arial', 'size'=>11, 'color'=>'#000000', 'top'=>150, 'left'=>20, 'width'=>750, 'height'=>'auto'],
    'style_footer' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000000', 'top'=>800, 'left'=>20, 'width'=>750, 'height'=>'auto']
];

$sql_ayar = "SELECT ayarlar FROM rapor_ayarlari WHERE rapor_adi = 'hacizli_arac_raporu'";
$res_ayar = $conn->query($sql_ayar);
$ayarlar = ($res_ayar->num_rows > 0) ? array_replace_recursive($varsayilan, json_decode($res_ayar->fetch_assoc()['ayarlar'], true)) : $varsayilan;

// *** GÜNCEL FİRMA ADINI ZORLA (Tasarım dosyasıyla eşleşmesi için) ***
$ayarlar['alt_baslik'] = $firma_adi;
// ============================================================================


// --- FİLTRELEME MANTIĞI (ORİJİNAL) ---
$where = "WHERE (haciz_eden IS NOT NULL AND haciz_eden != '')";
$params = [];
$types = "";

if (!empty($_GET['haciz_eden'])) { $where .= " AND haciz_eden LIKE ?"; $params[] = "%" . $_GET['haciz_eden'] . "%"; $types .= "s"; }
if (!empty($_GET['dosya_no'])) { $where .= " AND dosya_no LIKE ?"; $params[] = "%" . $_GET['dosya_no'] . "%"; $types .= "s"; }
if (!empty($_GET['plaka'])) { $where .= " AND plaka LIKE ?"; $params[] = "%" . $_GET['plaka'] . "%"; $types .= "s"; }
if (!empty($_GET['durum'])) { $where .= " AND durumu = ?"; $params[] = $_GET['durum']; $types .= "s"; }
if (!empty($_GET['baslangic']) && !empty($_GET['bitis'])) { $where .= " AND giris_tarihi BETWEEN ? AND ?"; $params[] = $_GET['baslangic'] . " 00:00:00"; $params[] = $_GET['bitis'] . " 23:59:59"; $types .= "ss"; }

// --- SORGULAR ---
$sql = "SELECT * FROM araclar $where ORDER BY giris_tarihi DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// VERİYİ DİZİYE AL (Önemli Değişiklik: 2 Kere Kullanmak İçin)
$kayitlar = [];
while($row = $result->fetch_assoc()) {
    $kayitlar[] = $row;
}

// İstatistikler (ORİJİNAL)
$sql_park = "SELECT COUNT(*) as sayi FROM araclar WHERE (haciz_eden IS NOT NULL AND haciz_eden != '') AND durumu = 'Otoparkta'"; $res_park = $conn->query($sql_park); $cnt_park = $res_park->fetch_assoc()['sayi'];
$sql_exit = "SELECT COUNT(*) as sayi FROM araclar WHERE (haciz_eden IS NOT NULL AND haciz_eden != '') AND durumu = 'CikisYapildi'"; $res_exit = $conn->query($sql_exit); $cnt_exit = $res_exit->fetch_assoc()['sayi'];
$sql_yakalama = "SELECT COUNT(*) as sayi FROM araclar WHERE (haciz_eden IS NOT NULL AND haciz_eden != '') AND men_nedeni LIKE '%YAKALAMA%'"; $res_yakalama = $conn->query($sql_yakalama); $cnt_yakalama = $res_yakalama->fetch_assoc()['sayi'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hacizli Araç Raporları - Yediemin Otopark</title>
    <link rel="stylesheet" href="<?php echo $yol; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&family=Open+Sans&family=Lato&family=Montserrat&family=Courier+Prime&display=swap" rel="stylesheet">
    <style>
        /* ORİJİNAL STİLLER */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; }
        .stat-info h4 { margin: 0 0 5px 0; font-size: 14px; color: #6c757d; }
        .stat-info span { font-size: 24px; font-weight: 700; color: #343a40; }
        .stat-icon { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .bg-orange { background-color: #fff3bf; color: #f08c00; } 
        .bg-green { background-color: #e6f7ec; color: #28a745; }
        .bg-red { background-color: #fdecea; color: #dc3545; }
        .report-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .report-table th { background-color: #f8f9fa; color: #495057; font-weight: 700; text-transform: uppercase; padding: 10px; border-bottom: 2px solid #dee2e6; text-align: left; }
        .report-table td { padding: 8px 10px; border-bottom: 1px solid #eee; color: #333; }
        .report-table tr:hover { background-color: #f1f3f5; }
        .row-otoparkta { border-left: 4px solid #28a745; }
        .row-cikis { border-left: 4px solid #dc3545; }
        
        #printable-area { display: none; }

        /* YAZDIRMA MODU STİLLERİ (YENİ) */
        @media print {
            @page { margin: 0; size: A4; }
            body * { visibility: hidden; }
            .main-header, .stats-grid, .filter-card, .table-responsive, .page-header-row, .header-actions { display: none !important; }
            #printable-area, #printable-area * { visibility: visible; }
            
            #printable-area {
                display: block !important;
                position: absolute; left: 0; top: 0; 
                width: 210mm; height: 297mm;
                background-color: white;
            }
            .print-el { position: absolute; overflow: hidden; }

            /* 1. LOGO */
            <?php $s = $ayarlar['style_logo']; ?>
            #print-logo {
                top: <?php echo $s['top']; ?>px; left: <?php echo $s['left']; ?>px;
                width: <?php echo $s['width']; ?>px; height: <?php echo $s['height']!='auto' ? $s['height'].'px' : 'auto'; ?>;
            }
            #print-logo img { width: 100%; height: 100%; object-fit: contain; }

            /* 2. BAŞLIK */
            <?php $s = $ayarlar['style_baslik']; ?>
            #print-baslik {
                top: <?php echo $s['top']; ?>px; left: <?php echo $s['left']; ?>px; width: <?php echo $s['width']; ?>px;
                text-align: center;
                font-family: <?php echo $s['font']; ?>; font-size: <?php echo $s['size']; ?>px; color: <?php echo $s['color']; ?>;
            }
            #print-baslik h1 { margin: 0; font-weight: 800; text-transform: uppercase; font-size: inherit; color: inherit; }

            /* 3. ALT BAŞLIK */
            <?php $s = $ayarlar['style_alt_baslik']; ?>
            #print-alt-baslik {
                top: <?php echo $s['top']; ?>px; left: <?php echo $s['left']; ?>px; width: <?php echo $s['width']; ?>px;
                text-align: center;
                font-family: <?php echo $s['font']; ?>; font-size: <?php echo $s['size']; ?>px; color: <?php echo $s['color']; ?>;
            }
            #print-alt-baslik p { margin: 5px 0 0 0; font-weight: 600; text-transform: uppercase; font-size: inherit; color: inherit; }

            /* 4. TABLO */
            <?php $s = $ayarlar['style_table']; ?>
            #print-table {
                top: <?php echo $s['top']; ?>px; left: <?php echo $s['left']; ?>px; width: <?php echo $s['width']; ?>px;
                font-family: <?php echo $s['font']; ?>; font-size: <?php echo $s['size']; ?>px; color: <?php echo $s['color']; ?>;
            }
            .print-table { width: 100%; border-collapse: collapse; font-size: inherit; color: inherit; }
            .print-table th, .print-table td { border: 1px solid #000; padding: 5px; text-align: left; }
            .print-table th { background-color: #f0f0f0 !important; font-weight: 800; -webkit-print-color-adjust: exact; }

            /* 5. İMZA */
            <?php $s = $ayarlar['style_footer']; ?>
            #print-footer {
                top: <?php echo $s['top']; ?>px; left: <?php echo $s['left']; ?>px; width: <?php echo $s['width']; ?>px;
                font-family: <?php echo $s['font']; ?>; font-size: <?php echo $s['size']; ?>px; color: <?php echo $s['color']; ?>;
                display: <?php echo ($ayarlar['imza_alani'] == 1) ? 'block' : 'none'; ?> !important; 
            }
            .sign-wrapper { display: flex; justify-content: space-between; width: 100%; }
            .sign-box { text-align: center; width: 45%; }
            .sign-line { border-top: 1px solid <?php echo $s['color']; ?>; margin-top: 30px; }
        }
    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-content">
        
        <div class="page-header-row">
            <h2><i class="fas fa-gavel"></i> Hacizli Araç Analiz Raporları</h2>
            <div class="header-actions">
                <button class="btn" onclick="window.print()"><i class="fas fa-print"></i> Yazdır</button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-info"><h4>Otoparktaki Hacizli</h4><span><?php echo $cnt_park; ?></span></div><div class="stat-icon bg-orange"><i class="fas fa-lock"></i></div></div>
            <div class="stat-card"><div class="stat-info"><h4>Teslim Edilen</h4><span><?php echo $cnt_exit; ?></span></div><div class="stat-icon bg-green"><i class="fas fa-check-circle"></i></div></div>
            <div class="stat-card"><div class="stat-info"><h4>Yakalama Hacizli</h4><span><?php echo $cnt_yakalama; ?></span></div><div class="stat-icon bg-red"><i class="fas fa-exclamation-triangle"></i></div></div>
        </div>

        <div class="filter-card">
            <form method="GET" action="" class="filter-form">
                <div class="filter-grid" style="grid-template-columns: repeat(5, 1fr);">
                    <div class="filter-item"><label>Plaka</label><input type="text" name="plaka" class="force-uppercase" value="<?php echo $_GET['plaka'] ?? ''; ?>"></div>
                    <div class="filter-item"><label>Haciz Eden Kurum</label><input type="text" name="haciz_eden" class="force-uppercase" value="<?php echo $_GET['haciz_eden'] ?? ''; ?>"></div>
                    <div class="filter-item"><label>Dosya No</label><input type="text" name="dosya_no" class="force-uppercase" value="<?php echo $_GET['dosya_no'] ?? ''; ?>"></div>
                    <div class="filter-item"><label>Durum</label><select name="durum"><option value="">Tümü</option><option value="Otoparkta" <?php if(($_GET['durum']??'')=='Otoparkta') echo 'selected'; ?>>Otoparkta</option><option value="CikisYapildi" <?php if(($_GET['durum']??'')=='CikisYapildi') echo 'selected'; ?>>Çıkış Yapıldı</option></select></div>
                    <div class="filter-item"><label>Başlangıç Tarihi</label><input type="date" name="baslangic" value="<?php echo $_GET['baslangic'] ?? ''; ?>"></div>
                </div>
                <div class="filter-buttons"><button type="submit" class="btn"><i class="fas fa-filter"></i> Raporu Getir</button><a href="hacizli_arac_analiz_raporu.php" class="btn btn-secondary">Sıfırla</a></div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="report-table">
                <thead><tr><th>Sıra</th><th>Giriş Tarihi</th><th>Plaka</th><th>Cinsi</th><th>Marka / Model</th><th style="background-color: #fff3bf;">Haciz Eden</th><th style="background-color: #fff3bf;">Dosya No</th><th>Durumu</th><th>Gün</th></tr></thead>
                <tbody>
                    <?php
                    if (!empty($kayitlar)) {
                        $sira = 1;
                        foreach($kayitlar as $row) {
                            $giris = new DateTime($row['giris_tarihi']); $bugun = new DateTime(); $gunFarki = $giris->diff($bugun)->days;
                            $rowClass = ($row['durumu'] == 'Otoparkta') ? 'row-otoparkta' : 'row-cikis';
                            $durumText = ($row['durumu'] == 'Otoparkta') ? '<span style="color:#28a745; font-weight:bold;">OTOPARKTA</span>' : '<span style="color:#dc3545; font-weight:bold;">ÇIKIŞ</span>';
                            echo "<tr class='$rowClass'>";
                            echo "<td>" . $sira++ . "</td>";
                            echo "<td>" . date("d.m.Y", strtotime($row['giris_tarihi'])) . "</td>";
                            echo "<td><strong>" . htmlspecialchars($row['plaka']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['cinsi']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['marka']) . " " . htmlspecialchars($row['model']) . "</td>";
                            echo "<td style='font-weight:600; color:#f08c00;'>" . htmlspecialchars($row['haciz_eden']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['dosya_no']) . "</td>";
                            echo "<td>" . $durumText . "</td>";
                            echo "<td><strong>" . $gunFarki . " Gün</strong></td>";
                            echo "</tr>";
                        }
                    } else { echo "<tr><td colspan='9' style='text-align:center; padding: 30px; color: #999;'>Kriterlere uygun hacizli araç bulunamadı.</td></tr>"; }
                    ?>
                </tbody>
            </table>
        </div>

        <div id="printable-area">
            
            <?php if(!empty($ayarlar['logo_url']) && !empty($ayarlar['goster_logo'])): ?>
            <div id="print-logo" class="print-el">
                <img src="<?php echo $ayarlar['logo_url']; ?>">
            </div>
            <?php endif; ?>

            <div id="print-baslik" class="print-el">
                <h1><?php echo htmlspecialchars($ayarlar['baslik']); ?></h1>
            </div>

            <div id="print-alt-baslik" class="print-el">
                <p><?php echo htmlspecialchars($ayarlar['alt_baslik']); ?></p>
            </div>

            <div id="print-table" class="print-el">
                <table class="print-table">
                    <thead>
                        <tr>
                            <?php if($ayarlar['goster_sira']): ?><th>SIRA</th><?php endif; ?>
                            <?php if($ayarlar['goster_giris']): ?><th>GİRİŞ TAR.</th><?php endif; ?>
                            <?php if($ayarlar['goster_plaka']): ?><th>PLAKA</th><?php endif; ?>
                            <?php if($ayarlar['goster_cinsi']): ?><th>CİNSİ</th><?php endif; ?>
                            <?php if($ayarlar['goster_marka']): ?><th>MARKA/MODEL</th><?php endif; ?>
                            <?php if($ayarlar['goster_haciz']): ?><th>HACİZ EDEN</th><?php endif; ?>
                            <?php if($ayarlar['goster_dosya']): ?><th>DOSYA NO</th><?php endif; ?>
                            <?php if($ayarlar['goster_durum']): ?><th>DURUM</th><?php endif; ?>
                            <?php if($ayarlar['goster_gun']): ?><th>GÜN</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sira = 1;
                        foreach($kayitlar as $row): 
                            $giris = new DateTime($row['giris_tarihi']); $bugun = new DateTime(); $gunFarki = $giris->diff($bugun)->days;
                        ?>
                            <tr>
                                <?php if($ayarlar['goster_sira']): ?><td><?php echo $sira++; ?></td><?php endif; ?>
                                <?php if($ayarlar['goster_giris']): ?><td><?php echo date("d.m.Y", strtotime($row['giris_tarihi'])); ?></td><?php endif; ?>
                                <?php if($ayarlar['goster_plaka']): ?><td><?php echo htmlspecialchars($row['plaka']); ?></td><?php endif; ?>
                                <?php if($ayarlar['goster_cinsi']): ?><td><?php echo htmlspecialchars($row['cinsi']); ?></td><?php endif; ?>
                                <?php if($ayarlar['goster_marka']): ?><td><?php echo htmlspecialchars($row['marka'] . ' ' . $row['model']); ?></td><?php endif; ?>
                                <?php if($ayarlar['goster_haciz']): ?><td><?php echo htmlspecialchars($row['haciz_eden']); ?></td><?php endif; ?>
                                <?php if($ayarlar['goster_dosya']): ?><td><?php echo htmlspecialchars($row['dosya_no']); ?></td><?php endif; ?>
                                <?php if($ayarlar['goster_durum']): ?><td><?php echo ($row['durumu']=='Otoparkta')?'OTOPARKTA':'ÇIKIŞ'; ?></td><?php endif; ?>
                                <?php if($ayarlar['goster_gun']): ?><td><?php echo $gunFarki . ' Gün'; ?></td><?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="print-footer" class="print-el">
                <div class="sign-wrapper">
                    <div class="sign-box"><strong>TESLİM EDEN</strong><div class="sign-line"></div></div>
                    <div class="sign-box"><strong>TESLİM ALAN</strong><div class="sign-line"></div></div>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.querySelectorAll('.force-uppercase').forEach(function(input) {
            input.addEventListener('input', function() {
                var start = this.selectionStart; var end = this.selectionEnd;
                this.value = this.value.toUpperCase(); this.setSelectionRange(start, end);
            });
        });
    </script>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>