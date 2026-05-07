<?php

require_once __DIR__ . '/../config/helpers.php';

startSession();

$isGuest = !empty($_SESSION['guest_mode']) && empty($_SESSION['user']);
$userName = $isGuest ? 'Guest' : ($_SESSION['user']['name'] ?? 'Nayaka');

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
            <?php for ($i = 0; $i < 4; $i++): ?>
                <article class="recipe-card" data-node-id="16:155">
                    <div class="recipe-card__panel"></div>
                    <img class="recipe-card__image" src="../assets/img/recipe-salad-card.png" alt="Special Salad Chicken">
                    <button class="recipe-card__bookmark" type="button" aria-label="Simpan resep">
                        <img src="../assets/img/icon-bookmark.svg" alt="">
                    </button>
                    <h2>Special Salad Chiken</h2>
                    <div class="recipe-card__line"></div>
                    <div class="recipe-card__meta">
                        <span class="recipe-card__stars">★ ★ ☆ ☆</span>
                        <span>20 mins</span>
                    </div>
                </article>
            <?php endfor; ?>
        </section>

        <section class="home-content-row">
            <article class="feature-card" data-node-id="16:163">
                <img class="feature-card__image" src="../assets/img/recipe-salad-card.png" alt="Special Salad Chicken">
                <div class="feature-card__body">
                    <h2>Special Salad<br>Chicken</h2>
                    <p>Enjoy the perfect combination of protein-rich grilled chicken breast and a selection of fresh vegetables (such as romaine lettuce, cherry tomatoes, cucumbers, and purple cabbage). Served with a light and appetizing special dressing, this dish is not only delicious, but also healthy and filling. The perfect choice for your healthy lifestyle.</p>
                    <div class="feature-card__footer">
                        <a href="#">Get the recipe</a>
                        <span>Cook Time : 20mins</span>
                    </div>
                </div>
            </article>

            <aside class="side-recipes" aria-label="Resep rekomendasi">
                <article>
                    <img src="../assets/img/recipe-salad-card.png" alt="">
                    <div>
                        <h3>Japanese Macha</h3>
                        <a href="#">Get the recipe</a>
                    </div>
                </article>
                <article>
                    <img src="../assets/img/recipe-salad-card.png" alt="">
                    <div>
                        <h3>Sweet Strawberry Cake</h3>
                        <a href="#">Get the recipe</a>
                    </div>
                </article>
            </aside>
        </section>

        <section class="recipe-grid recipe-grid--bottom" aria-label="Resep lainnya">
            <?php for ($i = 0; $i < 4; $i++): ?>
                <article class="recipe-card" data-node-id="16:155">
                    <div class="recipe-card__panel"></div>
                    <img class="recipe-card__image" src="../assets/img/recipe-salad-card.png" alt="Special Salad Chicken">
                    <button class="recipe-card__bookmark" type="button" aria-label="Simpan resep">
                        <img src="../assets/img/icon-bookmark.svg" alt="">
                    </button>
                    <h2>Special Salad Chiken</h2>
                    <div class="recipe-card__line"></div>
                    <div class="recipe-card__meta">
                        <span class="recipe-card__stars">★ ★ ☆ ☆</span>
                        <span>20 mins</span>
                    </div>
                </article>
            <?php endfor; ?>
        </section>
    </main>
</body>
</html>
