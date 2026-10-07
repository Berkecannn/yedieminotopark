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
// YENİ: AJAX İŞLEMLERİ (GİDER TANIMLARI İÇİN)
// ============================================================================
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'İşlem başarısız'];

    // 1. GİDER TÜRÜ EKLEME
    if ($_POST['ajax_action'] == 'gider_turu_ekle') {
        $tur = str_replace(array("i","ı","ğ","ü","ş","ö","ç"), array("İ","I","Ğ","Ü","Ş","Ö","Ç"), $_POST['deger']);
        $tur = mb_strtoupper(trim($tur), 'UTF-8');
        if (!empty($tur)) {
            $conn->query("INSERT IGNORE INTO tanim_gider_turleri (tanim_adi) VALUES ('$tur')");
            $response = ['status' => 'success', 'message' => 'Gider türü eklendi.'];
        }
    } 
    // 2. GİDER ADI EKLEME
    elseif ($_POST['ajax_action'] == 'gider_adi_ekle') {
        $tur_adi = str_replace(array("i","ı","ğ","ü","ş","ö","ç"), array("İ","I","Ğ","Ü","Ş","Ö","Ç"), $_POST['ust_deger']);
        $tur_adi = mb_strtoupper(trim($tur_adi), 'UTF-8');
        
        $ad = str_replace(array("i","ı","ğ","ü","ş","ö","ç"), array("İ","I","Ğ","Ü","Ş","Ö","Ç"), $_POST['deger']);
        $ad = mb_strtoupper(trim($ad), 'UTF-8');
        
        if (!empty($tur_adi) && !empty($ad)) {
            $res = $conn->query("SELECT id FROM tanim_gider_turleri WHERE tanim_adi = '$tur_adi'");
            if ($res->num_rows > 0) {
                $tur_id = $res->fetch_assoc()['id'];
            } else {
                $conn->query("INSERT INTO tanim_gider_turleri (tanim_adi) VALUES ('$tur_adi')");
                $tur_id = $conn->insert_id;
            }
            $conn->query("INSERT INTO tanim_gider_adlari (tur_id, tanim_adi) VALUES ($tur_id, '$ad')");
            $response = ['status' => 'success', 'message' => 'Gider adı eklendi.'];
        } else {
            $response = ['status' => 'error', 'message' => 'Lütfen önce Gider Türü seçiniz.'];
        }
    }
    // 3. TÜRE GÖRE GİDER ADLARINI GETİR
    elseif ($_POST['ajax_action'] == 'get_gider_adlari') {
        $tur_adi = str_replace(array("i","ı","ğ","ü","ş","ö","ç"), array("İ","I","Ğ","Ü","Ş","Ö","Ç"), $_POST['tur_adi']);
        $tur_adi = mb_strtoupper(trim($tur_adi), 'UTF-8');
        $adlar = [];
        if (!empty($tur_adi)) {
            $res = $conn->query("SELECT id FROM tanim_gider_turleri WHERE tanim_adi = '$tur_adi'");
            if ($res->num_rows > 0) {
                $tur_id = $res->fetch_assoc()['id'];
                $res_a = $conn->query("SELECT tanim_adi FROM tanim_gider_adlari WHERE tur_id = $tur_id ORDER BY tanim_adi ASC");
                while($row = $res_a->fetch_assoc()) {
                    $adlar[] = $row['tanim_adi'];
                }
            }
        }
        echo json_encode(['status' => 'success', 'items' => $adlar]);
        exit;
    }
    
    echo json_encode($response);
    exit;
}

// --- VERİLERİ ÇEK ---
$db_gider_turleri = $conn->query("SELECT * FROM tanim_gider_turleri ORDER BY tanim_adi ASC");

// --- İŞLEM 1: GİDER EKLEME / GÜNCELLEME ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'gider_kayit') {
    $id = !empty($_POST['g_id']) ? $_POST['g_id'] : null;
    
    $kayit_tarihi = $_POST['kayit_tarihi'];
    $odeme_tarihi = $_POST['odeme_tarihi'];
    $gider_adi = mb_strtoupper($_POST['gider_adi'], 'UTF-8');
    $gider_turu = $_POST['gider_turu'];
    $odeme_sekli = $_POST['odeme_sekli'];
    $tutar = $_POST['gider_tutari'];
    $aciklama = mb_strtoupper($_POST['aciklama'], 'UTF-8');
    
    $odeme_durumu = isset($_POST['odeme_durumu']) ? 1 : 0;

    if ($id) {
        $stmt = $conn->prepare("UPDATE giderler SET kayit_tarihi=?, odeme_tarihi=?, gider_adi=?, gider_turu=?, odeme_sekli=?, gider_tutari=?, aciklama=?, odeme_durumu=? WHERE id=?");
        $stmt->bind_param("sssssdsii", $kayit_tarihi, $odeme_tarihi, $gider_adi, $gider_turu, $odeme_sekli, $tutar, $aciklama, $odeme_durumu, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO giderler (kayit_tarihi, odeme_tarihi, gider_adi, gider_turu, odeme_sekli, gider_tutari, aciklama, odeme_durumu) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssdsi", $kayit_tarihi, $odeme_tarihi, $gider_adi, $gider_turu, $odeme_sekli, $tutar, $aciklama, $odeme_durumu);
    }
    $stmt->execute();
    $stmt->close();
    header("location: gider_kayit.php"); exit;
}

