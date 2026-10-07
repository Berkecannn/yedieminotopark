<?php
// Oturumu başlat
session_start();

// --- HEADER VE MENÜ AYARLARI ---
$yol = ""; 
$menu = "kullanicilar"; 

require_once 'db_config.php';

// Güvenlik: Oturum açmamışsa login'e at
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// GÜVENLİK 2: Sadece YÖNETİCİLER bu sayfayı görebilir.
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("location: index.php");
    exit;
}

// 1. SİLME İŞLEMİ
if (isset($_GET['sil_id'])) {
    $id = $_GET['sil_id'];
    $conn->query("DELETE FROM users WHERE id = $id");
    header("location: kullanicilar.php");
    exit;
}

// --- İŞLEMLER ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // KAYDET / GÜNCELLE
    if (isset($_POST['action']) && $_POST['action'] == 'kaydet') {
        $id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
        $ad_soyad = mb_strtoupper(trim($_POST['ad_soyad']), 'UTF-8');
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Yetkileri JSON olarak sakla
        $yetkiler = isset($_POST['yetki']) ? json_encode($_POST['yetki']) : json_encode([]);

        if ($id) {
            // GÜNCELLEME
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET ad_soyad=?, username=?, password=?, yetkiler=? WHERE id=?");
                $stmt->bind_param("ssssi", $ad_soyad, $username, $hashed_password, $yetkiler, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET ad_soyad=?, username=?, yetkiler=? WHERE id=?");
                $stmt->bind_param("sssi", $ad_soyad, $username, $yetkiler, $id);
            }
        } else {
            // YENİ KAYIT
            if(!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (ad_soyad, username, password, yetkiler) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $ad_soyad, $username, $hashed_password, $yetkiler);
            }
        }
        
        if (isset($stmt)) {
            $stmt->execute();
            $stmt->close();
        }
        header("Location: kullanicilar.php");
        exit;
    }
}

// Kullanıcıları Çek
$kullanicilar = $conn->query("SELECT * FROM users ORDER BY id ASC");

// Seçili Kullanıcıyı Çek
$secili_user = null;
if (isset($_GET['id'])) {
    $uid = $_GET['id'];
    $res = $conn->query("SELECT * FROM users WHERE id = $uid");
    $secili_user = $res->fetch_assoc();
}

