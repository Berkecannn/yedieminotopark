<?php
// Oturumu başlat
session_start();

// --- HEADER VE MENÜ AYARLARI ---
$yol = "../";
$menu = "gider";

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

// --- 1. RAPOR AYARLARINI ÇEK ---
$varsayilan = [
    'baslik' => 'GİDER ANALİZ RAPORU', 'alt_baslik' => $firma_adi, // GÜNCELLENDİ: Değişken atandı
    'logo_url' => '',
    'goster_logo' => 1, // Logo görünürlük varsayılan açık
    'goster_tarih' => 1, 'goster_gider_adi' => 1, 'goster_gider_turu' => 1,
    'goster_odeme_sekli' => 1, 'goster_aciklama' => 1, 'goster_durum' => 1, 'goster_tutar' => 1, 'imza_alani' => 1,
    // Varsayılan Stil Değerleri
    'style_baslik' => ['font'=>'Arial', 'size'=>24, 'color'=>'#000', 'top'=>30, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_alt_baslik' => ['font'=>'Arial', 'size'=>14, 'color'=>'#555', 'top'=>70, 'left'=>150, 'width'=>400, 'height'=>'auto'],
    'style_table' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>150, 'left'=>20, 'width'=>750, 'height'=>'auto'],
    'style_footer' => ['font'=>'Arial', 'size'=>12, 'color'=>'#000', 'top'=>800, 'left'=>20, 'width'=>750, 'height'=>'auto'],
    'style_logo' => ['top'=>20, 'left'=>20, 'width'=>100, 'height'=>'auto']
];

$sql_ayar = "SELECT ayarlar FROM rapor_ayarlari WHERE rapor_adi = 'giderler_raporu'";
$res_ayar = $conn->query($sql_ayar);
// array_replace_recursive kullanarak iç içe ayarları birleştiriyoruz
$ayarlar = ($res_ayar->num_rows > 0) ? array_replace_recursive($varsayilan, json_decode($res_ayar->fetch_assoc()['ayarlar'], true)) : $varsayilan;

// *** GÜNCEL FİRMA ADINI ZORLA (Tasarım dosyasıyla eşleşmesi için) ***
$ayarlar['alt_baslik'] = $firma_adi;

// --- FİLTRELEME MANTIĞI (ORİJİNAL KODLARINIZ) ---
$where = "WHERE 1=1";
$params = [];
$types = "";

// 1. Tarih Türü Seçimi (Kayıt Tarihi mi, Tahsil Tarihi mi?)
$tarih_sutunu = (isset($_GET['tarih_turu']) && $_GET['tarih_turu'] == 'kayit_tarihi') ? 'kayit_tarihi' : 'odeme_tarihi';

// 2. Tarih Aralığı
if (!empty($_GET['baslangic']) && !empty($_GET['bitis'])) {
    $where .= " AND $tarih_sutunu BETWEEN ? AND ?";
    $params[] = $_GET['baslangic'];
    $params[] = $_GET['bitis'];
    $types .= "ss";
}

// 3. Gider Adı
if (!empty($_GET['gider_adi'])) {
    $where .= " AND gider_adi LIKE ?";
    $params[] = "%" . $_GET['gider_adi'] . "%";
    $types .= "s";
}

// 4. Ödeme Şekli
if (!empty($_GET['odeme_sekli'])) {
    $where .= " AND odeme_sekli = ?";
    $params[] = $_GET['odeme_sekli'];
    $types .= "s";
}

// 5. Ödeme Durumu
if (isset($_GET['durum']) && $_GET['durum'] !== '') {
    $where .= " AND odeme_durumu = ?";
    $params[] = $_GET['durum'];
    $types .= "i";
}

// --- SORGULARI ÇALIŞTIR ---

// Liste Sorgusu
$sql = "SELECT * FROM giderler $where ORDER BY $tarih_sutunu DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// --- İSTATİSTİK HESAPLAMA ---
$toplam_gider = 0;
$odenen_toplam = 0;
$bekleyen_borc = 0;

