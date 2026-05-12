<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

$isAdmin = isAdmin();
$isGuest = !empty($_SESSION['guest_mode']) && empty($_SESSION['user']);
$currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
$userName = $isGuest ? 'Tamu' : ($_SESSION['user']['name'] ?? 'Nayaka');
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
$hasActiveFilters = !$isDefaultView;

$recipes = recipe_catalog_filtered_db($filters, 24, $currentUserId > 0 ? $currentUserId : null);
$defaultRecipes = $isDefaultView ? recipe_catalog_from_db(null, $currentUserId > 0 ? $currentUserId : null) : [];
$topRecipes = $isDefaultView ? array_slice($defaultRecipes, 0, 8) : [];
$bottomRecipes = $isDefaultView ? array_slice($defaultRecipes, 8, 8) : [];

if ($isDefaultView && $defaultRecipes !== [] && count($bottomRecipes) < 8) {
    $bottomRecipes = array_merge($bottomRecipes, array_slice($defaultRecipes, 0, 8 - count($bottomRecipes)));
}

$activeCategory = mb_strtolower($filters['category']);

$categoryPills = [
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

$timeOptions = [
    ['label' => 'Semua waktu', 'value' => ''],
    ['label' => '< 15 menit', 'value' => '15'],
    ['label' => '< 30 menit', 'value' => '30'],
    ['label' => '< 60 menit', 'value' => '60'],
];

$sortOptions = [
    ['label' => 'Terbaru', 'value' => 'newest'],
    ['label' => 'Populer', 'value' => 'popular'],
    ['label' => 'Terlama', 'value' => 'oldest'],
];

$buildHomeFilterQuery = static function (array $overrides) use ($filters): string {
    return http_build_query(array_filter([
        'q' => $overrides['q'] ?? $filters['q'],
        'category' => $overrides['category'] ?? $filters['category'],
        'difficulty' => $overrides['difficulty'] ?? $filters['difficulty'],
        'max_time' => $overrides['max_time'] ?? $filters['max_time'],
        'sort' => $overrides['sort'] ?? $filters['sort'],
    ], static fn ($value) => $value !== ''));
};

$featured = [
    'title' => 'Salad Ayam Spesial',
    'image' => '../assets/img/recipe-salad-hero.png',
    'summary' => 'Nikmati perpaduan dada ayam panggang kaya protein dengan sayuran segar seperti selada romaine, tomat ceri, timun, dan kol ungu. Disajikan dengan dressing spesial yang ringan dan menggugah selera, hidangan ini enak, sehat, dan mengenyangkan.',
    'cook_time' => '20 menit',
    'id' => null,
];

$sideRecipes = [
    [
        'title' => 'Matcha Jepang',
        'image' => '../assets/img/recipe-matcha-card.png',
        'id' => null,
    ],
    [
        'title' => 'Kue Stroberi Manis',
        'image' => '../assets/img/recipe-strawberry-cake-card.png',
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
    <title>Beranda - Resepku</title>
        <?= sidebarInitialStateScript() ?>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="home-page" data-guest-mode="<?= $isGuest ? '1' : '0' ?>" data-csrf-token="<?= e(csrfToken()) ?>" data-api-base="../api/" data-login-url="../auth/login.php">
    <aside class="home-sidebar" data-node-id="16:154">
        <div class="home-sidebar__profile">
            <div class="home-sidebar__brand">
                <img src="../assets/img/resepku-logo.png" alt="" class="home-sidebar__logo">
                <div>
                    <p class="home-sidebar__name">Resepku</p>
                    <p class="home-sidebar__status"><?= $isGuest ? 'Mode tamu' : 'Sudah masuk' ?></p>
                </div>
                <?= sidebarToggleButton() ?>
            </div>

            <div class="home-sidebar__identity">
                <img src="../assets/img/home-profile.png" alt="" class="home-sidebar__avatar">
                <div class="home-sidebar__welcome">
                    <strong><?= e($userName) ?></strong>
                    <span><?= $isGuest ? 'Masuk untuk simpan resep dan kelola profil.' : 'Akses resep pribadi dan aktivitas akun.' ?></span>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <?= sidebarLink('../admin/', 'Panel Admin', 'admin', 'home-sidebar__admin-panel') ?>
            <?php endif; ?>

            <?= sidebarLink('../auth/logout.php', 'Keluar', 'logout', 'home-sidebar__logout') ?>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi Home">
            <?= sidebarSearchForm('../cari.php', $filters['q'] ?? '') ?>
            <?= sidebarLink('../home/', 'Beranda', 'home', '', true) ?>
            <?= sidebarLink('../profil/', 'Profil', 'user') ?>
            <?= sidebarLink('../resep/myresep.php', 'Resep Saya', 'book') ?>
            <?= sidebarLink('../resep/buat.php', 'Tambah Resep', 'plus') ?>
            <?= sidebarLink('../resep/favorite.php', 'Favorit', 'bookmark') ?>
            <?= sidebarLink('../profil/laporan.php', 'Pengaduan Saya', 'bell') ?>
        </nav>

        <img src="../assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="home-main" data-node-id="1:312">
        <header class="home-topbar">
            <form class="home-search-form" method="get" action="../cari.php" data-empty-action="../home/">
                <label class="sr-only" for="recipe-search">Cari resep</label>
                <input id="recipe-search" class="home-search" type="search" name="q" placeholder="Cari resep..." value="<?= e($filters['q']) ?>">
                <input type="hidden" name="category" value="<?= e($filters['category']) ?>">
                <input type="hidden" name="difficulty" value="<?= e($filters['difficulty']) ?>">
                <input type="hidden" name="max_time" value="<?= e($filters['max_time']) ?>">
                <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
            </form>

            <a class="home-add" href="../resep/buat.php">
                <img src="../assets/img/icon-add-recipe.svg" alt="">
                <span>Tambah Resep</span>
            </a>

            <a class="home-cs" href="<?= e(reportInboxHref('../profil/laporan.php', '../auth/login.php')) ?>">
                <img src="../assets/img/icon-cs.svg" alt="">
                <span>Pengaduan</span>
            </a>
        </header>

        <section class="home-hero">
            <div>
                <h1>Belajar, Masak, dan Nikmati Hidanganmu</h1>
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
                <div class="home-controls" aria-label="Filter resep lanjutan">
                    <details class="home-dropdown">
                        <summary class="home-dropdown__trigger">
                            <span class="home-dropdown__icon">Lv</span>
                            <span>Level</span>
                        </summary>
                        <div class="home-dropdown__menu">
                            <?php foreach ($difficultyOptions as $option): ?>
                                <?php $difficultyQuery = $buildHomeFilterQuery(['difficulty' => $option['value']]); ?>
                                <a class="home-dropdown__item<?= $filters['difficulty'] === $option['value'] ? ' is-active' : '' ?>" href="../home/<?= $difficultyQuery !== '' ? '?' . e($difficultyQuery) : '' ?>">
                                    <?= e($option['value'] === '' ? 'Semua' : $option['label']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <details class="home-dropdown">
                        <summary class="home-dropdown__trigger">
                            <span class="home-dropdown__icon">Tm</span>
                            <span>Waktu</span>
                        </summary>
                        <div class="home-dropdown__menu">
                            <?php foreach ($timeOptions as $option): ?>
                                <?php $timeQuery = $buildHomeFilterQuery(['max_time' => $option['value']]); ?>
                                <a class="home-dropdown__item<?= $filters['max_time'] === $option['value'] ? ' is-active' : '' ?>" href="../home/<?= $timeQuery !== '' ? '?' . e($timeQuery) : '' ?>">
                                    <?= e($option['value'] === '' ? 'Semua' : str_replace('< ', '', $option['label'])) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <details class="home-dropdown home-dropdown--sort">
                        <summary class="home-dropdown__trigger">
                            <span class="home-dropdown__icon">A-Z</span>
                            <span>Sort</span>
                        </summary>
                        <div class="home-dropdown__menu">
                            <?php foreach ($sortOptions as $option): ?>
                                <?php $sortQuery = $buildHomeFilterQuery(['sort' => $option['value']]); ?>
                                <a class="home-dropdown__item<?= $filters['sort'] === $option['value'] ? ' is-active' : '' ?>" href="../home/<?= $sortQuery !== '' ? '?' . e($sortQuery) : '' ?>">
                                    <?= e($option['label']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <?php if ($hasActiveFilters): ?>
                        <a class="home-controls__clear" href="../home/">Reset</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="home-stats">
                <strong>+19</strong>
                <span>resep baru</span>
            </div>
        </section>

        <?php if ($isDefaultView): ?>
            <section class="recipe-grid" aria-label="Daftar resep">
                <?php foreach ($topRecipes as $recipe): ?>
                    <article class="recipe-card" data-node-id="16:155">
                        <?php if (!empty($recipe['id'])): ?>
                            <a class="recipe-card__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                                <span class="sr-only">Buka resep <?= e($recipe['title']) ?></span>
                            </a>
                        <?php endif; ?>
                        <div class="recipe-card__panel"></div>
                        <img class="recipe-card__image" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                        <button class="recipe-card__bookmark<?= !empty($recipe['favorited']) ? ' is-active' : '' ?>" type="button" aria-label="Simpan resep" aria-pressed="<?= !empty($recipe['favorited']) ? 'true' : 'false' ?>" data-card-favorite data-recipe-id="<?= e((string) $recipe['id']) ?>">
                            <img src="../assets/img/icon-bookmark.svg" alt="">
                        </button>
                        <h2><?= e($recipe['title']) ?></h2>
                        <div class="recipe-card__line"></div>
                        <div class="recipe-card__meta">
                            <?= ratingStarsHtml($recipe['rating'] ?? 0, 'recipe-card__stars') ?>
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
                            <a href="#">Lihat resep</a>
                            <span>Waktu memasak: <?= e($featured['cook_time']) ?></span>
                        </div>
                    </div>
                </article>
                <?php endif; ?>

                <aside class="side-recipes" aria-label="Resep rekomendasi">
                    <?php foreach ($sideRecipes as $recipe): ?>
                        <article class="side-recipe-card" data-node-id="78:339">
                            <img class="side-recipe-card__image" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                            <div class="side-recipe-card__content">
                                <h3 class="side-recipe-card__title"><?= e($recipe['title']) ?></h3>
                                <?php if (!empty($recipe['id'])): ?>
                                    <a class="side-recipe-card__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">Lihat resep</a>
                                <?php else: ?>
                                    <span class="side-recipe-card__link side-recipe-card__link--static">Lihat resep</span>
                                <?php endif; ?>
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
                                <span class="sr-only">Buka resep <?= e($recipe['title']) ?></span>
                            </a>
                        <?php endif; ?>
                        <div class="recipe-card__panel"></div>
                        <img class="recipe-card__image" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                        <button class="recipe-card__bookmark<?= !empty($recipe['favorited']) ? ' is-active' : '' ?>" type="button" aria-label="Simpan resep" aria-pressed="<?= !empty($recipe['favorited']) ? 'true' : 'false' ?>" data-card-favorite data-recipe-id="<?= e((string) $recipe['id']) ?>">
                            <img src="../assets/img/icon-bookmark.svg" alt="">
                        </button>
                        <h2><?= e($recipe['title']) ?></h2>
                        <div class="recipe-card__line"></div>
                        <div class="recipe-card__meta">
                            <?= ratingStarsHtml($recipe['rating'] ?? 0, 'recipe-card__stars') ?>
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
                        <a href="../home/">Atur ulang filter</a>
                    </article>
                <?php endif; ?>
                <?php foreach ($recipes as $recipe): ?>
                    <article class="recipe-card" data-node-id="16:155">
                        <?php if (!empty($recipe['id'])): ?>
                            <a class="recipe-card__link" href="../resep/detail.php?id=<?= e((string) $recipe['id']) ?>">
                                <span class="sr-only">Buka resep <?= e($recipe['title']) ?></span>
                            </a>
                        <?php endif; ?>
                        <div class="recipe-card__panel"></div>
                        <img class="recipe-card__image" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                        <button class="recipe-card__bookmark<?= !empty($recipe['favorited']) ? ' is-active' : '' ?>" type="button" aria-label="Simpan resep" aria-pressed="<?= !empty($recipe['favorited']) ? 'true' : 'false' ?>" data-card-favorite data-recipe-id="<?= e((string) $recipe['id']) ?>">
                            <img src="../assets/img/icon-bookmark.svg" alt="">
                        </button>
                        <h2><?= e($recipe['title']) ?></h2>
                        <div class="recipe-card__line"></div>
                        <div class="recipe-card__meta">
                            <?= ratingStarsHtml($recipe['rating'] ?? 0, 'recipe-card__stars') ?>
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
