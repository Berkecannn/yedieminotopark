<?php
// Oturumu başlat
session_start();

// Kullanıcı giriş yapmamışsa, login sayfasına geri yönlendir
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// BU SAYFA ANA DİZİNDE OLDUĞU İÇİN YOL BOŞ BIRAKILDI
$yol = "";

require_once 'db_config.php';

// ============================================================================
// 1. AJAX İŞLEMİ (TEKRARLAYAN PLAKALAR)
// ============================================================================
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'get_tekrarlayan_plakalar') {
    header('Content-Type: application/json');
    $sql = "SELECT plaka, COUNT(*) as adet FROM araclar WHERE durumu = 'Otoparkta' GROUP BY plaka HAVING adet > 1";
    $result = $conn->query($sql);
    $data = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) { $data[] = $row; }
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

// ============================================================================
// 2. KAPASİTE VE İSTATİSTİKLER
// ============================================================================
$sql_kapasite = "SELECT COUNT(*) as toplam FROM tanim_otopark_konum";
$res_kapasite = $conn->query($sql_kapasite);
$toplam_kapasite = ($res_kapasite->num_rows > 0) ? $res_kapasite->fetch_assoc()['toplam'] : 0;

$sql_dolu = "SELECT COUNT(*) as dolu FROM araclar WHERE durumu = 'Otoparkta'";
$res_dolu = $conn->query($sql_dolu);
$dolu_arac_sayisi = ($res_dolu->num_rows > 0) ? $res_dolu->fetch_assoc()['dolu'] : 0;

$bos_yer_sayisi = $toplam_kapasite - $dolu_arac_sayisi;
if($bos_yer_sayisi < 0) $bos_yer_sayisi = 0;

$bugun = date('Y-m-d');
$yil = date('Y');
$ay = date('m');

