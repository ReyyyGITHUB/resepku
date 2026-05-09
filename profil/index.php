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
$recentRecipes = !$isUnavailable ? recipe_user_recipes_db($profileUserId, 8) : [];
$communityRecipes = !$isUnavailable ? array_values(array_filter(
    recipe_catalog_from_db(6),
    static fn (array $recipe) => (int) ($recipe['user_id'] ?? 0) !== $profileUserId
)) : [];
$suggestedAccounts = [];

if (!$isUnavailable) {
    $accountSql = <<<SQL
        SELECT
            p.pengguna_id AS user_id,
            p.nama_pengguna AS name,
            p.foto_profil AS avatar,
            p.bio,
            (
                SELECT COUNT(*)
                FROM following f
                WHERE f.following_id_user = p.pengguna_id
            ) AS follower_count
        FROM pengguna p
        WHERE p.status = 'aktif'
          AND p.pengguna_id <> :profile_user_id
    SQL;

    $accountParams = [':profile_user_id' => $profileUserId];
    if ($currentUserId > 0) {
        $accountSql .= ' AND p.pengguna_id <> :current_user_id';
        $accountParams[':current_user_id'] = $currentUserId;
    }

    $accountSql .= ' ORDER BY follower_count DESC, p.dibuat_pada DESC LIMIT 5';
    $accountStmt = db()->prepare($accountSql);
    $accountStmt->execute($accountParams);
    $suggestedAccounts = $accountStmt->fetchAll() ?: [];
}
$sidebarProfile = $currentUserId > 0 ? recipe_user_profile_db($currentUserId) : null;
$sidebarName = $sidebarProfile['name'] ?? 'Guest';
$sidebarAvatar = $sidebarProfile['avatar'] ?? '../assets/img/home-profile.png';
$sidebarBio = $sidebarProfile['bio'] ?? '';
$pageTitle = $isPublicProfile && !$isUnavailable ? $profile['name'] . ' - Resepku' : 'Profile - Resepku';
$profileRoleLabel = !$isUnavailable
    ? ($profile['role'] === 'admin' ? 'Admin Community' : 'Member Community')
    : 'Profile unavailable';
