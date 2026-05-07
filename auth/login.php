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
        $errors[] = 'Email atau password tidak valid.';
    }

    if ($errors === []) {
        try {
            $stmt = db()->prepare(
                'SELECT pengguna_id, nama_pengguna, email, kata_sandi, role, status FROM pengguna WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $old['email']]);
            $user = $stmt->fetch();

            if (!$user || $password !== $user['kata_sandi']) {
                $errors[] = 'Email atau password salah.';
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

                redirectTo('../home/');
            }
        } catch (PDOException) {
            $errors[] = 'Login gagal. Periksa koneksi database.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Resepku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <main class="login-screen" data-node-id="1:2054" data-name="member">
        <img class="login-screen__bg" src="../assets/img/login-bg.png" alt="">

        <section class="login-screen__content" aria-label="Login Resepku">
            <header class="brand" data-node-id="1:2056">
                <img class="brand__mark" src="../assets/img/resepku-logo.png" alt="" data-node-id="1:2055">
                <div class="brand__copy">
                    <p class="brand__name" data-node-id="1:2057">Resepku</p>
                    <p class="brand__tagline" data-node-id="1:2058">Find recipes, Bookmarks favorite, and Cook easily</p>
                </div>
            </header>

            <form class="login-form" action="login.php" method="post" data-node-id="1:2059">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <h1 class="login-form__title" data-node-id="1:2067">
                    <span>Welcome!</span>
                    <span>Ready to makes something?</span>
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

                <label class="sr-only" for="password">Password</label>
                <input class="login-form__input auth-form__input" id="password" name="password" type="password" placeholder="Password" autocomplete="current-password" required data-node-id="1:2065">

                <button class="login-form__button" type="submit" data-node-id="1:2061">Login</button>

                <button class="login-form__guest" type="submit" name="guest_login" value="1" formnovalidate>Login as Guest</button>

                <p class="login-form__signup" data-node-id="1:2068">
                    <span>Don't have an account?</span>
                    <a href="register.php" data-node-id="1:2069">Create one!</a>
                </p>
            </form>
        </section>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
