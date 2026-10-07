<?php
// Oturumu başlat
session_start();

// Global ayar dosyasını dahil et
require_once '../db_config.php';

// Kullanıcı giriş yapmamışsa login'e at
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ============================================================================
// AJAX İŞLEMLERİ
// ============================================================================
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'İşlem başarısız'];

    if ($_POST['ajax_action'] == 'marka_ekle') {
        $marka = mb_strtoupper(trim($_POST['deger']), 'UTF-8');
        if (!empty($marka)) {
            $conn->query("INSERT IGNORE INTO tanim_marka (marka_adi) VALUES ('$marka')");
            $response = ['status' => 'success', 'message' => 'Marka tanımlara eklendi.'];
        }
    } 
    elseif ($_POST['ajax_action'] == 'model_ekle') {
        $marka_adi = mb_strtoupper(trim($_POST['ust_deger']), 'UTF-8');
        $model_adi = mb_strtoupper(trim($_POST['deger']), 'UTF-8');
        
        if (!empty($marka_adi) && !empty($model_adi)) {
            $res = $conn->query("SELECT id FROM tanim_marka WHERE marka_adi = '$marka_adi'");
            if ($res->num_rows > 0) {
                $marka_id = $res->fetch_assoc()['id'];
            } else {
                $conn->query("INSERT INTO tanim_marka (marka_adi) VALUES ('$marka_adi')");
                $marka_id = $conn->insert_id;
            }
            $conn->query("INSERT INTO tanim_model (marka_id, model_adi) VALUES ($marka_id, '$model_adi')");
            $response = ['status' => 'success', 'message' => 'Model tanımlara eklendi.'];
        } else {
            $response = ['status' => 'error', 'message' => 'Model eklemek için önce Marka bilgisini giriniz.'];
        }
    }
    elseif ($_POST['ajax_action'] == 'get_modeller') {
        $marka_adi = mb_strtoupper(trim($_POST['marka_adi']), 'UTF-8');
        $modeller = [];
        if (!empty($marka_adi)) {
            $res = $conn->query("SELECT id FROM tanim_marka WHERE marka_adi = '$marka_adi'");
            if ($res->num_rows > 0) {
                $marka_id = $res->fetch_assoc()['id'];
                $res_m = $conn->query("SELECT model_adi FROM tanim_model WHERE marka_id = $marka_id ORDER BY model_adi ASC");
                while($row = $res_m->fetch_assoc()) {
                    $modeller[] = $row['model_adi'];
                }
            }
        }
        echo json_encode(['status' => 'success', 'models' => $modeller]);
        exit;
    }
    elseif ($_POST['ajax_action'] == 'men_nedeni_ekle') {
        $neden = mb_strtoupper(trim($_POST['deger']), 'UTF-8');
        if (!empty($neden)) {
            $conn->query("INSERT IGNORE INTO tanim_men_nedeni (men_nedeni) VALUES ('$neden')");
            $response = ['status' => 'success', 'message' => 'Men nedeni tanımlara eklendi.'];
        }
    }
    elseif ($_POST['ajax_action'] == 'cins_ekle') {
        $cins = mb_strtoupper(trim($_POST['deger']), 'UTF-8');
        if (!empty($cins)) {
            $conn->query("INSERT IGNORE INTO tanim_arac_cins (cins_adi, gunluk_ucret) VALUES ('$cins', 0)");
            $response = ['status' => 'success', 'message' => 'Araç cinsi tanımlara eklendi.'];
        }
    }
    // YENİ: KURUM EKLEME
    elseif ($_POST['ajax_action'] == 'kurum_ekle') {
        $kurum = mb_strtoupper(trim($_POST['deger']), 'UTF-8');
        if (!empty($kurum)) {
            $conn->query("INSERT IGNORE INTO tanim_kurum (kurum_adi) VALUES ('$kurum')");
            $response = ['status' => 'success', 'message' => 'Kurum tanımlara eklendi.'];
        }
    }
    
    echo json_encode($response);
    exit;
}

