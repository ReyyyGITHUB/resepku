<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/admin_repository.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

$currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
if ($currentUserId <= 0) {
    redirectTo('../auth/login.php');
}

$profile = recipe_user_profile_db($currentUserId);
if ($profile === null) {
    redirectTo('../profil/');
}

$reportCategories = report_category_options();
$pageTitle = 'Pusat Bantuan - Resepku';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="cs-page" data-guest-mode="0" data-csrf-token="<?= e(csrfToken()) ?>" data-api-base="../api/" data-login-url="../auth/login.php" data-report-success-redirect="../home/">
    <main class="cs-screen" data-node-id="76:1837">
        <section class="cs-card" aria-label="Pusat bantuan">
            <a class="cs-back" href="../home/" aria-label="Kembali">
                <img src="../assets/img/icon-back.svg" alt="">
            </a>

            <div class="cs-copy">
                <p class="cs-eyebrow">KAMI SIAP MEMBANTU!</p>
                <h1><span>Ceritakan</span> Kendalamu Kepada Kami!</h1>
                <p class="cs-description">Sampaikan <strong>apa pun</strong> yang kamu rasakan saat menggunakan website ini.</p>
            </div>

            <form class="cs-form" data-report-form>
                <input type="hidden" name="target_type" value="pengguna">
                <input type="hidden" name="target_id" value="0">

                <label class="cs-field">
                    <span>Nama</span>
                    <input type="text" value="<?= e($profile['name']) ?>" readonly>
                </label>

                <label class="cs-field">
                    <span>Email</span>
                    <input type="email" value="<?= e((string) ($_SESSION['user']['email'] ?? '')) ?>" placeholder="email@contoh.com" readonly>
                </label>

                <label class="cs-field">
                    <span>Kategori</span>
                    <select name="category" required>
                        <option value="">Contoh: fitur, akun, konten, dan lainnya.</option>
                        <?php foreach ($reportCategories as $value => $label): ?>
                            <option value="<?= e($value) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="cs-field">
                    <span>Pesan</span>
                    <textarea name="note" placeholder="Tulis pesan kamu..." required></textarea>
                </label>

                <button class="cs-send" type="submit">
                    <span class="cs-send__icon" aria-hidden="true">-&gt;</span>
                    <span>Kirim</span>
                </button>
            </form>
        </section>

        <img class="cs-food" src="../assets/img/recipe-salad-card.png" alt="">
    </main>

    <div class="cs-success" data-report-success-feedback aria-hidden="true">
        <div class="cs-success__backdrop"></div>
        <div class="cs-success__dialog" role="status" aria-live="polite" aria-label="Laporan berhasil dikirim">
            <p class="cs-success__title">
                <span><span class="is-accent">Terima kasih</span></span>
                <span>Sudah memberi tahu <span class="is-accent">kami</span></span>
                <span>tentang kendalamu</span>
                <span class="is-accent">di Resepku</span>
            </p>
            <p class="cs-success__meta">Kembali ke beranda dalam <span data-report-success-countdown>5</span> detik</p>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
