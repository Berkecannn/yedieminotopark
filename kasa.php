<?php
// Oturumu başlat
session_start();

// --- HEADER VE MENÜ AYARLARI ---
$yol = ""; 
$menu = "kasa";

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
// TASARIM AYARLARINI ÇEK
// ============================================================================
$varsayilan = [
    'baslik' => 'GENEL KASA RAPORU', 'alt_baslik' => $firma_adi, // GÜNCELLENDİ: Değişken atandı
    'logo_url' => '',
    'goster_logo'=>1, 'imza_alani'=>1,
    'goster_tarih'=>1, 'goster_tur'=>1, 'goster_aciklama'=>1, 'goster_sekil'=>1, 'goster_giris'=>1, 'goster_cikis'=>1,
    
    'style_logo' => ['top'=>20, 'left'=>20, 'width'=>100, 'height'=>'auto'],
    'style_baslik' => ['font'=>'Arial', 'size'=>24, 'color'=>'#000', 'top'=>30, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_alt_baslik' => ['font'=>'Arial', 'size'=>14, 'color'=>'#555', 'top'=>70, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_table' => ['font'=>'Arial', 'size'=>11, 'color'=>'#000', 'top'=>150, 'left'=>20, 'width'=>750, 'height'=>'auto'],
    'style_footer' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>800, 'left'=>20, 'width'=>750, 'height'=>'auto']
];

$sql_ayar = "SELECT ayarlar FROM rapor_ayarlari WHERE rapor_adi = 'kasa_raporu'";
$res_ayar = $conn->query($sql_ayar);
$ayarlar = ($res_ayar->num_rows > 0) ? array_replace_recursive($varsayilan, json_decode($res_ayar->fetch_assoc()['ayarlar'], true)) : $varsayilan;

// *** GÜNCEL FİRMA ADINI ZORLA (Tasarım dosyasıyla eşleşmesi için) ***
$ayarlar['alt_baslik'] = $firma_adi;
// ============================================================================


// --- TARİH VE FİLTRE AYARLARI ---
$baslangic = isset($_GET['baslangic']) ? $_GET['baslangic'] : date('Y-m-01');
$bitis = isset($_GET['bitis']) ? $_GET['bitis'] : date('Y-m-d');

// --- SQL UNION SORGUSU (ORİJİNAL) ---
$sql = "
    SELECT 
        'GELİR' as tur, 
        tahsil_tarihi as tarih, 
        gelir_adi COLLATE utf8mb4_general_ci as aciklama, 
        odeme_sekli COLLATE utf8mb4_general_ci as sekil, 
        gelir_tutari as giris, 
        0 as cikis
    FROM gelirler WHERE tahsilat_durumu = 1 AND (tahsil_tarihi BETWEEN ? AND ?)
    
    UNION ALL
    
    SELECT 
        'GİDER' as tur, 
        odeme_tarihi as tarih, 
        gider_adi COLLATE utf8mb4_general_ci as aciklama, 
        odeme_sekli COLLATE utf8mb4_general_ci as sekil, 
        0 as giris, 
        gider_tutari as cikis
    FROM giderler WHERE odeme_durumu = 1 AND (odeme_tarihi BETWEEN ? AND ?)
    
    UNION ALL
    
    SELECT 
        'PERSONEL' as tur, 
        islem_tarihi as tarih, 
        CONCAT('Personel: ', aciklama) COLLATE utf8mb4_general_ci as aciklama, 
        odeme_sekli COLLATE utf8mb4_general_ci as sekil, 
        0 as giris, 
        tutar as cikis
    FROM personel_hareketleri WHERE (islem_turu = 'Odeme' OR islem_turu = 'Avans') AND (islem_tarihi BETWEEN ? AND ?)

    UNION ALL

    SELECT 
        'MAKBUZ' as tur, 
        tarih as tarih, 
        CONCAT('Makbuz No:', id, ' - ', sayin, ' (', aciklama, ')') COLLATE utf8mb4_general_ci as aciklama, 
        'Nakit' COLLATE utf8mb4_general_ci as sekil, 
        tutar as giris, 
        0 as cikis
    FROM makbuzlar WHERE (tarih BETWEEN ? AND ?)

    ORDER BY tarih DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssss", $baslangic, $bitis, $baslangic, $bitis, $baslangic, $bitis, $baslangic, $bitis);
$stmt->execute();
$result = $stmt->get_result();

$toplam_giris = 0;
$toplam_cikis = 0;
$hareketler = [];