// --- HEADER YAPISINA GÖRE YETKİ LİSTESİ ---
// 'type' => 'single' (Tekil Menü) veya 'group' (Dropdown Menü)
$menuler = [
    // 1. ARAÇ KAYIT İŞLEMLERİ (Dropdown)
    'arac_kayit_grubu' => [
        'label' => 'Araç Kayıt İşlemleri',
        'type'  => 'group',
        'items' => [
            'arac_kayit_detayli' => 'Araç Kayıt (Detaylı)',
            'arac_kayit_hizli'   => 'Hızlı Araç Kayıt',
            'arac_sorgula'       => 'Araç Sorgulama',
            'arac_analiz'        => 'Araç Analiz Raporları',
            'hacizli_analiz'     => 'Hacizli Araç Analiz Raporları',
            'hasar_formu'        => 'Boş Araç Hasar Durum Formu',
            'teslim_tutanagi'    => 'Araç Teslim Tutanağı'
        ]
    ],
    // 2. PERSONELLER (Tekil)
    'personel_listesi' => [
        'label' => 'Personeller',
        'type'  => 'single'
    ],
    // 3. GELİR KAYITLARI (Dropdown)
    'gelir_grubu' => [
        'label' => 'Gelir Kayıtları',
        'type'  => 'group',
        'items' => [
            'gelir_extra'  => 'Extra Gelirler',
            'gelir_analiz' => 'Gelirler Analiz Raporu'
        ]
    ],
    // 4. GİDER KAYITLARI (Dropdown)
    'gider_grubu' => [
        'label' => 'Gider Kayıtları',
        'type'  => 'group',
        'items' => [
            'gider_kayit' => 'Gider Kayıt',
            'gider_rapor' => 'Gider Raporları & Yazdır'
        ]
    ],
    // 5. KASA (Tekil)
    'kasa' => [
        'label' => 'Kasa',
        'type'  => 'single'
    ],
    // 6. MAKBUZ (Tekil)
    'makbuz' => [
        'label' => 'Makbuz',
        'type'  => 'single'
    ],
    // 7. GRAFİKSEL RAPORLAMA (Tekil)
    'grafik_rapor' => [
        'label' => 'Grafiksel Raporlama',
        'type'  => 'single'
    ],
    // 8. VERİTABANI (Tekil)
    'veritabani' => [
        'label' => 'Veritabanı',
        'type'  => 'single'
    ],
    // 9. YAZICI TASARIM (Dropdown)
    'yazici_tasarim_grubu' => [
        'label' => 'Yazıcı Tasarım',
        'type'  => 'group',
        'items' => [
            'tasarim_giris_cikis'    => 'Araç Giriş-Çıkış Raporu',
            'tasarim_hacizli'        => 'Hacizli Araç Analiz Raporu',
            'tasarim_gelir'          => 'Gelirler Raporu',
            'tasarim_gider'          => 'Giderler Raporu',
            'tasarim_kasa'           => 'Kasa Raporu Şablonu',
            'tasarim_makbuz'         => 'Para Makbuzu Tasarımı',
            'tasarim_personel_list'  => 'Personel Listesi Raporu',
            'tasarim_personel_bilgi' => 'Personel Bilgileri Raporu',
            'tasarim_personel_odeme' => 'Personel Ödemeleri Raporu'
        ]
    ],
    // 10. TANIMLAR (Dropdown)
    'tanimlar_grubu' => [
        'label' => 'Tanımlar',
        'type'  => 'group',
        'items' => [
            'tanim_firma'       => 'Firma Bilgileriniz',
            'tanim_marka'       => 'Araç Marka & Model',
            'tanim_men'         => 'Men Nedeni Tanımlama',
            'tanim_ucret'       => 'Araç Cins & Ücret',
            'tanim_teslim'      => 'Araç Teslim Eden Kurumlar',
            'tanim_haciz'       => 'Haciz Yetkili Kurumlar',
            'tanim_cekici'      => 'Çekici Firma Tanımları',
            'tanim_gelir_gider' => 'Gelir & Gider Tanımları',
            'tanim_ada'         => 'Ada - Parsel Tanımları'
        ]
    ],
    // 11. AYARLAR (Dropdown)
    'ayarlar_grubu' => [
        'label' => 'Ayarlar',
        'type'  => 'group',
        'items' => [
            'ayar_program'  => 'Program Ayarları',
            'ayar_hakkinda' => 'Hakkında'
        ]
    ]
];

