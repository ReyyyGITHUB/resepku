<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/admin_repository.php';
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
$recentRecipes = !$isUnavailable ? recipe_user_recipes_db($profileUserId, 9) : [];
$communityRecipes = !$isUnavailable ? array_values(array_filter(
    recipe_catalog_from_db(7),
    static fn (array $recipe) => (int) ($recipe['user_id'] ?? 0) !== $profileUserId
)) : [];
$trendingRecipes = !$isUnavailable ? recipe_catalog_filtered_db(['sort' => 'popular'], 7, $currentUserId > 0 ? $currentUserId : null) : [];
$suggestedAccounts = [];
$reportCategoryOptions = report_category_options();

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
$sidebarName = $sidebarProfile['name'] ?? 'Tamu';
$sidebarAvatar = $sidebarProfile['avatar'] ?? '../assets/img/home-profile.png';
$sidebarBio = $sidebarProfile['bio'] ?? '';
$pageTitle = $isPublicProfile && !$isUnavailable ? $profile['name'] . ' - Resepku' : 'Profil - Resepku';
$profileRoleLabel = !$isUnavailable
    ? ($profile['role'] === 'admin' ? 'Admin Komunitas' : 'Anggota Komunitas')
    : 'Profil tidak tersedia';
$profileJoined = !$isUnavailable && !empty($profile['joined_at'])
    ? date('d M Y', strtotime($profile['joined_at']))
    : '-';