while($row = $result->fetch_assoc()) {
    $hareketler[] = $row;
    $toplam_giris += $row['giris'];
    $toplam_cikis += $row['cikis'];
}
$net_bakiye = $toplam_giris - $toplam_cikis;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kasa İşlemleri - Yediemin Otopark</title>
    <link rel="stylesheet" href="<?php echo $yol; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&family=Open+Sans&family=Lato&family=Montserrat&family=Courier+Prime&display=swap" rel="stylesheet">
    <style>
        /* ORİJİNAL CSS (KORUNDU) */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card {
            background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;
            position: relative; overflow: hidden; -webkit-print-color-adjust: exact;
        }
        .stat-card::after { content: ''; position: absolute; right: 0; top: 0; bottom: 0; width: 4px; }
        .stat-card.income::after { background-color: #28a745; }
        .stat-card.expense::after { background-color: #dc3545; }
        .stat-card.balance::after { background-color: #007bff; }

        .stat-info h4 { margin: 0 0 5px 0; font-size: 13px; color: #6c757d; text-transform: uppercase; }
        .stat-info span { font-size: 26px; font-weight: 800; color: #343a40; }
        .stat-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        
        .bg-green-light { background-color: #d4edda; color: #155724; }
        .bg-red-light { background-color: #f8d7da; color: #721c24; }
        .bg-blue-light { background-color: #cce5ff; color: #004085; }

        .text-success { color: #28a745 !important; font-weight: bold; }
        .text-danger { color: #dc3545 !important; font-weight: bold; }
        
        .row-gelir { border-left: 4px solid #28a745; }
        .row-gider { border-left: 4px solid #dc3545; }
        .row-personel { border-left: 4px solid #ffc107; }

        #printable-area { display: none; }

        /* YAZDIRMA MODU (YENİ) */
        @media print {
            @page { margin: 0; size: A4; }
            body * { visibility: hidden; }
            .main-header, .page-header-row, .stats-grid, .filter-card, .table-responsive, .header-actions { display: none !important; }
            
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

    <?php include 'header.php'; ?>

    <div class="main-content">
        
        <div class="page-header-row">
            <h2><i class="fas fa-cash-register"></i> Kasa Hareketleri ve Durum</h2>
            <div class="header-actions no-print">
                <button class="btn" onclick="window.print()"><i class="fas fa-print"></i> Yazdır</button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card income">
                <div class="stat-info"><h4>Toplam Giriş</h4><span style="color:#28a745"><?php echo number_format($toplam_giris, 2, ',', '.'); ?> ₺</span></div>
                <div class="stat-icon bg-green-light"><i class="fas fa-arrow-down"></i></div>
            </div>
            <div class="stat-card expense">
                <div class="stat-info"><h4>Toplam Çıkış</h4><span style="color:#dc3545"><?php echo number_format($toplam_cikis, 2, ',', '.'); ?> ₺</span></div>
                <div class="stat-icon bg-red-light"><i class="fas fa-arrow-up"></i></div>
            </div>
            <div class="stat-card balance">
                <div class="stat-info"><h4>Net Bakiye</h4><span style="color:#007bff"><?php echo number_format($net_bakiye, 2, ',', '.'); ?> ₺</span></div>
                <div class="stat-icon bg-blue-light"><i class="fas fa-wallet"></i></div>
            </div>
        </div>

        <div class="filter-card no-print">
            <form method="GET" action="" class="filter-form">
                <div class="filter-grid" style="grid-template-columns: 1fr 1fr 100px;">
                    <div class="filter-item">
                        <label>Başlangıç Tarihi</label>
                        <input type="date" name="baslangic" value="<?php echo $baslangic; ?>">
                    </div>
                    <div class="filter-item">
                        <label>Bitiş Tarihi</label>
                        <input type="date" name="bitis" value="<?php echo $bitis; ?>">
                    </div>
                    <div class="filter-buttons" style="border:none; padding:0; display:flex; align-items:flex-end;">
                        <button type="submit" class="btn" style="width:100%; height:35px;"><i class="fas fa-search"></i> Ara</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive no-print">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>İşlem Türü</th>
                        <th>Açıklama</th>
                        <th>Ödeme Şekli</th>
                        <th style="text-align:right;">Giriş</th>
                        <th style="text-align:right;">Çıkış</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($hareketler)) {
                        foreach ($hareketler as $row) {
                            $rowClass = '';
                            if($row['tur'] == 'GELİR' || $row['tur'] == 'MAKBUZ') $rowClass = 'row-gelir';
                            elseif($row['tur'] == 'GİDER') $rowClass = 'row-gider';
                            else $rowClass = 'row-personel';

                            echo "<tr class='$rowClass'>";
                            echo "<td>" . date("d.m.Y", strtotime($row['tarih'])) . "</td>";
                            echo "<td><strong>" . htmlspecialchars($row['tur']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['aciklama']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['sekil']) . "</td>";
                            
                            echo "<td style='text-align:right;' class='" . ($row['giris'] > 0 ? 'text-success' : '') . "'>" . 
                                 ($row['giris'] > 0 ? number_format($row['giris'], 2, ',', '.') . ' ₺' : '-') . "</td>";
                            
                            echo "<td style='text-align:right;' class='" . ($row['cikis'] > 0 ? 'text-danger' : '') . "'>" . 
                                 ($row['cikis'] > 0 ? number_format($row['cikis'], 2, ',', '.') . ' ₺' : '-') . "</td>";
                            echo "</tr>";
                        }
                        
                        // DİP TOPLAM SATIRI
                        echo "<tr style='background-color:#f1f3f5; color:#333; font-weight:bold; -webkit-print-color-adjust: exact;'>";
                        echo "<td colspan='4' style='text-align:right;'>GENEL TOPLAM:</td>";
                        echo "<td style='text-align:right; color:#28a745;'>" . number_format($toplam_giris, 2, ',', '.') . " ₺</td>";
                        echo "<td style='text-align:right; color:#dc3545;'>" . number_format($toplam_cikis, 2, ',', '.') . " ₺</td>";
                        echo "</tr>";

                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding: 20px; color: #777;'>Bu tarih aralığında kasa hareketi bulunamadı.</td></tr>";
                    }
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
                            <?php if($ayarlar['goster_tarih']): ?><th>TARİH</th><?php endif; ?>
                            <?php if($ayarlar['goster_tur']): ?><th>TÜR</th><?php endif; ?>
                            <?php if($ayarlar['goster_aciklama']): ?><th>AÇIKLAMA</th><?php endif; ?>
                            <?php if($ayarlar['goster_sekil']): ?><th>ÖDEME</th><?php endif; ?>
                            <?php if($ayarlar['goster_giris']): ?><th style="text-align:right; color:green;">GİRİŞ</th><?php endif; ?>
                            <?php if($ayarlar['goster_cikis']): ?><th style="text-align:right; color:red;">ÇIKIŞ</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($hareketler)): ?>
                            <?php foreach($hareketler as $row): ?>
                                <tr>
                                    <?php if($ayarlar['goster_tarih']): ?><td><?php echo date("d.m.Y", strtotime($row['tarih'])); ?></td><?php endif; ?>
                                    <?php if($ayarlar['goster_tur']): ?><td><?php echo htmlspecialchars($row['tur']); ?></td><?php endif; ?>
                                    <?php if($ayarlar['goster_aciklama']): ?><td><?php echo htmlspecialchars($row['aciklama']); ?></td><?php endif; ?>
                                    <?php if($ayarlar['goster_sekil']): ?><td><?php echo htmlspecialchars($row['sekil']); ?></td><?php endif; ?>
                                    <?php if($ayarlar['goster_giris']): ?><td style="text-align:right;"><?php echo ($row['giris']>0)?number_format($row['giris'], 2, ',', '.').' ₺':'-'; ?></td><?php endif; ?>
                                    <?php if($ayarlar['goster_cikis']): ?><td style="text-align:right;"><?php echo ($row['cikis']>0)?number_format($row['cikis'], 2, ',', '.').' ₺':'-'; ?></td><?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            
                            <tr style="font-weight:bold; background-color:#f0f0f0;">
                                <?php 
                                    $colspan = 0;
                                    if($ayarlar['goster_tarih']) $colspan++;
                                    if($ayarlar['goster_tur']) $colspan++;
                                    if($ayarlar['goster_aciklama']) $colspan++;
                                    if($ayarlar['goster_sekil']) $colspan++;
                                ?>
                                <td colspan="<?php echo $colspan; ?>" style="text-align:right;">GENEL TOPLAM:</td>
                                <?php if($ayarlar['goster_giris']): ?><td style="text-align:right; color:green;"><?php echo number_format($toplam_giris, 2, ',', '.') . ' ₺'; ?></td><?php endif; ?>
                                <?php if($ayarlar['goster_cikis']): ?><td style="text-align:right; color:red;"><?php echo number_format($toplam_cikis, 2, ',', '.') . ' ₺'; ?></td><?php endif; ?>
                            </tr>

                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;">Kayıt bulunamadı.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="print-footer" class="print-el">
                <div class="sign-wrapper">
                    <div class="sign-box"><strong>MUHASEBE</strong><div class="sign-line"></div></div>
                    <div class="sign-box"><strong>ONAYLAYAN</strong><div class="sign-line"></div></div>
                </div>
            </div>

        </div>

    </div>

</body>
</html>