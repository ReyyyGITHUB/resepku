<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

$isGuest = empty($_SESSION['user']);
$userName = $_SESSION['user']['name'] ?? 'Guest';
$recipeId = (int) ($_GET['id'] ?? 1);
$recipe = recipe_find_db($recipeId);

if ($recipe === null) {
    $recipe = [
        'id' => 0,
        'title' => 'Recipe not available',
        'image' => '../assets/img/recipe-salad-hero.png',
        'author' => 'ResepKu',
        'author_avatar' => '../assets/img/home-profile.png',
        'cook_time' => '-',
        'servings' => '-',
        'difficulty' => '-',
        'rating' => 0,
        'summary' => 'No recipe data is available yet.',
        'description' => 'No recipe data is available yet.',
        'ingredients' => [],
        'tools' => [],
        'steps' => [],
        'category' => '',
    ];
}

$relatedRecipes = $recipe['id'] > 0 ? recipe_related_db($recipe, 3) : [];

if ($recipe === null) {
    $recipe = [
        'id' => 0,
        'title' => 'Recipe not available',
        'image' => '/assets/img/recipe-salad-hero.png',
        'author' => 'ResepKu',
        'author_avatar' => '/assets/img/home-profile.png',
        'cook_time' => '-',
        'servings' => '-',
        'difficulty' => '-',
        'rating' => 0,
        'summary' => 'No recipe data is available yet.',
        'description' => 'No recipe data is available yet.',
        'ingredients' => [],
        'tools' => [],
        'steps' => [],
        'related' => [],
    ];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($recipe['title']) ?> - Resepku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="detail-page" data-guest-mode="<?= $isGuest ? '1' : '0' ?>">
    <aside class="home-sidebar detail-sidebar">
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

        <nav class="home-sidebar__nav" aria-label="Navigasi Resep">
            <a href="../home/">Home</a>
            <a href="#">Favorite</a>
            <a href="#">My Recipes</a>
            <a class="is-active" href="#">Recipe Detail</a>
            <a href="#">Food</a>
            <a href="#">Salad</a>
            <a href="#">Dessert</a>
            <a href="#">Drinks</a>
        </nav>

        <img src="../assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="detail-main">
        <a class="detail-back" href="../home/" aria-label="Kembali ke halaman home">
            <span aria-hidden="true">←</span>
            <span>Back</span>
        </a>

        <section class="detail-hero">
            <div class="detail-hero__media">
                <img src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
            </div>

            <div class="detail-hero__panel">
                <p class="detail-hero__eyebrow">Recipe Detail</p>
                <h1><?= e($recipe['title']) ?></h1>

                <div class="detail-author">
                    <img src="<?= e($recipe['author_avatar']) ?>" alt="">
                    <div>
                        <strong><?= e($recipe['author']) ?></strong>
                        <span>Shared on ResepKu</span>
                    </div>
                </div>

                <div class="detail-meta">
                    <span><?= e($recipe['cook_time']) ?></span>
                    <span><?= e($recipe['servings']) ?></span>
                    <span><?= e($recipe['difficulty']) ?></span>
                    <span><?= number_format((float) $recipe['rating'], 1) ?> ★</span>
                </div>

                <p class="detail-summary"><?= e($recipe['summary']) ?></p>

                <div class="detail-actions" aria-label="Aksi resep">
                    <button type="button" class="detail-action" data-guest-gate data-action-toggle>Like</button>
                    <button type="button" class="detail-action" data-guest-gate data-action-toggle>Favorite</button>
                    <button type="button" class="detail-action" data-guest-gate data-action-toggle>Rate</button>
                    <button type="button" class="detail-action" data-guest-gate>Share</button>
                </div>
            </div>
        </section>

        <section class="detail-grid">
            <div class="detail-column">
                <article class="detail-panel">
                    <p class="detail-panel__label">About</p>
                    <h2>Description</h2>
                    <p class="detail-panel__text"><?= e($recipe['description']) ?></p>
                </article>

                <div class="detail-duo">
                    <article class="detail-panel">
                        <p class="detail-panel__label">Ingredients</p>
                        <h2>Bahan</h2>
                        <ul class="detail-list">
                            <?php foreach ($recipe['ingredients'] as $ingredient): ?>
                                <li><?= e($ingredient) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </article>

                    <article class="detail-panel">
                        <p class="detail-panel__label">Tools</p>
                        <h2>Peralatan</h2>
                        <ul class="detail-list">
                            <?php foreach ($recipe['tools'] as $tool): ?>
                                <li><?= e($tool) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                </div>

                <article class="detail-panel">
                    <p class="detail-panel__label">Steps</p>
                    <h2>Langkah Memasak</h2>
                    <ol class="detail-steps">
                        <?php foreach ($recipe['steps'] as $step): ?>
                            <li><?= e($step) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </article>
            </div>

            <aside class="detail-side">
                <article class="detail-panel detail-panel--compact">
                    <p class="detail-panel__label">Recommended</p>
                    <h2>Resep Terkait</h2>
                    <div class="detail-related">
                        <?php foreach ($relatedRecipes as $relatedRecipe): ?>
                            <article class="detail-related__item">
                                <img src="<?= e($relatedRecipe['image']) ?>" alt="<?= e($relatedRecipe['title']) ?>">
                                <div>
                                    <h3><?= e($relatedRecipe['title']) ?></h3>
                                    <p><?= e($relatedRecipe['cook_time']) ?></p>
                                    <a href="detail.php?id=<?= e((string) $relatedRecipe['id']) ?>">Get the recipe</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="detail-panel detail-panel--guest">
                    <p class="detail-panel__label">Guest Mode</p>
                    <h2>Unlock actions</h2>
                    <p class="detail-panel__text">Like, favorite, rating, and comments will open a register gate for guest users.</p>
                    <button type="button" class="detail-action detail-action--full" data-guest-gate>Create account</button>
                </article>
            </aside>
        </section>
    </main>

    <div class="guest-modal" data-guest-modal aria-hidden="true">
        <div class="guest-modal__backdrop" data-guest-close></div>
        <div class="guest-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="guest-modal-title">
            <p class="guest-modal__eyebrow">Guest Mode</p>
            <h2 id="guest-modal-title">Register to unlock actions</h2>
            <p>Like, favorite, rating, and comment are available after you create an account.</p>
            <div class="guest-modal__actions">
                <a class="guest-modal__primary" href="../auth/register.php">Create account</a>
                <button type="button" class="guest-modal__secondary" data-guest-close>Maybe later</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
