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

$mesaj = "";
$mesaj_tur = "";

// --- İŞLEMLER ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // TOPLU OLUŞTURMA İŞLEMİ
    if (isset($_POST['action']) && $_POST['action'] == 'toplu_ekle') {
        
        // Parsel Ayarları
        $p_bas = (int)$_POST['parsel_bas'];
        $p_bit = (int)$_POST['parsel_bit'];
        $p_onek = trim($_POST['parsel_onek']); // Örn: P-

        // Ada Ayarları
        $a_bas = (int)$_POST['ada_bas'];
        $a_bit = (int)$_POST['ada_bit'];
        $a_onek = trim($_POST['ada_onek']); // Örn: A-

        // Park Yeri (Sıra) Ayarları
        $s_bas = (int)$_POST['sira_bas'];
        $s_bit = (int)$_POST['sira_bit'];
        $s_onek = trim($_POST['sira_onek']); // Örn: No-

        if ($p_bit >= $p_bas && $a_bit >= $a_bas && $s_bit >= $s_bas) {
            
            $values = [];
            $count = 0;
            
            // Döngüleri Kuruyoruz
            for ($p = $p_bas; $p <= $p_bit; $p++) {
                $parsel_val = $p_onek . $p;
                
                for ($a = $a_bas; $a <= $a_bit; $a++) {
                    $ada_val = $a_onek . $a;
                    
                    for ($s = $s_bas; $s <= $s_bit; $s++) {
                        $sira_val = $s_onek . $s;
                        
                        // SQL Sorgusu için hazırla
                        $values[] = "('$parsel_val', '$ada_val', '$sira_val')";
                        $count++;
                        
                        // 1000 kayıtta bir insert atalım ki sunucu yorulmasın
                        if (count($values) >= 1000) {
                            $sql = "INSERT IGNORE INTO tanim_otopark_konum (parsel_no, ada_no, park_sira_no) VALUES " . implode(',', $values);
                            $conn->query($sql);
                            $values = []; // Diziyi boşalt
                        }
                    }
                }
            }
            
            // Kalanları kaydet
            if (!empty($values)) {
                $sql = "INSERT IGNORE INTO tanim_otopark_konum (parsel_no, ada_no, park_sira_no) VALUES " . implode(',', $values);
                $conn->query($sql);
            }
            
            $mesaj = "İşlem Başarılı! Toplam $count adet park yeri kombinasyonu oluşturuldu.";
            $mesaj_tur = "success";
        } else {
            $mesaj = "Hata: Başlangıç değerleri bitiş değerlerinden büyük olamaz.";
            $mesaj_tur = "danger";
        }
    }

    // TEKİL GÜNCELLEME İŞLEMİ
    if (isset($_POST['action']) && $_POST['action'] == 'tekil_guncelle') {
        $id = $_POST['konum_id'];
        $parsel = $_POST['parsel_no'];
        $ada = $_POST['ada_no'];
        $sira = $_POST['park_sira_no'];
        
        if($id && $parsel && $ada && $sira) {
            $sql = "UPDATE tanim_otopark_konum SET parsel_no='$parsel', ada_no='$ada', park_sira_no='$sira' WHERE id=$id";
            if($conn->query($sql)) {
                $mesaj = "Kayıt başarıyla güncellendi.";
                $mesaj_tur = "success";
            }
        }
    }

    // TEKİL SİLME
    if (isset($_POST['sil_id'])) {
        $id = $_POST['sil_id'];
        $conn->query("DELETE FROM tanim_otopark_konum WHERE id = $id");
    }
    
    // TÜMÜNÜ SİLME (SIFIRLAMA)
    if (isset($_POST['action']) && $_POST['action'] == 'tumunu_sil') {
        $conn->query("TRUNCATE TABLE tanim_otopark_konum");
        $mesaj = "Tüm tanımlar temizlendi.";
        $mesaj_tur = "warning";
    }
}

// İSTATİSTİKLER
$total = $conn->query("SELECT COUNT(*) as sayi FROM tanim_otopark_konum")->fetch_assoc()['sayi'];
$dolu = $conn->query("SELECT COUNT(*) as sayi FROM araclar WHERE durumu = 'Otoparkta'")->fetch_assoc()['sayi'];
$bos = $total - $dolu;
$oran = ($total > 0) ? round(($dolu / $total) * 100) : 0;

