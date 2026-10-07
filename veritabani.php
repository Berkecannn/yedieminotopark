<?php
// Oturumu başlat
session_start();

// --- HEADER VE MENÜ AYARLARI ---
$yol = ""; 
$menu = "db";

require_once 'db_config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$mesaj = "";
$hata = "";

// --- YEDEKLEME (EXPORT) İŞLEMİ ---
if (isset($_POST['islem']) && ($_POST['islem'] == 'yedekle_json' || $_POST['islem'] == 'yedekle_excel')) {
    if (!empty($_POST['secilen_idler'])) {
        $ids = array_map('intval', $_POST['secilen_idler']);
        $id_list = implode(',', $ids);
        
        $sql = "SELECT * FROM araclar WHERE id IN ($id_list)";
        $result = $conn->query($sql);
        $data = [];
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        // JSON OLARAK İNDİR
        if ($_POST['islem'] == 'yedekle_json') {
            $filename = "yedek_araclar_" . date("Y-m-d_H-i") . ".json";
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // EXCEL (CSV) OLARAK İNDİR
        elseif ($_POST['islem'] == 'yedekle_excel') {
            $filename = "yedek_araclar_" . date("Y-m-d_H-i") . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            // Türkçe karakter sorunu için BOM ekle
            fputs($output, "\xEF\xBB\xBF");
            
            // Başlıkları yaz
            if (!empty($data)) {
                fputcsv($output, array_keys($data[0]), ";"); // Excel için noktalı virgül daha iyidir
                // Verileri yaz
                foreach ($data as $row) {
                    fputcsv($output, $row, ";");
                }
            }
            fclose($output);
            exit;
        }

    } else {
        $hata = "Yedeklenecek veri seçilmedi.";
    }
}

// --- TOPLU SİLME İŞLEMİ ---
if (isset($_POST['islem']) && $_POST['islem'] == 'sil') {
    if (!empty($_POST['secilen_idler'])) {
        $ids = array_map('intval', $_POST['secilen_idler']);
        $id_list = implode(',', $ids);
        
        $sql = "DELETE FROM araclar WHERE id IN ($id_list)";
        if ($conn->query($sql)) {
            $mesaj = count($ids) . " adet kayıt başarıyla silindi.";
        } else {
            $hata = "Silme işlemi başarısız: " . $conn->error;
        }
    } else {
        $hata = "Silinecek veri seçilmedi.";
    }
}

// --- GERİ YÜKLEME (IMPORT) İŞLEMİ ---
if (isset($_POST['islem']) && $_POST['islem'] == 'geriyukle') {
    if (isset($_FILES['yedek_dosyasi']) && $_FILES['yedek_dosyasi']['error'] == 0) {
        
        $dosyaAdi = $_FILES['yedek_dosyasi']['name'];
        $uzanti = strtolower(pathinfo($dosyaAdi, PATHINFO_EXTENSION));
        $tmpName = $_FILES['yedek_dosyasi']['tmp_name'];
        
        $data_to_import = []; // İşlenecek verileri burada toplayacağız

        // A. JSON DOSYASI İSE
        if ($uzanti == 'json') {
            $json_data = file_get_contents($tmpName);
            $data_to_import = json_decode($json_data, true);
        } 
        // B. CSV (EXCEL) DOSYASI İSE
        elseif ($uzanti == 'csv') {
            $handle = fopen($tmpName, "r");
            // BOM varsa atla
            $bom = fread($handle, 3);
            if ($bom != "\xEF\xBB\xBF") {
                rewind($handle);
            }
            
            // İlk satırı başlık olarak al
            $headers = fgetcsv($handle, 10000, ";"); // Excel CSV'si genelde noktalı virgüldür
            
            // Eğer noktalı virgül ile ayrışmadıysa, virgül dene
            if (count($headers) < 2) {
                rewind($handle);
                $headers = fgetcsv($handle, 10000, ",");
            }

            if ($headers) {
                while (($row = fgetcsv($handle, 10000, ";")) !== FALSE) {
                    // Virgül kontrolü
                    if(count($row) < count($headers)) {
                         // Satırın formatı bozuksa veya virgül kullanılmışsa tekrar dene
                         // (Basitlik adına burada sadece noktalı virgül varsayıyoruz ama geliştirilebilir)
                    }
                    if(count($row) == count($headers)) {
                        $data_to_import[] = array_combine($headers, $row);
                    }
                }
            }
            fclose($handle);
        } else {
            $hata = "Geçersiz dosya formatı. Sadece .json veya .csv (Excel) yükleyebilirsiniz.";
        }

        // VERİTABANINA İŞLEME KISMI (Ortak)
        if (!empty($data_to_import)) {
            $basarili = 0;
            foreach ($data_to_import as $row) {
                // Veritabanı sütunlarına göre dinamik INSERT/UPDATE
                $keys = array_keys($row);
                $values = array_values($row);
                
                // Güvenlik için escape
                $escaped_values = array_map(function($val) use ($conn) {
                    return "'" . $conn->real_escape_string($val) . "'";
                }, $values);
                
                $columns = implode(", ", $keys);
                $vals = implode(", ", $escaped_values);
                
                // Update kısmı
                $update_arr = [];
                foreach($keys as $key) {
                    if($key != 'id') { // ID hariç güncelle
                        $val = $conn->real_escape_string($row[$key]);
                        $update_arr[] = "$key = '$val'";
                    }
                }
                $update_str = implode(", ", $update_arr);
                
                $sql = "INSERT INTO araclar ($columns) VALUES ($vals) ON DUPLICATE KEY UPDATE $update_str";
                
                if($conn->query($sql)) {
                    $basarili++;
                }
            }
            $mesaj = "$basarili adet kayıt başarıyla veritabanına aktarıldı.";
        } elseif(empty($hata)) {
            $hata = "Dosya boş veya okunamadı.";
        }

    } else {
        $hata = "Lütfen bir dosya seçin.";
    }
}

// --- LİSTELEME VE FİLTRELEME ---
$where_sql = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($_GET['baslangic']) && !empty($_GET['bitis'])) {
    $where_sql .= " AND giris_tarihi BETWEEN ? AND ?";
    $params[] = $_GET['baslangic'] . " 00:00:00";
    $params[] = $_GET['bitis'] . " 23:59:59";
    $types .= "ss";
}

