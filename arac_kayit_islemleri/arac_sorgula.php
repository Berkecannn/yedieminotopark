<?php
session_start();

// --- HEADER VE MENÜ AYARLARI ---
$yol = "../";
$menu = "arac_kayit";

require_once '../db_config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

$where_sql = "WHERE 1=1"; 
$params = [];
$types = "";

if (!empty($_GET['plaka'])) {
    $where_sql .= " AND plaka LIKE ?";
    $params[] = "%" . $_GET['plaka'] . "%";
    $types .= "s";
}
if (!empty($_GET['durumu'])) {
    $where_sql .= " AND durumu = ?";
    $params[] = $_GET['durumu'];
    $types .= "s";
}
if (!empty($_GET['baslangic_tarihi']) && !empty($_GET['bitis_tarihi'])) {
    $where_sql .= " AND giris_tarihi BETWEEN ? AND ?";
    $params[] = $_GET['baslangic_tarihi'] . " 00:00:00";
    $params[] = $_GET['bitis_tarihi'] . " 23:59:59";
    $types .= "ss";
}

$sql = "SELECT * FROM araclar $where_sql ORDER BY id DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Araç Sorgulama - Yediemin Otopark</title>
    <link rel="stylesheet" href="<?php echo $yol; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* BUTON GRUBU */
        .action-buttons {
            display: flex;
            gap: 8px; /* Butonlar arası boşluk */
            justify-content: center; /* Ortala */
        }

        /* ORTAK BUTON STİLİ (Düzenle butonuyla aynı yapı) */
        .btn-icon-small {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid #e2e8f0; /* İnce gri çerçeve */
            background-color: white;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
        }

        /* DÜZENLE BUTONU (Mevcut Mavi/Gri Stil) */
        .btn-icon-small.edit {
            color: #3b82f6; /* Mavi ikon */
        }
        .btn-icon-small.edit:hover {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        /* MAKBUZ BUTONU (Yeni Uyumlu Stil - Yeşil Ton) */
        .btn-icon-small.receipt {
            color: #10b981; /* Yeşil ikon */
        }
        .btn-icon-small.receipt:hover {
            background-color: #10b981;
            color: white;
            border-color: #10b981;
        }
    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-content">
        
        <div class="page-header-row">
            <h2><i class="fas fa-search"></i> Araç Sorgulama ve Listeleme</h2>
        </div>

        <div class="filter-card">
            <form method="GET" action="" class="filter-form">
                <div class="filter-grid">
                    <div class="filter-item"><label>Plaka</label><input type="text" name="plaka" class="force-uppercase" value="<?php echo isset($_GET['plaka']) ? htmlspecialchars($_GET['plaka']) : ''; ?>"></div>
                    <div class="filter-item"><label>Durumu</label><select name="durumu"><option value="">Tümü</option><option value="Otoparkta">Otoparkta</option><option value="CikisYapildi">Çıkış Yapıldı</option></select></div>
                    <div class="filter-item"><label>Başlangıç</label><input type="date" name="baslangic_tarihi" value="<?php echo isset($_GET['baslangic_tarihi']) ? $_GET['baslangic_tarihi'] : ''; ?>"></div>
                    <div class="filter-item"><label>Bitiş</label><input type="date" name="bitis_tarihi" value="<?php echo isset($_GET['bitis_tarihi']) ? $_GET['bitis_tarihi'] : ''; ?>"></div>
                </div>
                <div class="filter-buttons"><button type="submit" class="btn"><i class="fas fa-filter"></i> Filtrele</button><a href="arac_sorgula.php" class="btn btn-secondary">Temizle</a></div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr><th>#</th><th>Giriş</th><th>Plaka</th><th>Cinsi</th><th>Marka/Model</th><th>Kurum</th><th>Durumu</th><th style="width:120px;">İşlem</th></tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        $sira = 1;
                        while($row = $result->fetch_assoc()) {
                            $durumBadge = ($row['durumu'] == 'Otoparkta') ? '<span class="badge badge-success">Otoparkta</span>' : '<span class="badge badge-danger">Çıkış Yapıldı</span>';
                            
                            echo "<tr>";
                            echo "<td>" . $sira++ . "</td>";
                            echo "<td>" . date("d.m.Y H:i", strtotime($row['giris_tarihi'])) . "</td>";
                            echo "<td><strong>" . htmlspecialchars($row['plaka']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['cinsi']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['marka']) . " / " . htmlspecialchars($row['model']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['kurum_adi']) . "</td>";
                            echo "<td>" . $durumBadge . "</td>";
                            
                            echo "<td><div class='action-buttons'>";
                            
                            // DÜZENLEME BUTONU (Artık ortak stile sahip)
                            echo "<a href='arac_kayit_detayli.php?id=" . $row['id'] . "' class='btn-icon-small edit' title='Düzenle'><i class='fas fa-edit'></i></a>";
                            
                            // MAKBUZ BUTONU (Sadece Otoparkta ise)
                            if($row['durumu'] == 'Otoparkta') {
                                echo "<a href='../makbuz.php?arac_id=" . $row['id'] . "' class='btn-icon-small receipt' title='Makbuz Kes & Çıkış Ver'><i class='fas fa-file-invoice-dollar'></i></a>";
                            }

                            echo "</div></td>";
                            echo "</tr>";
                        }
                    } else { echo "<tr><td colspan='8' style='text-align:center;'>Kayıt bulunamadı.</td></tr>"; }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div class="pagination-wrapper">
            <span class="paging-info">Toplam <?php echo $result->num_rows; ?> kayıt listeleniyor.</span>
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