$profileHeroImage = (!$isUnavailable && $recentRecipes !== [])
    ? ($recentRecipes[0]['image'] ?? '../assets/img/recipe-salad-hero.png')
    : '../assets/img/recipe-salad-hero.png';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
        <?= sidebarInitialStateScript() ?>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="profile-page <?= $isPublicProfile ? 'profile-page--public' : 'profile-page--own' ?>" data-guest-mode="<?= $currentUserId > 0 ? '0' : '1' ?>" data-csrf-token="<?= e(csrfToken()) ?>">
    <?= renderGeneralSidebar([
        'basePath' => '../',
        'asideClass' => 'profile-sidebar',
        'activeKey' => 'profile',
        'searchAction' => '../cari.php',
        'userContext' => [
            'isLoggedIn' => $currentUserId > 0,
            'isGuest' => $currentUserId <= 0,
            'isAdmin' => $isAdmin,
            'name' => $sidebarName,
            'avatar' => $sidebarAvatar,
        ],
    ]) ?>

    <main class="profile-main">
        <?php if ($flashSuccess): ?>
            <div class="profile-alert profile-alert--success" role="status">
                <?= e($flashSuccess) ?>
            </div>
        <?php endif; ?>

        <header class="profile-topbar" aria-label="Alat profil">
            <form class="home-search-form profile-search" method="get" action="../cari.php" data-empty-action="../home/">
                <label class="sr-only" for="profile-search">Cari resep, pengguna, atau bahan</label>
                <input id="profile-search" class="profile-search__input" type="search" name="q" placeholder="Cari resep, pengguna, atau bahan...">
                <input type="hidden" name="category" value="">
                <input type="hidden" name="difficulty" value="">
                <input type="hidden" name="sort" value="newest">
                <button class="profile-search__button" type="submit" aria-label="Cari">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </button>
            </form>

            <div class="profile-topbar__account">
                <?php if ($currentUserId > 0): ?>
                    <a class="profile-topbar__notice" href="../profil/laporan.php" aria-label="Pengaduan Saya">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22zm8-6V11a8 8 0 1 0-16 0v5L2 18v1h20v-1l-2-2zm-2 1H6v-6a6 6 0 1 1 12 0v6z" fill="currentColor"></path>
                        </svg>
                    </a>
                    <img class="profile-topbar__avatar" src="<?= e($sidebarAvatar) ?>" alt="<?= e($sidebarName) ?>">
                    <strong><?= e($sidebarName) ?></strong>
                <?php else: ?>
                    <a class="profile-topbar__login" href="../auth/login.php">Masuk</a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($isUnavailable): ?>
            <section class="profile-empty-state" aria-label="Profil tidak tersedia">
                <div class="profile-empty-state__card">
                    <img class="profile-empty-state__avatar" src="../assets/img/home-profile.png" alt="">
                    <p class="profile-empty-state__eyebrow">Profil</p>
                    <h1>Profil tidak tersedia</h1>
                    <p>Pengguna tidak aktif, profil tidak bisa dilihat.</p>
                    <div class="profile-actions">
                        <a class="profile-actions__primary" href="../home/">Kembali ke Beranda</a>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <?php if ($isPublicProfile): ?>
            <section class="profile-shell" aria-label="Konten profil">
                <div class="profile-shell__main">
                    <section class="profile-summary" aria-label="Profil pengguna">
                        <div class="profile-cover">
                            <img class="profile-cover__image" src="<?= e($profileHeroImage) ?>" alt="">
                            <div class="profile-cover__shade"></div>
                            <div class="profile-cover__copy">
                                <p><?= $isPublicProfile ? 'Profil Chef' : 'Profil Saya' ?></p>
                                <h1><?= e($profile['name']) ?></h1>
                            </div>
                        </div>

                        <div class="profile-summary__card">
                            <div class="profile-summary__avatar-wrap">
                                <img class="profile-summary__avatar" src="<?= e($profile['avatar']) ?>" alt="<?= e($profile['name']) ?>">
                            </div>

                            <div class="profile-summary__content">
                                <div class="profile-summary__header">
                                    <div>
                                        <div class="profile-summary__identity">
                                            <h2 class="profile-summary__name"><?= e($profile['name']) ?></h2>
                                            <?= userAdminBadge((string) ($profile['role'] ?? 'pengguna'), 'profile-admin-badge') ?>
                                        </div>
                                        <div class="profile-summary__badge"><?= e($profileRoleLabel) ?></div>
                                    </div>

                                    <div class="profile-summary__actions">
                                        <button
                                            class="profile-summary__follow<?= $isFollowingProfile ? ' is-active' : '' ?>"
                                            type="button"
                                            data-guest-gate
                                            data-social-action="follow"
                                            data-user-id="<?= e((string) $profileUserId) ?>"
                                            data-follow-label="Ikuti"
                                            data-followed-label="Mengikuti"
                                        >
                                            <svg class="profile-summary__follow-icon profile-summary__follow-icon--idle" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="M15 19a6 6 0 0 0-12 0"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                                <path d="M19 8v6"></path>
                                                <path d="M22 11h-6"></path>
                                            </svg>
                                            <svg class="profile-summary__follow-icon profile-summary__follow-icon--active" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="M20 6 9 17l-5-5"></path>
                                            </svg>
                                            <span data-follow-text><?= $isFollowingProfile ? 'Mengikuti' : 'Ikuti' ?></span>
                                        </button>
                                        <button
                                            class="profile-summary__report"
                                            type="button"
                                            data-guest-gate
                                            data-report-open
                                            data-report-target-type="pengguna"
                                            data-report-target-id="<?= e((string) $profileUserId) ?>"
                                            data-report-target-label="<?= e($profile['name']) ?>"
                                        >
                                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="M4 21V5"></path>
                                                <path d="M4 5h12l-1 4 1 4H4"></path>
                                            </svg>
                                            <span>Laporkan</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="profile-summary__stats" aria-label="Statistik profil">
                                    <div class="profile-stat">
                                        <strong><?= e((string) $profile['recipe_count']) ?></strong>
                                        <span>Resep</span>
                                    </div>
                                    <div class="profile-stat">
                                        <strong data-follower-count><?= e((string) $profile['follower_count']) ?></strong>
                                        <span>Pengikut</span>
                                    </div>
                                    <div class="profile-stat">
                                        <strong><?= e((string) $profile['following_count']) ?></strong>
                                        <span>Mengikuti</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="profile-panel profile-panel--recipes" aria-label="Daftar resep profil">
                        <div class="profile-filterbar" aria-label="Filter resep">
                            <button class="is-active" type="button" data-profile-filter-action aria-pressed="true">Semua Resep</button>
                            <span class="profile-filterbar__spacer"></span>
                            <button type="button" data-profile-filter-action aria-pressed="false">Terbaru</button>
                            <div class="profile-view-toggle" aria-label="Mode tampilan">
                                <button class="is-active" type="button" aria-label="Tampilan grid" data-profile-view="grid" aria-pressed="true">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z" fill="none" stroke="currentColor" stroke-width="2"></path>
                                    </svg>
                                </button>
                                <button type="button" aria-label="Tampilan daftar" data-profile-view="list" aria-pressed="false">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <?php if ($recentRecipes === []): ?>
                            <div class="profile-panel__empty">Belum ada resep untuk ditampilkan.</div>
                        <?php else: ?>
                            <section class="profile-recipe-grid" data-profile-recipe-grid aria-label="Daftar resep terbaru">
                                <?php foreach ($recentRecipes as $recipe): ?>
                                    <article class="profile-recipe-card">
                                        <?php if (!empty($recipe['id'])): ?>
                                            <a class="profile-recipe-card__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                                                <span class="sr-only">Buka resep <?= e($recipe['title']) ?></span>
                                            </a>
                                        <?php endif; ?>
                                        <button class="profile-recipe-card__bookmark<?= !empty($recipe['favorited']) ? ' is-active' : '' ?>" type="button" aria-label="Simpan resep" aria-pressed="<?= !empty($recipe['favorited']) ? 'true' : 'false' ?>" data-card-favorite data-recipe-id="<?= e((string) ($recipe['id'] ?? 0)) ?>">
                                            <img src="../assets/img/icon-bookmark.svg" alt="">
                                        </button>
                                        <div class="profile-recipe-card__media">
                                            <img class="profile-recipe-card__image" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                                        </div>
                                        <div class="profile-recipe-card__body">
                                            <h3><?= e($recipe['title']) ?></h3>
                                            <div class="profile-recipe-card__meta">
                                                <span class="profile-recipe-card__rating-wrap">
                                                    <?= ratingStarsHtml($recipe['rating'] ?? 0, 'profile-recipe-card__rating') ?>
                                                    <span><?= e(number_format((float) ($recipe['rating'] ?? 0), 1)) ?></span>
                                                </span>
                                                <span><?= e($recipe['cook_time']) ?></span>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </section>
                        <?php endif; ?>

                        <div class="profile-public-feature" data-figma-node="76:2631" aria-hidden="true"></div>
                    </section>
                </div>

                <aside class="profile-shell__rail">
                    <section class="profile-panel profile-panel--suggested" aria-label="Akun yang disarankan">
                        <div class="profile-panel__head">
                        <h2>Disarankan untuk kamu</h2>
                        <a href="../home/" class="profile-panel__link">Lihat semua</a>
                        </div>

                        <?php if ($suggestedAccounts === []): ?>
                            <div class="profile-panel__empty">Belum ada akun yang disarankan.</div>
                        <?php else: ?>
                            <div class="profile-account-list">
                                <?php foreach ($suggestedAccounts as $account): ?>
                                    <?php $accountId = (int) ($account['user_id'] ?? 0); ?>
                                    <article class="profile-account-item">
                                        <a class="profile-account-item__profile" href="../profil/?id=<?= e((string) $accountId) ?>">
                                            <img class="profile-account-item__avatar" src="<?= e(recipe_asset_path($account['avatar'] ?? null)) ?>" alt="<?= e($account['name'] ?? 'Pengguna') ?>">
                                            <span class="profile-account-item__copy">
                                                <strong><?= e($account['name'] ?? 'Pengguna') ?></strong>
                                            <span><?= e((string) ($account['follower_count'] ?? 0)) ?> pengikut</span>
                                            </span>
                                        </a>
                                        <button
                                            class="profile-account-item__follow"
                                            type="button"
                                            data-guest-gate
                                            data-social-action="follow"
                                            data-user-id="<?= e((string) $accountId) ?>"
                                            data-follow-label="Ikuti"
                                            data-followed-label="Diikuti"
                                        >
                                            <span class="profile-account-item__follow-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" focusable="false">
                                                    <path d="M16 19v-1.5A3.5 3.5 0 0 0 12.5 14h-5A3.5 3.5 0 0 0 4 17.5V19m11-11a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm3 4v6m3-3h-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                                </svg>
                                            </span>
                                            <span class="profile-account-item__follow-label">Ikuti</span>
                                        </button>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="profile-panel profile-panel--trending" aria-label="Resep populer">
                        <div class="profile-panel__head">
                        <h2>Resep Populer</h2>
                        <a href="../cari.php?sort=popular" class="profile-panel__link">Lihat semua</a>
                        </div>

                        <?php if ($trendingRecipes === []): ?>
                            <div class="profile-panel__empty">Belum ada resep trending.</div>
                        <?php else: ?>
                            <div class="profile-activity-list">
                                <?php foreach ($trendingRecipes as $recipe): ?>
                                    <article class="profile-activity-item profile-activity-item--recipe">
                                        <?php if (!empty($recipe['id'])): ?>
                                            <a class="profile-activity-item__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                                <span class="sr-only">Buka resep <?= e($recipe['title']) ?></span>
                                            </a>
                                        <?php endif; ?>
                                        <img class="profile-activity-item__avatar profile-activity-item__avatar--square" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                                        <div class="profile-activity-item__copy">
                                            <strong><?= e($recipe['title']) ?></strong>
                                            <span>★ <?= e(number_format((float) ($recipe['rating'] ?? 0), 1)) ?></span>
                                        </div>
                                        <time><?= e($recipe['cook_time']) ?></time>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="profile-panel profile-panel--activity" aria-label="Aktivitas terbaru">
                        <div class="profile-panel__head">
                            <h2>Aktivitas Terbaru</h2>
                        </div>

                        <?php if ($communityRecipes === []): ?>
                            <div class="profile-panel__empty">Belum ada aktivitas komunitas.</div>
                        <?php else: ?>
                            <div class="profile-activity-list">
                                <?php foreach ($communityRecipes as $recipe): ?>
                                    <article class="profile-activity-item">
                                        <?php if (!empty($recipe['id'])): ?>
                                            <a class="profile-activity-item__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                                                <span class="sr-only">Buka resep <?= e($recipe['title']) ?></span>
                                            </a>
                                        <?php endif; ?>
                                        <img class="profile-activity-item__avatar" src="<?= e($recipe['author_avatar'] ?? '../assets/img/home-profile.png') ?>" alt="<?= e($recipe['author'] ?? 'Pengguna') ?>">
                                        <div class="profile-activity-item__copy">
                                            <span><?= e($recipe['author'] ?? 'Pengguna') ?> menyukai resepmu</span>
                                            <strong><?= e($recipe['title']) ?></strong>
                                        </div>
                                        <time>2h</time>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                            <a class="profile-panel__footer-link" href="../home/">Lihat semua aktivitas</a>
                        <?php endif; ?>
                    </section>
                </aside>
            </section>
            <?php else: ?>
                <section class="profile-summary" aria-label="Profil pengguna">
                    <div class="profile-cover">
                        <img class="profile-cover__image" src="<?= e($profileHeroImage) ?>" alt="">
                        <div class="profile-cover__shade"></div>
                        <div class="profile-cover__copy">
                            <p>Profil Saya</p>
                            <h1><?= e($profile['name']) ?></h1>
                        </div>
                    </div>

                    <div class="profile-summary__card">
                        <div class="profile-summary__avatar-wrap">
                            <img class="profile-summary__avatar" src="<?= e($profile['avatar']) ?>" alt="<?= e($profile['name']) ?>">
                        </div>

                        <div class="profile-summary__content">
                            <div class="profile-summary__header">
                                <div>
                                    <div class="profile-summary__identity">
                                        <h2 class="profile-summary__name"><?= e($profile['name']) ?></h2>
                                        <?= userAdminBadge((string) ($profile['role'] ?? 'pengguna'), 'profile-admin-badge') ?>
                                    </div>
                                    <div class="profile-summary__badge"><?= e($profileRoleLabel) ?></div>
                                </div>

                                <button class="profile-summary__edit" type="button" data-profile-edit-open>
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M12 20h9"></path>
                                        <path d="m16.5 3.5 4 4L7 21H3v-4L16.5 3.5z"></path>
                                    </svg>
                                    <span>Edit Profil</span>
                                </button>
                            </div>

                            <div class="profile-summary__stats" aria-label="Statistik profil">
                                <div class="profile-stat">
                                    <strong><?= e((string) $profile['recipe_count']) ?></strong>
                                    <span>Resep</span>
                                </div>
                                <div class="profile-stat">
                                    <strong data-follower-count><?= e((string) $profile['follower_count']) ?></strong>
                                    <span>Pengikut</span>
                                </div>
                                <div class="profile-stat">
                                    <strong><?= e((string) $profile['following_count']) ?></strong>
                                    <span>Mengikuti</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="profile-bento" aria-label="Konten profil">
                    <div class="profile-bento__main">
                        <section class="profile-panel profile-panel--recent" aria-label="Resep terbaru">
                            <div class="profile-panel__head">
                                <div>
                                    <h2>Resep Terbaru</h2>
                                </div>
                                <a href="../resep/myresep.php" class="profile-panel__link">Lihat semua →</a>
                            </div>

                            <?php if ($recentRecipes === []): ?>
                                <div class="profile-panel__empty">Belum ada resep untuk ditampilkan.</div>
                            <?php else: ?>
                                <section class="profile-recipe-grid" aria-label="Daftar resep terbaru">
                                    <?php foreach ($recentRecipes as $recipe): ?>
                                        <article class="profile-recipe-card">
                                            <?php if (!empty($recipe['id'])): ?>
                                                <a class="profile-recipe-card__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                                                    <span class="sr-only">Buka resep <?= e($recipe['title']) ?></span>
                                                </a>
                                            <?php endif; ?>
                                            <button class="profile-recipe-card__bookmark<?= !empty($recipe['favorited']) ? ' is-active' : '' ?>" type="button" aria-label="Simpan resep" aria-pressed="<?= !empty($recipe['favorited']) ? 'true' : 'false' ?>" data-card-favorite data-recipe-id="<?= e((string) ($recipe['id'] ?? 0)) ?>">
                                                <img src="../assets/img/icon-bookmark.svg" alt="">
                                            </button>
                                            <div class="profile-recipe-card__media">
                                                <img class="profile-recipe-card__image" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                                            </div>
                                            <div class="profile-recipe-card__body">
                                                <h3><?= e($recipe['title']) ?></h3>
                                                <div class="profile-recipe-card__meta">
                                                    <span class="profile-recipe-card__rating-wrap">
                                                        <?= ratingStarsHtml($recipe['rating'] ?? 0, 'profile-recipe-card__rating') ?>
                                                        <span><?= e(number_format((float) ($recipe['rating'] ?? 0), 1)) ?></span>
                                                    </span>
                                                    <span><?= e($recipe['cook_time']) ?></span>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </section>
                            <?php endif; ?>
                        </section>
                    </div>

                    <aside class="profile-bento__aside">
                        <section class="profile-panel profile-panel--activity" aria-label="Aktivitas komunitas">
                            <div class="profile-panel__head">
                                <div>
                                    <h2>Aktivitas Komunitas</h2>
                                </div>
                                <a href="../home/" class="profile-panel__link">Lihat semua</a>
                            </div>

                            <?php if ($communityRecipes === []): ?>
                                <div class="profile-panel__empty">Belum ada aktivitas komunitas.</div>
                            <?php else: ?>
                                <div class="profile-activity-list">
                                    <?php foreach ($communityRecipes as $recipe): ?>
                                        <article class="profile-activity-item">
                                            <?php if (!empty($recipe['id'])): ?>
                                                <a class="profile-activity-item__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                                                    <span class="sr-only">Buka resep <?= e($recipe['title']) ?></span>
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

                        <section class="profile-panel profile-panel--suggested" aria-label="Akun yang disarankan">
                            <div class="profile-panel__head">
                                <div>
                                    <h2>Disarankan untuk kamu</h2>
                                </div>
                                <a href="../home/" class="profile-panel__link">Lihat semua</a>
                            </div>

                            <?php if ($suggestedAccounts === []): ?>
                                <div class="profile-panel__empty">Belum ada akun yang disarankan.</div>
                            <?php else: ?>
                                <div class="profile-account-list">
                                    <?php foreach ($suggestedAccounts as $account): ?>
                                        <?php $accountId = (int) ($account['user_id'] ?? 0); ?>
                                        <article class="profile-account-item">
                                            <a class="profile-account-item__profile" href="../profil/?id=<?= e((string) $accountId) ?>">
                                                <img class="profile-account-item__avatar" src="<?= e(recipe_asset_path($account['avatar'] ?? null)) ?>" alt="<?= e($account['name'] ?? 'Pengguna') ?>">
                                                <span class="profile-account-item__copy">
                                                    <strong><?= e($account['name'] ?? 'Pengguna') ?></strong>
                                                    <span><?= e((string) ($account['follower_count'] ?? 0)) ?> pengikut</span>
                                                </span>
                                            </a>
                                            <button
                                                class="profile-account-item__follow"
                                                type="button"
                                                data-guest-gate
                                                data-social-action="follow"
                                                data-user-id="<?= e((string) $accountId) ?>"
                                                data-follow-label="Ikuti"
                                                data-followed-label="Diikuti"
                                            >
                                                <span class="profile-account-item__follow-icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" focusable="false">
                                                        <path d="M16 19v-1.5A3.5 3.5 0 0 0 12.5 14h-5A3.5 3.5 0 0 0 4 17.5V19m11-11a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm3 4v6m3-3h-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                                    </svg>
                                                </span>
                                                <span class="profile-account-item__follow-label">Ikuti</span>
                                            </button>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    </aside>
                </section>
            <?php endif; ?>

            <?php if (!$isPublicProfile): ?>
                <div class="profile-modal" data-profile-edit-modal aria-hidden="true">
                    <div class="profile-modal__backdrop" data-profile-edit-close></div>
                    <section class="profile-modal__dialog" role="dialog" aria-modal="true" aria-label="Edit profil">
                        <button class="profile-modal__close" type="button" aria-label="Tutup" data-profile-edit-close>×</button>

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
                                        <h3>Ganti Kata Sandi</h3>
                                        <p>Buka hanya kalau ingin mengganti kata sandi.</p>
                                    </div>
                                    <svg class="profile-accordion__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </summary>

                                <div class="profile-accordion__body">
                                    <div class="profile-edit-form__grid">
                                        <label class="profile-field">
                                            <span>Kata Sandi Sekarang</span>
                                            <input class="profile-input" type="password" name="current_password" autocomplete="current-password">
                                        </label>

                                        <label class="profile-field">
                                            <span>Kata Sandi Baru</span>
                                            <input class="profile-input" type="password" name="new_password" autocomplete="new-password">
                                        </label>

                                        <label class="profile-field">
                                            <span>Konfirmasi Kata Sandi Baru</span>
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

    <div class="report-modal" data-report-modal aria-hidden="true">
        <div class="report-modal__backdrop" data-report-close></div>
        <div class="report-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="report-modal-title">
            <p class="report-modal__eyebrow">Pengaduan</p>
            <h2 id="report-modal-title">Laporkan profil</h2>
            <p data-report-target-preview>Pengaduan akan dikirim untuk profil ini.</p>
            <div class="report-modal__summary">
                <span>Diproses admin</span>
                <span>Status awal: menunggu</span>
            </div>

            <form class="report-form" data-report-form>
                <input type="hidden" name="target_type" value="pengguna">
                <input type="hidden" name="target_id" value="<?= e((string) $profileUserId) ?>">

                <p class="report-form__note">Gunakan pengaduan ini untuk spam, akun palsu, pelecehan, pelanggaran hak cipta, atau konten yang tidak pantas.</p>

                <label class="report-field">
                    <span>Kategori pengaduan</span>
                    <select name="category" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($reportCategoryOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="report-field">
                    <span>Detail singkat</span>
                    <textarea name="note" rows="4" maxlength="500" placeholder="Jelaskan masalahnya secara singkat dan jelas" required></textarea>
                </label>

                <div class="report-modal__actions">
                    <button type="button" class="report-modal__secondary" data-report-close>Batal</button>
                    <button type="submit" class="report-modal__primary">Kirim pengaduan</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
