<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

startSession();

$errors = [];
$old = [
    'name' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name'] = trim($_POST['name'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesi form tidak valid. Silakan coba lagi.';
    }

    if ($old['name'] === '' || mb_strlen($old['name']) > 50) {
        $errors[] = 'Nama wajib diisi maksimal 50 karakter.';
    }

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Kata sandi minimal 8 karakter.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Konfirmasi kata sandi tidak cocok.';
    }

    if ($errors === []) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO pengguna (nama_pengguna, email, kata_sandi) VALUES (:name, :email, :password)'
            );
            $stmt->execute([
                'name' => $old['name'],
                'email' => $old['email'],
                'password' => $password,
            ]);

            $_SESSION['flash_success'] = 'Akun berhasil dibuat. Silakan masuk.';
            redirectTo('login.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'Email sudah terdaftar.';
            } else {
                $errors[] = 'Registrasi gagal. Periksa koneksi database.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Resepku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <main class="register-screen" data-node-id="1:2082" data-name="Desktop - 3">
        <img class="login-screen__bg" src="../assets/img/register-bg.png" alt="">

        <a class="register-back" href="login.php" aria-label="Kembali ke halaman masuk" data-node-id="1:2100">
            <img src="../assets/img/icon-back.svg" alt="">
        </a>

        <section class="login-screen__content" aria-label="Daftar Resepku">
            <header class="brand register-brand" data-node-id="1:2102">
                <img class="brand__mark" src="../assets/img/resepku-logo.png" alt="" data-node-id="1:2096">
                <div class="brand__copy">
                    <p class="brand__name" data-node-id="1:2103">Resepku</p>
                    <p class="brand__tagline" data-node-id="1:2104">Temukan resep, simpan favorit, dan masak lebih mudah</p>
                </div>
            </header>

            <form class="register-form" action="register.php" method="post" data-node-id="1:2086">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <div class="register-form__intro" data-node-id="1:2097">
                    <h1 data-node-id="1:2098">Buat akunmu</h1>
                    <p data-node-id="1:2099">Bergabung dan mulai bagikan kreasi masakanmu sendiri!</p>
                </div>

                <?php if ($errors !== []): ?>
                    <div class="auth-alert auth-alert--error" role="alert">
                        <?= e($errors[0]) ?>
                    </div>
                <?php endif; ?>

                <label class="sr-only" for="name">Nama</label>
                <input class="login-form__input auth-form__input" id="name" name="name" type="text" placeholder="Nama" autocomplete="name" value="<?= e($old['name']) ?>" required data-node-id="1:2087">

                <label class="sr-only" for="register-email">Email</label>
                <input class="login-form__input auth-form__input" id="register-email" name="email" type="email" placeholder="Email" autocomplete="email" value="<?= e($old['email']) ?>" required data-node-id="1:2089">

                <label class="sr-only" for="register-password">Kata sandi</label>
                <input class="login-form__input auth-form__input" id="register-password" name="password" type="password" placeholder="Kata sandi" autocomplete="new-password" required data-node-id="1:2092">

                <label class="sr-only" for="confirm-password">Konfirmasi kata sandi</label>
                <input class="login-form__input auth-form__input" id="confirm-password" name="confirm_password" type="password" placeholder="Konfirmasi kata sandi" autocomplete="new-password" required data-node-id="1:2094">

                <button class="login-form__button register-form__button" type="submit" data-node-id="1:2084">Daftar Akun</button>
            </form>
        </section>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
