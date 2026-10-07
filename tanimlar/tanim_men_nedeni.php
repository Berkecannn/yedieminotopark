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
        $deger = mb_strtoupper(trim($_POST['men_nedeni']), 'UTF-8');
        if($deger) $conn->query("INSERT IGNORE INTO tanim_men_nedeni (men_nedeni) VALUES ('$deger')");
    }
    // YENİ: Güncelleme
    if (isset($_POST['action']) && $_POST['action'] == 'guncelle') {
        $id = $_POST['id'];
        $deger = mb_strtoupper(trim($_POST['men_nedeni']), 'UTF-8');
        if($deger && $id) $conn->query("UPDATE tanim_men_nedeni SET men_nedeni = '$deger' WHERE id = $id");
    }
    // Silme
    if (isset($_POST['sil_id'])) {
        $id = $_POST['sil_id'];
        $conn->query("DELETE FROM tanim_men_nedeni WHERE id = $id");
    }
}

$list = $conn->query("SELECT * FROM tanim_men_nedeni ORDER BY men_nedeni ASC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Men Nedeni Tanımları</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .def-container { max-width: 600px; margin: 20px auto; background: #fff; border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
        .def-header { font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .mini-form { display: flex; gap: 5px; margin-bottom: 15px; }
        .mini-form input { flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 3px; }
        .list-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .list-table th, .list-table td { border-bottom: 1px solid #eee; padding: 8px; text-align: left; }
        .btn-green { background: #28a745; color: #fff; border: none; padding: 8px 15px; cursor: pointer; border-radius: 3px; }
        .btn-blue { background: #007bff; color: #fff; border: none; padding: 8px 15px; cursor: pointer; border-radius: 3px; }
        .btn-red { background: #dc3545; color: #fff; border: none; padding: 3px 8px; cursor: pointer; border-radius: 3px; font-size: 12px; }
        .btn-edit { background: #007bff; color: #fff; border: none; padding: 3px 8px; cursor: pointer; border-radius: 3px; font-size: 12px; margin-right: 5px; }
        .btn-cancel { background: #6c757d; color: #fff; border: none; padding: 8px 15px; cursor: pointer; border-radius: 3px; display:none; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="main-content">
        <div class="page-header-row">
            <h2><i class="fas fa-gavel"></i> Men Nedeni Tanımları</h2>
        </div>
        
        <div class="def-container">
            <div class="def-header">Tanımlı Men Nedenleri</div>
            <form method="POST" class="mini-form" id="mainForm">
                <input type="hidden" name="action" id="formAction" value="ekle">
                <input type="hidden" name="id" id="editId" value="">
                <input type="text" name="men_nedeni" id="menInput" placeholder="Yeni Men Nedeni (Örn: HACİZLİ YAKALAMA)" required oninput="this.value=this.value.toLocaleUpperCase('tr-TR')">
                <button type="submit" id="submitBtn" class="btn-green"><i class="fas fa-plus"></i> Ekle</button>
                <button type="button" id="cancelBtn" class="btn-cancel" onclick="resetForm()">İptal</button>
            </form>
            <div style="max-height: 400px; overflow-y: auto;">
                <table class="list-table">
                    <thead><tr><th>Men Nedeni</th><th width="100" style="text-align:right;">İşlem</th></tr></thead>
                    <tbody>
                        <?php if($list->num_rows>0): foreach($list as $l): ?>
                        <tr>
                            <td><?php echo $l['men_nedeni']; ?></td>
                            <td align="right" style="display:flex; justify-content:flex-end;">
                                <button type="button" class="btn-edit" onclick="duzenle('<?php echo $l['id']; ?>', '<?php echo $l['men_nedeni']; ?>')"><i class="fas fa-edit"></i></button>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="sil_id" value="<?php echo $l['id']; ?>">
                                    <button class="btn-red" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; else: echo "<tr><td colspan='2'>Kayıt yok.</td></tr>"; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function duzenle(id, ad) {
            document.getElementById('formAction').value = 'guncelle';
            document.getElementById('editId').value = id;
            document.getElementById('menInput').value = ad;
            
            var btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-save"></i> Güncelle';
            btn.className = 'btn-blue';
            
            document.getElementById('cancelBtn').style.display = 'block';
        }

        function resetForm() {
            document.getElementById('formAction').value = 'ekle';
            document.getElementById('editId').value = '';
            document.getElementById('menInput').value = '';
            
            var btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-plus"></i> Ekle';
            btn.className = 'btn-green';
            
            document.getElementById('cancelBtn').style.display = 'none';
        }
    </script>
</body>
</html>