<?php
// ============================================================================
// 1. VERİTABANI VE AYARLARI YÜKLE
// ============================================================================

if (!isset($conn)) {
    $db_dosya_yolu = (isset($yol) ? $yol : '') . 'db_config.php';
    if (file_exists($db_dosya_yolu)) {
        require_once $db_dosya_yolu;
    }
}

// Varsayılan Ayarlar (Veritabanı bağlantısı yoksa kullanılır)
$genel_ayarlar = [
    'firma_unvan' => 'Yediemin Otopark',
    'site_tema' => 'blue',
    'bakim_modu' => 0,
    'liste_satir_sayisi' => 20
];

if (isset($conn)) {
    // 1. Firma Bilgisini Çek (Eski tablodan)
    $sql_h = "SELECT firma_adi FROM firma_bilgileri WHERE id = 1";
    if ($res_h = @$conn->query($sql_h)) {
        if ($row_h = $res_h->fetch_assoc()) {
            if (!empty($row_h['firma_adi'])) {
                $genel_ayarlar['firma_unvan'] = $row_h['firma_adi'];
            }
        }
    }

    // 2. Program Ayarlarını Çek (Yeni tablodan)
    $sql_ayar = "SELECT * FROM program_ayarlari WHERE id = 1";
    if ($res_ayar = @$conn->query($sql_ayar)) {
        if ($row_ayar = $res_ayar->fetch_assoc()) {
            $genel_ayarlar['site_tema'] = $row_ayar['site_tema'];
            $genel_ayarlar['bakim_modu'] = $row_ayar['bakim_modu'];
            $genel_ayarlar['liste_satir_sayisi'] = $row_ayar['liste_satir_sayisi'];
        }
    }
}