// Verileri çek
$db_markalar = $conn->query("SELECT * FROM tanim_marka ORDER BY marka_adi ASC");
$db_men_nedenleri = $conn->query("SELECT * FROM tanim_men_nedeni ORDER BY men_nedeni ASC");
$db_cinsler = $conn->query("SELECT * FROM tanim_arac_cins ORDER BY cins_adi ASC");
// YENİ: Kurumları Çek
$db_kurumlar = $conn->query("SELECT * FROM tanim_kurum ORDER BY kurum_adi ASC");

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hızlı Araç Kayıt - Yediemin Otopark</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="modal-body">

    <div class="modal-overlay">
        
        <div class="modal-window">
            
            <div class="modal-header">
                <h3><i class="fas fa-bolt"></i> Hızlı Araç Kayıt Formu</h3>
                <a href="../index.php" class="close-modal"><i class="fas fa-times"></i></a>
            </div>

            <div class="modal-content">
                <form id="hizliKayitFormu" action="kayit_islemi.php" method="POST">
                    
                    <input type="hidden" name="durumu" value="Otoparkta">

                    <div class="modal-top-row">
                        <div class="mini-field">
                            <label>Kayıt Tarihi</label>
                            <input type="datetime-local" name="kayit_tarihi" value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <div class="mini-field">
                            <label>Giriş Tarihi</label>
                            <input type="datetime-local" name="giris_tarihi" value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <div class="mini-field">
                            <label>Fiş No</label>
                            <input type="text" name="fis_no" class="force-uppercase">
                        </div>
                        <div class="mini-field">
                            <label>Cilt No</label>
                            <input type="text" name="cilt_no" class="force-uppercase">
                        </div>
                    </div>

                    <div class="modal-grid">
                        
                        <div class="modal-column">
                            <h4 class="section-title">Aracı Teslim Eden Kurum / Kuruluş</h4>
                            
                            <div class="form-field-compact">
                                <label>Kurum:</label>
                                <div class="input-group-button">
                                    <input type="text" list="kurumListesi" id="kurumInput" name="kurum_adi" class="force-uppercase" placeholder="Seç/Yaz">
                                    <datalist id="kurumListesi">
                                        <?php 
                                        if($db_kurumlar->num_rows > 0) {
                                            $db_kurumlar->data_seek(0);
                                            while($k = $db_kurumlar->fetch_assoc()) { echo "<option value='".htmlspecialchars($k['kurum_adi'])."'>"; }
                                        }
                                        ?>
                                    </datalist>
                                    <button type="button" class="btn-icon small" onclick="yeniOgeEkle('kurumInput', 'kurumListesi')"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>

                            <div class="form-field-compact">
                                <label>Men Nedeni:</label>
                                <div class="input-group-button">
                                    <input type="text" list="menNedenleri" id="menNedeniInput" name="men_nedeni" class="force-uppercase" placeholder="Seç/Yaz">
                                    <datalist id="menNedenleri">
                                        <?php 
                                        if($db_men_nedenleri->num_rows > 0) {
                                            $db_men_nedenleri->data_seek(0);
                                            while($mn = $db_men_nedenleri->fetch_assoc()) { echo "<option value='".htmlspecialchars($mn['men_nedeni'])."'>"; }
                                        }
                                        ?>
                                    </datalist>
                                    <button type="button" class="btn-icon small" onclick="yeniOgeEkle('menNedeniInput', 'menNedenleri')"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>

                            <div class="form-field-compact">
                                <label>Ekip Kodu:</label>
                                <input type="text" name="ekip_kodu" class="force-uppercase">
                            </div>

                            <div class="form-field-compact">
                                <label>Açıklama:</label>
                                <textarea name="kurum_aciklama" rows="2" class="force-uppercase" style="width: 100%; border: 1px solid #ccc; border-radius: 4px; padding: 6px; font-size: 13px; font-family: inherit; box-sizing: border-box;"></textarea>
                            </div>

                            <hr class="dashed-line">

                            <h4 class="section-title">Aracı Teslim Eden Kişi</h4>
                            <div class="form-field-compact">
                                <label>TC No:</label>
                                <input type="text" name="teslim_eden_tc" maxlength="11" class="numeric-only">
                            </div>
                            <div class="form-field-compact">
                                <label>Adı Soyadı:</label>
                                <input type="text" name="teslim_eden_ad" class="force-uppercase">
                            </div>
                        </div>

                        <div class="modal-column">
                            <h4 class="section-title">Araç Bilgileri</h4>

                            <div class="form-field-compact">
                                <label>Plakası:</label>
                                <div class="input-with-toggle">
                                    <div class="labeled-switch-wrapper">
                                        <span class="switch-label yok" style="font-size: 10px;">YOK</span>
                                        <div class="toggle-wrapper small-toggle">
                                            <input type="checkbox" id="plaka_var" name="plaka_var" class="toggle-checkbox" onchange="togglePlakaDurumu()" checked>
                                            <label for="plaka_var" class="toggle-label"></label>
                                        </div>
                                        <span class="switch-label var" style="font-size: 10px;">VAR</span>
                                    </div>
                                    <input type="text" id="plakaInput" name="plaka" required class="force-uppercase no-space" style="margin-left: 5px;">
                                </div>
                            </div>

                            <div class="form-field-compact">
                                <label>Cinsi:</label>
                                <div class="input-group-button">
                                    <input type="text" list="cinsListesi" id="cinsInput" name="cinsi" class="force-uppercase" placeholder="Seç/Yaz">
                                    <datalist id="cinsListesi">
                                        <?php 
                                        if($db_cinsler->num_rows > 0) {
                                            $db_cinsler->data_seek(0);
                                            while($c = $db_cinsler->fetch_assoc()) { echo "<option value='".htmlspecialchars($c['cins_adi'])."'>"; }
                                        }
                                        ?>
                                    </datalist>
                                    <button type="button" class="btn-icon small" onclick="yeniOgeEkle('cinsInput', 'cinsListesi')"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>

                            <div class="form-field-compact">
                                <label>Markası:</label>
                                <div class="input-group-button">
                                    <input type="text" list="markaListesi" id="markaInput" name="marka" class="force-uppercase" placeholder="Seç/Yaz" autocomplete="off">
                                    <datalist id="markaListesi">
                                        <?php 
                                        if($db_markalar->num_rows > 0) {
                                            $db_markalar->data_seek(0);
                                            while($m = $db_markalar->fetch_assoc()) { echo "<option value='".htmlspecialchars($m['marka_adi'])."'>"; }
                                        }
                                        ?>
                                    </datalist>
                                    <button type="button" class="btn-icon small" onclick="yeniOgeEkle('markaInput', 'markaListesi')"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>

                            <div class="form-field-compact">
                                <label>Modeli:</label>
                                <div class="input-group-button">
                                    <input type="text" list="modelListesi" id="modelInput" name="model" class="force-uppercase" placeholder="Seç/Yaz" autocomplete="off">
                                    <datalist id="modelListesi"></datalist>
                                    <button type="button" class="btn-icon small" onclick="yeniOgeEkle('modelInput', 'modelListesi')"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>

                            <div class="form-field-compact">
                                <label>Model Yılı:</label>
                                <select name="yil">
                                    <option value="">Seç</option>
                                    <?php
                                    $currentYear = date("Y");
                                    for ($i = $currentYear; $i >= $currentYear - 30; $i--) {
                                        echo "<option value='$i'>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-field-compact">
                                <label>Rengi:</label>
                                <input type="text" list="renkListesi" id="renkInput" name="renk" class="force-uppercase">
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> KAYDET</button>
                        <a href="../index.php" class="btn btn-danger"><i class="fas fa-times-circle"></i> VAZGEÇ</a>
                    </div>

                </form>
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

        document.querySelectorAll('.no-space').forEach(function(input) {
            input.addEventListener('keydown', function(e) { if (e.key === " ") e.preventDefault(); });
            input.addEventListener('input', function() { this.value = this.value.replace(/\s/g, ''); });
        });

        document.querySelectorAll('.numeric-only').forEach(function(input) {
            input.addEventListener('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); });
        });

        function togglePlakaDurumu() {
            var checkbox = document.getElementById('plaka_var');
            var input = document.getElementById('plakaInput');
            if (checkbox.checked) {
                input.style.display = 'block'; input.setAttribute('required', 'required');
            } else {
                input.style.display = 'none'; input.value = ''; input.removeAttribute('required');
            }
        }
        
        // --- AJAX İŞLEMLERİ ---

        document.getElementById('markaInput').addEventListener('change', function() {
            modelleriGuncelle(this.value);
        });

        function modelleriGuncelle(markaAdi) {
            var modelInput = document.getElementById('modelInput');
            var datalist = document.getElementById('modelListesi');
            
            datalist.innerHTML = "";
            modelInput.value = ""; // Marka değişince model sıfırlansın

            if(!markaAdi) return;

            var formData = new FormData();
            formData.append('ajax_action', 'get_modeller');
            formData.append('marka_adi', markaAdi);

            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    data.models.forEach(function(mod) {
                        var option = document.createElement('option');
                        option.value = mod;
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

            if (inputId === 'markaInput') {
                formData.append('ajax_action', 'marka_ekle');
                isAjax = true;
            } else if (inputId === 'modelInput') {
                var markaVal = document.getElementById('markaInput').value.trim();
                if(markaVal === "") { alert("Model eklemek için önce Marka bilgisini giriniz."); return; }
                formData.append('ajax_action', 'model_ekle');
                formData.append('ust_deger', markaVal); 
                isAjax = true;
            } else if (inputId === 'menNedeniInput') {
                formData.append('ajax_action', 'men_nedeni_ekle');
                isAjax = true;
            } else if (inputId === 'cinsInput') {
                formData.append('ajax_action', 'cins_ekle');
                isAjax = true;
            } else if (inputId === 'kurumInput') { // YENİ EKLENDİ
                formData.append('ajax_action', 'kurum_ekle');
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
            } else {
                var dataList = document.getElementById(datalistId);
                var option = document.createElement('option');
                option.value = yeniDeger;
                dataList.appendChild(option);
            }
        }
    </script>

</body>
</html>