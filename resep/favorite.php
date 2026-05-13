<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

if (empty($_SESSION['user'])) {
    redirectTo('../auth/login.php');
}

$user = $_SESSION['user'];
$isAdmin = isAdmin();
$profile = recipe_user_profile_db((int) ($user['id'] ?? 0));
if ($profile === null) {
    redirectTo('../home/');
}

$alerts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['_token'] ?? null)) {
        $alerts[] = 'Token form tidak valid.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $recipeId = (int) ($_POST['recipe_id'] ?? 0);

        if ($action === 'remove' && $recipeId > 0) {
            recipe_remove_favorite_db($recipeId, (int) $user['id']);
            redirectTo('../resep/favorite.php?removed=1');
        }
    }
}

if (!empty($_GET['removed'])) {
    $alerts[] = 'Resep berhasil dihapus dari favorit.';
}

$recipes = recipe_user_favorites_db((int) $user['id'], 200);
$totalFavorites = count($recipes);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorit - Resepku</title>
        <?= sidebarInitialStateScript() ?>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="myrecipes-page">
    <?= renderGeneralSidebar([
        'basePath' => '../',
        'asideClass' => 'myrecipes-sidebar',
        'activeKey' => 'favorite',
        'searchAction' => '../cari.php',
        'userContext' => [
            'isLoggedIn' => true,
            'isGuest' => false,
            'isAdmin' => $isAdmin,
            'name' => $profile['name'] ?? '',
            'avatar' => $profile['avatar'] ?? '',
        ],
    ]) ?>

    <main class="myrecipes-main">
        <header class="myrecipes-topbar">
            <form class="home-search-form myrecipes-search-form" method="get" action="../resep/favorite.php">
                <label class="sr-only" for="favorite-search">Cari resep favorit</label>
                <input id="favorite-search" class="home-search" type="search" name="q" placeholder="Cari favorit..." value="<?= e(trim((string) ($_GET['q'] ?? ''))) ?>">
            </form>

            <a class="home-add" href="../resep/buat.php">
                <img src="../assets/img/icon-add-recipe.svg" alt="">
                <span>Tambah Resep</span>
            </a>

            <a class="home-cs" href="<?= e(reportInboxHref('../profil/laporan.php', '../auth/login.php')) ?>">
                <img src="../assets/img/icon-cs.svg" alt="">
                <span>Pengaduan</span>
            </a>
        </header>

        <section class="myrecipes-hero">
            <div>
                <p class="myrecipes-hero__eyebrow">Resep tersimpan</p>
                <h1>Favorit</h1>
                <p class="myrecipes-hero__copy">Resep yang kamu simpan ke favorit tersusun dari yang paling baru difavoritkan.</p>
            </div>

            <div class="myrecipes-stats">
                <div>
                    <strong><?= e((string) $totalFavorites) ?></strong>
                    <span>Total favorit</span>
                </div>
                <div>
                    <strong><?= e((string) $profile['recipe_count']) ?></strong>
                    <span>Total resep</span>
                </div>
            </div>
        </section>

        <?php if ($alerts !== []): ?>
            <section class="myrecipes-alerts" aria-live="polite">
                <?php foreach ($alerts as $alert): ?>
                    <div class="myrecipes-alert"><?= e($alert) ?></div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="recipe-grid myrecipes-grid favorite-grid" aria-label="Daftar favorit">
            <?php if ($recipes === []): ?>
                <article class="myrecipes-empty">
                    <h2>Belum ada favorit</h2>
                    <p>Simpan resep dari detail resep untuk melihatnya di sini.</p>
                    <a href="../home/">Cari resep</a>
                </article>
            <?php else: ?>
                <?php foreach ($recipes as $recipe): ?>
                    <article class="myrecipe-card">
                        <a class="myrecipe-card__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                            <span class="sr-only">Buka resep <?= e($recipe['title']) ?></span>
                        </a>
                        <img class="myrecipe-card__image" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                        <div class="myrecipe-card__body">
                            <div class="myrecipe-card__head">
                                <div>
                                    <h2><?= e($recipe['title']) ?></h2>
                                    <p><?= e($recipe['author']) ?></p>
                                </div>
                                <span class="myrecipe-card__badge"><?= e($recipe['difficulty']) ?></span>
                            </div>

                            <div class="myrecipe-card__meta">
                                <span><?= e($recipe['cook_time']) ?></span>
                                <span>★ <?= number_format((float) $recipe['rating'], 1) ?></span>
                                <span><?= e((string) ($recipe['likes_count'] ?? 0)) ?> suka</span>
                            </div>

                            <p class="myrecipe-card__summary"><?= e($recipe['summary'] !== '' ? $recipe['summary'] : 'Belum ada deskripsi singkat.') ?></p>

                            <div class="myrecipe-card__actions">
                                <a href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">Lihat</a>
                                <form method="post" onsubmit="return confirm('Hapus dari favorit?');">
                                    <input type="hidden" name="_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="recipe_id" value="<?= e((string) $recipe['id']) ?>">
                                    <button type="submit">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