// ============================================================================
// 2. BAKIM MODU KONTROLÜ
// ============================================================================
if ($genel_ayarlar['bakim_modu'] == 1) {
    $is_admin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);
    if (!$is_admin) {
        if (basename($_SERVER['PHP_SELF']) != 'login.php') {
            ?>
            <!DOCTYPE html>
            <html lang="tr">
            <head>
                <meta charset="UTF-8">
                <title>Sistem Bakımda</title>
                <style>
                    body { font-family: sans-serif; background: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                    .maintenance-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; max-width: 500px; }
                    h1 { color: #dc3545; margin-top: 0; }
                    p { color: #555; line-height: 1.6; }
                    a { color: #007bff; text-decoration: none; font-weight: bold; }
                </style>
            </head>
            <body>
                <div class="maintenance-box">
                    <h1><i class="fas fa-tools"></i> Sistem Bakımda</h1>
                    <p>Şu anda sistem üzerinde bakım çalışması yapılmaktadır. Lütfen daha sonra tekrar deneyiniz.</p>
                    <p>Yönetici iseniz <a href="<?php echo $yol; ?>login.php">Giriş Yapabilirsiniz</a>.</p>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}

// ============================================================================
// 3. TEMA RENKLERİNİ AYARLA
// ============================================================================
$tema_renkleri = [
    'blue'  => '#2196F3',
    'red'   => '#dc3545',
    'green' => '#28a745',
    'dark'  => '#343a40'
];
$ana_renk = isset($tema_renkleri[$genel_ayarlar['site_tema']]) ? $tema_renkleri[$genel_ayarlar['site_tema']] : $tema_renkleri['blue'];

// ============================================================================
// 4. YETKİ KONTROL FONKSİYONU
// ============================================================================
function yetkiVar($kod) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        return true;
    }
    if (isset($_SESSION['yetkiler']) && !empty($_SESSION['yetkiler'])) {
        $yetkiler = is_string($_SESSION['yetkiler']) ? json_decode($_SESSION['yetkiler'], true) : $_SESSION['yetkiler'];
        if (is_array($yetkiler) && in_array($kod, $yetkiler)) {
            return true;
        }
    }
    return false;
}
?>

<style>
    /* CSS TASARIM */
    *, *::before, *::after { box-sizing: border-box; }
    
    :root {
        --ana-renk: <?php echo $ana_renk; ?>;
    }

    .main-header { 
        background-color: #fff; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        display: flex; 
        flex-direction: column; 
        width: 100%; 
        position: relative; 
        z-index: 1000; 
    }
    
    .header-top-row { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 5px 25px; 
        border-bottom: 1px solid #f0f0f0; 
        height: 55px; 
        background: #fff; 
    }
    
    .header-title { 
        font-size: 20px !important; 
        font-weight: 700 !important; 
        color: #333; 
        margin: 0; 
        line-height: 1; 
        letter-spacing: 0.5px; 
        text-transform: uppercase; 
        white-space: nowrap; 
    }
    .header-title a { text-decoration: none; color: inherit; }
    
    .user-info { 
        display: flex; 
        align-items: center; 
        gap: 15px; 
        font-size: 13px; 
        color: #555; 
        white-space: nowrap; 
    }
    
    .logout-link { 
        background-color: #dc3545; 
        color: white !important; 
        padding: 5px 12px; 
        border-radius: 4px; 
        text-decoration: none; 
        font-weight: 600; 
        font-size: 12px; 
        transition: background 0.2s; 
    }
    .logout-link:hover { background-color: #c82333; }
    
    .header-bottom-row { 
        background-color: #f8f9fa; 
        padding: 0 25px; 
        border-bottom: 1px solid #e0e0e0; 
        position: relative; 
    }
    
    .main-nav { width: 100%; }
    
    .main-nav ul { 
        display: flex; 
        flex-wrap: wrap; 
        align-items: center;    
        list-style: none; 
        margin: 0; 
        padding: 0; 
    }
    
    .main-nav ul li { position: relative; }
    
    .main-nav ul li a { 
        display: block; 
        padding: 12px 6px; 
        text-decoration: none; 
        color: #444; 
        font-weight: 500; 
        font-size: 13px; 
        transition: all 0.2s; 
        border-bottom: 2px solid transparent; 
        white-space: nowrap; 
    }
    
    .main-nav ul li a:hover, 
    .main-nav ul li a.active, 
    .main-nav ul li:hover > a { 
        color: var(--ana-renk); 
        background-color: #fff; 
        border-bottom: 2px solid var(--ana-renk); 
    }
    
    /* Dropdown Menü Stilleri */
    .dropdown-menu { 
        display: none; 
        position: absolute; 
        top: 100%; 
        left: 0; 
        background-color: #fff; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.15); 
        border: 1px solid #eee; 
        border-top: 2px solid var(--ana-renk);
        min-width: 230px; 
        z-index: 9999; 
        border-radius: 0 0 4px 4px; 
        padding: 5px 0; 
        margin-top: 0; 
    }
    
    .dropdown:hover .dropdown-menu { display: block; }
    .dropdown-menu li { width: 100%; display: block; }
    
    .dropdown-menu li a { 
        padding: 8px 15px; 
        font-size: 12px; 
        border-bottom: none !important; 
        color: #333; 
        white-space: normal; 
        background: transparent; 
    }
    
    .dropdown-menu li a:hover { 
        background-color: #f1f1f1; 
        color: #000; 
        border-left: 3px solid var(--ana-renk);
    }
    
    .dropdown-header { 
        padding: 8px 15px; 
        font-size: 11px; 
        font-weight: bold; 
        color: #888; 
        text-transform: uppercase; 
        pointer-events: none; 
        background-color: #f9f9f9; 
        margin-bottom: 2px; 
    }
    
    .dropdown-divider { 
        height: 1px; 
        background-color: #eee; 
        margin: 4px 0; 
    }
</style>

<header class="main-header">
    
    <div class="header-top-row">
        <div class="logo-area">
            <h1 class="header-title">
                <a href="<?php echo $yol; ?>index.php">
                    <?php echo htmlspecialchars($genel_ayarlar['firma_unvan']); ?>
                </a>
            </h1>
        </div>
        
        <div class="user-info">
            <span class="welcome-message">
                Hoş Geldiniz, <strong><?php echo isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : 'Misafir'; ?></strong>!
                <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <span style="background:var(--ana-renk); color:white; padding:2px 5px; border-radius:3px; font-size:10px;">YÖNETİCİ</span>
                <?php endif; ?>
            </span>
            <a href="<?php echo $yol; ?>cikis.php" class="logout-link">
                <i class="fas fa-power-off"></i> Çıkış
            </a>
        </div>
    </div>

    <div class="header-bottom-row">
        <nav class="main-nav">
            <ul>
                <?php if(yetkiVar('arac_kayit_detayli') || yetkiVar('arac_kayit_hizli') || yetkiVar('arac_sorgula') || yetkiVar('arac_analiz') || yetkiVar('hacizli_analiz') || yetkiVar('hasar_formu') || yetkiVar('teslim_tutanagi')): ?>
                <li class="dropdown">
                    <a href="#" class="<?php echo ($menu == 'arac_kayit') ? 'active' : ''; ?>">Araç Kayıt İşlemleri <i class="fas fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Kayıt İşlemleri</li>
                        <?php if(yetkiVar('arac_kayit_detayli')): ?><li><a href="<?php echo $yol; ?>arac_kayit_islemleri/arac_kayit_detayli.php">Araç Kayıt (Detaylı)</a></li><?php endif; ?>
                        <?php if(yetkiVar('arac_kayit_hizli')): ?><li><a href="<?php echo $yol; ?>arac_kayit_islemleri/arac_kayit_hizli.php">Hızlı Araç Kayıt (Detaysız)</a></li><?php endif; ?>
                        <?php if(yetkiVar('arac_sorgula')): ?><li><a href="<?php echo $yol; ?>arac_kayit_islemleri/arac_sorgula.php">Araç Sorgulama</a></li><?php endif; ?>
                        
                        <li class="dropdown-divider"></li>
                        <li class="dropdown-header">Raporlar</li>
                        <?php if(yetkiVar('arac_analiz')): ?><li><a href="<?php echo $yol; ?>arac_kayit_islemleri/arac_analiz_raporu.php">Araç Analiz Raporları</a></li><?php endif; ?>
                        <?php if(yetkiVar('hacizli_analiz')): ?><li><a href="<?php echo $yol; ?>arac_kayit_islemleri/hacizli_arac_analiz_raporu.php">Hacizli Araç Analiz Raporları</a></li><?php endif; ?>
                        
                        <li class="dropdown-divider"></li>
                        <li class="dropdown-header">Formlar</li>
                        <?php if(yetkiVar('hasar_formu')): ?><li><a href="<?php echo $yol; ?>arac_kayit_islemleri/bos_arac_hasar_formu.php">Boş Araç Hasar Durum Formu</a></li><?php endif; ?>
                        <?php if(yetkiVar('teslim_tutanagi')): ?><li><a href="<?php echo $yol; ?>arac_kayit_islemleri/arac_teslim_tutanagi.php">Araç Teslim Tutanağı</a></li><?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if(yetkiVar('personel_listesi')): ?>
                <li><a href="<?php echo $yol; ?>personeller.php" class="<?php echo ($menu == 'personel') ? 'active' : ''; ?>">Personeller</a></li>
                <?php endif; ?>

                <?php if(yetkiVar('gelir_extra') || yetkiVar('gelir_analiz')): ?>
                <li class="dropdown">
                    <a href="#" class="<?php echo ($menu == 'gelir') ? 'active' : ''; ?>">Gelir Kayıtları <i class="fas fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Kayıt İşlemleri</li>
                        <?php if(yetkiVar('gelir_extra')): ?><li><a href="<?php echo $yol; ?>gelir_kayitlari/extra_gelirler.php">Extra Gelirler</a></li><?php endif; ?>
                        <li class="dropdown-divider"></li>
                        <li class="dropdown-header">Raporlar</li>
                        <?php if(yetkiVar('gelir_analiz')): ?><li><a href="<?php echo $yol; ?>gelir_kayitlari/gelirler_analiz_raporu.php">Gelirler Analiz Raporu</a></li><?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if(yetkiVar('gider_kayit') || yetkiVar('gider_rapor')): ?>
                <li class="dropdown">
                    <a href="#" class="<?php echo ($menu == 'gider') ? 'active' : ''; ?>">Gider Kayıtları <i class="fas fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Kayıt İşlemleri</li>
                        <?php if(yetkiVar('gider_kayit')): ?><li><a href="<?php echo $yol; ?>gider_kayitlari/gider_kayit.php">Gider Kayıt</a></li><?php endif; ?>
                        <li class="dropdown-divider"></li>
                        <li class="dropdown-header">Raporlar</li>
                        <?php if(yetkiVar('gider_rapor')): ?><li><a href="<?php echo $yol; ?>gider_kayitlari/gider_raporu_yazdir.php">Gider Raporları & Yazdır</a></li><?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if(yetkiVar('kasa')): ?>
                <li><a href="<?php echo $yol; ?>kasa.php" class="<?php echo ($menu == 'kasa') ? 'active' : ''; ?>">Kasa</a></li>
                <?php endif; ?>

                <?php if(yetkiVar('makbuz')): ?>
                <li><a href="<?php echo $yol; ?>makbuz.php" class="<?php echo ($menu == 'makbuz') ? 'active' : ''; ?>">Makbuz</a></li>
                <?php endif; ?>

                <?php if(yetkiVar('grafik_rapor')): ?>
                <li><a href="<?php echo $yol; ?>raporlar.php" class="<?php echo ($menu == 'rapor') ? 'active' : ''; ?>">Grafiksel Raporlama</a></li>
                <?php endif; ?>

                <?php if(yetkiVar('veritabani')): ?>
                <li><a href="<?php echo $yol; ?>veritabani.php" class="<?php echo ($menu == 'db') ? 'active' : ''; ?>">Veritabanı</a></li>
                <?php endif; ?>

                <?php if(yetkiVar('tasarim_giris_cikis') || yetkiVar('tasarim_hacizli') || yetkiVar('tasarim_gelir') || yetkiVar('tasarim_gider') || yetkiVar('tasarim_kasa') || yetkiVar('tasarim_makbuz') || yetkiVar('tasarim_personel_list') || yetkiVar('tasarim_personel_bilgi') || yetkiVar('tasarim_personel_odeme')): ?>
                <li class="dropdown">
                    <a href="#" class="<?php echo ($menu == 'yazici') ? 'active' : ''; ?>">Yazıcı Tasarım <i class="fas fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Araç Sorgulama Raporları</li>
                        <?php if(yetkiVar('tasarim_giris_cikis')): ?><li><a href="<?php echo $yol; ?>yazici_tasarim/tasarim_giris_cikis_raporu.php">Araç Giriş-Çıkış Rapor Tasarımı</a></li><?php endif; ?>
                        <?php if(yetkiVar('tasarim_hacizli')): ?><li><a href="<?php echo $yol; ?>yazici_tasarim/tasarim_hacizli_arac_analiz_raporu.php">Hacizli Araç Analiz Rapor Tasarımı</a></li><?php endif; ?>
                        
                        <li class="dropdown-divider"></li>
                        <li class="dropdown-header">Gelirler-Giderler</li>
                        <?php if(yetkiVar('tasarim_gelir')): ?><li><a href="<?php echo $yol; ?>yazici_tasarim/tasarim_gelirler_raporu.php">Gelirler Rapor Tasarımı</a></li><?php endif; ?>
                        <?php if(yetkiVar('tasarim_gider')): ?><li><a href="<?php echo $yol; ?>yazici_tasarim/tasarim_giderler_raporu.php">Giderler Rapor Tasarımı</a></li><?php endif; ?>
                        
                        <li class="dropdown-divider"></li>
                        <li class="dropdown-header">Kasa Raporu</li>
                        <?php if(yetkiVar('tasarim_kasa')): ?><li><a href="<?php echo $yol; ?>yazici_tasarim/tasarim_kasa_raporu.php">Kasa Raporu Şablonu</a></li><?php endif; ?>
                        <?php if(yetkiVar('tasarim_makbuz')): ?><li><a href="<?php echo $yol; ?>yazici_tasarim/tasarim_para_makbuzu.php">Para Makbuzu Tasarımı</a></li><?php endif; ?>
                        
                        <li class="dropdown-divider"></li>
                        <li class="dropdown-header">Personel Raporları</li>
                        <?php if(yetkiVar('tasarim_personel_list')): ?><li><a href="<?php echo $yol; ?>yazici_tasarim/tasarim_personel_listesi.php">Personel Listesi Rapor Tasarımı</a></li><?php endif; ?>
                        <?php if(yetkiVar('tasarim_personel_bilgi')): ?><li><a href="<?php echo $yol; ?>yazici_tasarim/tasarim_personel_bilgileri.php">Personel Bilgileri Rapor Tasarımı</a></li><?php endif; ?>
                        <?php if(yetkiVar('tasarim_personel_odeme')): ?><li><a href="<?php echo $yol; ?>yazici_tasarim/tasarim_personel_odemeleri.php">Personel Ödemeleri Rapor Tasarımı</a></li><?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if(yetkiVar('tanim_firma') || yetkiVar('tanim_marka') || yetkiVar('tanim_men') || yetkiVar('tanim_ucret') || yetkiVar('tanim_teslim') || yetkiVar('tanim_haciz') || yetkiVar('tanim_cekici') || yetkiVar('tanim_gelir_gider') || yetkiVar('tanim_ada')): ?>
                <li class="dropdown">
                    <a href="#" class="<?php echo ($menu == 'tanim') ? 'active' : ''; ?>">Tanımlar <i class="fas fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></a>
                    <ul class="dropdown-menu">
                        <?php if(yetkiVar('tanim_firma')): ?><li><a href="<?php echo $yol; ?>tanimlar/tanim_firma_bilgileri.php">Firma Bilgileriniz</a></li><?php endif; ?>
                        <?php if(yetkiVar('tanim_marka')): ?><li><a href="<?php echo $yol; ?>tanimlar/tanim_marka_model.php">Araç Marka & Model Tanımları</a></li><?php endif; ?>
                        <?php if(yetkiVar('tanim_men')): ?><li><a href="<?php echo $yol; ?>tanimlar/tanim_men_nedeni.php">Men Nedeni Tanımlama</a></li><?php endif; ?>
                        <?php if(yetkiVar('tanim_ucret')): ?><li><a href="<?php echo $yol; ?>tanimlar/tanim_arac_cins_ucret.php">Araç Cins & Ücret Tanımları</a></li><?php endif; ?>
                        <?php if(yetkiVar('tanim_teslim')): ?><li><a href="<?php echo $yol; ?>tanimlar/tanim_teslim_eden_kurum.php">Araç Teslim Eden Kurumlar</a></li><?php endif; ?>
                        <?php if(yetkiVar('tanim_haciz')): ?><li><a href="<?php echo $yol; ?>tanimlar/tanim_haciz_yetkili_kurum.php">Haciz Yetkisine Sahip Kurumlar</a></li><?php endif; ?>
                        <?php if(yetkiVar('tanim_cekici')): ?><li><a href="<?php echo $yol; ?>tanimlar/tanim_cekici_firma.php">Çekici Firma Tanımları</a></li><?php endif; ?>
                        <?php if(yetkiVar('tanim_gelir_gider')): ?><li><a href="<?php echo $yol; ?>tanimlar/tanim_gelir_gider.php">Gelir & Gider Tanımları</a></li><?php endif; ?>
                        <?php if(yetkiVar('tanim_ada')): ?><li><a href="<?php echo $yol; ?>tanimlar/tanim_ada_parsel.php">Ada - Parsel Tanımları</a></li><?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                <li>
                    <a href="<?php echo $yol; ?>kullanicilar.php" class="<?php echo ($menu == 'kullanicilar') ? 'active' : ''; ?>">Kullanıcılar</a>
                </li>
                <?php endif; ?>

                <?php if(yetkiVar('ayar_program') || yetkiVar('ayar_hakkinda')): ?>
                <li class="dropdown">
                    <a href="#" class="<?php echo ($menu == 'ayar') ? 'active' : ''; ?>">Ayarlar <i class="fas fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></a>
                    <ul class="dropdown-menu">
                        <?php if(yetkiVar('ayar_program')): ?><li><a href="<?php echo $yol; ?>ayarlar/ayarlar_program.php">Program Ayarları</a></li><?php endif; ?>
                        <?php if(yetkiVar('ayar_hakkinda')): ?><li><a href="<?php echo $yol; ?>ayarlar/hakkinda.php">Hakkında</a></li><?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

            </ul>
        </nav>
    </div>
</header>