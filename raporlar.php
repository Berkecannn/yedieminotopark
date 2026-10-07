<?php
// Oturumu başlat
session_start();

// --- HEADER VE MENÜ AYARLARI ---
$yol = ""; // Ana dizinde olduğu için boş
$menu = "rapor";

require_once 'db_config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// --- TARİH AYARLARI ---
$bu_yil = date("Y");
$bu_ay_sayi = date("m");
$aylar = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
$bu_ay_yazi = $aylar[intval($bu_ay_sayi) - 1];

// --- SQL VERİ ÇEKME İŞLEMLERİ ---

// 1. TOPLAM ARAÇ DURUMU
$sql_toplam_giris = "SELECT COUNT(*) as sayi FROM araclar";
$sql_toplam_cikis = "SELECT COUNT(*) as sayi FROM araclar WHERE durumu = 'CikisYapildi'";
$total_giris = $conn->query($sql_toplam_giris)->fetch_assoc()['sayi'];
$total_cikis = $conn->query($sql_toplam_cikis)->fetch_assoc()['sayi'];

// 2. TOPLAM KASA DURUMU
$sql_gelir = "SELECT COALESCE(SUM(gelir_tutari),0) as toplam FROM gelirler WHERE tahsilat_durumu = 1";
$sql_gider = "SELECT COALESCE(SUM(gider_tutari),0) as toplam FROM giderler WHERE odeme_durumu = 1";
$total_gelir = $conn->query($sql_gelir)->fetch_assoc()['toplam'];
$total_gider = $conn->query($sql_gider)->fetch_assoc()['toplam'];

// 3. BU YIL AYLIK ARAÇ GİRİŞLERİ (12 Ay)
$aylik_arac_verileri = array_fill(0, 12, 0); // 12 ayı 0 ile doldur
$sql_aylik_arac = "SELECT MONTH(giris_tarihi) as ay, COUNT(*) as sayi FROM araclar WHERE YEAR(giris_tarihi) = '$bu_yil' GROUP BY MONTH(giris_tarihi)";
$res_aylik_arac = $conn->query($sql_aylik_arac);
while($row = $res_aylik_arac->fetch_assoc()) {
    $aylik_arac_verileri[$row['ay'] - 1] = $row['sayi'];
}

// 4. BU YIL AYLIK KASA HAREKETLERİ (Gelir)
$aylik_gelir_verileri = array_fill(0, 12, 0);
$sql_aylik_gelir = "SELECT MONTH(tahsil_tarihi) as ay, SUM(gelir_tutari) as toplam FROM gelirler WHERE YEAR(tahsil_tarihi) = '$bu_yil' AND tahsilat_durumu = 1 GROUP BY MONTH(tahsil_tarihi)";
$res_aylik_gelir = $conn->query($sql_aylik_gelir);
while($row = $res_aylik_gelir->fetch_assoc()) {
    $aylik_gelir_verileri[$row['ay'] - 1] = $row['toplam'];
}

// 5. BU AY GÜNLÜK ARAÇ GİRİŞİ
$gunluk_arac_verileri = [];
$gunler_etiket = [];
$gun_sayisi = date("t"); // Ayın kaç çektiği
for($i=1; $i<=$gun_sayisi; $i++) {
    $gunluk_arac_verileri[$i] = 0;
    $gunler_etiket[] = $i;
}
$sql_gunluk_arac = "SELECT DAY(giris_tarihi) as gun, COUNT(*) as sayi FROM araclar WHERE MONTH(giris_tarihi) = '$bu_ay_sayi' AND YEAR(giris_tarihi) = '$bu_yil' GROUP BY DAY(giris_tarihi)";
$res_gunluk_arac = $conn->query($sql_gunluk_arac);
while($row = $res_gunluk_arac->fetch_assoc()) {
    $gunluk_arac_verileri[$row['gun']] = $row['sayi'];
}

