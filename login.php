<?php
// Oturumu başlat
session_start();

// GÜNCELLENDİ: index.php'ye yönlendir
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yediemin Otopark Sistemi - Yönetici Girişi</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <div class="login-page-wrapper">

        <header class="login-header">
            <h1>Yediemin Otopark Sistemi</h1>
            <p>Lütfen yönetici bilgileri ile giriş yapınız.</p>
        </header>

        <div class="login-container">
            <form action="login_process.php" method="POST">
                
                <?php
                if (isset($_GET['error']) && $_GET['error'] == 1) {
                    echo '<div class="error-message">Kullanıcı adı veya şifre hatalı!</div>';
                }
                ?>

                <div class="input-group">
                    <label for="kullanici_adi">Kullanıcı Adı</label>
                    <input type="text" id="kullanici_adi" name="kullanici_adi" required>
                </div>

                <div class="input-group">
                    <label for="sifre">Şifre</label>
                    <div class="password-wrapper">
                        <input type="password" id="sifre" name="sifre" required>
                        <i class="fas fa-eye" id="toggle-password"></i>
                    </div>
                </div>

                <div class="form-options">
                    <a href="sifre_sifirlama.php" class="forgot-password">Şifremi Unuttum?</a>
                </div>

                <button type="submit">Giriş Yap</button>
            </form>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('toggle-password');
        const password = document.getElementById('sifre');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });
    </script>

</body>
</html>