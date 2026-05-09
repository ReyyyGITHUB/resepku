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

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'category' => trim((string) ($_GET['category'] ?? '')),
];

$recipes = recipe_user_recipes_db((int) $user['id'], 100);
$allRecipes = $recipes;

if ($filters['q'] !== '' || $filters['category'] !== '') {
    $recipes = array_values(array_filter($recipes, static function (array $recipe) use ($filters): bool {
        if ($filters['q'] !== '' && stripos($recipe['title'], $filters['q']) === false) {
            return false;
        }

        if ($filters['category'] !== '' && mb_strtolower((string) ($recipe['category'] ?? '')) !== mb_strtolower($filters['category'])) {
            return false;
        }

        return true;
    }));
}

$categoryPills = [
    ['label' => 'All', 'value' => ''],
    ['label' => 'Food', 'value' => 'food'],
    ['label' => 'Salad', 'value' => 'salad'],
    ['label' => 'Dessert', 'value' => 'dessert'],
    ['label' => 'Drinks', 'value' => 'drinks'],
];

$alerts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['_token'] ?? null)) {
        $alerts[] = 'Token form tidak valid.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $recipeId = (int) ($_POST['recipe_id'] ?? 0);

        if ($action === 'delete' && $recipeId > 0) {
            if (recipe_delete_db($recipeId, (int) $user['id'])) {
                redirectTo('../resep/myresep.php?deleted=1');
            }

            $alerts[] = 'Resep tidak ditemukan atau bukan milik akun ini.';
        }
    }
}

if (!empty($_GET['deleted'])) {
    $alerts[] = 'Resep berhasil dihapus.';
}

if (!empty($_GET['updated'])) {
    $alerts[] = 'Resep berhasil diperbarui.';
}

$totalRecipes = count($allRecipes);
$visibleRecipes = count($recipes);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Recipes - Resepku</title>
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
                    <span>Kelola, edit, dan hapus resep milikmu dari satu tempat.</span>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <a href="../admin/" class="home-sidebar__admin-panel">Admin Panel</a>
            <?php endif; ?>

            <a href="../auth/logout.php" class="home-sidebar__logout">Log Out</a>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi Resep">
            <a href="../home/">Home</a>
            <a href="../profil/">Profile</a>
            <a class="is-active" href="../resep/myresep.php">My Recipes</a>
            <a href="../resep/buat.php">Add Recipe</a>
            <a href="../home/?sort=popular">Favorite</a>
            <a href="../profil/laporan.php">Pengaduan Saya</a>
            <a href="../cari.php">Search</a>
        </nav>

        <img src="../assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="myrecipes-main">
        <header class="myrecipes-topbar">
            <form class="home-search-form myrecipes-search-form" method="get" action="../cari.php" data-empty-action="../home/">
                <label class="sr-only" for="myrecipes-search">Search recipes</label>
                <input id="myrecipes-search" class="home-search" type="search" name="q" placeholder="Search my recipes..." value="<?= e($filters['q']) ?>">
                <input type="hidden" name="category" value="<?= e($filters['category']) ?>">
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
                <p class="myrecipes-hero__eyebrow">Recipe management</p>
                <h1>My Recipes</h1>
                <p class="myrecipes-hero__copy">Edit, update cover image, dan hapus resep yang kamu miliki.</p>
                <div class="home-filters myrecipes-filters" aria-label="Kategori resep">
                    <?php foreach ($categoryPills as $pill): ?>
                        <?php
                        $query = array_filter([
                            'q' => $filters['q'],
                            'category' => $pill['value'],
                        ], static fn ($value) => $value !== '');
                        ?>
                        <a class="home-filter<?= mb_strtolower($filters['category']) === mb_strtolower($pill['value']) ? ' is-active' : '' ?>" href="../resep/myresep.php?<?= e(http_build_query($query)) ?>"><?= e($pill['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="myrecipes-stats">
                <div>
                    <strong><?= e((string) $totalRecipes) ?></strong>
                    <span>Total resep</span>
                </div>
                <div>
                    <strong><?= e((string) $visibleRecipes) ?></strong>
                    <span>Hasil tampil</span>
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

        <section class="recipe-grid myrecipes-grid" aria-label="Resep milik saya">
            <?php if ($recipes === []): ?>
                <article class="myrecipes-empty">
                    <h2>Belum ada resep</h2>
                    <p>Buat resep pertama supaya halaman ini langsung berisi daftar kelolaan.</p>
                    <a href="../resep/buat.php">Tambah resep</a>
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
                                    <p><?= e($recipe['category'] !== '' ? ucfirst((string) $recipe['category']) : 'Uncategorized') ?></p>
                                </div>
                                <span class="myrecipe-card__badge"><?= e($recipe['difficulty']) ?></span>
                            </div>

                            <div class="myrecipe-card__meta">
                                <span><?= e($recipe['cook_time']) ?></span>
                                <span><?= e($recipe['servings']) ?></span>
                            </div>

                            <p class="myrecipe-card__summary"><?= e($recipe['summary'] !== '' ? $recipe['summary'] : 'Belum ada deskripsi singkat.') ?></p>

                            <div class="myrecipe-card__actions">
                                <a href="../resep/edit.php?id=<?= e((string) $recipe['id']) ?>">Edit</a>
                                <a href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">View</a>
                                <form method="post" onsubmit="return confirm('Hapus resep ini?');">
                                    <input type="hidden" name="_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="recipe_id" value="<?= e((string) $recipe['id']) ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>