// Verileri diziye alalım
$kayitlar = [];
while ($row = $result->fetch_assoc()) {
    $kayitlar[] = $row;
    
    // İstatistikler
    if ($row['odeme_durumu'] == 1) {
        $odenen_toplam += $row['gider_tutari'];
    } else {
        $bekleyen_borc += $row['gider_tutari'];
    }
    $toplam_gider += $row['gider_tutari'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Gider Raporları - Yediemin Otopark</title>
    <link rel="stylesheet" href="<?php echo $yol; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&family=Open+Sans&family=Lato&family=Montserrat&family=Courier+Prime&display=swap" rel="stylesheet">
    <style>
        /* İstatistik Kartları */
        .stats-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;
        }
        .stat-card {
            background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;
        }
        .stat-info h4 { margin: 0 0 5px 0; font-size: 13px; color: #6c757d; text-transform: uppercase; }
        .stat-info span { font-size: 22px; font-weight: 700; color: #343a40; }
        .stat-icon {
            width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;
        }
        .bg-green { background-color: #e6f7ec; color: #28a745; }
        .bg-red { background-color: #fdecea; color: #dc3545; }
        .bg-orange { background-color: #fff3bf; color: #f08c00; }

        /* Tablo Özelleştirme */
        .report-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .report-table th { background-color: #f8f9fa; color: #495057; font-weight: 700; text-transform: uppercase; padding: 10px; border-bottom: 2px solid #dee2e6; text-align: left; }
        .report-table td { padding: 8px 10px; border-bottom: 1px solid #eee; color: #333; }
        .report-table tr:hover { background-color: #f1f3f5; }
        
        /* Durum Renkleri */
        .text-success { color: #28a745; font-weight: bold; }
        .text-danger { color: #dc3545; font-weight: bold; }
        
        /* --- YAZDIRMA ALANI (GİZLİ) --- */
        #printable-area { display: none; }

        /* --- YAZDIRMA MODU (ÖZEL TASARIM) --- */
        @media print {
            @page { margin: 0; size: A4; }
            
            /* Ekrandaki her şeyi gizle */
            body * { visibility: hidden; }
            .main-header, .stats-grid, .filter-card, .table-responsive, .page-header-row, .no-print { display: none !important; }

            /* Sadece bizim özel alanımızı göster */
            #printable-area, #printable-area * { visibility: visible; }
            
            #printable-area { 
                display: block !important; 
                position: absolute; left: 0; top: 0; 
                width: 210mm; height: 297mm;
                background-color: white;
            }

            /* --- POZİSYONLAMA SINIFI (Absolute) --- */
            .print-el { position: absolute; overflow: hidden; }

            /* 1. LOGO AYARLARI */
            <?php $s = $ayarlar['style_logo']; ?>
            #print-logo {
                top: <?php echo $s['top']; ?>px; left: <?php echo $s['left']; ?>px;
                width: <?php echo $s['width']; ?>px; height: <?php echo $s['height']!='auto' ? $s['height'].'px' : 'auto'; ?>;
            }
            #print-logo img { width: 100%; height: 100%; object-fit: contain; }

            /* 2. BAŞLIK AYARLARI */
            <?php $s = $ayarlar['style_baslik']; ?>
            #print-baslik {
                top: <?php echo $s['top']; ?>px; left: <?php echo $s['left']; ?>px; width: <?php echo $s['width']; ?>px;
                text-align: center;
                font-family: <?php echo $s['font']; ?>; font-size: <?php echo $s['size']; ?>px; color: <?php echo $s['color']; ?>;
            }
            #print-baslik h1 { margin: 0; font-weight: 800; text-transform: uppercase; font-size: inherit; color: inherit; }

            /* 3. ALT BAŞLIK AYARLARI */
            <?php $s = $ayarlar['style_alt_baslik']; ?>
            #print-alt-baslik {
                top: <?php echo $s['top']; ?>px; left: <?php echo $s['left']; ?>px; width: <?php echo $s['width']; ?>px;
                text-align: center;
                font-family: <?php echo $s['font']; ?>; font-size: <?php echo $s['size']; ?>px; color: <?php echo $s['color']; ?>;
            }
            #print-alt-baslik p { margin: 5px 0 0 0; font-weight: 600; text-transform: uppercase; font-size: inherit; color: inherit; }

            /* 4. TABLO AYARLARI */
            <?php $s = $ayarlar['style_table']; ?>
            #print-table {
                top: <?php echo $s['top']; ?>px; left: <?php echo $s['left']; ?>px; width: <?php echo $s['width']; ?>px;
                font-family: <?php echo $s['font']; ?>; font-size: <?php echo $s['size']; ?>px; color: <?php echo $s['color']; ?>;
            }
            .print-table { width: 100%; border-collapse: collapse; font-size: inherit; color: inherit; }
            .print-table th, .print-table td { border: 1px solid #000; padding: 5px; text-align: left; }
            .print-table th { background-color: #f0f0f0 !important; font-weight: 800; -webkit-print-color-adjust: exact; }

            /* 5. İMZA AYARLARI */
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
        
        <div class="page-header-row no-print">
            <h2><i class="fas fa-chart-bar"></i> Gider Raporları ve Yazdırma</h2>
            <div class="header-actions">
                <button class="btn" onclick="window.print()"><i class="fas fa-print"></i> Yazdır</button>
            </div>
        </div>

        <div class="stats-grid no-print">
            <div class="stat-card">
                <div class="stat-info"><h4>Toplam Gider</h4><span><?php echo number_format($toplam_gider, 2, ',', '.'); ?> ₺</span></div>
                <div class="stat-icon bg-red"><i class="fas fa-wallet"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><h4>Ödenen Tutar</h4><span><?php echo number_format($odenen_toplam, 2, ',', '.'); ?> ₺</span></div>
                <div class="stat-icon bg-green"><i class="fas fa-check-double"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><h4>Bekleyen Borç</h4><span><?php echo number_format($bekleyen_borc, 2, ',', '.'); ?> ₺</span></div>
                <div class="stat-icon bg-orange"><i class="fas fa-clock"></i></div>
            </div>
        </div>

        <div class="filter-card no-print">
            <form method="GET" action="" class="filter-form">
                <div class="filter-grid" style="grid-template-columns: repeat(6, 1fr);">
                    
                    <div class="filter-item">
                        <label>Tarih Türü</label>
                        <select name="tarih_turu">
                            <option value="odeme_tarihi" <?php if($tarih_sutunu=='odeme_tarihi') echo 'selected'; ?>>Ödeme Tarihi</option>
                            <option value="kayit_tarihi" <?php if($tarih_sutunu=='kayit_tarihi') echo 'selected'; ?>>Kayıt Tarihi</option>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>Başlangıç</label>
                        <input type="date" name="baslangic" value="<?php echo $_GET['baslangic'] ?? ''; ?>">
                    </div>
                    <div class="filter-item">
                        <label>Bitiş</label>
                        <input type="date" name="bitis" value="<?php echo $_GET['bitis'] ?? ''; ?>">
                    </div>
                    
                    <div class="filter-item">
                        <label>Gider Adı</label>
                        <input type="text" name="gider_adi" class="force-uppercase" value="<?php echo $_GET['gider_adi'] ?? ''; ?>">
                    </div>
                    
                    <div class="filter-item">
                        <label>Ödeme Şekli</label>
                        <select name="odeme_sekli">
                            <option value="">Tümü</option>
                            <option value="Nakit" <?php if(($_GET['odeme_sekli']??'')=='Nakit') echo 'selected'; ?>>Nakit</option>
                            <option value="Kredi Kartı" <?php if(($_GET['odeme_sekli']??'')=='Kredi Kartı') echo 'selected'; ?>>Kredi Kartı</option>
                            <option value="Havale/EFT" <?php if(($_GET['odeme_sekli']??'')=='Havale/EFT') echo 'selected'; ?>>Havale/EFT</option>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>Durum</label>
                        <select name="durum">
                            <option value="">Tümü</option>
                            <option value="1" <?php if(isset($_GET['durum']) && $_GET['durum']=='1') echo 'selected'; ?>>Ödendi</option>
                            <option value="0" <?php if(isset($_GET['durum']) && $_GET['durum']=='0') echo 'selected'; ?>>Bekliyor</option>
                        </select>
                    </div>

                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn"><i class="fas fa-filter"></i> Raporu Getir</button>
                    <a href="gider_raporu_yazdir.php" class="btn btn-secondary">Sıfırla</a>
                </div>
            </form>
        </div>

        <div class="table-responsive no-print">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Kayıt Tar.</th>
                        <th>Ödeme Tar.</th>
                        <th>Gider Adı</th>
                        <th>Gider Türü</th>
                        <th>Ödeme Şekli</th>
                        <th>Durum</th>
                        <th>Açıklama</th>
                        <th style="text-align:right;">Tutar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($kayitlar)) {
                        foreach ($kayitlar as $row) {
                            $durum_text = ($row['odeme_durumu'] == 1) ? '<span class="text-success">ÖDENDİ</span>' : '<span class="text-danger">BEKLİYOR</span>';
                            echo "<tr>";
                            echo "<td>" . date("d.m.Y", strtotime($row['kayit_tarihi'])) . "</td>";
                            echo "<td>" . date("d.m.Y", strtotime($row['odeme_tarihi'])) . "</td>";
                            echo "<td>" . htmlspecialchars($row['gider_adi']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['gider_turu']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['odeme_sekli']) . "</td>";
                            echo "<td>" . $durum_text . "</td>";
                            echo "<td>" . htmlspecialchars($row['aciklama']) . "</td>";
                            echo "<td style='text-align:right; font-weight:bold;'>" . number_format($row['gider_tutari'], 2, ',', '.') . " ₺</td>";
                            echo "</tr>";
                        }
                        echo "<tr style='background-color:#fdecea; font-weight:bold;'>";
                        echo "<td colspan='7' style='text-align:right;'>GENEL TOPLAM GİDER:</td>";
                        echo "<td style='text-align:right; color:#721c24;'>" . number_format($toplam_gider, 2, ',', '.') . " ₺</td>";
                        echo "</tr>";
                    } else {
                        echo "<tr><td colspan='8' style='text-align:center; padding: 20px; color: #999;'>Kriterlere uygun gider kaydı bulunamadı.</td></tr>";
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
                            <?php if($ayarlar['goster_gider_adi']): ?><th>GİDER ADI</th><?php endif; ?>
                            <?php if($ayarlar['goster_gider_turu']): ?><th>TÜR</th><?php endif; ?>
                            <?php if($ayarlar['goster_odeme_sekli']): ?><th>ÖDEME ŞEKLİ</th><?php endif; ?>
                            <?php if($ayarlar['goster_durum']): ?><th>DURUM</th><?php endif; ?>
                            <?php if($ayarlar['goster_aciklama']): ?><th>AÇIKLAMA</th><?php endif; ?>
                            <?php if($ayarlar['goster_tutar']): ?><th style="text-align:right;">TUTAR</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($kayitlar)): ?>
                            <?php foreach($kayitlar as $row): ?>
                                <tr>
                                    <?php if($ayarlar['goster_tarih']): ?><td><?php echo date("d.m.Y", strtotime($row[$tarih_sutunu])); ?></td><?php endif; ?>
                                    <?php if($ayarlar['goster_gider_adi']): ?><td><?php echo htmlspecialchars($row['gider_adi']); ?></td><?php endif; ?>
                                    <?php if($ayarlar['goster_gider_turu']): ?><td><?php echo htmlspecialchars($row['gider_turu']); ?></td><?php endif; ?>
                                    <?php if($ayarlar['goster_odeme_sekli']): ?><td><?php echo htmlspecialchars($row['odeme_sekli']); ?></td><?php endif; ?>
                                    <?php if($ayarlar['goster_durum']): ?><td><?php echo ($row['odeme_durumu'] == 1) ? 'ÖDENDİ' : 'BEKLİYOR'; ?></td><?php endif; ?>
                                    <?php if($ayarlar['goster_aciklama']): ?><td><?php echo htmlspecialchars($row['aciklama']); ?></td><?php endif; ?>
                                    <?php if($ayarlar['goster_tutar']): ?><td style="text-align:right;"><?php echo number_format($row['gider_tutari'], 2, ',', '.') . ' ₺'; ?></td><?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if($ayarlar['goster_tutar']): ?>
                                <?php 
                                    $colspan = 0;
                                    if($ayarlar['goster_tarih']) $colspan++;
                                    if($ayarlar['goster_gider_adi']) $colspan++;
                                    if($ayarlar['goster_gider_turu']) $colspan++;
                                    if($ayarlar['goster_odeme_sekli']) $colspan++;
                                    if($ayarlar['goster_durum']) $colspan++;
                                    if($ayarlar['goster_aciklama']) $colspan++;
                                ?>
                                <tr style="font-weight:bold; background-color:#f0f0f0;">
                                    <td colspan="<?php echo $colspan; ?>" style="text-align:right;">GENEL TOPLAM:</td>
                                    <td style="text-align:right;"><?php echo number_format($toplam_gider, 2, ',', '.') . ' ₺'; ?></td>
                                </tr>
                            <?php endif; ?>

                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;">Kayıt bulunamadı.</td></tr>
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