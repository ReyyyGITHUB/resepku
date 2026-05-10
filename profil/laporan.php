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
$pageTitle = 'Customer Support - Resepku';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="cs-page" data-guest-mode="0" data-csrf-token="<?= e(csrfToken()) ?>" data-api-base="../api/" data-login-url="../auth/login.php">
    <main class="cs-screen" data-node-id="76:1837">
        <section class="cs-card" aria-label="Customer support">
            <a class="cs-back" href="../home/" aria-label="Kembali">
                <img src="../assets/img/icon-back.svg" alt="">
            </a>

            <div class="cs-copy">
                <p class="cs-eyebrow">WE'RE HERE TO HELP YOU!</p>
                <h1><span>Tell</span> Your Solution Needs With Us!</h1>
                <p class="cs-description">Tell us <strong>ANYTHING</strong> about what you feel when using our website!</p>
            </div>

            <form class="cs-form" data-report-form>
                <input type="hidden" name="target_type" value="pengguna">
                <input type="hidden" name="target_id" value="0">

                <label class="cs-field">
                    <span>Name</span>
                    <input type="text" value="<?= e($profile['name']) ?>" readonly>
                </label>

                <label class="cs-field">
                    <span>Email</span>
                    <input type="email" value="<?= e((string) ($_SESSION['user']['email'] ?? '')) ?>" placeholder="your@email.com" readonly>
                </label>

                <label class="cs-field">
                    <span>Category</span>
                    <select name="category" required>
                        <option value="">Ex: Functional, Account, Content , etc.</option>
                        <?php foreach ($reportCategories as $value => $label): ?>
                            <option value="<?= e($value) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="cs-field">
                    <span>Massage</span>
                    <textarea name="note" placeholder="Type your massage....." required></textarea>
                </label>

                <button class="cs-send" type="submit">
                    <span class="cs-send__icon" aria-hidden="true">-&gt;</span>
                    <span>Send</span>
                </button>
            </form>
        </section>

        <img class="cs-food" src="../assets/img/recipe-salad-card.png" alt="">
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