// Mevcut kullanıcının yetkileri
$mevcut_yetkiler = ($secili_user && !empty($secili_user['yetkiler'])) ? json_decode($secili_user['yetkiler'], true) : [];
if(!is_array($mevcut_yetkiler)) $mevcut_yetkiler = [];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kullanıcı Yönetimi</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .page-grid { display: grid; grid-template-columns: 1fr 450px; gap: 20px; margin-top: 20px; }
        .panel-card { background: #fff; border: 1px solid #ddd; border-radius: 5px; overflow: hidden; display: flex; flex-direction: column; }
        .panel-header { background: #f1f3f5; padding: 10px 15px; border-bottom: 1px solid #ddd; font-weight: bold; color: #444; display: flex; justify-content: space-between; align-items: center; }
        .panel-body { padding: 15px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; color: #555; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; box-sizing: border-box; }
        
        /* YETKİ LİSTESİ TASARIMI */
        .perm-container { border: 1px solid #eee; border-radius: 4px; background: #fdfdfd; max-height: 500px; overflow-y: auto; }
        
        /* Tekil Eleman Stili */
        .perm-single {
            padding: 8px 10px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px;
            font-size: 12px; font-weight: 600; color: #333; background: #fff;
        }
        .perm-single:hover { background: #f8f9fa; }
        .perm-single label { margin: 0; cursor: pointer; flex: 1; font-weight: 600; }
        .perm-single input { width: 16px; height: 16px; margin: 0; cursor: pointer; }

        /* Grup Eleman Stili */
        .perm-group { border-bottom: 1px solid #eee; }
        .perm-group:last-child { border-bottom: none; }
        
        .group-header { 
            background: #f1f3f5; padding: 8px 10px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; 
            font-size: 12px; font-weight: bold; color: #333; user-select: none;
        }
        .group-header:hover { background: #e9ecef; }
        .header-left { display: flex; align-items: center; gap: 8px; }
        .header-left input { width: 16px; height: 16px; cursor: pointer; margin: 0; }
        .toggle-icon { color: #666; font-size: 10px; transition: transform 0.2s; }
        .group-header.active .toggle-icon { transform: rotate(180deg); }
        
        .sub-menu { display: none; padding: 5px 10px 5px 34px; background: #fff; border-top: 1px solid #f0f0f0; }
        .sub-menu.open { display: block; }
        
        .sub-item { display: flex; align-items: center; gap: 8px; padding: 6px 0; font-size: 12px; color: #555; border-bottom: 1px dotted #eee; }
        .sub-item:last-child { border-bottom: none; }
        .sub-item input { width: 14px; height: 14px; margin: 0; cursor: pointer; }
        .sub-item label { margin: 0; cursor: pointer; font-weight: normal; width: 100%; }

        .btn-action { padding: 5px 10px; border-radius: 3px; color: white; border: none; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-edit { background-color: #17a2b8; }
        .btn-del { background-color: #dc3545; }
        
        .role-badge { background: #17a2b8; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
        .password-container { position: relative; }
        .password-container input { padding-right: 30px; }
        .toggle-password { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #777; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="main-content">
        <div class="page-header-row">
            <h2><i class="fas fa-users-cog"></i> Kullanıcı ve Yetki Yönetimi</h2>
        </div>

        <div class="page-grid">
            
            <div class="panel-card">
                <div class="panel-header">Kayıtlı Kullanıcılar (Personel)</div>
                <div class="panel-body">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Adı Soyadı</th>
                                <th>Kullanıcı Adı</th>
                                <th>Yetki Durumu</th>
                                <th width="100">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($kullanicilar->num_rows > 0): ?>
                                <?php while($u = $kullanicilar->fetch_assoc()): 
                                    $u_yetkiler = $u['yetkiler'] ? json_decode($u['yetkiler'], true) : [];
                                    $yetki_sayisi = is_array($u_yetkiler) ? count($u_yetkiler) : 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['ad_soyad']); ?></td>
                                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                                    <td>
                                        <?php if($yetki_sayisi > 20): ?>
                                            <span class="role-badge" style="background:#007bff;">Geniş Yetki</span>
                                        <?php elseif($yetki_sayisi > 0): ?>
                                            <span class="role-badge"><?php echo $yetki_sayisi; ?> Erişim</span>
                                        <?php else: ?>
                                            <span class="role-badge" style="background:#6c757d;">Yetkisiz</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="kullanicilar.php?id=<?php echo $u['id']; ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i></a>
                                        <a href="kullanicilar.php?sil_id=<?php echo $u['id']; ?>" onclick="return confirm('Bu personeli silmek istediğinize emin misiniz?')" class="btn-action btn-del"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; color:#999; padding:20px;">Henüz personel kaydı yok.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel-card">
                <div class="panel-header">
                    <span><?php echo $secili_user ? 'Personel Düzenle' : 'Yeni Personel Ekle'; ?></span>
                    <?php if($secili_user): ?><a href="kullanicilar.php" class="btn-action btn-del" style="background:#6c757d;">İptal</a><?php endif; ?>
                </div>
                <div class="panel-body">
                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="action" value="kaydet">
                        <input type="hidden" name="user_id" value="<?php echo $secili_user['id'] ?? ''; ?>">

                        <div class="form-group">
                            <label>Adı Soyadı</label>
                            <input type="text" name="ad_soyad" class="force-uppercase" value="<?php echo $secili_user['ad_soyad'] ?? ''; ?>" required placeholder="Örn: AHMET YILMAZ">
                        </div>

                        <div class="form-group">
                            <label>Kullanıcı Adı (Giriş İçin)</label>
                            <input type="text" name="username" value="<?php echo $secili_user['username'] ?? ''; ?>" required autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label>Şifre <?php echo $secili_user ? '<small style="color:red">(Değiştirmek istemiyorsanız boş bırakın)</small>' : ''; ?></label>
                            <div class="password-container">
                                <input type="password" name="password" id="passInput" <?php echo $secili_user ? '' : 'required'; ?> autocomplete="new-password">
                                <i class="fas fa-eye toggle-password" onclick="sifreGoster()"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label style="border-bottom:1px solid #eee; padding-bottom:5px; margin-bottom:10px;">
                                <i class="fas fa-lock"></i> Menü Erişim Yetkileri
                            </label>
                            
                            <div class="perm-container">
                                <?php foreach($menuler as $key => $menuData): 
                                    // TEKİL MENÜLER (Dropdown Yok)
                                    if($menuData['type'] == 'single') {
                                        $checked = in_array($key, $mevcut_yetkiler) ? 'checked' : '';
                                        ?>
                                        <div class="perm-single">
                                            <input type="checkbox" name="yetki[]" value="<?php echo $key; ?>" id="perm_<?php echo $key; ?>" <?php echo $checked; ?>>
                                            <label for="perm_<?php echo $key; ?>"><?php echo $menuData['label']; ?></label>
                                        </div>
                                        <?php
                                    } 
                                    // GRUP MENÜLER (Dropdown)
                                    else {
                                        // Grup içinde seçili var mı kontrol et (Menüyü açık getirmek için)
                                        $grupAktif = false;
                                        foreach($menuData['items'] as $subKey => $subLabel) {
                                            if(in_array($subKey, $mevcut_yetkiler)) $grupAktif = true;
                                        }
                                        ?>
                                        <div class="perm-group">
                                            <div class="group-header <?php echo $grupAktif ? 'active' : ''; ?>" onclick="toggleGroup(this)">
                                                <div class="header-left">
                                                    <input type="checkbox" onclick="toggleAllInGroup(this, event)"> 
                                                    <span><?php echo $menuData['label']; ?></span>
                                                </div>
                                                <i class="fas fa-chevron-down toggle-icon"></i>
                                            </div>
                                            <div class="sub-menu <?php echo $grupAktif ? 'open' : ''; ?>">
                                                <?php foreach($menuData['items'] as $subKey => $subLabel): 
                                                    $checked = in_array($subKey, $mevcut_yetkiler) ? 'checked' : '';
                                                ?>
                                                <div class="sub-item">
                                                    <input type="checkbox" name="yetki[]" value="<?php echo $subKey; ?>" id="perm_<?php echo $subKey; ?>" <?php echo $checked; ?>>
                                                    <label for="perm_<?php echo $subKey; ?>"><?php echo $subLabel; ?></label>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success" style="width:100%; padding:10px;"><i class="fas fa-save"></i> Kaydet</button>
                    </form>
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

        function sifreGoster() {
            var input = document.getElementById("passInput");
            var icon = document.querySelector(".toggle-password");
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        function toggleGroup(header) {
            header.classList.toggle('active');
            var subMenu = header.nextElementSibling;
            if (subMenu.classList.contains('open')) {
                subMenu.classList.remove('open');
            } else {
                subMenu.classList.add('open');
            }
        }

        function toggleAllInGroup(checkbox, event) {
            event.stopPropagation(); // Akordiyonu tetikleme
            
            var groupHeader = checkbox.closest('.group-header');
            var subMenu = groupHeader.nextElementSibling;
            
            // Kapalıysa aç, ne olduğunu görsün
            if (!groupHeader.classList.contains('active')) {
                toggleGroup(groupHeader);
            }

            var subCheckboxes = subMenu.querySelectorAll('input[type="checkbox"]');
            subCheckboxes.forEach(function(cb) {
                cb.checked = checkbox.checked;
            });
        }
    </script>

</body>
</html>