if (!empty($_GET['arama'])) {
    $term = "%" . $_GET['arama'] . "%";
    $where_sql .= " AND (plaka LIKE ? OR marka LIKE ? OR model LIKE ?)";
    $params[] = $term; $params[] = $term; $params[] = $term;
    $types .= "sss";
}

$sql = "SELECT * FROM araclar $where_sql ORDER BY id DESC";
$stmt = $conn->prepare($sql);
if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Veritabanı İşlemleri - Yediemin Otopark</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Tarayıcı Geçmişi Tarzı Liste */
        .history-container {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .history-toolbar {
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
        }

        .action-buttons { display: flex; gap: 10px; }
        
        .history-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .history-item {
            display: grid;
            grid-template-columns: 40px 150px 120px 1fr 150px;
            align-items: center;
            padding: 12px 20px;
            border-bottom: 1px solid #f1f1f1;
            transition: background 0.2s;
        }
        
        .history-item:hover { background-color: #f9fbff; }
        .history-item:last-child { border-bottom: none; }
        
        .item-checkbox { transform: scale(1.2); cursor: pointer; }
        .item-date { font-size: 13px; color: #666; }
        .item-plate { font-weight: bold; color: #333; font-size: 14px; }
        .item-desc { font-size: 13px; color: #555; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .item-status { font-size: 12px; text-align: right; }
        
        .badge-otopark { background: #e6f7ec; color: #28a745; padding: 4px 8px; border-radius: 12px; font-weight: 600; }
        .badge-cikis { background: #fdecea; color: #dc3545; padding: 4px 8px; border-radius: 12px; font-weight: 600; }

        /* Filtre Alanı */
        .filter-bar {
            display: flex; gap: 10px; align-items: center; background: #fff; padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #e9ecef;
        }
        .filter-bar input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        
        /* Modal */
        .upload-modal {
            display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;
        }
        .upload-box {
            background: #fff; padding: 30px; border-radius: 10px; width: 400px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .upload-area {
            border: 2px dashed #ccc; padding: 30px; margin: 20px 0; border-radius: 8px; cursor: pointer; color: #666;
        }
        .upload-area:hover { border-color: var(--ana-mavi); color: var(--ana-mavi); }
        
        /* Dropdown Buton */
        .dropdown-btn-wrapper { position: relative; display: inline-block; }
        .dropdown-content {
            display: none; position: absolute; background-color: #f9f9f9; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; border-radius: 4px; overflow: hidden;
        }
        .dropdown-content a {
            color: black; padding: 12px 16px; text-decoration: none; display: block; cursor: pointer;
        }
        .dropdown-content a:hover { background-color: #f1f1f1; }
        .dropdown-btn-wrapper:hover .dropdown-content { display: block; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="main-content">
        
        <div class="page-header-row">
            <h2><i class="fas fa-database"></i> Veritabanı İşlemleri</h2>
        </div>

        <?php if($mesaj): ?><div class="alert alert-success"><?php echo $mesaj; ?></div><?php endif; ?>
        <?php if($hata): ?><div class="alert alert-danger"><?php echo $hata; ?></div><?php endif; ?>

        <form method="GET" class="filter-bar">
            <input type="date" name="baslangic" value="<?php echo $_GET['baslangic'] ?? ''; ?>">
            <input type="date" name="bitis" value="<?php echo $_GET['bitis'] ?? ''; ?>">
            <input type="text" name="arama" placeholder="Plaka veya Model Ara..." class="force-uppercase" value="<?php echo $_GET['arama'] ?? ''; ?>" style="flex-grow:1;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrele</button>
            <a href="veritabani.php" class="btn btn-secondary">Sıfırla</a>
        </form>

        <form method="POST" id="mainForm" enctype="multipart/form-data">
            <input type="hidden" name="islem" id="islemInput">
            
            <div class="history-container">
                <div class="history-toolbar">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" id="selectAll" class="item-checkbox" onclick="toggleSelectAll()">
                        <span style="font-weight:600; font-size:14px; color:#555;">Tümünü Seç</span>
                    </div>
                    <div class="action-buttons">
                        
                        <div class="dropdown-btn-wrapper">
                            <button type="button" class="btn btn-success"><i class="fas fa-download"></i> Veri Yedekle <i class="fas fa-caret-down"></i></button>
                            <div class="dropdown-content">
                                <a onclick="submitForm('yedekle_json')"><i class="fas fa-file-code"></i> JSON Formatında</a>
                                <a onclick="submitForm('yedekle_excel')"><i class="fas fa-file-excel"></i> Excel (CSV) Formatında</a>
                            </div>
                        </div>

                        <button type="button" class="btn btn-info" onclick="openUploadModal()"><i class="fas fa-upload"></i> Veri Yükle</button>
                        
                        <button type="button" class="btn btn-danger" onclick="confirmDelete()"><i class="fas fa-trash"></i> Sil</button>
                    </div>
                </div>

                <ul class="history-list">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <li class="history-item">
                                <input type="checkbox" name="secilen_idler[]" value="<?php echo $row['id']; ?>" class="item-checkbox sub-checkbox">
                                <div class="item-date"><i class="far fa-clock"></i> <?php echo date("d.m.Y H:i", strtotime($row['giris_tarihi'])); ?></div>
                                <div class="item-plate"><?php echo htmlspecialchars($row['plaka']); ?></div>
                                <div class="item-desc">
                                    <?php echo htmlspecialchars($row['marka']) . " " . htmlspecialchars($row['model']); ?> 
                                    - <small style="color:#888;"><?php echo htmlspecialchars($row['cinsi']); ?></small>
                                </div>
                                <div class="item-status">
                                    <?php if($row['durumu'] == 'Otoparkta'): ?>
                                        <span class="badge-otopark">Otoparkta</span>
                                    <?php else: ?>
                                        <span class="badge-cikis">Çıkış Yapıldı</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li style="padding:20px; text-align:center; color:#999;">Gösterilecek kayıt bulunamadı.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </form>
    </div>

    <div id="uploadModal" class="upload-modal">
        <div class="upload-box">
            <h3><i class="fas fa-cloud-upload-alt"></i> Veri Geri Yükle / Aktar</h3>
            <p style="color:#666; font-size:13px; margin-bottom: 20px;">
                Yedeklediğiniz veya hazırladığınız dosyayı seçin.<br>
                Desteklenen formatlar: <b>.json</b> ve <b>.csv (Excel)</b>
            </p>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="islem" value="geriyukle">
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-file-import" style="font-size:30px; margin-bottom:10px;"></i><br>
                    Dosya Seçmek İçin Tıklayın
                </div>
                <input type="file" name="yedek_dosyasi" id="fileInput" accept=".json,.csv,.xlsx" style="display:none;" onchange="this.form.submit()">
                <button type="button" class="btn btn-danger" onclick="closeUploadModal()">İptal</button>
            </form>
        </div>
    </div>

    <script>
        // Tümünü Seç
        function toggleSelectAll() {
            var checkboxes = document.querySelectorAll('.sub-checkbox');
            var mainCheck = document.getElementById('selectAll');
            checkboxes.forEach(cb => cb.checked = mainCheck.checked);
        }

        // Form Gönder
        function submitForm(action) {
            var checkboxes = document.querySelectorAll('.sub-checkbox:checked');
            if(checkboxes.length === 0 && action !== 'geriyukle') {
                alert("Lütfen en az bir kayıt seçin.");
                return;
            }
            document.getElementById('islemInput').value = action;
            document.getElementById('mainForm').submit();
        }

        // Silme Onayı
        function confirmDelete() {
            var checkboxes = document.querySelectorAll('.sub-checkbox:checked');
            if(checkboxes.length === 0) {
                alert("Silinecek kayıt seçilmedi.");
                return;
            }
            if(confirm(checkboxes.length + " adet kaydı kalıcı olarak silmek istediğinize emin misiniz? Bu işlem geri alınamaz!")) {
                submitForm('sil');
            }
        }

        // Modal İşlemleri
        function openUploadModal() { document.getElementById('uploadModal').style.display = 'flex'; }
        function closeUploadModal() { document.getElementById('uploadModal').style.display = 'none'; }

        // Büyük Harf
        document.querySelectorAll('.force-uppercase').forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });
    </script>

</body>
</html>