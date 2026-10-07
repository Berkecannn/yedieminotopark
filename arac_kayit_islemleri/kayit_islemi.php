<?php
session_start();
require_once '../db_config.php';

// Oturum kontrolü
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. FORM VERİLERİNİ AL ---
    $id = $_POST['id'] ?? null;
    
    // Tarih ve Evrak
    $kayit_tarihi = $_POST['kayit_tarihi'];
    $giris_tarihi = $_POST['giris_tarihi'];
    $fis_no = mb_strtoupper($_POST['fis_no'], 'UTF-8');
    $cilt_no = mb_strtoupper($_POST['cilt_no'], 'UTF-8');

    // Araç Bilgileri
    $plaka_var = isset($_POST['plaka_var']) ? 1 : 0;
    $plaka = $plaka_var ? mb_strtoupper(str_replace(' ', '', $_POST['plaka']), 'UTF-8') : 'PLAKASIZ';
    $cinsi = $_POST['cinsi'];
    $marka = mb_strtoupper($_POST['marka'], 'UTF-8');
    $model = mb_strtoupper($_POST['model'], 'UTF-8');
    $yil = (int)$_POST['yil']; 
    $km = (int)$_POST['km'];    
    $renk = mb_strtoupper($_POST['renk'], 'UTF-8');
    $motor_no = mb_strtoupper($_POST['motor_no'], 'UTF-8');
    $sasi_no = mb_strtoupper($_POST['sasi_no'], 'UTF-8');
    
    // --- 2. ÇIKIŞ TARİHİ AYARLAMASI (DÜZELTİLDİ) ---
    $durumu = $_POST['durumu'];
    $cikis_tarihi = null;

    if ($durumu == 'CikisYapildi') {
        if ($id) {
            // ID varsa veritabanındaki eski tarihi kontrol et
            $sorgu_id = (int)$id; 
            $check = $conn->query("SELECT cikis_tarihi FROM araclar WHERE id = $sorgu_id")->fetch_assoc();
            
            // Eğer veritabanında tarih zaten doluysa ve '0000...' değilse, ESKİSİNİ koru.
            if (!empty($check['cikis_tarihi']) && $check['cikis_tarihi'] != '0000-00-00 00:00:00') {
                $cikis_tarihi = $check['cikis_tarihi']; 
            } else {
                // Tarih yoksa ŞİMDİKİ zamanı kaydet
                $cikis_tarihi = date('Y-m-d H:i:s');
            }
        } else {
            // Yeni kayıtsa ve direkt çıkış yapıldıysa şimdiki zaman
            $cikis_tarihi = date('Y-m-d H:i:s');
        }
    } else {
        // Otoparkta ise tarih boş kalsın
        $cikis_tarihi = null;
    }

    // Kurum ve Men
    $kurum_adi = mb_strtoupper($_POST['kurum_adi'], 'UTF-8');
    $men_nedeni = mb_strtoupper($_POST['men_nedeni'], 'UTF-8');
    $men_no = mb_strtoupper($_POST['men_no'], 'UTF-8');
    $ekip_kodu = mb_strtoupper($_POST['ekip_kodu'], 'UTF-8');
    $men_yeri_aciklama = mb_strtoupper($_POST['men_yeri_aciklama'], 'UTF-8');

    // Sürücü / Sahibi
    $tc_no = $_POST['tc_no'];
    $surucu_adi = mb_strtoupper($_POST['surucu_adi'], 'UTF-8');
    $baba_adi = mb_strtoupper($_POST['baba_adi'], 'UTF-8');
    $dogum_yeri = mb_strtoupper($_POST['dogum_yeri'], 'UTF-8');
    $dogum_yili = $_POST['dogum_yili'];
    $tel1 = $_POST['tel1'];
    $tel2 = $_POST['tel2'];
    $adres = mb_strtoupper($_POST['adres'], 'UTF-8');

    // Çekici
    $cekici_unvan = mb_strtoupper($_POST['cekici_unvan'], 'UTF-8');
    $cekici_plaka = mb_strtoupper($_POST['cekici_plaka'], 'UTF-8');
    $cekici_sofor = mb_strtoupper($_POST['cekici_sofor'], 'UTF-8');
    $cekici_ucret = !empty($_POST['cekici_ucret']) ? $_POST['cekici_ucret'] : 0; 
    $cekici_not = mb_strtoupper($_POST['cekici_not'], 'UTF-8');

    // Konum
    $ada_no = $_POST['ada_no'];
    $parsel_no = $_POST['parsel_no'];
    $park_sira_no = $_POST['park_sira_no'];
    $anahtar_var = isset($_POST['anahtar_var']) ? 1 : 0; 
    $dolap_no = (int)$_POST['dolap_no']; 
    $sira_no = (int)$_POST['sira_no'];    

    // Haciz
    $haciz_eden = $_POST['haciz_eden'];
    $dosya_no = $_POST['dosya_no'];
    $haciz_aciklama = mb_strtoupper($_POST['haciz_aciklama'], 'UTF-8');

    // --- 3. TÜR DİZİSİ (DÜZELTİLDİ: ArgumentCountError Giderildi) ---
    // Toplam değişken sayısı ile harf sayısı eşitlendi.
    // 'd' (ücret) + 's'x4 (not, ada, parsel, park) + 'iii' (anahtar, dolap, sira) + 'sss'
    
    $type_insert = "sssssssssiissssssssssssssssssssdssssiiisss";
    $type_update = "sssssssssiissssssssssssssssssssdssssiiisssi";

    // --- 4. SQL İŞLEMİ ---
    if ($id) {
        // UPDATE
        $sql = "UPDATE araclar SET 
                kayit_tarihi=?, giris_tarihi=?, cikis_tarihi=?, fis_no=?, cilt_no=?, 
                plaka=?, cinsi=?, marka=?, model=?, yil=?, km=?, renk=?, motor_no=?, sasi_no=?, durumu=?,
                kurum_adi=?, men_nedeni=?, men_no=?, ekip_kodu=?, men_yeri_aciklama=?,
                tc_no=?, surucu_adi=?, baba_adi=?, dogum_yeri=?, dogum_yili=?, tel1=?, tel2=?, adres=?,
                cekici_unvan=?, cekici_plaka=?, cekici_sofor=?, cekici_ucret=?, cekici_not=?,
                ada_no=?, parsel_no=?, park_sira_no=?, anahtar_var=?, dolap_no=?, sira_no=?,
                haciz_eden=?, dosya_no=?, haciz_aciklama=?
                WHERE id=?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($type_update, 
            $kayit_tarihi, $giris_tarihi, $cikis_tarihi, $fis_no, $cilt_no,
            $plaka, $cinsi, $marka, $model, $yil, $km, $renk, $motor_no, $sasi_no, $durumu,
            $kurum_adi, $men_nedeni, $men_no, $ekip_kodu, $men_yeri_aciklama,
            $tc_no, $surucu_adi, $baba_adi, $dogum_yeri, $dogum_yili, $tel1, $tel2, $adres,
            $cekici_unvan, $cekici_plaka, $cekici_sofor, $cekici_ucret, $cekici_not,
            $ada_no, $parsel_no, $park_sira_no, $anahtar_var, $dolap_no, $sira_no,
            $haciz_eden, $dosya_no, $haciz_aciklama, $id
        );
    } else {
        // INSERT
        $sql = "INSERT INTO araclar (
                kayit_tarihi, giris_tarihi, cikis_tarihi, fis_no, cilt_no, 
                plaka, cinsi, marka, model, yil, km, renk, motor_no, sasi_no, durumu,
                kurum_adi, men_nedeni, men_no, ekip_kodu, men_yeri_aciklama,
                tc_no, surucu_adi, baba_adi, dogum_yeri, dogum_yili, tel1, tel2, adres,
                cekici_unvan, cekici_plaka, cekici_sofor, cekici_ucret, cekici_not,
                ada_no, parsel_no, park_sira_no, anahtar_var, dolap_no, sira_no,
                haciz_eden, dosya_no, haciz_aciklama
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($type_insert, 
            $kayit_tarihi, $giris_tarihi, $cikis_tarihi, $fis_no, $cilt_no,
            $plaka, $cinsi, $marka, $model, $yil, $km, $renk, $motor_no, $sasi_no, $durumu,
            $kurum_adi, $men_nedeni, $men_no, $ekip_kodu, $men_yeri_aciklama,
            $tc_no, $surucu_adi, $baba_adi, $dogum_yeri, $dogum_yili, $tel1, $tel2, $adres,
            $cekici_unvan, $cekici_plaka, $cekici_sofor, $cekici_ucret, $cekici_not,
            $ada_no, $parsel_no, $park_sira_no, $anahtar_var, $dolap_no, $sira_no,
            $haciz_eden, $dosya_no, $haciz_aciklama
        );
    }

    if ($stmt->execute()) {
        header("location: arac_sorgula.php");
    } else {
        echo "Hata: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
?>