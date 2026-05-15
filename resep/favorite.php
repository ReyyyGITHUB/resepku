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

$searchQuery = trim((string) ($_GET['q'] ?? ''));
$recipes = recipe_user_favorites_db((int) $user['id'], 200);
$allRecipes = $recipes;

if ($searchQuery !== '') {
    $recipes = array_values(array_filter($recipes, static function (array $recipe) use ($searchQuery): bool {
        return stripos($recipe['title'] ?? '', $searchQuery) !== false
            || stripos($recipe['author'] ?? '', $searchQuery) !== false
            || stripos($recipe['summary'] ?? '', $searchQuery) !== false;
    }));
}

$totalFavorites = count($allRecipes);
$visibleFavorites = count($recipes);

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
<body class="myrecipes-page myrecipes-page--favorite">
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
                <input id="favorite-search" class="home-search" type="search" name="q" placeholder="Cari favorit..." value="<?= e($searchQuery) ?>">
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
                <div class="myrecipes-stats myrecipes-stats--inline">
                    <div>
                        <strong><?= e((string) $totalFavorites) ?></strong>
                        <span>Total favorit</span>
                    </div>
                    <div>
                        <strong><?= e((string) $visibleFavorites) ?></strong>
                        <span>Hasil tampil</span>
                    </div>
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
                    <?php if ($searchQuery !== ''): ?>
                        <h2>Favorit tidak ditemukan</h2>
                        <p>Coba kata kunci lain untuk mencari resep favoritmu.</p>
                        <a href="../resep/favorite.php">Reset pencarian</a>
                    <?php else: ?>
                        <h2>Belum ada favorit</h2>
                        <p>Simpan resep dari detail resep untuk melihatnya di sini.</p>
                        <a href="../home/">Cari resep</a>
                    <?php endif; ?>
                </article>
            <?php else: ?>
                <?php foreach ($recipes as $recipe): ?>
                    <?php
                    $recipeId = (int) $recipe['id'];
                    $detailHref = '../resep/detail.php?id=' . $recipeId;
                    $summary = $recipe['summary'] !== '' ? $recipe['summary'] : 'Belum ada deskripsi singkat.';
                    ?>
                    <article class="myrecipe-card">
                        <a class="myrecipe-card__link" href="<?= e($detailHref) ?>">
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

                            <p class="myrecipe-card__summary"><?= e($summary) ?></p>
                        </div>

                        <div class="myrecipe-card__actions">
                            <form method="post" onsubmit="return confirm('Hapus dari favorit?');">
                                <input type="hidden" name="_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="recipe_id" value="<?= e((string) $recipeId) ?>">
                                <button class="myrecipe-card__remove" type="submit" aria-label="Hapus favorit <?= e($recipe['title']) ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-2 6h10l-.7 11H7.7L7 9Zm3 2 .3 7h1.5l-.3-7H10Zm2.5 0v7H14v-7h-1.5Z" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
