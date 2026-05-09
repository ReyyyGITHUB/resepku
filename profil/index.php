<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

$isAdmin = isAdmin();
$currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
$requestedUserId = (int) ($_GET['id'] ?? 0);
$isPublicProfile = $requestedUserId > 0;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

if (!$isPublicProfile && $currentUserId <= 0) {
    redirectTo('../auth/login.php');
}

if ($isPublicProfile && $currentUserId > 0 && $requestedUserId === $currentUserId) {
    redirectTo('../profil/');
}

$profileUserId = $isPublicProfile ? $requestedUserId : $currentUserId;
$profile = $profileUserId > 0 ? recipe_user_profile_db($profileUserId) : null;
$isUnavailable = $profile === null || ($isPublicProfile && $profile['status'] !== 'aktif');
$isFollowingProfile = !$isUnavailable && $isPublicProfile && $currentUserId > 0
    ? recipe_is_following_db($currentUserId, $profileUserId)
    : false;
$recipes = !$isUnavailable ? recipe_user_recipes_db($profileUserId, 12) : [];
$sidebarProfile = $currentUserId > 0 ? recipe_user_profile_db($currentUserId) : null;
$sidebarName = $sidebarProfile['name'] ?? 'Guest';
$sidebarAvatar = $sidebarProfile['avatar'] ?? '../assets/img/home-profile.png';
$sidebarBio = $sidebarProfile['bio'] ?? '';
$pageTitle = $isPublicProfile && !$isUnavailable ? $profile['name'] . ' - Resepku' : 'Profile - Resepku';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="profile-page" data-guest-mode="<?= $currentUserId > 0 ? '0' : '1' ?>" data-csrf-token="<?= e(csrfToken()) ?>">
    <aside class="home-sidebar" data-node-id="16:154">
        <div class="home-sidebar__profile">
            <div class="home-sidebar__brand">
                <img src="../assets/img/resepku-logo.png" alt="" class="home-sidebar__logo">
                <div>
                    <p class="home-sidebar__name">Resepku</p>
                    <p class="home-sidebar__status"><?= $currentUserId > 0 ? 'Signed in' : 'Guest mode' ?></p>
                </div>
            </div>

            <div class="home-sidebar__identity">
                <img src="<?= e($sidebarAvatar) ?>" alt="<?= e($sidebarName) ?>" class="home-sidebar__avatar">
                <div class="home-sidebar__welcome">
                    <strong><?= e($sidebarName) ?></strong>
                    <span><?= $sidebarBio !== '' ? e($sidebarBio) : ($currentUserId > 0 ? 'Kelola akun dan resep publik kamu dari sini.' : 'Jelajahi profil dan resep komunitas.') ?></span>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <a href="../admin/" class="home-sidebar__admin-panel">Admin Panel</a>
            <?php endif; ?>

            <?php if ($currentUserId > 0): ?>
                <a href="../auth/logout.php" class="home-sidebar__logout">Log Out</a>
            <?php else: ?>
                <a href="../auth/login.php" class="home-sidebar__logout">Login</a>
            <?php endif; ?>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi Profil">
            <a href="../home/">Home</a>
            <?php if ($currentUserId > 0): ?>
                <a class="<?= !$isPublicProfile ? 'is-active' : '' ?>" href="../profil/">Profile</a>
                <a href="../resep/myresep.php">My Recipes</a>
                <a href="../resep/buat.php">Add Recipe</a>
                <a href="../resep/favorite.php">Favorite</a>
            <?php else: ?>
                <a href="../auth/login.php">Login</a>
                <a href="../auth/register.php">Register</a>
            <?php endif; ?>
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
        <?php if ($flashSuccess): ?>
            <div class="profile-alert profile-alert--success" role="status">
                <?= e($flashSuccess) ?>
            </div>
        <?php endif; ?>

        <?php if ($isUnavailable): ?>
            <section class="profile-hero" aria-label="Profil tidak tersedia">
                <div class="profile-hero__content">
                    <img class="profile-hero__avatar" src="../assets/img/home-profile.png" alt="">
                    <h1 class="profile-hero__name">Profile tidak tersedia</h1>
                    <p class="profile-hero__message">User tidak aktif, tidak bisa melihat profile.</p>
                    <div class="profile-actions">
                        <a class="profile-actions__primary" href="../home/">Kembali ke Home</a>
                    </div>
                </div>
            </section>
        <?php else: ?>
        <section class="profile-hero" aria-label="Profil pengguna">
            <div class="profile-hero__content">
                <img class="profile-hero__avatar" src="<?= e($profile['avatar']) ?>" alt="<?= e($profile['name']) ?>">
                <h1 class="profile-hero__name"><?= e($profile['name']) ?></h1>
                <div class="profile-hero__badge">
                    <?= $profile['role'] === 'admin' ? 'Admin Community' : 'Member Community' ?>
                </div>

                <?php if (trim((string) ($profile['bio'] ?? '')) !== ''): ?>
                    <p class="profile-hero__bio"><?= e((string) $profile['bio']) ?></p>
                <?php endif; ?>

                <?php if (!$isPublicProfile): ?>
                    <a class="profile-hero__edit" href="../profil/edit.php">Edit Profile</a>
                <?php endif; ?>

                <div class="profile-stats" aria-label="Statistik profil">
                    <div class="profile-stat">
                        <strong><?= e((string) $profile['recipe_count']) ?></strong>
                        <span>Recipe</span>
                    </div>
                    <div class="profile-stat">
                        <strong data-follower-count><?= e((string) $profile['follower_count']) ?></strong>
                        <span>Follower</span>
                    </div>
                    <div class="profile-stat">
                        <strong><?= e((string) $profile['following_count']) ?></strong>
                        <span>Following</span>
                    </div>
                </div>

                <div class="profile-actions">
                    <?php if ($isPublicProfile): ?>
                        <button
                            class="profile-actions__primary<?= $isFollowingProfile ? ' is-active' : '' ?>"
                            type="button"
                            data-guest-gate
                            data-social-action="follow"
                            data-user-id="<?= e((string) $profileUserId) ?>"
                        ><?= $isFollowingProfile ? 'Following' : 'Follow' ?></button>
                    <?php else: ?>
                        <a class="profile-actions__primary" href="../resep/buat.php">Add Recipe</a>
                        <a class="profile-actions__secondary" href="../auth/logout.php">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="profile-section" aria-label="<?= $isPublicProfile ? 'Daftar resep pengguna' : 'Daftar resep saya' ?>">
            <div class="profile-section__head">
                <div>
                    <h2><?= $isPublicProfile ? 'Recipes' : 'My Recipes' ?></h2>
                    <p><?= $isPublicProfile ? 'Resep yang dibagikan pengguna ini ke komunitas.' : 'Resep yang kamu bagikan ke komunitas.' ?></p>
                </div>
            </div>

            <?php if ($recipes === []): ?>
                <div class="profile-empty-wrap">
                    <article class="profile-empty">
                        <h2>Belum ada resep</h2>
                        <p><?= $isPublicProfile ? 'Belum ada resep dari pengguna ini.' : 'Buat resep pertama supaya profilmu langsung terisi.' ?></p>
                        <?php if (!$isPublicProfile): ?>
                            <a href="../resep/buat.php">Tambah resep</a>
                        <?php endif; ?>
                    </article>
                </div>
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
        <?php endif; ?>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>
