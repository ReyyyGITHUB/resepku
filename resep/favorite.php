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
    <title>Favorite - Resepku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="myrecipes-page">
    <aside class="home-sidebar myrecipes-sidebar">
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
                    <span>Daftar resep yang sudah kamu simpan ke favorit.</span>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <a href="../admin/" class="home-sidebar__admin-panel">Admin Panel</a>
            <?php endif; ?>

            <a href="../auth/logout.php" class="home-sidebar__logout">Log Out</a>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi Favorit">
            <a href="../home/">Home</a>
            <a href="../profil/">Profile</a>
            <a href="../resep/myresep.php">My Recipes</a>
            <a href="../resep/buat.php">Add Recipe</a>
            <a class="is-active" href="../resep/favorite.php">Favorite</a>
            <a href="../profil/laporan.php">Pengaduan Saya</a>
            <a href="../home/?sort=popular">Popular</a>
        </nav>

        <img src="../assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="myrecipes-main">
        <header class="myrecipes-topbar">
            <form class="home-search-form myrecipes-search-form" method="get" action="../resep/favorite.php">
                <label class="sr-only" for="favorite-search">Search favorite recipes</label>
                <input id="favorite-search" class="home-search" type="search" name="q" placeholder="Search favorites..." value="<?= e(trim((string) ($_GET['q'] ?? ''))) ?>">
            </form>

            <a class="home-add" href="../resep/buat.php">
                <img src="../assets/img/icon-add-recipe.svg" alt="">
                <span>Add Recipe</span>
            </a>

            <a class="home-cs" href="<?= e(reportInboxHref('../profil/laporan.php', '../auth/login.php')) ?>">
                <img src="../assets/img/icon-cs.svg" alt="">
                <span>Pengaduan</span>
            </a>
        </header>

        <section class="myrecipes-hero">
            <div>
                <p class="myrecipes-hero__eyebrow">Saved recipes</p>
                <h1>Favorite</h1>
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

        <section class="recipe-grid myrecipes-grid" aria-label="Daftar favorit">
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
                            <span class="sr-only">Open recipe <?= e($recipe['title']) ?></span>
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
                                <span><?= e((string) ($recipe['likes_count'] ?? 0)) ?> likes</span>
                            </div>

                            <p class="myrecipe-card__summary"><?= e($recipe['summary'] !== '' ? $recipe['summary'] : 'Belum ada deskripsi singkat.') ?></p>

                            <div class="myrecipe-card__actions">
                                <a href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">View</a>
                                <form method="post" onsubmit="return confirm('Hapus dari favorit?');">
                                    <input type="hidden" name="_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="recipe_id" value="<?= e((string) $recipe['id']) ?>">
                                    <button type="submit">Remove</button>
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
