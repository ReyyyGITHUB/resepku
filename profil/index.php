<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

if (empty($_SESSION['user'])) {
    redirectTo('../auth/login.php');
}

$userId = (int) ($_SESSION['user']['id'] ?? 0);
$profile = $userId > 0 ? recipe_user_profile_db($userId) : null;

if ($profile === null) {
    redirectTo('../home/');
}

$recipes = recipe_user_recipes_db($userId, 12);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Resepku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="profile-page">
    <aside class="home-sidebar" data-node-id="16:154">
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
                    <span><?= $profile['bio'] !== '' ? e($profile['bio']) : 'Kelola akun dan resep publik kamu dari sini.' ?></span>
                </div>
            </div>

            <a href="../auth/logout.php" class="home-sidebar__logout">Log Out</a>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi Profil">
            <a href="../home/">Home</a>
            <a class="is-active" href="../profil/">Profile</a>
            <a href="#" aria-disabled="true" tabindex="-1">My Recipes</a>
            <a href="../resep/buat.php">Add Recipe</a>
            <a href="#" aria-disabled="true" tabindex="-1">Favorite</a>
        </nav>

        <p class="home-sidebar__label home-sidebar__label--compact">kategori</p>
        <nav class="home-sidebar__nav home-sidebar__nav--categories" aria-label="Kategori resep">
            <a href="../home/?category=food">Food</a>
            <a href="../home/?category=salad">Salad</a>
            <a href="../home/?category=dessert">Dessert</a>
            <a href="../home/?category=drinks">Drinks</a>
        </nav>

        <img src="../assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="profile-main">
        <section class="profile-hero" aria-label="Profil pengguna">
            <div class="profile-hero__content">
                <img class="profile-hero__avatar" src="<?= e($profile['avatar']) ?>" alt="<?= e($profile['name']) ?>">
                <h1 class="profile-hero__name"><?= e($profile['name']) ?></h1>
                <div class="profile-hero__badge">
                    <?= $profile['role'] === 'admin' ? 'Admin Community' : 'Member Community' ?>
                </div>

                <div class="profile-stats" aria-label="Statistik profil">
                    <div class="profile-stat">
                        <strong><?= e((string) $profile['recipe_count']) ?></strong>
                        <span>Recipe</span>
                    </div>
                    <div class="profile-stat">
                        <strong><?= e((string) $profile['follower_count']) ?></strong>
                        <span>Follower</span>
                    </div>
                    <div class="profile-stat">
                        <strong><?= e((string) $profile['following_count']) ?></strong>
                        <span>Following</span>
                    </div>
                </div>

                <div class="profile-actions">
                    <a class="profile-actions__primary" href="../resep/buat.php">Add Recipe</a>
                    <a class="profile-actions__secondary" href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </section>

        <section class="profile-section" aria-label="Daftar resep saya">
            <div class="profile-section__head">
                <div>
                    <h2>My Recipes</h2>
                    <p>Resep yang kamu bagikan ke komunitas.</p>
                </div>
            </div>

            <?php if ($recipes === []): ?>
                <article class="profile-empty">
                    <h2>Belum ada resep</h2>
                    <p>Buat resep pertama supaya profilmu langsung terisi.</p>
                    <a href="../resep/buat.php">Tambah resep</a>
                </article>
            <?php else: ?>
                <section class="recipe-grid profile-grid" aria-label="Resep profil">
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
        </section>
    </main>
</body>
</html>
