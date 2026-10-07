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
$mesaj = "";
$secili_marka_id = isset($_GET['marka_id']) ? $_GET['marka_id'] : null;

// 1. MARKA İŞLEMLERİ
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ekle
    if (isset($_POST['action']) && $_POST['action'] == 'marka_ekle') {
        $marka = mb_strtoupper(trim($_POST['marka_adi']), 'UTF-8');
        if(!empty($marka)) {
            $conn->query("INSERT IGNORE INTO tanim_marka (marka_adi) VALUES ('$marka')");
            header("Location: tanim_marka_model.php"); exit;
        }
    }
    // YENİ: Güncelle
    if (isset($_POST['action']) && $_POST['action'] == 'marka_guncelle') {
        $id = $_POST['marka_id'];
        $marka = mb_strtoupper(trim($_POST['marka_adi']), 'UTF-8');
        if(!empty($marka) && !empty($id)) {
            $conn->query("UPDATE tanim_marka SET marka_adi = '$marka' WHERE id = $id");
            header("Location: tanim_marka_model.php"); exit;
        }
    }

    // 2. MODEL İŞLEMLERİ
    // Ekle
    if (isset($_POST['action']) && $_POST['action'] == 'model_ekle') {
        $model = mb_strtoupper(trim($_POST['model_adi']), 'UTF-8');
        $m_id = $_POST['m_id'];
        if(!empty($model) && !empty($m_id)) {
            $conn->query("INSERT INTO tanim_model (marka_id, model_adi) VALUES ($m_id, '$model')");
            header("Location: tanim_marka_model.php?marka_id=$m_id"); exit;
        }
    }
    // YENİ: Güncelle
    if (isset($_POST['action']) && $_POST['action'] == 'model_guncelle') {
        $id = $_POST['model_id'];
        $m_id = $_POST['m_id'];
        $model = mb_strtoupper(trim($_POST['model_adi']), 'UTF-8');
        if(!empty($model) && !empty($id)) {
            $conn->query("UPDATE tanim_model SET model_adi = '$model' WHERE id = $id");
            header("Location: tanim_marka_model.php?marka_id=$m_id"); exit;
        }
    }
}

// 3. SİLME İŞLEMLERİ
if (isset($_GET['sil_marka'])) {
    $id = $_GET['sil_marka'];
    $conn->query("DELETE FROM tanim_marka WHERE id = $id");
    header("Location: tanim_marka_model.php"); exit;
}
if (isset($_GET['sil_model']) && isset($_GET['marka_id'])) {
    $id = $_GET['sil_model'];
    $mid = $_GET['marka_id'];
    $conn->query("DELETE FROM tanim_model WHERE id = $id");
    header("Location: tanim_marka_model.php?marka_id=$mid"); exit;
}

// VERİLERİ ÇEK
$markalar = $conn->query("SELECT * FROM tanim_marka ORDER BY marka_adi ASC");
$modeller = [];
$secili_marka_adi = "";

