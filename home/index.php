<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

$isAdmin = isAdmin();
$isGuest = !empty($_SESSION['guest_mode']) && empty($_SESSION['user']);
$userName = $isGuest ? 'Guest' : ($_SESSION['user']['name'] ?? 'Nayaka');
$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'category' => trim((string) ($_GET['category'] ?? '')),
    'difficulty' => trim((string) ($_GET['difficulty'] ?? '')),
    'max_time' => trim((string) ($_GET['max_time'] ?? '')),
    'sort' => trim((string) ($_GET['sort'] ?? 'newest')),
];

$isDefaultView = $filters['q'] === ''
    && $filters['category'] === ''
    && $filters['difficulty'] === ''
    && $filters['max_time'] === ''
    && $filters['sort'] === 'newest';

$recipes = recipe_catalog_filtered_db($filters, 24);
$defaultRecipes = $isDefaultView ? recipe_catalog_from_db() : [];
$topRecipes = $isDefaultView ? array_slice($defaultRecipes, 0, 4) : [];
$bottomRecipes = $isDefaultView ? array_slice($defaultRecipes, 4) : [];
$activeCategory = mb_strtolower($filters['category']);

$categoryPills = [
    ['label' => 'Foods', 'value' => 'food'],
    ['label' => 'Salads', 'value' => 'salad'],
    ['label' => 'Dessert', 'value' => 'dessert'],
    ['label' => 'Drinks', 'value' => 'drinks'],
];

$difficultyOptions = [
    ['label' => 'All difficulty', 'value' => ''],
    ['label' => 'Easy', 'value' => 'mudah'],
    ['label' => 'Medium', 'value' => 'sedang'],
    ['label' => 'Hard', 'value' => 'sulit'],
];

$timeOptions = [
    ['label' => 'Any time', 'value' => ''],
    ['label' => '< 15 mins', 'value' => '15'],
    ['label' => '< 30 mins', 'value' => '30'],
    ['label' => '< 60 mins', 'value' => '60'],
];

$sortOptions = [
    ['label' => 'Newest', 'value' => 'newest'],
    ['label' => 'Popular', 'value' => 'popular'],
    ['label' => 'Oldest', 'value' => 'oldest'],
];

$featured = [
    'title' => 'Special Salad Chicken',
    'image' => '../assets/img/recipe-salad-hero.png',
    'summary' => 'Enjoy the perfect combination of protein-rich grilled chicken breast and fresh vegetables.',
    'cook_time' => '20 mins',
    'id' => null,
];

$sideRecipes = [
    [
        'title' => 'Fresh Salad Bowl',
        'image' => '../assets/img/recipe-salad-card.png',
        'id' => null,
    ],
    [
        'title' => 'Green Matcha Drink',
        'image' => '../assets/img/recipe-salad-card.png',
        'id' => null,
    ],
];