// SİLME
if (isset($_GET['sil_id'])) {
    $conn->query("DELETE FROM giderler WHERE id = {$_GET['sil_id']}");
    header("location: gider_kayit.php"); exit;
}

// LİSTELEME
$giderler = $conn->query("SELECT * FROM giderler ORDER BY kayit_tarihi DESC");
$toplam_gider = 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Gider Yönetimi</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .page-layout { display: grid; grid-template-columns: 350px 1fr; gap: 20px; padding: 20px; }
        .form-panel { background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; height: fit-content; }
        .table-panel { background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px; color: #555; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { width: 100%; background: #dc3545; color: white; border: none; padding: 10px; cursor: pointer; border-radius: 4px; font-weight: bold; }
        .custom-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .custom-table th, .custom-table td { border: 1px solid #eee; padding: 8px; text-align: left; }
        .custom-table th { background: #f8f9fa; font-weight: bold; }
        .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-pending { background: #f8d7da; color: #721c24; }
        .total-bar { background: #e9ecef; padding: 10px; margin-top: 15px; text-align: right; font-weight: bold; font-size: 16px; border-radius: 4px; color: #dc3545; }
        
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; -webkit-transition: .4s; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; -webkit-transition: .4s; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #dc3545; }
        input:focus + .slider { box-shadow: 0 0 1px #dc3545; }
        input:checked + .slider:before { -webkit-transform: translateX(26px); -ms-transform: translateX(26px); transform: translateX(26px); }

        .input-group-btn { display: flex; gap: 5px; }
        .input-group-btn input { flex-grow: 1; }
        .btn-icon-add { background: #6c757d; color: white; border: none; width: 35px; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-content">
        <div class="page-header-row">
            <h2><i class="fas fa-file-invoice-dollar"></i> Gider Yönetimi</h2>
        </div>

        <div class="page-layout">
            
            <div class="form-panel">
                <div style="border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:15px; font-weight:bold; color:#dc3545;">
                    <i class="fas fa-minus-circle"></i> Yeni Gider Ekle
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="gider_kayit">
                    <input type="hidden" name="g_id" id="giderId">
                    
                    <div class="form-group">
                        <label>Kayıt Tarihi</label>
                        <input type="date" name="kayit_tarihi" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Gider Türü (Kategori)</label>
                        <div class="input-group-btn">
                            <input type="text" list="turListesi" id="turInput" name="gider_turu" class="force-uppercase" placeholder="Seç veya Yaz" autocomplete="off" required>
                            <datalist id="turListesi">
                                <?php 
                                if($db_gider_turleri->num_rows > 0) {
                                    $db_gider_turleri->data_seek(0);
                                    while($t = $db_gider_turleri->fetch_assoc()) { echo "<option value='".htmlspecialchars($t['tanim_adi'])."'>"; }
                                }
                                ?>
                            </datalist>
                            <button type="button" class="btn-icon-add" onclick="yeniOgeEkle('turInput', 'turListesi')"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Gider Adı (Tanımı)</label>
                        <div class="input-group-btn">
                            <input type="text" list="adListesi" id="adInput" name="gider_adi" class="force-uppercase" placeholder="Seç veya Yaz" autocomplete="off" required>
                            <datalist id="adListesi"></datalist>
                            <button type="button" class="btn-icon-add" onclick="yeniOgeEkle('adInput', 'adListesi')"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tutar (TL)</label>
                        <input type="number" name="gider_tutari" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label>Ödeme Şekli</label>
                        <select name="odeme_sekli">
                            <option value="Nakit">Nakit</option>
                            <option value="Kredi Kartı">Kredi Kartı</option>
                            <option value="Havale/EFT">Havale/EFT</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <label style="margin:0;">Ödeme Yapıldı mı?</label>
                            <label class="switch">
                                <input type="checkbox" name="odeme_durumu" id="odemeCheck" onchange="toggleOdemeTarihi()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" id="odemeTarihiDiv" style="display:none;">
                        <label>Ödeme Tarihi</label>
                        <input type="date" name="odeme_tarihi" value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label>Not / Açıklama</label>
                        <textarea name="aciklama" rows="2" class="force-uppercase"></textarea>
                    </div>

                    <button type="submit" class="btn-submit">KAYDET</button>
                    <button type="button" onclick="formTemizle()" style="width:100%; background:#6c757d; color:white; border:none; padding:8px; margin-top:5px; border-radius:4px; cursor:pointer;">TEMİZLE</button>
                </form>
            </div>

            <div class="table-panel">
                <div style="height: 500px; overflow-y: auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Tür</th>
                                <th>Gider Adı</th>
                                <th>Ödeme</th>
                                <th>Durum</th>
                                <th style="text-align:right;">Tutar</th>
                                <th width="60"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($giderler->num_rows > 0): ?>
                                <?php while($row = $giderler->fetch_assoc()): 
                                    $toplam_gider += $row['gider_tutari'];
                                ?>
                                <tr>
                                    <td><?php echo date("d.m.Y", strtotime($row['kayit_tarihi'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['gider_turu']); ?></td>
                                    <td><?php echo htmlspecialchars($row['gider_adi']); ?></td>
                                    <td><?php echo htmlspecialchars($row['odeme_sekli']); ?></td>
                                    <td>
                                        <?php if($row['odeme_durumu']): ?>
                                            <span class="status-badge status-paid">ÖDENDİ</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">BEKLİYOR</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right; font-weight:bold;"><?php echo number_format($row['gider_tutari'], 2, ',', '.'); ?> ₺</td>
                                    <td>
                                        <div style="display:flex; gap:5px;">
                                            <button onclick='duzenle(<?php echo json_encode($row); ?>)' style="border:none; background:none; color:#007bff; cursor:pointer;"><i class="fas fa-edit"></i></button>
                                            <a href="?sil_id=<?php echo $row['id']; ?>" onclick="return confirm('Silmek istediğinize emin misiniz?')" style="color:#dc3545;"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align:center;">Kayıt bulunamadı.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="total-bar">
                    TOPLAM GİDER: <?php echo number_format($toplam_gider, 2, ',', '.'); ?> TL
                </div>
            </div>

        </div>

    </div>

    <script>
        document.querySelectorAll('.force-uppercase').forEach(function(input) {
            input.addEventListener('input', function() {
                var start = this.selectionStart; var end = this.selectionEnd;
                this.value = this.value.toLocaleUpperCase('tr-TR');
                this.setSelectionRange(start, end);
            });
        });
        
        function toggleOdemeTarihi() {
            var isChecked = document.getElementById('odemeCheck').checked;
            document.getElementById('odemeTarihiDiv').style.display = isChecked ? 'block' : 'none';
        }

        function formTemizle() {
            document.querySelector('form').reset();
            document.getElementById('giderId').value = "";
            document.getElementById('odemeTarihiDiv').style.display = 'none';
        }

        function duzenle(data) {
            document.getElementById('giderId').value = data.id;
            document.getElementsByName('kayit_tarihi')[0].value = data.kayit_tarihi;
            document.getElementsByName('odeme_tarihi')[0].value = data.odeme_tarihi;
            document.getElementsByName('gider_turu')[0].value = data.gider_turu;
            
            // Tür değiştiği için adları güncelle, sonra adı seç
            modelleriGuncelle(data.gider_turu, true); 
            setTimeout(function(){ document.getElementsByName('gider_adi')[0].value = data.gider_adi; }, 200);

            document.getElementsByName('gider_tutari')[0].value = data.gider_tutari;
            document.getElementsByName('odeme_sekli')[0].value = data.odeme_sekli;
            document.getElementsByName('aciklama')[0].value = data.aciklama;
            
            var check = document.getElementById('odemeCheck');
            check.checked = (data.odeme_durumu == 1);
            toggleOdemeTarihi();
        }

        // --- DİNAMİK VERİ İŞLEMLERİ ---

        document.getElementById('turInput').addEventListener('change', function() {
            modelleriGuncelle(this.value);
        });

        function modelleriGuncelle(turAdi, koru = false) {
            var adInput = document.getElementById('adInput');
            var datalist = document.getElementById('adListesi');
            
            datalist.innerHTML = "";
            if(!koru) adInput.value = "";

            if(!turAdi) return;

            var formData = new FormData();
            formData.append('ajax_action', 'get_gider_adlari');
            formData.append('tur_adi', turAdi);

            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    data.items.forEach(function(item) {
                        var option = document.createElement('option');
                        option.value = item;
                        datalist.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Hata:', error));
        }

        function yeniOgeEkle(inputId, datalistId) {
            var input = document.getElementById(inputId);
            var yeniDeger = input.value.trim();
            if(yeniDeger === "") return;

            var formData = new FormData();
            formData.append('deger', yeniDeger);
            var isAjax = false;

            if (inputId === 'turInput') {
                formData.append('ajax_action', 'gider_turu_ekle');
                isAjax = true;
            } else if (inputId === 'adInput') {
                var turVal = document.getElementById('turInput').value.trim();
                if(turVal === "") { alert("Lütfen önce Gider Türü seçiniz."); return; }
                formData.append('ajax_action', 'gider_adi_ekle');
                formData.append('ust_deger', turVal); 
                isAjax = true;
            }

            if (isAjax) {
                fetch(window.location.href, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        var dataList = document.getElementById(datalistId);
                        var option = document.createElement('option');
                        option.value = yeniDeger;
                        dataList.appendChild(option);
                        alert(data.message);
                    } else {
                        alert("Hata: " + data.message);
                    }
                })
                .catch(error => console.error('Hata:', error));
            }
        }
    </script>

</body>
</html>