<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/user_repository.php';

startSession();

$errors = [];
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$resetRecord = $token !== '' ? user_find_by_reset_token_db($token) : null;
$old = [
    'password' => '',
    'confirm_password' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['password'] = (string) ($_POST['password'] ?? '');
    $old['confirm_password'] = (string) ($_POST['confirm_password'] ?? '');

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesi form tidak valid. Silakan coba lagi.';
    }

    if ($resetRecord === null) {
        $errors[] = 'Token reset tidak valid atau sudah kedaluwarsa.';
    }

    if ($old['password'] === '' || strlen($old['password']) < 8) {
        $errors[] = 'Password baru minimal 8 karakter.';
    }

    if ($old['password'] !== $old['confirm_password']) {
        $errors[] = 'Konfirmasi password baru tidak cocok.';
    }

    if ($errors === [] && $resetRecord !== null) {
        try {
            user_reset_password_db($resetRecord['reset_id'], $resetRecord['user_id'], $old['password']);

            $_SESSION['flash_success'] = 'Password berhasil diubah. Silakan login dengan password baru.';
            redirectTo('../auth/login.php');
        } catch (PDOException) {
            $errors[] = 'Gagal mengubah password. Periksa koneksi database.';
        } catch (RuntimeException) {
            $errors[] = 'Token reset tidak dapat dipakai. Silakan minta link baru.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Sandi - Resepku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <main class="login-screen">
        <img class="login-screen__bg" src="../assets/img/login-bg.png" alt="">

        <section class="login-screen__content" aria-label="Reset sandi Resepku">
            <header class="brand">
                <img class="brand__mark" src="../assets/img/resepku-logo.png" alt="">
                <div class="brand__copy">
                    <p class="brand__name">Resepku</p>
                    <p class="brand__tagline">Find recipes, Bookmarks favorite, and Cook easily</p>
                </div>
            </header>

            <form class="reset-form" action="reset-sandi.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="reset-form__intro">
                    <h1>Reset password</h1>
                    <p>Buat password baru untuk akun kamu.</p>
                </div>

                <?php if ($flashSuccess): ?>
                    <div class="auth-alert auth-alert--success" role="status">
                        <?= e($flashSuccess) ?>
                    </div>
                <?php endif; ?>

                <?php if ($errors !== []): ?>
                    <div class="auth-alert auth-alert--error" role="alert">
                        <?= e($errors[0]) ?>
                    </div>
                <?php endif; ?>

                <?php if ($resetRecord === null && $errors === []): ?>
                    <div class="auth-alert auth-alert--error" role="alert">
                        Token reset tidak valid atau sudah kedaluwarsa.
                    </div>
                <?php else: ?>
                    <label class="sr-only" for="password">Password Baru</label>
                    <input class="login-form__input auth-form__input" id="password" name="password" type="password" placeholder="Password Baru" autocomplete="new-password" required>

                    <label class="sr-only" for="confirm_password">Konfirmasi Password Baru</label>
                    <input class="login-form__input auth-form__input" id="confirm_password" name="confirm_password" type="password" placeholder="Konfirmasi Password Baru" autocomplete="new-password" required>

                    <button class="login-form__button reset-form__button" type="submit">Ubah Password</button>
                <?php endif; ?>

                <p class="login-form__signup">
                    <a href="login.php">Kembali ke login</a>
                </p>
            </form>
        </section>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>
