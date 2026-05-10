<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../data/user_repository.php';

startSession();

$errors = [];
$successMessage = null;
$old = [
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['email'] = trim((string) ($_POST['email'] ?? ''));

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesi form tidak valid. Silakan coba lagi.';
    }

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    }

    if ($errors === []) {
        try {
            $user = user_find_by_email_db($old['email']);

            if ($user !== null && $user['status'] === 'aktif') {
                $reset = user_create_password_reset_db($user['id'], $user['email']);
                $resetLink = appUrl('auth/reset-sandi.php?token=' . urlencode($reset['token']));

                $subject = 'Atur Ulang Kata Sandi Resepku';
                $message = implode("\n", [
                    'Halo ' . $user['name'] . ',',
                    '',
                    'Kami menerima permintaan atur ulang kata sandi untuk akun Resepku milik kamu.',
                    'Silakan buka tautan berikut untuk membuat kata sandi baru:',
                    $resetLink,
                    '',
                    'Tautan ini berlaku sampai: ' . $reset['expires_at'],
                    '',
                    'Kalau kamu tidak meminta atur ulang kata sandi, abaikan email ini.',
                ]);

                sendSmtpMail($user['email'], $subject, $message);
            }

            $successMessage = 'Jika email terdaftar, kami sudah mengirim tautan atur ulang kata sandi.';
        } catch (PDOException) {
            $errors[] = 'Gagal memproses permintaan. Periksa koneksi database.';
        } catch (RuntimeException) {
            $errors[] = 'Gagal mengirim email atur ulang kata sandi. Coba lagi nanti.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Sandi - Resepku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <main class="login-screen">
        <img class="login-screen__bg" src="../assets/img/login-bg.png" alt="">

        <section class="login-screen__content" aria-label="Lupa sandi Resepku">
            <header class="brand">
                <img class="brand__mark" src="../assets/img/resepku-logo.png" alt="">
                <div class="brand__copy">
                    <p class="brand__name">Resepku</p>
                    <p class="brand__tagline">Temukan resep, simpan favorit, dan masak lebih mudah</p>
                </div>
            </header>

            <form class="reset-form" action="lupa-sandi.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <div class="reset-form__intro">
                    <h1>Lupa sandi</h1>
                    <p>Masukkan email akun kamu untuk menerima tautan atur ulang kata sandi.</p>
                </div>

                <?php if ($successMessage): ?>
                    <div class="auth-alert auth-alert--success" role="status">
                        <?= e($successMessage) ?>
                    </div>
                <?php endif; ?>

                <?php if ($errors !== []): ?>
                    <div class="auth-alert auth-alert--error" role="alert">
                        <?= e($errors[0]) ?>
                    </div>
                <?php endif; ?>

                <label class="sr-only" for="email">Email</label>
                <input class="login-form__input auth-form__input" id="email" name="email" type="email" placeholder="Email" autocomplete="email" value="<?= e($old['email']) ?>" required>

                <button class="login-form__button reset-form__button" type="submit">Kirim Tautan Atur Ulang</button>

                <p class="login-form__signup">
                    <a href="login.php">Kembali ke halaman masuk</a>
                </p>
            </form>
        </section>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>
