<?php

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/data/recipe_repository.php';

startSession();

if ($_GET === []) {
    redirectTo('home/');
}

$isAdmin = isAdmin();
$isGuest = !empty($_SESSION['guest_mode']) && empty($_SESSION['user']);
$currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
$userName = $isGuest ? 'Tamu' : ($_SESSION['user']['name'] ?? 'Nayaka');

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'category' => trim((string) ($_GET['category'] ?? '')),
    'difficulty' => trim((string) ($_GET['difficulty'] ?? '')),
    'sort' => trim((string) ($_GET['sort'] ?? 'newest')),
];

$recipes = recipe_catalog_filtered_db($filters, 24, $currentUserId > 0 ? $currentUserId : null);
$resultCount = count($recipes);
$hasQuery = $filters['q'] !== '';
$hasFilters = $filters['category'] !== '' || $filters['difficulty'] !== '' || $filters['sort'] !== 'newest';

$categoryPills = [
    ['label' => 'Semua', 'value' => ''],
    ['label' => 'Makanan', 'value' => 'food'],
    ['label' => 'Salad', 'value' => 'salad'],
    ['label' => 'Makanan Penutup', 'value' => 'dessert'],
    ['label' => 'Minuman', 'value' => 'drinks'],
];

$difficultyOptions = [
    ['label' => 'Semua tingkat', 'value' => ''],
    ['label' => 'Mudah', 'value' => 'mudah'],
    ['label' => 'Sedang', 'value' => 'sedang'],
    ['label' => 'Sulit', 'value' => 'sulit'],
];

$sortOptions = [
    ['label' => 'Terbaru', 'value' => 'newest'],
    ['label' => 'Populer', 'value' => 'popular'],
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
        <?= sidebarInitialStateScript() ?>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="home-page" data-guest-mode="<?= $isGuest ? '1' : '0' ?>" data-csrf-token="<?= e(csrfToken()) ?>" data-api-base="api/" data-login-url="auth/login.php">
    <aside class="home-sidebar">
        <div class="home-sidebar__profile">
            <div class="home-sidebar__brand">
                <img src="assets/img/resepku-logo.png" alt="" class="home-sidebar__logo">
                <div>
                    <p class="home-sidebar__name">Resepku</p>
                    <p class="home-sidebar__status"><?= $isGuest ? 'Mode tamu' : 'Sudah masuk' ?></p>
                </div>
                <?= sidebarToggleButton() ?>
            </div>

            <div class="home-sidebar__identity">
                <img src="assets/img/home-profile.png" alt="" class="home-sidebar__avatar">
                <div class="home-sidebar__welcome">
                    <strong><?= e($userName) ?></strong>
                    <span><?= $isGuest ? 'Masuk untuk simpan resep dan kelola profil.' : 'Akses resep pribadi dan aktivitas akun.' ?></span>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <?= sidebarLink('admin/', 'Panel Admin', 'admin', 'home-sidebar__admin-panel') ?>
            <?php endif; ?>

            <?= sidebarLink('auth/logout.php', 'Keluar', 'logout', 'home-sidebar__logout') ?>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi pencarian">
            <?= sidebarSearchForm('cari.php', $filters['q'] ?? '') ?>
            <?= sidebarLink('home/', 'Beranda', 'home') ?>
            <?= sidebarLink('profil/', 'Profil', 'user') ?>
            <?= sidebarLink('resep/myresep.php', 'Resep Saya', 'book') ?>
            <?= sidebarLink('resep/buat.php', 'Tambah Resep', 'plus') ?>
            <?= sidebarLink('resep/favorite.php', 'Favorit', 'bookmark') ?>
            <?php if (!$isGuest): ?>
                <?= sidebarLink('profil/laporan.php', 'Pengaduan Saya', 'bell') ?>
            <?php endif; ?>
        </nav>

        <img src="assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="home-main">
        <header class="home-topbar">
            <form class="home-search-form" method="get" action="cari.php" data-empty-action="home/">
                <label class="sr-only" for="recipe-search">Cari resep</label>
                <input id="recipe-search" class="home-search" type="search" name="q" placeholder="Cari resep..." value="<?= e($filters['q']) ?>">
                <input type="hidden" name="category" value="<?= e($filters['category']) ?>">
                <input type="hidden" name="difficulty" value="<?= e($filters['difficulty']) ?>">
                <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
            </form>

            <a class="home-add" href="resep/buat.php">
                <img src="assets/img/icon-add-recipe.svg" alt="">
                <span>Tambah Resep</span>
            </a>

            <a class="home-cs" href="<?= e(reportInboxHref('profil/laporan.php', 'auth/login.php')) ?>">
                <img src="assets/img/icon-cs.svg" alt="">
                <span>Pengaduan</span>
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
                        <span>Kesulitan</span>
                        <select name="difficulty" class="home-select__field" onchange="this.form.submit()">
                            <?php foreach ($difficultyOptions as $option): ?>
                                <option value="<?= e($option['value']) ?>" <?= $filters['difficulty'] === $option['value'] ? 'selected' : '' ?>>
                                    <?= e($option['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="home-select">
                        <span>Urutkan</span>
                        <select name="sort" class="home-select__field" onchange="this.form.submit()">
                            <?php foreach ($sortOptions as $option): ?>
                                <option value="<?= e($option['value']) ?>" <?= $filters['sort'] === $option['value'] ? 'selected' : '' ?>>
                                    <?= e($option['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="home-controls__actions">
                        <button class="home-controls__apply" type="submit">Terapkan filter</button>
                        <a class="home-controls__clear" href="?">Hapus filter</a>
                    </div>
                </form>
            </div>

            <div class="home-stats">
                <strong><?= e((string) $resultCount) ?></strong>
                <span>hasil</span>
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
                    <a href="?">Atur ulang filter</a>
                    </article>
            <?php else: ?>
                <?php foreach ($recipes as $recipe): ?>
                    <article class="recipe-card">
                        <a class="recipe-card__link" href="resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                            <span class="sr-only">Buka resep <?= e($recipe['title']) ?></span>
                        </a>
                        <div class="recipe-card__panel"></div>
                        <img class="recipe-card__image" src="<?= e(search_asset_path($recipe['image'])) ?>" alt="<?= e($recipe['title']) ?>">
                        <button class="recipe-card__bookmark<?= !empty($recipe['favorited']) ? ' is-active' : '' ?>" type="button" aria-label="Simpan resep" aria-pressed="<?= !empty($recipe['favorited']) ? 'true' : 'false' ?>" data-card-favorite data-recipe-id="<?= e((string) $recipe['id']) ?>">
                            <img src="assets/img/icon-bookmark.svg" alt="">
                        </button>
                        <h2><?= e($recipe['title']) ?></h2>
                        <div class="recipe-card__line"></div>
                        <div class="recipe-card__meta">
                            <?= ratingStarsHtml($recipe['rating'] ?? 0, 'recipe-card__stars') ?>
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
