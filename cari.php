<?php

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/data/recipe_repository.php';

startSession();

if ($_GET === []) {
    redirectTo('home/');
}

$isAdmin = isAdmin();
$isGuest = !empty($_SESSION['guest_mode']) && empty($_SESSION['user']);
$userName = $isGuest ? 'Guest' : ($_SESSION['user']['name'] ?? 'Nayaka');

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'category' => trim((string) ($_GET['category'] ?? '')),
    'difficulty' => trim((string) ($_GET['difficulty'] ?? '')),
    'sort' => trim((string) ($_GET['sort'] ?? 'newest')),
];

$recipes = recipe_catalog_filtered_db($filters, 24);
$resultCount = count($recipes);
$hasQuery = $filters['q'] !== '';
$hasFilters = $filters['category'] !== '' || $filters['difficulty'] !== '' || $filters['sort'] !== 'newest';

$categoryPills = [
    ['label' => 'All', 'value' => ''],
    ['label' => 'Food', 'value' => 'food'],
    ['label' => 'Salad', 'value' => 'salad'],
    ['label' => 'Dessert', 'value' => 'dessert'],
    ['label' => 'Drinks', 'value' => 'drinks'],
];

$difficultyOptions = [
    ['label' => 'All difficulty', 'value' => ''],
    ['label' => 'Easy', 'value' => 'mudah'],
    ['label' => 'Medium', 'value' => 'sedang'],
    ['label' => 'Hard', 'value' => 'sulit'],
];

$sortOptions = [
    ['label' => 'Newest', 'value' => 'newest'],
    ['label' => 'Popular', 'value' => 'popular'],
];

$searchHint = $hasQuery
    ? 'Hasil untuk "' . $filters['q'] . '"'
    : 'Mulai ketik nama resep untuk mencari';

