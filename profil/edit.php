<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

$isAdmin = isAdmin();
$currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
if ($currentUserId <= 0) {
    redirectTo('../auth/login.php');
}

$profile = recipe_user_profile_db($currentUserId);
if ($profile === null) {
    redirectTo('../profil/');
}

$errors = [];
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$formValues = [
    'name' => $profile['name'] ?? '',
    'bio' => $profile['bio'] ?? '',
    'email' => $profile['email'] ?? ($_SESSION['user']['email'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountStmt = db()->prepare(
        'SELECT email, kata_sandi FROM pengguna WHERE pengguna_id = :user_id LIMIT 1'
    );
    $accountStmt->execute([':user_id' => $currentUserId]);
    $account = $accountStmt->fetch() ?: [];

    $formValues['name'] = trim((string) ($_POST['name'] ?? ''));
    $formValues['bio'] = trim((string) ($_POST['bio'] ?? ''));
    $formValues['email'] = trim((string) ($_POST['email'] ?? ''));
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $passwordSectionOpen = $currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '';

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesi form tidak valid. Silakan coba lagi.';
    }

    if ($formValues['name'] === '' || mb_strlen($formValues['name']) > 50) {
        $errors[] = 'Nama wajib diisi maksimal 50 karakter.';
    }

    if (mb_strlen($formValues['bio']) > 255) {
        $errors[] = 'Bio maksimal 255 karakter.';
    }

    if (!filter_var($formValues['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    }

    $needsPasswordUpdate = $currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '';

    if ($needsPasswordUpdate) {
        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $errors[] = 'Untuk ganti password, email, password sekarang, dan password baru wajib diisi.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'Konfirmasi password baru tidak cocok.';
        } elseif ($formValues['email'] !== (string) ($account['email'] ?? '')) {
            $errors[] = 'Email tidak sesuai dengan akun yang sedang login.';
        } elseif ($currentPassword !== (string) ($account['kata_sandi'] ?? '')) {
            $errors[] = 'Password sekarang salah.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'Password baru minimal 8 karakter.';
        }
    }

    if ($errors === []) {
        try {
            recipe_update_user_profile_db(
                $currentUserId,
                $formValues['name'],
                $formValues['bio'],
                $needsPasswordUpdate ? $newPassword : null
            );

            $_SESSION['user']['name'] = $formValues['name'];
            $_SESSION['user']['email'] = $formValues['email'];
            $_SESSION['flash_success'] = $needsPasswordUpdate
                ? 'Profil dan password berhasil diperbarui.'
                : 'Profil berhasil diperbarui.';
            redirectTo('../profil/');
        } catch (PDOException) {
            $errors[] = 'Gagal menyimpan profil. Periksa koneksi database.';
        }
    }
} else {
    $passwordSectionOpen = false;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Resepku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="profile-page" data-guest-mode="0" data-csrf-token="<?= e(csrfToken()) ?>">
    <aside class="home-sidebar profile-sidebar" data-node-id="16:154">
        <div class="home-sidebar__profile">
            <div class="home-sidebar__brand">
                <img src="../assets/img/resepku-logo.png" alt="" class="home-sidebar__logo">
                <div>
                    <p class="home-sidebar__name">Resepku</p>
                    <p class="home-sidebar__status">Signed in</p>
                </div>
            </div>

            <div class="home-sidebar__identity">
                <img src="<?= e($profile['avatar']) ?>" alt="<?= e($profile['name']) ?>" class="home-sidebar__avatar">
                <div class="home-sidebar__welcome">
                    <strong><?= e($profile['name']) ?></strong>
                    <span><?= $profile['bio'] !== '' ? e($profile['bio']) : 'Kelola akun dan resep publik kamu dari sini.' ?></span>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <a href="../admin/" class="home-sidebar__admin-panel">Admin Panel</a>
            <?php endif; ?>

            <a href="../auth/logout.php" class="home-sidebar__logout">Log Out</a>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi Profil">
            <a href="../home/">Home</a>
            <a class="is-active" href="../profil/">Profile</a>
            <a href="../resep/myresep.php">My Recipes</a>
            <a href="../resep/buat.php">Add Recipe</a>
            <a href="../resep/favorite.php">Favorite</a>
        </nav>

        <img src="../assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="profile-main">
        <?php if ($flashSuccess): ?>
            <div class="profile-alert profile-alert--success" role="status">
                <?= e($flashSuccess) ?>
            </div>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="profile-alert profile-alert--error" role="alert">
                <?= e($errors[0]) ?>
            </div>
        <?php endif; ?>

        <section class="profile-edit-layout" aria-label="Edit profile">
            <header class="profile-edit-hero">
                <p class="profile-section__kicker">Settings</p>
                <h1>Edit Profile</h1>
                <p>Ubah nama, bio, dan password dari satu panel yang lebih rapi.</p>
            </header>

            <div class="profile-edit-layout__grid">
                <section class="profile-edit-card profile-edit-card--summary">
                    <div class="profile-edit-card__avatar">
                        <img src="<?= e($profile['avatar']) ?>" alt="<?= e($profile['name']) ?>">
                    </div>
                    <h2><?= e($profile['name']) ?></h2>
                    <p><?= $profile['bio'] !== '' ? e($profile['bio']) : 'Tambahkan bio singkat agar profile terlihat lebih hidup.' ?></p>

                    <dl class="profile-details profile-details--compact">
                        <div>
                            <dt>Recipe</dt>
                            <dd><?= e((string) $profile['recipe_count']) ?></dd>
                        </div>
                        <div>
                            <dt>Follower</dt>
                            <dd><?= e((string) $profile['follower_count']) ?></dd>
                        </div>
                        <div>
                            <dt>Following</dt>
                            <dd><?= e((string) $profile['following_count']) ?></dd>
                        </div>
                    </dl>
                </section>

                <section class="profile-edit-card profile-edit-card--form">
                    <form class="profile-form" action="../profil/edit.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                        <div class="profile-form__grid">
                            <label class="profile-field">
                                <span>Nama</span>
                                <input class="profile-input" type="text" name="name" maxlength="50" value="<?= e($formValues['name']) ?>" required>
                            </label>

                            <label class="profile-field">
                                <span>Email</span>
                                <input class="profile-input" type="email" name="email" value="<?= e($formValues['email']) ?>" readonly>
                            </label>

                            <label class="profile-field profile-field--full">
                                <span>Bio</span>
                                <textarea class="profile-input profile-textarea" name="bio" rows="4" maxlength="255" placeholder="Tulis bio singkat"><?= e($formValues['bio']) ?></textarea>
                            </label>
                        </div>

                        <details class="profile-accordion"<?= $passwordSectionOpen ? ' open' : '' ?>>
                            <summary class="profile-accordion__summary">
                                <div>
                                    <h3>Ganti Password</h3>
                                    <p>Isi hanya kalau ingin mengganti password.</p>
                                </div>
                            </summary>

                            <div class="profile-accordion__body">
                                <div class="profile-form__grid">
                                    <label class="profile-field">
                                        <span>Password Sekarang</span>
                                        <input class="profile-input" type="password" name="current_password" autocomplete="current-password">
                                    </label>

                                    <label class="profile-field">
                                        <span>Password Baru</span>
                                        <input class="profile-input" type="password" name="new_password" autocomplete="new-password">
                                    </label>

                                    <label class="profile-field">
                                        <span>Konfirmasi Password Baru</span>
                                        <input class="profile-input" type="password" name="confirm_password" autocomplete="new-password">
                                    </label>
                                </div>
                            </div>
                        </details>

                        <div class="profile-form__actions">
                            <a class="profile-actions__secondary" href="../profil/">Cancel</a>
                            <button class="profile-actions__primary" type="submit">Simpan Perubahan</button>
                        </div>
                    </form>
                </section>
            </div>
        </section>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>
