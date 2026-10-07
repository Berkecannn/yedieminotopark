<?php
// Oturumu başlat
session_start();
$yol = "../"; 
$menu = "tanimlar";

require_once '../db_config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// --- İŞLEMLER ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ekleme
    if (isset($_POST['action']) && $_POST['action'] == 'ekle') {
        $firma = mb_strtoupper(trim($_POST['firma_adi']), 'UTF-8');
        $ucret = floatval($_POST['ucret']);
        if($firma) {
            $check = $conn->query("SELECT id FROM tanim_cekici WHERE firma_adi = '$firma'");
            if($check->num_rows > 0) {
                $conn->query("UPDATE tanim_cekici SET ucret = $ucret WHERE firma_adi = '$firma'");
            } else {
                $conn->query("INSERT INTO tanim_cekici (firma_adi, ucret) VALUES ('$firma', $ucret)");
            }
        }
    }
    // YENİ: Güncelleme
    if (isset($_POST['action']) && $_POST['action'] == 'guncelle') {
        $id = $_POST['id'];
        $firma = mb_strtoupper(trim($_POST['firma_adi']), 'UTF-8');
        $ucret = floatval($_POST['ucret']);
        if($firma && $id) {
            $conn->query("UPDATE tanim_cekici SET firma_adi = '$firma', ucret = '$ucret' WHERE id = $id");
        }
    }
    // Silme
    if (isset($_POST['sil_id'])) {
        $id = $_POST['sil_id'];
        $conn->query("DELETE FROM tanim_cekici WHERE id = $id");
    }
}

$list = $conn->query("SELECT * FROM tanim_cekici ORDER BY firma_adi ASC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Çekici Firma ve Ücret Tanımları</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .def-container { max-width: 800px; margin: 20px auto; }
        .split-box { background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .box-header { font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; font-size: 15px; color:#333; }
        .mini-form { display: flex; gap: 10px; margin-bottom: 15px; }
        .mini-form input[type="text"] { flex: 2; padding: 8px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; }
        .mini-form input[type="number"] { flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; }
        .list-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .list-table th, .list-table td { border-bottom: 1px solid #eee; padding: 8px; text-align: left; }
        .list-table th { background-color: #f9f9f9; font-weight: 600; color: #555; }
        .btn-green { background: #28a745; color: #fff; border: none; padding: 8px 15px; cursor: pointer; border-radius: 3px; font-size: 13px; }
        .btn-blue { background: #007bff; color: #fff; border: none; padding: 8px 15px; cursor: pointer; border-radius: 3px; font-size: 13px; }
        .btn-red { background: #dc3545; color: #fff; border: none; padding: 4px 8px; cursor: pointer; border-radius: 3px; font-size: 11px; }
        .btn-edit { background: #007bff; color: #fff; border: none; padding: 4px 8px; cursor: pointer; border-radius: 3px; font-size: 11px; margin-right: 5px; }
        .btn-cancel { background: #6c757d; color: #fff; border: none; padding: 8px 15px; cursor: pointer; border-radius: 3px; font-size: 13px; display:none; }
        .badge-money { background: #e9ecef; padding: 3px 8px; border-radius: 10px; font-weight: bold; color: #28a745; border: 1px solid #ced4da; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="main-content">
        <div class="page-header-row">
            <h2><i class="fas fa-truck-pickup"></i> Çekici Firma ve Ücret Tanımları</h2>
        </div>
        
        <div class="def-container">
            <div class="split-box">
                <div class="box-header">Firma Listesi ve Standart Ücretler</div>
                <form method="POST" class="mini-form" id="mainForm">
                    <input type="hidden" name="action" id="formAction" value="ekle">
                    <input type="hidden" name="id" id="editId" value="">
                    <input type="text" name="firma_adi" id="firmaInput" placeholder="Firma Adı (Örn: ÖZEL ÇEKİCİ HİZMETLERİ)" required oninput="this.value=this.value.toLocaleUpperCase('tr-TR')">
                    <input type="number" name="ucret" id="ucretInput" step="0.01" placeholder="Standart Ücret (TL)" required>
                    <button type="submit" id="submitBtn" class="btn-green"><i class="fas fa-plus"></i> Kaydet</button>
                    <button type="button" id="cancelBtn" class="btn-cancel" onclick="resetForm()">İptal</button>
                </form>
                <div style="height: 450px; overflow-y: auto;">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th>Firma Ünvanı</th>
                                <th>Standart Ücret</th>
                                <th width="100" style="text-align:right;">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($list->num_rows>0): foreach($list as $l): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($l['firma_adi']); ?></td>
                                <td><span class="badge-money"><?php echo number_format($l['ucret'], 2, ',', '.'); ?> ₺</span></td>
                                <td align="right" style="display:flex; justify-content:flex-end;">
                                    <button type="button" class="btn-edit" onclick="duzenle('<?php echo $l['id']; ?>', '<?php echo $l['firma_adi']; ?>', '<?php echo $l['ucret']; ?>')"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="sil_id" value="<?php echo $l['id']; ?>">
                                        <button class="btn-red" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; else: echo "<tr><td colspan='3' style='text-align:center; color:#999;'>Henüz kayıt bulunmamaktadır.</td></tr>"; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function duzenle(id, firma, ucret) {
            document.getElementById('formAction').value = 'guncelle';
            document.getElementById('editId').value = id;
            document.getElementById('firmaInput').value = firma;
            document.getElementById('ucretInput').value = ucret;
            
            var btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-save"></i> Güncelle';
            btn.className = 'btn-blue';
            
            document.getElementById('cancelBtn').style.display = 'block';
        }

        function resetForm() {
            document.getElementById('formAction').value = 'ekle';
            document.getElementById('editId').value = '';
            document.getElementById('firmaInput').value = '';
            document.getElementById('ucretInput').value = '';
            
            var btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-plus"></i> Kaydet';
            btn.className = 'btn-green';
            
            document.getElementById('cancelBtn').style.display = 'none';
        }
    </script>
</body>
</html>