function search_asset_path(string $path): string
{
    return preg_replace('~^(?:\.\./)+~', '', $path) ?: $path;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Resep - Resepku</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="home-page">
    <aside class="home-sidebar">
        <div class="home-sidebar__profile">
            <div class="home-sidebar__brand">
                <img src="assets/img/resepku-logo.png" alt="" class="home-sidebar__logo">
                <div>
                    <p class="home-sidebar__name">Resepku</p>
                    <p class="home-sidebar__status"><?= $isGuest ? 'Guest mode' : 'Signed in' ?></p>
                </div>
            </div>

            <div class="home-sidebar__identity">
                <img src="assets/img/home-profile.png" alt="" class="home-sidebar__avatar">
                <div class="home-sidebar__welcome">
                    <strong><?= e($userName) ?></strong>
                    <span><?= $isGuest ? 'Login untuk simpan resep dan kelola profil.' : 'Akses resep pribadi dan aktivitas akun.' ?></span>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <a href="admin/" class="home-sidebar__admin-panel">Admin Panel</a>
            <?php endif; ?>

            <a href="auth/logout.php" class="home-sidebar__logout">Log Out</a>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi Search">
            <a href="home/">Home</a>
            <a href="profil/">Profile</a>
            <a href="resep/myresep.php">My Recipes</a>
            <a href="resep/buat.php">Add Recipe</a>
            <a href="#" aria-disabled="true" tabindex="-1">Favorite</a>
            <a class="is-active" href="cari.php">Search</a>
        </nav>

        <p class="home-sidebar__label home-sidebar__label--compact">kategori</p>
        <nav class="home-sidebar__nav home-sidebar__nav--categories" aria-label="Kategori resep">
                        <a href="?">All</a>
            <a href="cari.php?category=food">Food</a>
            <a href="cari.php?category=salad">Salad</a>
            <a href="cari.php?category=dessert">Dessert</a>
            <a href="cari.php?category=drinks">Drinks</a>
        </nav>

        <img src="assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="home-main">
        <header class="home-topbar">
            <form class="home-search-form" method="get" action="cari.php" data-empty-action="home/">
                <label class="sr-only" for="recipe-search">Search recipes</label>
                <input id="recipe-search" class="home-search" type="search" name="q" placeholder="Search Recipes....." value="<?= e($filters['q']) ?>">
                <input type="hidden" name="category" value="<?= e($filters['category']) ?>">
                <input type="hidden" name="difficulty" value="<?= e($filters['difficulty']) ?>">
                <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
            </form>

            <a class="home-add" href="resep/buat.php">
                <img src="assets/img/icon-add-recipe.svg" alt="">
                <span>Add Recipe</span>
            </a>

            <a class="home-cs" href="#">
                <img src="assets/img/icon-cs.svg" alt="">
                <span>Customer<br>Service</span>
            </a>
        </header>

        <section class="home-hero">
            <div>
                <h1>Cari Resep</h1>
                <div class="home-filters" aria-label="Kategori resep">
                    <?php foreach ($categoryPills as $pill): ?>
                        <?php
                        $query = array_filter([
                            'q' => $filters['q'],
                            'category' => $pill['value'],
                            'difficulty' => $filters['difficulty'],
                            'sort' => $filters['sort'],
                        ], static fn ($value) => $value !== '');
                        ?>
                        <a class="home-filter<?= mb_strtolower($filters['category']) === mb_strtolower($pill['value']) ? ' is-active' : '' ?>" href="cari.php?<?= e(http_build_query($query)) ?>"><?= e($pill['label']) ?></a>
                    <?php endforeach; ?>
                </div>
                <form class="home-controls" aria-label="Filter resep lanjutan" method="get" action="cari.php">
                    <input type="hidden" name="q" value="<?= e($filters['q']) ?>">
                    <input type="hidden" name="category" value="<?= e($filters['category']) ?>">

                    <label class="home-select">
                        <span>Difficulty</span>
                        <select name="difficulty" class="home-select__field" onchange="this.form.submit()">
                            <?php foreach ($difficultyOptions as $option): ?>
                                <option value="<?= e($option['value']) ?>" <?= $filters['difficulty'] === $option['value'] ? 'selected' : '' ?>>
                                    <?= e($option['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="home-select">
                        <span>Sort</span>
                        <select name="sort" class="home-select__field" onchange="this.form.submit()">
                            <?php foreach ($sortOptions as $option): ?>
                                <option value="<?= e($option['value']) ?>" <?= $filters['sort'] === $option['value'] ? 'selected' : '' ?>>
                                    <?= e($option['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="home-controls__actions">
                        <button class="home-controls__apply" type="submit">Apply filters</button>
                        <a class="home-controls__clear" href="?">Clear filter</a>
                    </div>
                </form>
            </div>

            <div class="home-stats">
                <strong><?= e((string) $resultCount) ?></strong>
                <span>results</span>
            </div>
        </section>

        <?php if (!$hasQuery && !$hasFilters): ?>
            <section class="recipe-grid__empty" aria-label="Mulai pencarian">
                <h2>Mulai pencarian</h2>
                <p>Gunakan nama resep atau filter untuk mempersempit hasil.</p>
            </section>
        <?php endif; ?>

        <section class="recipe-grid recipe-grid--filtered" aria-label="Hasil pencarian">
            <?php if ($recipes === []): ?>
                <article class="recipe-grid__empty">
                    <h2>Resep tidak ditemukan</h2>
                    <p><?= e($searchHint) ?> belum punya hasil. Coba kata lain atau ubah filter.</p>
                    <a href="?">Reset filter</a>
                    </article>
            <?php else: ?>
                <?php foreach ($recipes as $recipe): ?>
                    <article class="recipe-card">
                        <a class="recipe-card__link" href="resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                            <span class="sr-only">Open recipe <?= e($recipe['title']) ?></span>
                        </a>
                        <div class="recipe-card__panel"></div>
                        <img class="recipe-card__image" src="<?= e(search_asset_path($recipe['image'])) ?>" alt="<?= e($recipe['title']) ?>">
                        <button class="recipe-card__bookmark" type="button" aria-label="Simpan resep">
                            <img src="assets/img/icon-bookmark.svg" alt="">
                        </button>
                        <h2><?= e($recipe['title']) ?></h2>
                        <div class="recipe-card__line"></div>
                        <div class="recipe-card__meta">
                            <span class="recipe-card__stars">★ ★ ☆ ☆</span>
                            <span><?= e($recipe['cook_time']) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    <script src="assets/js/main.js"></script>
</body>
</html>
