<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

$isGuest = !empty($_SESSION['guest_mode']) && empty($_SESSION['user']);
$userName = $isGuest ? 'Guest' : ($_SESSION['user']['name'] ?? 'Nayaka');
$recipes = recipe_catalog_from_db(8);
$topRecipes = array_slice($recipes, 0, 4);
$bottomRecipes = array_slice($recipes, 4, 4);

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
        <div class="home-sidebar__brand">
            <img src="../assets/img/resepku-logo.png" alt="" class="home-sidebar__logo">
            <p class="home-sidebar__name">Resepku</p>
            <p class="home-sidebar__user"><?= e($userName) ?></p>
            <img src="../assets/img/home-profile.png" alt="" class="home-sidebar__avatar">
            <p class="home-sidebar__welcome">welcome back!<br><?= e($userName) ?></p>
            <a href="../auth/logout.php" class="home-sidebar__logout">Log Out</a>
        </div>

        <div class="home-sidebar__divider"></div>
        <p class="home-sidebar__label">kategori</p>

        <nav class="home-sidebar__nav" aria-label="Navigasi Home">
            <a class="is-active" href="#">Home</a>
            <a href="#">Favorite</a>
            <a href="#">My Recipes</a>
            <a href="#">Search Recipe</a>
            <a href="#">Food</a>
            <a href="#">Salad</a>
            <a href="#">Dessert</a>
            <a href="#">Drinks</a>
        </nav>

        <img src="../assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="home-main" data-node-id="1:312">
        <header class="home-topbar">
            <label class="sr-only" for="recipe-search">Search recipes</label>
            <input id="recipe-search" class="home-search" type="search" placeholder="Search Recipes.....">

            <a class="home-add" href="#">
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
                    <button>Foods</button>
                    <button>salads</button>
                    <button>Dessert</button>
                    <button>Drinks</button>
                </div>
            </div>

            <div class="home-stats">
                <strong>+19</strong>
                <span>new recipes</span>
            </div>
        </section>

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
    </main>
</body>
</html>