if($secili_marka_id) {
    $res = $conn->query("SELECT * FROM tanim_marka WHERE id = $secili_marka_id");
    if($res->num_rows > 0) $secili_marka_adi = $res->fetch_assoc()['marka_adi'];
    
    $modeller = $conn->query("SELECT * FROM tanim_model WHERE marka_id = $secili_marka_id ORDER BY model_adi ASC");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Marka ve Model Tanımları</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .def-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 20px; height: calc(100vh - 100px); }
        .def-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; }
        .def-header { background: #f8f9fa; padding: 15px; border-bottom: 1px solid #eee; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        .def-body { padding: 15px; overflow-y: auto; flex-grow: 1; }
        .def-list { list-style: none; padding: 0; margin: 0; }
        .def-list li { display: flex; justify-content: space-between; padding: 8px; border-bottom: 1px solid #eee; align-items: center; }
        .def-list li:hover { background-color: #f1f1f1; }
        .def-list li.active { background-color: #e3f2fd; border-left: 4px solid #2196F3; font-weight: bold; }
        .add-form { display: flex; gap: 10px; margin-bottom: 15px; }
        .add-form input { flex-grow: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-mini { padding: 5px 10px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; }
        .btn-red { background: #dc3545; color: white; }
        .btn-blue { background: #007bff; color: white; }
        .btn-edit { background: #007bff; color: white; margin-right: 5px; }
        .btn-cancel { background: #6c757d; color: #fff; border: none; padding: 8px 15px; cursor: pointer; border-radius: 4px; display:none; }
    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-content">
        <div class="page-header-row" style="padding: 0 20px;">
            <h2><i class="fas fa-car"></i> Marka ve Model Tanımları</h2>
        </div>

        <div class="def-container">
            
            <div class="def-card">
                <div class="def-header">ARAÇ MARKALARI</div>
                <div class="def-body">
                    <form method="POST" class="add-form" id="markaForm">
                        <input type="hidden" name="action" id="markaAction" value="marka_ekle">
                        <input type="hidden" name="marka_id" id="markaId">
                        <input type="text" name="marka_adi" id="markaInput" placeholder="Yeni Marka Adı..." class="force-uppercase" required>
                        <button type="submit" id="markaBtn" class="btn-blue"><i class="fas fa-plus"></i> Ekle</button>
                        <button type="button" id="markaCancel" class="btn-cancel" onclick="resetMarkaForm()"><i class="fas fa-times"></i></button>
                    </form>
                    <ul class="def-list">
                        <?php while($m = $markalar->fetch_assoc()): ?>
                            <li class="<?php echo ($m['id'] == $secili_marka_id) ? 'active' : ''; ?>">
                                <a href="?marka_id=<?php echo $m['id']; ?>" style="text-decoration:none; color:inherit; flex-grow:1;">
                                    <?php echo htmlspecialchars($m['marka_adi']); ?>
                                </a>
                                <div>
                                    <button type="button" class="btn-mini btn-edit" onclick="duzenleMarka('<?php echo $m['id']; ?>', '<?php echo $m['marka_adi']; ?>')"><i class="fas fa-edit"></i></button>
                                    <a href="?sil_marka=<?php echo $m['id']; ?>" onclick="return confirm('Bu markayı ve tüm modellerini silmek istediğinize emin misiniz?')" class="btn-mini btn-red"><i class="fas fa-trash"></i></a>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>

            <div class="def-card">
                <div class="def-header">
                    <?php echo $secili_marka_adi ? "$secili_marka_adi MODELLERİ" : "MODEL LİSTESİ"; ?>
                    <?php if(!$secili_marka_id): ?><small style="font-weight:normal; color:#666;">(Lütfen soldan bir marka seçiniz)</small><?php endif; ?>
                </div>
                <div class="def-body">
                    <?php if($secili_marka_id): ?>
                        <form method="POST" class="add-form" id="modelForm">
                            <input type="hidden" name="action" id="modelAction" value="model_ekle">
                            <input type="hidden" name="m_id" value="<?php echo $secili_marka_id; ?>">
                            <input type="hidden" name="model_id" id="modelId">
                            <input type="text" name="model_adi" id="modelInput" placeholder="Yeni Model Adı..." class="force-uppercase" required>
                            <button type="submit" id="modelBtn" class="btn-blue"><i class="fas fa-plus"></i> Ekle</button>
                            <button type="button" id="modelCancel" class="btn-cancel" onclick="resetModelForm()"><i class="fas fa-times"></i></button>
                        </form>
                        <ul class="def-list">
                            <?php if($modeller->num_rows > 0): ?>
                                <?php while($mod = $modeller->fetch_assoc()): ?>
                                    <li>
                                        <span><?php echo htmlspecialchars($mod['model_adi']); ?></span>
                                        <div>
                                            <button type="button" class="btn-mini btn-edit" onclick="duzenleModel('<?php echo $mod['id']; ?>', '<?php echo $mod['model_adi']; ?>')"><i class="fas fa-edit"></i></button>
                                            <a href="?marka_id=<?php echo $secili_marka_id; ?>&sil_model=<?php echo $mod['id']; ?>" onclick="return confirm('Silmek istediğinize emin misiniz?')" class="btn-mini btn-red"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li style="color:#999; justify-content:center;">Henüz model eklenmemiş.</li>
                            <?php endif; ?>
                        </ul>
                    <?php else: ?>
                        <div style="text-align:center; padding:50px; color:#999;">
                            <i class="fas fa-arrow-left"></i> Modelleri görmek veya eklemek için sol listeden bir marka seçiniz.
                        </div>
                    <?php endif; ?>
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

        // MARKA DÜZENLEME JS
        function duzenleMarka(id, adi) {
            document.getElementById('markaAction').value = 'marka_guncelle';
            document.getElementById('markaId').value = id;
            document.getElementById('markaInput').value = adi;
            document.getElementById('markaBtn').innerHTML = '<i class="fas fa-save"></i>';
            document.getElementById('markaCancel').style.display = 'block';
        }
        function resetMarkaForm() {
            document.getElementById('markaAction').value = 'marka_ekle';
            document.getElementById('markaId').value = '';
            document.getElementById('markaInput').value = '';
            document.getElementById('markaBtn').innerHTML = '<i class="fas fa-plus"></i> Ekle';
            document.getElementById('markaCancel').style.display = 'none';
        }

        // MODEL DÜZENLEME JS
        function duzenleModel(id, adi) {
            document.getElementById('modelAction').value = 'model_guncelle';
            document.getElementById('modelId').value = id;
            document.getElementById('modelInput').value = adi;
            document.getElementById('modelBtn').innerHTML = '<i class="fas fa-save"></i>';
            document.getElementById('modelCancel').style.display = 'block';
        }
        function resetModelForm() {
            document.getElementById('modelAction').value = 'model_ekle';
            document.getElementById('modelId').value = '';
            document.getElementById('modelInput').value = '';
            document.getElementById('modelBtn').innerHTML = '<i class="fas fa-plus"></i> Ekle';
            document.getElementById('modelCancel').style.display = 'none';
        }
    </script>
</body>
</html>