$sql_giris = "SELECT 
    SUM(CASE WHEN DATE(giris_tarihi) = '$bugun' THEN 1 ELSE 0 END) as giris_bugun,
    SUM(CASE WHEN YEARWEEK(giris_tarihi, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END) as giris_hafta,
    SUM(CASE WHEN MONTH(giris_tarihi) = '$ay' AND YEAR(giris_tarihi) = '$yil' THEN 1 ELSE 0 END) as giris_ay,
    SUM(CASE WHEN YEAR(giris_tarihi) = '$yil' THEN 1 ELSE 0 END) as giris_yil
FROM araclar";
$res_giris = $conn->query($sql_giris)->fetch_assoc();

$sql_cikis = "SELECT 
    SUM(CASE WHEN DATE(cikis_tarihi) = '$bugun' THEN 1 ELSE 0 END) as cikis_bugun,
    SUM(CASE WHEN YEARWEEK(cikis_tarihi, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END) as cikis_hafta,
    SUM(CASE WHEN MONTH(cikis_tarihi) = '$ay' AND YEAR(cikis_tarihi) = '$yil' THEN 1 ELSE 0 END) as cikis_ay,
    SUM(CASE WHEN YEAR(cikis_tarihi) = '$yil' THEN 1 ELSE 0 END) as cikis_yil
FROM araclar WHERE durumu = 'CikisYapildi'";
$res_cikis = $conn->query($sql_cikis)->fetch_assoc();

$stats = [
    'bugun' => ['giris' => (int)$res_giris['giris_bugun'], 'cikis' => (int)$res_cikis['cikis_bugun']],
    'bu_hafta' => ['giris' => (int)$res_giris['giris_hafta'], 'cikis' => (int)$res_cikis['cikis_hafta']],
    'bu_ay' => ['giris' => (int)$res_giris['giris_ay'], 'cikis' => (int)$res_cikis['cikis_ay']],
    'bu_yil' => ['giris' => (int)$res_giris['giris_yil'], 'cikis' => (int)$res_cikis['cikis_yil']]
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kontrol Paneli - Yediemin Otopark Sistemi</title>
    <link rel="stylesheet" href="<?php echo $yol; ?>style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Hızlı Araçlar ve Hatırlatıcı Modalleri */
        .custom-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
        .custom-modal-box { background: #fff; padding: 20px; border-radius: 8px; width: 350px; max-width: 90%; box-shadow: 0 5px 15px rgba(0,0,0,0.3); position: relative; }
        .custom-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .custom-modal-header h4 { margin: 0; color: #333; }
        .close-custom-modal { cursor: pointer; color: #999; font-size: 18px; }
        .close-custom-modal:hover { color: #dc3545; }
        
        /* Hesap Makinesi */
        .calc-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; }
        .calc-btn { padding: 15px; font-size: 18px; border: 1px solid #ddd; background: #f8f9fa; cursor: pointer; border-radius: 4px; }
        .calc-btn:hover { background: #e2e6ea; }
        .calc-btn.op { background: #ffc107; color: #000; font-weight: bold; }
        .calc-btn.eq { background: #28a745; color: white; grid-column: span 2; }
        .calc-btn.clr { background: #dc3545; color: white; grid-column: span 2; }
        #calc-display { width: 100%; padding: 10px; font-size: 24px; text-align: right; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; background: #fdfdfd; }

        /* Not ve Hatırlatıcı Stilleri */
        .note-item { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin-bottom: 5px; font-size: 13px; position: relative; border-radius: 3px; }
        .reminder-item { background: #d1ecf1; padding: 10px; border-left: 4px solid #17a2b8; margin-bottom: 5px; font-size: 13px; position: relative; border-radius: 3px; }
        .reminder-item.due { background: #f8d7da; border-left-color: #dc3545; } /* Günü gelenler */
        .delete-item { position: absolute; top: 5px; right: 5px; color: #dc3545; cursor: pointer; font-size: 12px; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="main-content">
        
        <div class="dashboard-wrapper">

            <div class="dashboard-main">
                
                <div class="widget-group">
                    <div class="widget kapasite-kutusu bos">
                        <i class="fas fa-check-circle"></i>
                        <div><h4>Boş Kapasite</h4><span id="bos-kapasite"><?php echo $bos_yer_sayisi; ?></span></div>
                    </div>
                    <div class="widget kapasite-kutusu dolu">
                        <i class="fas fa-times-circle"></i>
                        <div><h4>Dolu Kapasite</h4><span id="dolu-kapasite"><?php echo $dolu_arac_sayisi; ?></span></div>
                    </div>
                    <div class="widget kapasite-kutusu toplam">
                        <i class="fas fa-parking"></i>
                        <div><h4>Toplam Kapasite</h4><span id="toplam-kapasite"><?php echo $toplam_kapasite; ?></span></div>
                    </div>
                </div>

                <div class="widget">
                    <h3 class="widget-title"><i class="fas fa-chart-line"></i> Araç Giriş/Çıkış İstatistikleri</h3>
                    <div class="widget-tabs">
                        <button class="tab-link active" onclick="filtrele('bugun')">Bugün</button>
                        <button class="tab-link" onclick="filtrele('bu_hafta')">Bu Hafta</button>
                        <button class="tab-link" onclick="filtrele('bu_ay')">Bu Ay</button>
                        <button class="tab-link" onclick="filtrele('bu_yil')">Bu Yıl</button>
                    </div>
                    <div class="widget-content" id="istatistik-icerik">
                        <div style="display:flex; justify-content:space-around; align-items:center; text-align:center; padding:10px;">
                            <div style="color:#28a745;">
                                <i class="fas fa-car-side" style="font-size:24px;"></i><br>
                                <span style="font-size:20px; font-weight:bold;"><?php echo $stats['bugun']['giris']; ?></span><br>
                                <span style="font-size:12px; text-transform:uppercase;">Giriş</span>
                            </div>
                            <div style="border-left:1px solid #eee; height:40px;"></div>
                            <div style="color:#dc3545;">
                                <i class="fas fa-sign-out-alt" style="font-size:24px;"></i><br>
                                <span style="font-size:20px; font-weight:bold;"><?php echo $stats['bugun']['cikis']; ?></span><br>
                                <span style="font-size:12px; text-transform:uppercase;">Çıkış</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="widget">
                    <h3 class="widget-title"><i class="fas fa-exclamation-triangle"></i> Plaka Kontrol</h3>
                    <p>Sistemde aynı plakayla birden fazla aktif giriş olup olmadığını kontrol edin.</p>
                    <button class="btn" id="btn-tekrarlayan-plaka"><i class="fas fa-search"></i> Tekrarlayan Plakaları Göster</button>
                    <div class="widget-content" id="tekrarlayan-plaka-icerik" style="display:none; margin-top: 15px;">
                    </div>
                </div>
            </div>

            <div class="dashboard-sidebar">
                <div class="widget widget-saat">
                    <div id="dijital-saat">00:00:00</div>
                    <div id="dijital-tarih">...</div>
                </div>
                <div class="widget">
                    <h3 class="widget-title"><i class="fas fa-bolt"></i> Hızlı Araçlar</h3>
                    <button class="btn btn-block" id="btn-not-ekle"><i class="fas fa-plus"></i> Hızlı Not Ekle</button>
                    <button class="btn btn-block" id="btn-tarih-hesaplayici"><i class="fas fa-calendar-alt"></i> Gün Hesaplayıcı</button>
                    <button class="btn btn-block" id="btn-hesap-makinesi"><i class="fas fa-calculator"></i> Hesap Makinesi</button>
                </div>
                <div class="widget">
                    <h3 class="widget-title"><i class="fas fa-bell"></i> Hatırlatıcı</h3>
                    <div class="widget-content" id="hatirlatici-listesi" style="max-height: 200px; overflow-y: auto;">
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <p>Aktif hatırlatıcı yok.</p>
                        </div>
                    </div>
                    <button class="btn btn-success btn-block" onclick="openModal('modal-hatirlatici')" style="margin-top: 15px;"><i class="fas fa-plus-circle"></i> Hatırlatıcı Ekle</button>
                </div>
                <div class="widget">
                    <h3 class="widget-title"><i class="fas fa-sticky-note"></i> Hızlı Notlar</h3>
                    <div class="widget-content" id="not-listesi" style="max-height: 200px; overflow-y: auto;">
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p>Kayıtlı not yok.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-not" class="custom-modal-overlay">
        <div class="custom-modal-box">
            <div class="custom-modal-header"><h4><i class="fas fa-sticky-note"></i> Hızlı Not Ekle</h4><span class="close-custom-modal" onclick="closeModal('modal-not')">&times;</span></div>
            <textarea id="hizli-not-text" rows="4" style="width:100%; border:1px solid #ddd; padding:10px; border-radius:4px; box-sizing:border-box;" placeholder="Notunuzu buraya yazın..."></textarea>
            <button class="btn btn-success btn-block" style="margin-top:10px;" onclick="notKaydet()">Kaydet</button>
        </div>
    </div>

    <div id="modal-hatirlatici" class="custom-modal-overlay">
        <div class="custom-modal-box">
            <div class="custom-modal-header"><h4><i class="fas fa-bell"></i> Hatırlatıcı Ekle</h4><span class="close-custom-modal" onclick="closeModal('modal-hatirlatici')">&times;</span></div>
            <label style="display:block; margin-bottom:5px;">Tarih:</label>
            <input type="date" id="hatirlatici-tarih" class="form-control" style="width:100%; padding:8px; margin-bottom:10px; border:1px solid #ddd; box-sizing:border-box;" value="<?php echo date('Y-m-d'); ?>">
            <label style="display:block; margin-bottom:5px;">Hatırlatma Notu:</label>
            <textarea id="hatirlatici-text" rows="3" style="width:100%; border:1px solid #ddd; padding:10px; border-radius:4px; box-sizing:border-box;" placeholder="Örn: 34ABC123 çıkışı yapılacak..."></textarea>
            <button class="btn btn-success btn-block" style="margin-top:10px;" onclick="hatirlaticiKaydet()">Ekle</button>
        </div>
    </div>

    <div id="modal-tarih" class="custom-modal-overlay">
        <div class="custom-modal-box">
            <div class="custom-modal-header"><h4><i class="fas fa-calendar-alt"></i> Gün Hesaplayıcı</h4><span class="close-custom-modal" onclick="closeModal('modal-tarih')">&times;</span></div>
            <label style="display:block; margin-bottom:5px;">Başlangıç Tarihi:</label>
            <input type="date" id="tarih-bas" class="form-control" style="width:100%; padding:8px; margin-bottom:10px; border:1px solid #ddd; box-sizing:border-box;">
            <label style="display:block; margin-bottom:5px;">Bitiş Tarihi:</label>
            <input type="date" id="tarih-bit" class="form-control" style="width:100%; padding:8px; margin-bottom:10px; border:1px solid #ddd; box-sizing:border-box;" value="<?php echo date('Y-m-d'); ?>">
            <button class="btn btn-primary btn-block" onclick="gunHesapla()">Hesapla</button>
            <div id="tarih-sonuc" style="margin-top:15px; text-align:center; font-weight:bold; font-size:16px; color:#007bff;"></div>
        </div>
    </div>

    <div id="modal-hesap" class="custom-modal-overlay">
        <div class="custom-modal-box">
            <div class="custom-modal-header"><h4><i class="fas fa-calculator"></i> Hesap Makinesi</h4><span class="close-custom-modal" onclick="closeModal('modal-hesap')">&times;</span></div>
            <input type="text" id="calc-display" readonly>
            <div class="calc-grid">
                <button class="calc-btn clr" onclick="calcClear()">C</button>
                <button class="calc-btn op" onclick="calcInput('/')">/</button>
                <button class="calc-btn op" onclick="calcInput('*')">x</button>
                <button class="calc-btn" onclick="calcInput('7')">7</button>
                <button class="calc-btn" onclick="calcInput('8')">8</button>
                <button class="calc-btn" onclick="calcInput('9')">9</button>
                <button class="calc-btn op" onclick="calcInput('-')">-</button>
                <button class="calc-btn" onclick="calcInput('4')">4</button>
                <button class="calc-btn" onclick="calcInput('5')">5</button>
                <button class="calc-btn" onclick="calcInput('6')">6</button>
                <button class="calc-btn op" onclick="calcInput('+')">+</button>
                <button class="calc-btn" onclick="calcInput('1')">1</button>
                <button class="calc-btn" onclick="calcInput('2')">2</button>
                <button class="calc-btn" onclick="calcInput('3')">3</button>
                <button class="calc-btn eq" onclick="calcResult()">=</button>
                <button class="calc-btn" onclick="calcInput('0')" style="grid-column: span 2;">0</button>
            </div>
        </div>
    </div>

    <script>
        const istatistikData = <?php echo json_encode($stats); ?>;

        function guncelZaman() {
            const saatElementi = document.getElementById('dijital-saat');
            const tarihElementi = document.getElementById('dijital-tarih');
            const simdi = new Date();
            const saat = simdi.getHours().toString().padStart(2, '0');
            const dakika = simdi.getMinutes().toString().padStart(2, '0');
            const saniye = simdi.getSeconds().toString().padStart(2, '0');
            saatElementi.textContent = `${saat}:${dakika}:${saniye}`;
            if (tarihElementi.textContent === '...') {
                const gun = simdi.getDate().toString().padStart(2, '0');
                const ay = (simdi.getMonth() + 1).toString().padStart(2, '0');
                const yil = simdi.getFullYear();
                const gunAdi = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'][simdi.getDay()];
                tarihElementi.textContent = `${gun}.${ay}.${yil} - ${gunAdi}`;
            }
        }
        setInterval(guncelZaman, 1000);
        document.addEventListener('DOMContentLoaded', function() {
            guncelZaman();
            loadNotes();
            loadReminders(); // Hatırlatıcıları Yükle
        });

        function filtrele(zamanAraligi) {
            const data = istatistikData[zamanAraligi];
            const icerik = document.getElementById('istatistik-icerik');
            const html = `
                <div style="display:flex; justify-content:space-around; align-items:center; text-align:center; padding:10px;">
                    <div style="color:#28a745;">
                        <i class="fas fa-car-side" style="font-size:24px;"></i><br>
                        <span style="font-size:20px; font-weight:bold;">${data.giris}</span><br>
                        <span style="font-size:12px; text-transform:uppercase;">Giriş</span>
                    </div>
                    <div style="border-left:1px solid #eee; height:40px;"></div>
                    <div style="color:#dc3545;">
                        <i class="fas fa-sign-out-alt" style="font-size:24px;"></i><br>
                        <span style="font-size:20px; font-weight:bold;">${data.cikis}</span><br>
                        <span style="font-size:12px; text-transform:uppercase;">Çıkış</span>
                    </div>
                </div>
            `;
            icerik.innerHTML = html;
            document.querySelectorAll('.widget-tabs .tab-link').forEach(btn => { btn.classList.remove('active'); });
            event.target.classList.add('active');
        }

        // TEKRARLAYAN PLAKA KONTROLÜ
        document.getElementById('btn-tekrarlayan-plaka').addEventListener('click', function() {
            const container = document.getElementById('tekrarlayan-plaka-icerik');
            const btn = this;
            if(container.style.display === 'block') {
                container.style.display = 'none';
                btn.innerHTML = '<i class="fas fa-search"></i> Tekrarlayan Plakaları Göster';
                return;
            }
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kontrol Ediliyor...';
            const formData = new FormData();
            formData.append('ajax_action', 'get_tekrarlayan_plakalar');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                container.style.display = 'block';
                btn.innerHTML = '<i class="fas fa-search"></i> Tekrarlayan Plakaları Gizle';
                if (data.data.length > 0) {
                    let html = '<table style="width:100%; font-size:13px; border-collapse:collapse;">';
                    html += '<tr style="background:#f8d7da; color:#721c24;"><th>Plaka</th><th style="text-align:center;">Aktif Kayıt Sayısı</th></tr>';
                    data.data.forEach(item => {
                        html += `<tr><td style="border:1px solid #ddd; padding:8px;"><strong>${item.plaka}</strong></td><td style="border:1px solid #ddd; padding:8px; text-align:center; font-weight:bold; color:red;">${item.adet}</td></tr>`;
                    });
                    html += '</table>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div style="padding:15px; background:#d4edda; color:#155724; border-radius:4px; text-align:center; border:1px solid #c3e6cb;"><i class="fas fa-check-circle"></i> Sorun Yok: Sistemde aynı anda birden fazla girişi olan plaka bulunamadı.</div>';
                }
            })
            .catch(error => { console.error('Hata:', error); btn.innerHTML = '<i class="fas fa-search"></i> Tekrar Dene'; });
        });

        // --- HIZLI ARAÇLAR VE HATIRLATICI FONKSİYONLARI ---

        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }

        // Buton Dinleyicileri
        document.getElementById('btn-not-ekle').addEventListener('click', () => openModal('modal-not'));
        document.getElementById('btn-tarih-hesaplayici').addEventListener('click', () => openModal('modal-tarih'));
        document.getElementById('btn-hesap-makinesi').addEventListener('click', () => openModal('modal-hesap'));

        // 1. NOT SİSTEMİ
        function loadNotes() {
            const list = document.getElementById('not-listesi');
            const notes = JSON.parse(localStorage.getItem('hizliNotlar')) || [];
            list.innerHTML = '';
            if(notes.length === 0) {
                list.innerHTML = '<div class="empty-state"><i class="fas fa-file-alt"></i><p>Kayıtlı not yok.</p></div>';
            } else {
                notes.forEach((note, index) => {
                    const div = document.createElement('div');
                    div.className = 'note-item';
                    div.innerHTML = `${note} <i class="fas fa-times delete-item" onclick="deleteNote(${index})"></i>`;
                    list.appendChild(div);
                });
            }
        }
        function notKaydet() {
            const txt = document.getElementById('hizli-not-text').value.trim();
            if(txt) {
                const notes = JSON.parse(localStorage.getItem('hizliNotlar')) || [];
                notes.unshift(txt); 
                localStorage.setItem('hizliNotlar', JSON.stringify(notes));
                document.getElementById('hizli-not-text').value = '';
                loadNotes();
                closeModal('modal-not');
            }
        }
        function deleteNote(index) {
            const notes = JSON.parse(localStorage.getItem('hizliNotlar')) || [];
            notes.splice(index, 1);
            localStorage.setItem('hizliNotlar', JSON.stringify(notes));
            loadNotes();
        }

        // 2. HATIRLATICI SİSTEMİ (YENİ)
        function loadReminders() {
            const list = document.getElementById('hatirlatici-listesi');
            const reminders = JSON.parse(localStorage.getItem('hatirlaticilar')) || [];
            list.innerHTML = '';
            
            // Tarihe göre sırala
            reminders.sort((a, b) => new Date(a.date) - new Date(b.date));

            if(reminders.length === 0) {
                list.innerHTML = '<div class="empty-state"><i class="fas fa-bell-slash"></i><p>Aktif hatırlatıcı yok.</p></div>';
            } else {
                const today = new Date().toISOString().split('T')[0];
                reminders.forEach((rem, index) => {
                    const isDue = rem.date <= today;
                    const div = document.createElement('div');
                    div.className = 'reminder-item' + (isDue ? ' due' : '');
                    
                    // Tarih Formatı (DD.MM.YYYY)
                    const d = new Date(rem.date);
                    const formattedDate = d.getDate().toString().padStart(2, '0') + '.' + (d.getMonth()+1).toString().padStart(2, '0') + '.' + d.getFullYear();

                    div.innerHTML = `<strong>${formattedDate}</strong>: ${rem.text} <i class="fas fa-times delete-item" onclick="deleteReminder(${index})"></i>`;
                    list.appendChild(div);
                });
            }
        }
        function hatirlaticiKaydet() {
            const date = document.getElementById('hatirlatici-tarih').value;
            const text = document.getElementById('hatirlatici-text').value.trim();
            if(date && text) {
                const reminders = JSON.parse(localStorage.getItem('hatirlaticilar')) || [];
                reminders.push({ date: date, text: text });
                localStorage.setItem('hatirlaticilar', JSON.stringify(reminders));
                document.getElementById('hatirlatici-text').value = '';
                loadReminders();
                closeModal('modal-hatirlatici');
            } else {
                alert("Lütfen tarih ve not giriniz.");
            }
        }
        function deleteReminder(index) {
            const reminders = JSON.parse(localStorage.getItem('hatirlaticilar')) || [];
            reminders.splice(index, 1); // Sıralı listeden silmek için index uyuşmayabilir ama localStorage'dan yüklenip sıralandığı için render sırası ile data sırası aynı olmalı. Daha güvenli olması için ID eklenebilir ama basit yapı için bu yeterli.
            // Not: sort ettiğimiz için index kayabilir. O yüzden önce sort edip sonra silmek lazım veya ID kullanmak lazım. 
            // Düzeltme: Render ederken sıraladığımız için splice yanlış elemanı silebilir.
            // Doğrusu: Kayıt ederken veya çekerken sıralayıp tekrar kaydetmek.
            // Basitlik adına: Silme işleminde array'i tekrar sıralayıp öyle silelim.
            reminders.sort((a, b) => new Date(a.date) - new Date(b.date));
            reminders.splice(index, 1);
            localStorage.setItem('hatirlaticilar', JSON.stringify(reminders));
            loadReminders();
        }

        // 3. TARİH HESAPLAYICI
        function gunHesapla() {
            const d1 = new Date(document.getElementById('tarih-bas').value);
            const d2 = new Date(document.getElementById('tarih-bit').value);
            if(isNaN(d1) || isNaN(d2)) {
                document.getElementById('tarih-sonuc').innerText = "Lütfen tarih seçiniz.";
                return;
            }
            const diffTime = Math.abs(d2 - d1);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
            document.getElementById('tarih-sonuc').innerText = diffDays + " Gün";
        }

        // 4. HESAP MAKİNESİ
        let calcVal = "";
        function calcInput(v) {
            calcVal += v;
            document.getElementById('calc-display').value = calcVal;
        }
        function calcClear() {
            calcVal = "";
            document.getElementById('calc-display').value = "";
        }
        function calcResult() {
            try {
                if(/^[0-9+\-*/.]+$/.test(calcVal)) {
                    calcVal = eval(calcVal);
                    document.getElementById('calc-display').value = calcVal;
                }
            } catch (e) {
                document.getElementById('calc-display').value = "Hata";
                calcVal = "";
            }
        }
    </script>

</body>
</html>