// LİSTELEME SORGUSU (GÜNCELLENDİ: Doluluk Kontrolü ve Limit Kaldırıldı)
$sql_list = "SELECT t.*, 
            (SELECT COUNT(*) FROM araclar a 
             WHERE a.durumu = 'Otoparkta' 
             AND a.ada_no = t.ada_no 
             AND a.parsel_no = t.parsel_no 
             AND a.park_sira_no = t.park_sira_no) as dolu_mu
             FROM tanim_otopark_konum t 
             ORDER BY t.id DESC";
$list = $conn->query($sql_list);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Toplu Konum Oluşturucu</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .page-wrapper { max-width: 1000px; margin: 20px auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-box { background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 5px; text-align: center; }
        .stat-val { font-size: 20px; font-weight: bold; display: block; margin-top: 5px; }
        .stat-lbl { font-size: 11px; text-transform: uppercase; color: #666; font-weight: 600; }
        
        .generator-card { background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px; }
        .card-title { font-weight: bold; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; color: #333; display: flex; justify-content: space-between; }
        
        .gen-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .gen-col { background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #eee; }
        .gen-col h4 { margin: 0 0 10px 0; font-size: 13px; color: var(--ana-mavi); text-align: center; text-transform: uppercase; }
        
        .form-line { display: flex; align-items: center; margin-bottom: 8px; font-size: 12px; }
        .form-line label { width: 80px; }
        .form-line input { flex: 1; padding: 5px; border: 1px solid #ccc; border-radius: 3px; }
        
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }

        .btn-gen { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 15px; }
        .btn-gen:hover { background: #218838; }
        .btn-reset { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px; }
        
        /* Modal Stilleri */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
        .modal-box { background: #fff; padding: 20px; border-radius: 5px; width: 300px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .modal-header { font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; display: flex; justify-content: space-between; }
        .close-modal { cursor: pointer; color: #999; }
        .btn-save { background: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 3px; width: 100%; cursor: pointer; margin-top: 10px; }
        
        .btn-edit { background: #007bff; color: #fff; border: none; padding: 3px 8px; cursor: pointer; border-radius: 3px; font-size: 12px; margin-right: 5px; }
        .btn-delete { background: #dc3545; color: #fff; border: none; padding: 3px 8px; cursor: pointer; border-radius: 3px; font-size: 12px; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="main-content">
        <div class="page-wrapper">
            <div class="page-header-row">
                <h2><i class="fas fa-layer-group"></i> Ada / Parsel / Konum Sihirbazı</h2>
            </div>

            <?php if($mesaj): ?><div class="alert alert-<?php echo $mesaj_tur; ?>"><?php echo $mesaj; ?></div><?php endif; ?>

            <div class="stats-grid">
                <div class="stat-box" style="border-bottom: 3px solid #007bff;">
                    <span class="stat-lbl">Toplam Kapasite</span>
                    <span class="stat-val"><?php echo $total; ?></span>
                </div>
                <div class="stat-box" style="border-bottom: 3px solid #dc3545;">
                    <span class="stat-lbl">Dolu Araç</span>
                    <span class="stat-val"><?php echo $dolu; ?></span>
                </div>
                <div class="stat-box" style="border-bottom: 3px solid #28a745;">
                    <span class="stat-lbl">Boş Yer</span>
                    <span class="stat-val"><?php echo $bos; ?></span>
                </div>
                <div class="stat-box" style="border-bottom: 3px solid #ffc107;">
                    <span class="stat-lbl">Doluluk</span>
                    <span class="stat-val">%<?php echo $oran; ?></span>
                </div>
            </div>

            <div class="generator-card">
                <div class="card-title">
                    <span>Otomatik Konum Oluşturma</span>
                    <form method="POST" onsubmit="return confirm('Tüm tanımlar silinecek! Onaylıyor musunuz?')">
                        <input type="hidden" name="action" value="tumunu_sil">
                        <button type="submit" class="btn-reset"><i class="fas fa-trash"></i> Tümünü Sıfırla</button>
                    </form>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="toplu_ekle">
                    
                    <div class="gen-grid">
                        <div class="gen-col">
                            <h4>1. Parsel Yapısı</h4>
                            <div class="form-line"><label>Başlangıç:</label><input type="number" name="parsel_bas" value="1" required></div>
                            <div class="form-line"><label>Bitiş:</label><input type="number" name="parsel_bit" value="30" required></div>
                            <div class="form-line"><label>Ön Ek:</label><input type="text" name="parsel_onek" placeholder="Örn: P-" class="uppercase"></div>
                            <small style="color:#666; font-size:10px;">Örn: 1'den 30'a kadar Parsel oluştur.</small>
                        </div>

                        <div class="gen-col">
                            <h4>2. Ada Yapısı (Her Parsel İçin)</h4>
                            <div class="form-line"><label>Başlangıç:</label><input type="number" name="ada_bas" value="1" required></div>
                            <div class="form-line"><label>Bitiş:</label><input type="number" name="ada_bit" value="20" required></div>
                            <div class="form-line"><label>Ön Ek:</label><input type="text" name="ada_onek" placeholder="Örn: A-" class="uppercase"></div>
                            <small style="color:#666; font-size:10px;">Her Parselin içinde kaç Ada olacak?</small>
                        </div>

                        <div class="gen-col">
                            <h4>3. Park Yeri (Her Ada İçin)</h4>
                            <div class="form-line"><label>Başlangıç:</label><input type="number" name="sira_bas" value="1" required></div>
                            <div class="form-line"><label>Bitiş:</label><input type="number" name="sira_bit" value="10" required></div>
                            <div class="form-line"><label>Ön Ek:</label><input type="text" name="sira_onek" placeholder="Örn: NO-" class="uppercase"></div>
                            <small style="color:#666; font-size:10px;">Her Adada kaç araçlık yer var?</small>
                        </div>
                    </div>

                    <button type="submit" class="btn-gen"><i class="fas fa-cogs"></i> YAPILANDIRMAYI OLUŞTUR</button>
                </form>
            </div>

            <div class="generator-card">
                <div class="card-title">Kayıtlı Konumlar</div>
                
                <div style="max-height: 500px; overflow-y: auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:12px;">
                        <thead>
                            <tr style="background:#f8f9fa; text-align:left;">
                                <th style="padding:8px;">ID</th>
                                <th>Parsel</th>
                                <th>Ada</th>
                                <th>Park Sıra No</th>
                                <th>Durum</th>
                                <th width="100" style="text-align:right;">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($r = $list->fetch_assoc()): ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:8px;"><?php echo $r['id']; ?></td>
                                <td><?php echo $r['parsel_no']; ?></td>
                                <td><?php echo $r['ada_no']; ?></td>
                                <td><?php echo $r['park_sira_no']; ?></td>
                                <td>
                                    <?php if($r['dolu_mu'] > 0): ?>
                                        <span style="color:red; font-weight:bold;"><i class="fas fa-car"></i> DOLU</span>
                                    <?php else: ?>
                                        <span style="color:green;">Tanımlı</span>
                                    <?php endif; ?>
                                </td>
                                <td align="right" style="display:flex; justify-content:flex-end;">
                                    <button type="button" class="btn-edit" onclick="duzenle('<?php echo $r['id']; ?>', '<?php echo $r['parsel_no']; ?>', '<?php echo $r['ada_no']; ?>', '<?php echo $r['park_sira_no']; ?>')"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="sil_id" value="<?php echo $r['id']; ?>">
                                        <button class="btn-delete" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                Konum Düzenle
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="tekil_guncelle">
                <input type="hidden" name="konum_id" id="edit_id">
                
                <div class="form-line"><label>Parsel:</label><input type="text" name="parsel_no" id="edit_parsel" required></div>
                <div class="form-line"><label>Ada:</label><input type="text" name="ada_no" id="edit_ada" required></div>
                <div class="form-line"><label>Sıra No:</label><input type="text" name="park_sira_no" id="edit_sira" required></div>
                
                <button type="submit" class="btn-save">Güncelle</button>
            </form>
        </div>
    </div>
    
    <script>
        document.querySelectorAll('.uppercase').forEach(inp => {
            inp.addEventListener('input', function(){ this.value = this.value.toUpperCase(); });
        });

        function duzenle(id, parsel, ada, sira) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_parsel').value = parsel;
            document.getElementById('edit_ada').value = ada;
            document.getElementById('edit_sira').value = sira;
            
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>