// 6. BU AY GÜNLÜK GELİR
$gunluk_gelir_verileri = array_fill(1, $gun_sayisi, 0);
$sql_gunluk_gelir = "SELECT DAY(tahsil_tarihi) as gun, SUM(gelir_tutari) as toplam FROM gelirler WHERE MONTH(tahsil_tarihi) = '$bu_ay_sayi' AND YEAR(tahsil_tarihi) = '$bu_yil' AND tahsilat_durumu = 1 GROUP BY DAY(tahsil_tarihi)";
$res_gunluk_gelir = $conn->query($sql_gunluk_gelir);
while($row = $res_gunluk_gelir->fetch_assoc()) {
    $gunluk_gelir_verileri[$row['gun']] = $row['toplam'];
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Grafiksel Raporlama - Yediemin Otopark</title>
    <link rel="stylesheet" href="<?php echo $yol; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* EKRAN İÇİN GRAFİK IZGARASI */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* 3 Sütun */
            gap: 20px;
            margin-top: 20px;
        }
        .chart-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            height: 350px; /* Ekranda rahat görünsün diye 350px */
            overflow: hidden; /* Taşmaları gizle */
        }
        .chart-header {
            font-size: 12px;
            font-weight: 700;
            color: var(--ana-mavi);
            text-transform: uppercase;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
            text-align: center;
            flex-shrink: 0; /* Başlık küçülmesin */
        }
        .chart-body {
            flex-grow: 1;
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 0; /* Flexbox içinde canvas'ın taşmasını engeller */
        }
        
        /* Responsive Ayarlar */
        @media (max-width: 1200px) { .charts-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .charts-grid { grid-template-columns: 1fr; } }

        /* --- YAZDIRMA AYARLARI (GÜNCELLENMİŞ) --- */
        @media print {
            @page {
                size: landscape;
                margin: 5mm; /* Kenar boşluklarını daralt */
            }

            body {
                background-color: white;
                margin: 0;
                padding: 0;
            }

            /* Diğer her şeyi gizle */
            body * {
                visibility: hidden;
            }

            /* Sadece Grid'i ve içeriğini göster */
            .charts-grid, .charts-grid * {
                visibility: visible !important;
            }

            .main-content {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .page-header-row, .header, .sidebar, .no-print {
                display: none !important;
            }

            /* IZGARA YAPISI */
            .charts-grid {
                position: absolute;
                top: 0;
                left: 0;
                width: 100% !important;
                height: 100% !important;
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important; /* 3 Sütun */
                grid-template-rows: repeat(2, 1fr) !important;    /* 2 Satır */
                gap: 15px !important; /* Grafikler arası boşluk */
                margin: 0 !important;
                padding: 0 !important;
            }

            /* KART AYARLARI */
            .chart-card {
                /* Sayfa yüksekliğinin yaklaşık %45'i olsun ki 2 satır sığsın */
                height: 45vh !important; 
                width: 100% !important;
                margin: 0 !important;
                padding: 5px !important;
                border: 1px solid #ccc !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
            }

            /* Grafik ve Başlık Ayarları */
            .chart-header {
                font-size: 10px !important;
                margin-bottom: 2px !important;
                padding-bottom: 2px !important;
                border-bottom: 1px solid #ddd !important;
                color: #000 !important;
            }

            .chart-body {
                flex-grow: 1 !important;
                height: auto !important;
                width: 100% !important;
            }
            
            /* Canvas boyutunu zorla */
            canvas {
                max-width: 100% !important;
                max-height: 100% !important;
            }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="main-content">
        <div class="page-header-row no-print">
            <h2><i class="fas fa-chart-area"></i> Grafiksel Analiz ve Raporlama</h2>
            <div class="header-actions">
                <button class="btn" onclick="window.print()"><i class="fas fa-print"></i> Yazdır</button>
            </div>
        </div>

        <div class="charts-grid">

            <div class="chart-card">
                <div class="chart-header">TOPLAM ARAÇ DURUMU</div>
                <div class="chart-body"><canvas id="chartTotalArac"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header"><?php echo $bu_ay_yazi; ?> AYI GÜNLÜK ARAÇ GİRİŞİ</div>
                <div class="chart-body"><canvas id="chartMonthArac"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header"><?php echo $bu_yil; ?> YILI AYLIK ARAÇ GİRİŞİ</div>
                <div class="chart-body"><canvas id="chartYearArac"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header">TOPLAM FİNANSAL DURUM</div>
                <div class="chart-body"><canvas id="chartTotalKasa"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header"><?php echo $bu_ay_yazi; ?> AYI GÜNLÜK GELİR</div>
                <div class="chart-body"><canvas id="chartMonthKasa"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header"><?php echo $bu_yil; ?> YILI AYLIK GELİR</div>
                <div class="chart-body"><canvas id="chartYearKasa"></canvas></div>
            </div>

        </div>

    </div>

    <script>
        // PHP VERİLERİNİ JS'E AKTARMA
        const aylar = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
        const gunler = <?php echo json_encode($gunler_etiket); ?>;
        
        const dataTotalArac = [<?php echo $total_giris; ?>, <?php echo $total_cikis; ?>];
        const dataMonthArac = <?php echo json_encode(array_values($gunluk_arac_verileri)); ?>;
        const dataYearArac = <?php echo json_encode($aylik_arac_verileri); ?>;
        
        const dataTotalKasa = [<?php echo $total_gelir; ?>, <?php echo $total_gider; ?>];
        const dataMonthKasa = <?php echo json_encode(array_values($gunluk_gelir_verileri)); ?>;
        const dataYearKasa = <?php echo json_encode($aylik_gelir_verileri); ?>;

        // ORTAK AYARLAR (maintainAspectRatio: false ÖNEMLİDİR)
        const commonOptions = { 
            responsive: true, 
            maintainAspectRatio: false, /* Kutunun boyutuna uymasını sağlar */
            plugins: { 
                legend: { 
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        font: { size: 10 } /* Yazdırırken yazıların çok büyük olmaması için */
                    }
                } 
            } 
        };

        // 1. TOPLAM ARAÇ (PIE)
        new Chart(document.getElementById('chartTotalArac'), {
            type: 'doughnut',
            data: {
                labels: ['Toplam Giriş', 'Çıkış Yapan'],
                datasets: [{ data: dataTotalArac, backgroundColor: ['#007bff', '#dc3545'] }]
            },
            options: commonOptions
        });

        // 2. BU AY GÜNLÜK ARAÇ (LINE)
        new Chart(document.getElementById('chartMonthArac'), {
            type: 'line',
            data: {
                labels: gunler,
                datasets: [{ label: 'Araç Sayısı', data: dataMonthArac, borderColor: '#28a745', backgroundColor: 'rgba(40, 167, 69, 0.2)', fill: true }]
            },
            options: commonOptions
        });

        // 3. BU YIL AYLIK ARAÇ (BAR)
        new Chart(document.getElementById('chartYearArac'), {
            type: 'bar',
            data: {
                labels: aylar,
                datasets: [{ label: 'Araç Sayısı', data: dataYearArac, backgroundColor: '#17a2b8' }]
            },
            options: commonOptions
        });

        // 4. TOPLAM KASA (PIE)
        new Chart(document.getElementById('chartTotalKasa'), {
            type: 'pie',
            data: {
                labels: ['Toplam Gelir', 'Toplam Gider'],
                datasets: [{ data: dataTotalKasa, backgroundColor: ['#28a745', '#dc3545'] }]
            },
            options: commonOptions
        });

        // 5. BU AY GÜNLÜK GELİR (LINE)
        new Chart(document.getElementById('chartMonthKasa'), {
            type: 'line',
            data: {
                labels: gunler,
                datasets: [{ label: 'Gelir (TL)', data: dataMonthKasa, borderColor: '#ffc107', backgroundColor: 'rgba(255, 193, 7, 0.2)', fill: true }]
            },
            options: commonOptions
        });

        // 6. BU YIL AYLIK GELİR (BAR)
        new Chart(document.getElementById('chartYearKasa'), {
            type: 'bar',
            data: {
                labels: aylar,
                datasets: [{ label: 'Gelir (TL)', data: dataYearKasa, backgroundColor: '#6610f2' }]
            },
            options: commonOptions
        });

    </script>

</body>
</html>