$profileJoined = !$isUnavailable && !empty($profile['joined_at'])
    ? date('d M Y', strtotime($profile['joined_at']))
    : '-';

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
    <aside class="home-sidebar profile-sidebar" data-node-id="16:154">
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
            <section class="profile-empty-state" aria-label="Profil tidak tersedia">
                <div class="profile-empty-state__card">
                    <img class="profile-empty-state__avatar" src="../assets/img/home-profile.png" alt="">
                    <p class="profile-empty-state__eyebrow">Profile</p>
                    <h1>Profile tidak tersedia</h1>
                    <p>User tidak aktif, tidak bisa melihat profile.</p>
                    <div class="profile-actions">
                        <a class="profile-actions__primary" href="../home/">Kembali ke Home</a>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <section class="profile-summary" aria-label="Profil pengguna">
                <div class="profile-summary__card">
                    <div class="profile-summary__avatar-wrap">
                        <img class="profile-summary__avatar" src="<?= e($profile['avatar']) ?>" alt="<?= e($profile['name']) ?>">
                    </div>

                    <div class="profile-summary__content">
                        <div class="profile-summary__header">
                            <div>
                                <h1 class="profile-summary__name"><?= e($profile['name']) ?></h1>
                                <div class="profile-summary__badge"><?= e($profileRoleLabel) ?></div>
                            </div>

                            <?php if ($isPublicProfile): ?>
                                <button
                                    class="profile-summary__follow<?= $isFollowingProfile ? ' is-active' : '' ?>"
                                    type="button"
                                    data-guest-gate
                                    data-social-action="follow"
                                    data-user-id="<?= e((string) $profileUserId) ?>"
                                    data-follow-label="Follow"
                                    data-followed-label="Following"
                                ><?= $isFollowingProfile ? 'Following' : 'Follow' ?></button>
                            <?php else: ?>
                                <button class="profile-summary__edit" type="button" data-profile-edit-open>Edit Profile</button>
                            <?php endif; ?>
                        </div>

                        <?php if (trim((string) ($profile['bio'] ?? '')) !== ''): ?>
                            <p class="profile-summary__bio"><?= e((string) $profile['bio']) ?></p>
                        <?php else: ?>
                            <p class="profile-summary__bio profile-summary__bio--muted">
                                Profil ini belum punya bio. Tambahkan deskripsi singkat untuk memperjelas identitas akun.
                            </p>
                        <?php endif; ?>

                        <div class="profile-summary__stats" aria-label="Statistik profil">
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
                    </div>
                </div>
            </section>

            <section class="profile-bento" aria-label="Profile content">
                <div class="profile-bento__main">
                    <section class="profile-panel profile-panel--recent" aria-label="Recent recipes">
                        <div class="profile-panel__head">
                            <div>
                                <p class="profile-panel__kicker">Recent Recipes</p>
                                <h2>Recent Recipes</h2>
                            </div>
                            <a href="../home/" class="profile-panel__link">Lihat semua →</a>
                        </div>

                        <?php if ($recentRecipes === []): ?>
                            <div class="profile-panel__empty">Belum ada resep untuk ditampilkan.</div>
                        <?php else: ?>
                            <section class="profile-recipe-grid" aria-label="Recent recipes list">
                                <?php foreach ($recentRecipes as $recipe): ?>
                                    <article class="profile-recipe-card">
                                        <?php if (!empty($recipe['id'])): ?>
                                            <a class="profile-recipe-card__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                                                <span class="sr-only">Open recipe <?= e($recipe['title']) ?></span>
                                            </a>
                                        <?php endif; ?>
                                        <button class="profile-recipe-card__bookmark" type="button" aria-label="Simpan resep">
                                            <img src="../assets/img/icon-bookmark.svg" alt="">
                                        </button>
                                        <img class="profile-recipe-card__image" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                                        <div class="profile-recipe-card__body">
                                            <h3><?= e($recipe['title']) ?></h3>
                                            <div class="profile-recipe-card__rating">★★★★☆</div>
                                            <span><?= e($recipe['cook_time']) ?></span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </section>
                        <?php endif; ?>
                    </section>
                </div>

                <aside class="profile-bento__aside">
                    <section class="profile-panel profile-panel--activity" aria-label="Community activity">
                        <div class="profile-panel__head">
                            <div>
                                <p class="profile-panel__kicker">Community Activity</p>
                                <h2>Community Activity</h2>
                            </div>
                            <a href="../home/" class="profile-panel__link">View all</a>
                        </div>

                        <?php if ($communityRecipes === []): ?>
                            <div class="profile-panel__empty">Belum ada aktivitas komunitas.</div>
                        <?php else: ?>
                            <div class="profile-activity-list">
                                <?php foreach ($communityRecipes as $recipe): ?>
                                    <article class="profile-activity-item">
                                        <?php if (!empty($recipe['id'])): ?>
                                            <a class="profile-activity-item__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                                                <span class="sr-only">Open recipe <?= e($recipe['title']) ?></span>
                                            </a>
                                        <?php endif; ?>
                                        <img class="profile-activity-item__avatar" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                                        <div class="profile-activity-item__copy">
                                            <strong><?= e($recipe['title']) ?></strong>
                                            <span><?= e($recipe['cook_time']) ?></span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="profile-panel profile-panel--suggested" aria-label="Suggested accounts">
                        <div class="profile-panel__head">
                            <div>
                                <p class="profile-panel__kicker">Suggested for you</p>
                                <h2>Suggested for you</h2>
                            </div>
                            <a href="../home/" class="profile-panel__link">See all</a>
                        </div>

                        <?php if ($suggestedAccounts === []): ?>
                            <div class="profile-panel__empty">Belum ada akun yang disarankan.</div>
                        <?php else: ?>
                            <div class="profile-account-list">
                                <?php foreach ($suggestedAccounts as $account): ?>
                                    <?php $accountId = (int) ($account['user_id'] ?? 0); ?>
                                    <article class="profile-account-item">
                                        <img class="profile-account-item__avatar" src="<?= e(recipe_asset_path($account['avatar'] ?? null)) ?>" alt="<?= e($account['name'] ?? 'User') ?>">
                                        <div class="profile-account-item__copy">
                                            <strong><?= e($account['name'] ?? 'User') ?></strong>
                                            <span><?= e((string) ($account['follower_count'] ?? 0)) ?> mutual friends</span>
                                        </div>
                                        <button
                                            class="profile-account-item__follow"
                                            type="button"
                                            data-guest-gate
                                            data-social-action="follow"
                                            data-user-id="<?= e((string) $accountId) ?>"
                                            data-follow-label="Follow"
                                            data-followed-label="Followed"
                                        >Follow</button>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </aside>
            </section>

            <?php if (!$isPublicProfile): ?>
                <div class="profile-modal" data-profile-edit-modal aria-hidden="true">
                    <div class="profile-modal__backdrop" data-profile-edit-close></div>
                    <section class="profile-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="profile-edit-title">
                        <button class="profile-modal__close" type="button" aria-label="Close" data-profile-edit-close>×</button>

                        <div class="profile-modal__header">
                            <p class="profile-modal__eyebrow">Edit profile</p>
                            <h2 id="profile-edit-title">Edit Profile</h2>
                            <p>Ubah nama, bio, dan password dari popup ini.</p>
                        </div>

                        <form class="profile-edit-form" action="../profil/edit.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                            <div class="profile-edit-form__grid">
                                <label class="profile-field">
                                    <span>Nama</span>
                                    <input class="profile-input" type="text" name="name" maxlength="50" value="<?= e((string) $profile['name']) ?>" required>
                                </label>

                                <label class="profile-field">
                                    <span>Email</span>
                                    <input class="profile-input" type="email" name="email" value="<?= e((string) $profile['email']) ?>" readonly>
                                </label>

                                <label class="profile-field profile-field--full">
                                    <span>Bio</span>
                                    <textarea class="profile-input profile-textarea" name="bio" rows="4" maxlength="255"><?= e((string) ($profile['bio'] ?? '')) ?></textarea>
                                </label>
                            </div>

                            <details class="profile-accordion">
                                <summary class="profile-accordion__summary">
                                    <div>
                                        <h3>Ganti Password</h3>
                                        <p>Buka hanya kalau ingin mengganti password.</p>
                                    </div>
                                    <svg class="profile-accordion__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </summary>

                                <div class="profile-accordion__body">
                                    <div class="profile-edit-form__grid">
                                        <label class="profile-field">
                                            <span>Password Sekarang</span>
                                            <input class="profile-input" type="password" name="current_password" autocomplete="current-password">
                                        </label>

                                        <label class="profile-field">
                                            <span>Password Baru</span>
                                            <input class="profile-input" type="password" name="new_password" autocomplete="new-password">
                                        </label>

                                        <label class="profile-field">
                                            <span>Konfirmasi Password Baru</span>
                                            <input class="profile-input" type="password" name="confirm_password" autocomplete="new-password">
                                        </label>
                                    </div>
                                </div>
                            </details>

                            <input type="hidden" name="return_to" value="../profil/">

                            <div class="profile-edit-form__actions">
                                <button class="profile-actions__secondary" type="button" data-profile-edit-close>Batal</button>
                                <button class="profile-actions__primary" type="submit">Simpan Perubahan</button>
                            </div>
                        </form>
                    </section>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>
