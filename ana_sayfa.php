<?php
// Oturumu başlat
session_start();

// Kullanıcı giriş yapmamışsa, login sayfasına geri yönlendir
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kontrol Paneli - Yediemin Otopark Sistemi</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <header class="main-header">
        <div class="header-container">
            <h1 class="header-title"><a href="ana_sayfa.php">Yediemin Otopark</a></h1>
            
            <nav class="main-nav">
                <ul>
                    <li class="dropdown">
                        <a href="#">Araç Kayıt İşlemleri</a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">Kayıt İşlemleri</li>
                            <li><a href="arac_kayit_islemleri/arac_kayit_detayli.php">Araç Kayıt (Detaylı)</a></li>
                            <li><a href="arac_kayit_islemleri/arac_kayit_hizli.php">Hızlı Araç Kayıt (Detaysız)</a></li>
                            <li><a href="arac_kayit_islemleri/arac_sorgula.php">Araç Sorgula</a></li>
                            <li class="dropdown-divider"></li>
                            <li class="dropdown-header">Raporlar</li>
                            <li><a href="arac_kayit_islemleri/arac_analiz_raporu.php">Araç Analiz Raporları</a></li>
                            <li><a href="arac_kayit_islemleri/hacizli_arac_analiz_raporu.php">Hacizli Araç Analiz Raporları</a></li>
                            <li class="dropdown-divider"></li>
                            <li class="dropdown-header">Hasar Durum Formu</li>
                            <li><a href="arac_kayit_islemleri/bos_arac_hasar_formu.php">Boş Araç Hasar Durum Formu</a></li>
                        </ul>
                    </li>

                    <li><a href="personeller.php">Personeller</a></li> <li class="dropdown">
                        <a href="#">Gelir Kayıtları</a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">Kayıt İşlemleri</li>
                            <li><a href="gelir_kayitlari/extra_gelirler.php">Extra Gelirler</a></li>
                            <li class="dropdown-divider"></li>
                            <li class="dropdown-header">Raporlar</li>
                            <li><a href="gelir_kayitlari/gelirler_analiz_raporu.php">Gelirler Analiz Raporu</a></li>
                        </ul>
                    </li>
                    
                    <li class="dropdown">
                        <a href="#">Gider Kayıtları</a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">Kayıt İşlemleri</li>
                            <li><a href="gider_kayitlari/gider_kayit.php">Gider Kayıt</a></li>
                            <li class="dropdown-divider"></li>
                            <li class="dropdown-header">Raporlar</li>
                            <li><a href="gider_kayitlari/gider_raporu_yazdir.php">Gider Raporları & Yazdır</a></li>
                        </ul>
                    </li>

                    <li><a href="kasa.php">Kasa</a></li> <li><a href="raporlar.php">Grafiksel Raporlama</a></li> <li class="dropdown">
                        <a href="#">Veritabanı İşlemleri</a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">Veritabanı Yedekleme ve Geri Yükleme</li>
                            <li><a href="veritabani_islemleri/db_yedekle_geriyukle.php">Veritabanı Yedekle & Geri Yükle</a></li>
                            <li class="dropdown-divider"></li>
                            <li class="dropdown-header">Veritabanı Toplu Veri Silme İşlemleri</li>
                            <li><a href="veritabani_islemleri/db_toplu_veri_silme.php">Toplu Veri Silme İşlemleri</a></li>
                        </ul>
                    </li>

                    <li><a href="makbuz.php">Makbuz</a></li> <li class="dropdown">
                        <a href="#">Excel Transfer İşlemleri</a>
                        <ul class="dropdown-menu">
                            <li><a href="excel_islemleri/excel_veri_aktar.php">Veritabanından Excel'e Aktar</a></li>
                            <li><a href="excel_islemleri/excel_veri_al.php">Excel'den Veritabanına Aktar</a></li>
                        </ul>
                    </li>
                    
                    <li class="dropdown">
                        <a href="#">Yazıcı Çıktısı Tasarlama</a>
                        <ul class="dropdown-menu" id="yazici-menu">
                            <li class="dropdown-header">Araç Sorgulama Raporları</li>
                            <li><a href="yazici_tasarim/tasarim_giris_cikis_raporu.php">Araç Giriş-Çıkış Rapor Tasarımı</a></li>
                            <li class="dropdown-divider"></li>
                            <li class="dropdown-header">Gelirler-Giderler</li>
                            <li><a href="yazici_tasarim/tasarim_gelirler_raporu.php">Gelirler Rapor Tasarımı</a></li>
                            <li><a href="yazici_tasarim/tasarim_giderler_raporu.php">Giderler Rapor Tasarımı</a></li>
                            <li class="dropdown-divider"></li>
                            <li class="dropdown-header">Hasar Formu</li>
                            <li><a href="yazici_tasarim/tasarim_hasar_tespit_formu.php">Araç Hasar Tespit Formu Tasarımı</a></li>
                            <li><a href="yazici_tasarim/tasarim_hasar_durum_formu.php">Araç Hasar Durum Formu Tasarımı</a></li>
                            <li class="dropdown-divider"></li>
                            <li class="dropdown-header">Kasa Raporu</li>
                            <li><a href="yazici_tasarim/tasarim_kasa_raporu.php">Kasa Raporu Şablonu</a></li>
                            <li><a href="yazici_tasarim/tasarim_para_makbuzu.php">Para Makbuzu Tasarımı</a></li>
                            <li><a href="yazici_tasarim/tasarim_kasa_analiz_raporu.php">Kasa Tutar Analiz Raporu</a></li>
                            <li class="dropdown-divider"></li>
                            <li class="dropdown-header">Personel Raporları</li>
                            <li><a href="yazici_tasarim/tasarim_personel_listesi.php">Personel Listesi Rapor Tasarımı</a></li>
                            <li><a href="yazici_tasarim/tasarim_personel_bilgileri.php">Personel Bilgileri Rapor Tasarımı</a></li>
                            <li><a href="yazici_tasarim/tasarim_personel_odemeleri.php">Personel Ödemeleri Rapor Tasarımı</a></li>
                            <li class="dropdown-divider"></li>
                            <li class="dropdown-header">Tutanaklar</li>
                            <li><a href="yazici_tasarim/tasarim_arac_teslim_tutanagi.php">Araç Teslim Tutanağı</a></li>
                        </ul>
                    </li>
                    
                    <li class="dropdown">
                        <a href="#">Tanımlar</a>
                        <ul class="dropdown-menu">
                            <li><a href="tanimlar/tanim_firma_bilgileri.php">Firma Bilgileriniz</a></li>
                            <li><a href="tanimlar/tanim_marka_model.php">Araç Marka & Model Tanımları</a></li>
                            <li><a href="tanimlar/tanim_men_nedeni.php">Men Nedeni Tanımlama</a></li>
                            <li><a href="tanimlar/tanim_arac_cins_ucret.php">Araç Cins & Ücret Tanımları</a></li>
                            <li><a href="tanimlar/tanim_teslim_eden_kurum.php">Araç Teslim Eden Kurumlar</a></li>
                            <li><a href="tanimlar/tanim_haciz_yetkili_kurum.php">Haciz Yetkisine Sahip Kurumlar</a></li>
                            <li><a href="tanimlar/tanim_cekici_firma.php">Çekici Firma Tanımları</a></li>
                            <li><a href="tanimlar/guncelle_cekici_ucret.php">Çekici Ücretlerini Güncelle</a></li>
                            <li><a href="tanimlar/tanim_gelir_gider.php">Gelir & Gider Tanımları</a></li>
                            <li><a href="tanimlar/tanim_ada_parsel.php">Ada - Parsel Tanımları</a></li>
                        </ul>
                    </li>
                    
                    <li class="dropdown">
                        <a href="#">Kullanıcı Tanımları</a>
                        <ul class="dropdown-menu">
                            <li><a href="kullanici_tanimlari/kullanici_tanimlama.php">Kullanıcı Tanımlama Formu</a></li>
                            <li><a href="kullanici_tanimlari/kullanici_yetkileri.php">Kullanıcı Yetkileri Tanımlama Formu</a></li>
                        </ul>
                    </li>
                    
                    <li class="dropdown">
                        <a href="#">Ayarlar</a>
                        <ul class="dropdown-menu">
                            <li><a href="ayarlar/ayarlar_program.php">Program Ayarları</a></li>
                            <li><a href="ayarlar/hakkinda.php">Hakkında</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
            <div class="user-info">
                <span class="welcome-message">Hoş Geldiniz, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong>!</span>
                <a href="cikis.php" class="logout-link">Çıkış</a>
            </div>
        </div>
    </header>

    <div class="main-content">
        
        <div class="dashboard-wrapper">

            <div class="dashboard-main">
                
                <div class="widget-group">
                    <div class="widget kapasite-kutusu bos">
                        <i class="fas fa-check-circle"></i>
                        <div><h4>Boş Kapasite</h4><span id="bos-kapasite">120</span></div>
                    </div>
                    <div class="widget kapasite-kutusu dolu">
                        <i class="fas fa-times-circle"></i>
                        <div><h4>Dolu Kapasite</h4><span id="dolu-kapasite">80</span></div>
                    </div>
                    <div class="widget kapasite-kutusu toplam">
                        <i class="fas fa-parking"></i>
                        <div><h4>Toplam Kapasite</h4><span id="toplam-kapasite">200</span></div>
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
                        <p>Bugün için veriler buraya yüklenecek...</p>
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
                    <div class="widget-content" id="hatirlatici-listesi">
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <p>Aktif hatırlatıcı yok.</p>
                        </div>
                    </div>
                    <button class="btn btn-success btn-block" id="btn-hatirlatici-ekle" style="margin-top: 15px;"><i class="fas fa-plus-circle"></i> Hatırlatıcı Ekle</button>
                </div>
                <div class="widget">
                    <h3 class="widget-title"><i class="fas fa-sticky-note"></i> Hızlı Notlar</h3>
                    <div class="widget-content" id="not-listesi">
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p>Kayıtlı not yok.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
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
        document.addEventListener('DOMContentLoaded', guncelZaman);

        function filtrele(zamanAraligi) {
            const icerik = document.getElementById('istatistik-icerik');
            icerik.innerHTML = `<p><b>${zamanAraligi}</b> için veriler buraya yüklenecek...</p>`;
            document.querySelectorAll('.widget-tabs .tab-link').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Diğer butonlar için geçici uyarılar
        document.getElementById('btn-tekrarlayan-plaka').addEventListener('click', () => alert('Bu özellik yakında eklenecek.'));
        document.getElementById('btn-not-ekle').addEventListener('click', () => alert('Bu özellik yakında eklenecek.'));
        document.getElementById('btn-tarih-hesaplayici').addEventListener('click', () => alert('Bu özellik yakında eklenecek.'));
        document.getElementById('btn-hesap-makinesi').addEventListener('click', () => alert('Bu özellik yakında eklenecek.'));
        document.getElementById('btn-hatirlatici-ekle').addEventListener('click', () => alert('Bu özellik yakında eklenecek.'));
    </script>

</body>
</html>