if ($recipes === []) {
    $topRecipes = [[
        'id' => 0,
        'title' => 'Belum ada resep',
        'image' => '../assets/img/recipe-salad-card.png',
        'cook_time' => '-',
    ]];
    $bottomRecipes = [];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Resepku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="home-page">
    <aside class="home-sidebar" data-node-id="16:154">
        <div class="home-sidebar__profile">
            <div class="home-sidebar__brand">
                <img src="../assets/img/resepku-logo.png" alt="" class="home-sidebar__logo">
                <div>
                    <p class="home-sidebar__name">Resepku</p>
                    <p class="home-sidebar__status"><?= $isGuest ? 'Guest mode' : 'Signed in' ?></p>
                </div>
            </div>

            <div class="home-sidebar__identity">
                <img src="../assets/img/home-profile.png" alt="" class="home-sidebar__avatar">
                <div class="home-sidebar__welcome">
                    <strong><?= e($userName) ?></strong>
                    <span><?= $isGuest ? 'Login untuk simpan resep dan kelola profil.' : 'Akses resep pribadi dan aktivitas akun.' ?></span>
                </div>
            </div>

            <a href="../auth/logout.php" class="home-sidebar__logout">Log Out</a>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi Home">
            <a class="is-active" href="../home/">Home</a>
            <a href="../profil/">Profile</a>
            <a href="../resep/myresep.php">My Recipes</a>
            <a href="../resep/buat.php">Add Recipe</a>
            <a href="../resep/favorite.php">Favorite</a>
            <?php if ($isAdmin): ?>
                <a href="../admin/">Admin</a>
            <?php endif; ?>
            <a href="../cari.php">Search</a>
        </nav>

        <p class="home-sidebar__label home-sidebar__label--compact">kategori</p>
        <nav class="home-sidebar__nav home-sidebar__nav--categories" aria-label="Kategori resep">
            <a href="#">Food</a>
            <a href="#">Salad</a>
            <a href="#">Dessert</a>
            <a href="#">Drinks</a>
        </nav>

        <img src="../assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="home-main" data-node-id="1:312">
        <header class="home-topbar">
            <form class="home-search-form" method="get" action="../cari.php" data-empty-action="../home/">
                <label class="sr-only" for="recipe-search">Search recipes</label>
                <input id="recipe-search" class="home-search" type="search" name="q" placeholder="Search Recipes....." value="<?= e($filters['q']) ?>">
                <input type="hidden" name="category" value="<?= e($filters['category']) ?>">
                <input type="hidden" name="difficulty" value="<?= e($filters['difficulty']) ?>">
                <input type="hidden" name="max_time" value="<?= e($filters['max_time']) ?>">
                <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
            </form>

            <a class="home-add" href="../resep/buat.php">
                <img src="../assets/img/icon-add-recipe.svg" alt="">
                <span>Add Recipe</span>
            </a>

            <a class="home-cs" href="#">
                <img src="../assets/img/icon-cs.svg" alt="">
                <span>Customer<br>Service</span>
            </a>
        </header>

        <section class="home-hero">
            <div>
                <h1>Learn, Cook, &amp; Eat Your Food</h1>
                <div class="home-filters" aria-label="Kategori resep">
                    <?php foreach ($categoryPills as $pill): ?>
                        <?php
                        $query = array_filter([
                            'q' => $filters['q'],
                            'category' => $pill['value'],
                            'difficulty' => $filters['difficulty'],
                            'max_time' => $filters['max_time'],
                            'sort' => $filters['sort'],
                        ], static fn ($value) => $value !== '');
                        ?>
                        <a class="home-filter<?= $activeCategory === mb_strtolower($pill['value']) ? ' is-active' : '' ?>" href="../home/?<?= e(http_build_query($query)) ?>"><?= e($pill['label']) ?></a>
                    <?php endforeach; ?>
                </div>
                <form class="home-controls" aria-label="Filter resep lanjutan" method="get" action="../cari.php">
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
                        <span>Time</span>
                        <select name="max_time" class="home-select__field" onchange="this.form.submit()">
                            <?php foreach ($timeOptions as $option): ?>
                                <option value="<?= e($option['value']) ?>" <?= $filters['max_time'] === $option['value'] ? 'selected' : '' ?>>
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
                        <a class="home-controls__clear" href="../home/">Clear filter</a>
                    </div>
                </form>
            </div>

            <div class="home-stats">
                <strong>+19</strong>
                <span>new recipes</span>
            </div>
        </section>

        <?php if ($isDefaultView): ?>
            <section class="recipe-grid" aria-label="Daftar resep">
                <?php foreach ($topRecipes as $recipe): ?>
                    <article class="recipe-card" data-node-id="16:155">
                        <?php if (!empty($recipe['id'])): ?>
                            <a class="recipe-card__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                                <span class="sr-only">Open recipe <?= e($recipe['title']) ?></span>
                            </a>
                        <?php endif; ?>
                        <div class="recipe-card__panel"></div>
                        <img class="recipe-card__image" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                        <button class="recipe-card__bookmark" type="button" aria-label="Simpan resep">
                            <img src="../assets/img/icon-bookmark.svg" alt="">
                        </button>
                        <h2><?= e($recipe['title']) ?></h2>
                        <div class="recipe-card__line"></div>
                        <div class="recipe-card__meta">
                            <span class="recipe-card__stars">★ ★ ☆ ☆</span>
                            <span><?= e($recipe['cook_time']) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="home-content-row">
                <?php if ($featured): ?>
                <article class="feature-card" data-node-id="16:163">
                    <img class="feature-card__image" src="<?= e($featured['image']) ?>" alt="<?= e($featured['title']) ?>">
                    <div class="feature-card__body">
                        <h2><?= e($featured['title']) ?></h2>
                        <p><?= e($featured['summary']) ?></p>
                        <div class="feature-card__footer">
                            <a href="#">Get the recipe</a>
                            <span>Cook Time : <?= e($featured['cook_time']) ?></span>
                        </div>
                    </div>
                </article>
                <?php endif; ?>

                <aside class="side-recipes" aria-label="Resep rekomendasi">
                    <?php foreach ($sideRecipes as $recipe): ?>
                        <article>
                            <img src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                            <div>
                                <h3><?= e($recipe['title']) ?></h3>
                                <a href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">Get the recipe</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </aside>
            </section>

            <?php if ($bottomRecipes !== []): ?>
            <section class="recipe-grid recipe-grid--bottom" aria-label="Resep lainnya">
                <?php foreach ($bottomRecipes as $recipe): ?>
                    <article class="recipe-card" data-node-id="16:155">
                        <?php if (!empty($recipe['id'])): ?>
                            <a class="recipe-card__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                                <span class="sr-only">Open recipe <?= e($recipe['title']) ?></span>
                            </a>
                        <?php endif; ?>
                        <div class="recipe-card__panel"></div>
                        <img class="recipe-card__image" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                        <button class="recipe-card__bookmark" type="button" aria-label="Simpan resep">
                            <img src="../assets/img/icon-bookmark.svg" alt="">
                        </button>
                        <h2><?= e($recipe['title']) ?></h2>
                        <div class="recipe-card__line"></div>
                        <div class="recipe-card__meta">
                            <span class="recipe-card__stars">★ ★ ☆ ☆</span>
                            <span><?= e($recipe['cook_time']) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>
        <?php else: ?>
            <section class="recipe-grid recipe-grid--filtered" aria-label="Daftar resep terfilter">
                <?php if ($recipes === []): ?>
                    <article class="recipe-grid__empty">
                        <h2>Resep tidak ditemukan</h2>
                        <p>Coba ubah kata kunci, kategori, atau filter lain.</p>
                        <a href="../home/">Reset filter</a>
                    </article>
                <?php endif; ?>
                <?php foreach ($recipes as $recipe): ?>
                    <article class="recipe-card" data-node-id="16:155">
                        <?php if (!empty($recipe['id'])): ?>
                            <a class="recipe-card__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                                <span class="sr-only">Open recipe <?= e($recipe['title']) ?></span>
                            </a>
                        <?php endif; ?>
                        <div class="recipe-card__panel"></div>
                        <img class="recipe-card__image" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                        <button class="recipe-card__bookmark" type="button" aria-label="Simpan resep">
                            <img src="../assets/img/icon-bookmark.svg" alt="">
                        </button>
                        <h2><?= e($recipe['title']) ?></h2>
                        <div class="recipe-card__line"></div>
                        <div class="recipe-card__meta">
                            <span class="recipe-card__stars">★ ★ ☆ ☆</span>
                            <span><?= e($recipe['cook_time']) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>
