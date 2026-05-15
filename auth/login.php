<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

startSession();

$errors = [];
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$old = [
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guest_login'])) {
        $_SESSION['guest_mode'] = true;
        unset($_SESSION['user']);
        redirectTo('../home/');
    }

    $old['email'] = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesi form tidak valid. Silakan coba lagi.';
    }

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || $password === '') {
        $errors[] = 'Email atau kata sandi tidak valid.';
    }

    if ($errors === []) {
        try {
            $stmt = db()->prepare(
                'SELECT pengguna_id, nama_pengguna, email, kata_sandi, role, status FROM pengguna WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $old['email']]);
            $user = $stmt->fetch();

            if (!$user || $password !== $user['kata_sandi']) {
                $errors[] = 'Email atau kata sandi salah.';
            } elseif ($user['status'] !== 'aktif') {
                $errors[] = 'Akun sedang nonaktif.';
            } else {
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id' => (int) $user['pengguna_id'],
                    'name' => $user['nama_pengguna'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ];

                if ($user['role'] === 'admin') {
                    redirectTo('../admin/');
                }

                redirectTo('../home/');
            }
        } catch (PDOException) {
            $errors[] = 'Gagal masuk. Periksa koneksi database.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Resepku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <main class="login-screen" data-node-id="1:2054" data-name="member">
        <img class="login-screen__bg" src="../assets/img/login-bg.png" alt="">

        <section class="login-screen__content" aria-label="Masuk Resepku">
            <header class="brand" data-node-id="1:2056">
                <img class="brand__mark" src="../assets/img/resepku-logo.png" alt="" data-node-id="1:2055">
                <div class="brand__copy">
                    <p class="brand__name" data-node-id="1:2057">Resepku</p>
                    <p class="brand__tagline" data-node-id="1:2058">Temukan resep, simpan favorit, dan masak lebih mudah</p>
                </div>
            </header>

            <form class="login-form" action="login.php" method="post" data-node-id="1:2059">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <h1 class="login-form__title" data-node-id="1:2067">
                    <span>Selamat datang!</span>
                    <span>Siap membuat sesuatu?</span>
                </h1>

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

                <label class="sr-only" for="email">Email</label>
                <input class="login-form__input auth-form__input" id="email" name="email" type="email" placeholder="Email" autocomplete="email" value="<?= e($old['email']) ?>" required data-node-id="1:2066">

                <label class="sr-only" for="password">Kata sandi</label>
                <div class="login-form__password">
                    <input class="login-form__input auth-form__input" id="password" name="password" type="password" placeholder="Kata sandi" autocomplete="current-password" required data-node-id="1:2065">
                    <button class="login-form__password-toggle" type="button" aria-label="Tampilkan kata sandi" aria-pressed="false" data-password-toggle>
                        <svg class="login-form__password-icon login-form__password-icon--show" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                            <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"></circle>
                        </svg>
                        <svg class="login-form__password-icon login-form__password-icon--hide" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M3 3l18 18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"></path>
                            <path d="M10.7 5.2A10.7 10.7 0 0 1 12 5c6 0 9.5 7 9.5 7a16.6 16.6 0 0 1-3.1 4.1M6.3 6.7A16.7 16.7 0 0 0 2.5 12S6 19 12 19a9.6 9.6 0 0 0 3.8-.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M9.9 9.9A3 3 0 0 0 14.1 14.1" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                        </svg>
                    </button>
                </div>

                <p class="login-form__forgot">
                    <a href="lupa-sandi.php">Lupa kata sandi?</a>
                </p>

                <button class="login-form__button" type="submit" data-node-id="1:2061">Masuk</button>

                <div class="login-form__separator" aria-hidden="true"><span>atau</span></div>

                <button class="login-form__guest" type="submit" name="guest_login" value="1" formnovalidate>Masuk sebagai Tamu</button>

                <p class="login-form__signup" data-node-id="1:2068">
                    <span>Belum punya akun?</span>
                    <a href="register.php" data-node-id="1:2069">Buat akun!</a>
                </p>
            </form>
        </section>
    </main>

    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var field = button.closest('.login-form__password').querySelector('input');
                var visible = field.type === 'text';

                field.type = visible ? 'password' : 'text';
                button.setAttribute('aria-pressed', visible ? 'false' : 'true');
                button.setAttribute('aria-label', visible ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
            });
        });
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
