<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

if (empty($_SESSION['user'])) {
    redirectTo('../auth/login.php');
}

$user = $_SESSION['user'];
$isAdmin = isAdmin();
$profile = recipe_user_profile_db((int) ($user['id'] ?? 0));
if ($profile === null) {
    redirectTo('../home/');
}

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'category' => trim((string) ($_GET['category'] ?? '')),
];

$recipes = recipe_user_recipes_db((int) $user['id'], 100);
$allRecipes = $recipes;

if ($filters['q'] !== '' || $filters['category'] !== '') {
    $recipes = array_values(array_filter($recipes, static function (array $recipe) use ($filters): bool {
        if ($filters['q'] !== '' && stripos($recipe['title'], $filters['q']) === false) {
            return false;
        }

        if ($filters['category'] !== '' && mb_strtolower((string) ($recipe['category'] ?? '')) !== mb_strtolower($filters['category'])) {
            return false;
        }

        return true;
    }));
}

$categoryPills = [
    ['label' => 'Semua', 'value' => ''],
    ['label' => 'Makanan', 'value' => 'food'],
    ['label' => 'Salad', 'value' => 'salad'],
    ['label' => 'Makanan Penutup', 'value' => 'dessert'],
    ['label' => 'Minuman', 'value' => 'drinks'],
];

$alerts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['_token'] ?? null)) {
        $alerts[] = 'Token form tidak valid.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $recipeId = (int) ($_POST['recipe_id'] ?? 0);

        if ($action === 'delete' && $recipeId > 0) {
            if (recipe_delete_db($recipeId, (int) $user['id'])) {
                redirectTo('../resep/myresep.php?deleted=1');
            }

            $alerts[] = 'Resep tidak ditemukan atau bukan milik akun ini.';
        }
    }
}

if (!empty($_GET['deleted'])) {
    $alerts[] = 'Resep berhasil dihapus.';
}

if (!empty($_GET['updated'])) {
    $alerts[] = 'Resep berhasil diperbarui.';
}

$totalRecipes = count($allRecipes);
$visibleRecipes = count($recipes);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resep Saya - Resepku</title>
        <?= sidebarInitialStateScript() ?>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="myrecipes-page myrecipes-page--manage">
    <?= renderGeneralSidebar([
        'basePath' => '../',
        'asideClass' => 'myrecipes-sidebar',
        'activeKey' => 'myrecipes',
        'searchAction' => '../cari.php',
        'userContext' => [
            'isLoggedIn' => true,
            'isGuest' => false,
            'isAdmin' => $isAdmin,
            'name' => $profile['name'] ?? '',
            'avatar' => $profile['avatar'] ?? '',
        ],
    ]) ?>

    <main class="myrecipes-main">
        <header class="myrecipes-topbar">
            <form class="home-search-form myrecipes-search-form" method="get" action="../cari.php" data-empty-action="../home/">
                <label class="sr-only" for="myrecipes-search">Cari resep saya</label>
                <input id="myrecipes-search" class="home-search" type="search" name="q" placeholder="Cari resep saya..." value="<?= e($filters['q']) ?>">
                <input type="hidden" name="category" value="<?= e($filters['category']) ?>">
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

        <section class="myrecipes-hero">
            <div>
                <p class="myrecipes-hero__eyebrow">Kelola resep</p>
                <h1>Resep Saya</h1>
                <p class="myrecipes-hero__copy">Edit, perbarui gambar sampul, dan hapus resep yang kamu miliki.</p>
                <div class="myrecipes-stats myrecipes-stats--inline">
                    <div>
                        <strong><?= e((string) $totalRecipes) ?></strong>
                        <span>Total resep</span>
                    </div>
                    <div>
                        <strong><?= e((string) $visibleRecipes) ?></strong>
                        <span>Hasil tampil</span>
                    </div>
                </div>
                <div class="home-filters myrecipes-filters" aria-label="Kategori resep">
                    <?php foreach ($categoryPills as $pill): ?>
                        <?php
                        $query = array_filter([
                            'q' => $filters['q'],
                            'category' => $pill['value'],
                        ], static fn ($value) => $value !== '');
                        ?>
                        <a class="home-filter<?= mb_strtolower($filters['category']) === mb_strtolower($pill['value']) ? ' is-active' : '' ?>" href="../resep/myresep.php?<?= e(http_build_query($query)) ?>"><?= e($pill['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if ($alerts !== []): ?>
            <section class="myrecipes-alerts" aria-live="polite">
                <?php foreach ($alerts as $alert): ?>
                    <div class="myrecipes-alert"><?= e($alert) ?></div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="recipe-grid myrecipes-grid" aria-label="Resep milik saya">
            <?php if ($recipes === []): ?>
                <article class="myrecipes-empty">
                    <h2>Belum ada resep</h2>
                    <p>Buat resep pertama supaya halaman ini langsung berisi daftar kelolaan.</p>
                    <a href="../resep/buat.php">Tambah resep</a>
                </article>
            <?php else: ?>
                <?php foreach ($recipes as $recipe): ?>
                    <?php
                    $recipeId = (int) $recipe['id'];
                    $detailHref = '../resep/detail.php?id=' . $recipeId;
                    $editHref = '../resep/edit.php?id=' . $recipeId;
                    $categoryLabel = $recipe['category'] !== '' ? ucfirst((string) $recipe['category']) : 'Belum berkategori';
                    $summary = $recipe['summary'] !== '' ? $recipe['summary'] : 'Belum ada deskripsi singkat.';
                    ?>
                    <article class="myrecipe-card">
                        <a class="myrecipe-card__link" href="<?= e($detailHref) ?>">
                            <span class="sr-only">Buka resep <?= e($recipe['title']) ?></span>
                        </a>
                        <div class="myrecipe-card__thumb">
                            <img class="myrecipe-card__image" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
                        </div>
                        <div class="myrecipe-card__body">
                            <div class="myrecipe-card__head">
                                <div>
                                    <h2><?= e($recipe['title']) ?></h2>
                                    <p><?= e($categoryLabel) ?></p>
                                </div>
                                <span class="myrecipe-card__badge"><?= e($recipe['difficulty']) ?></span>
                            </div>

                            <div class="myrecipe-card__meta">
                                <span><?= e($recipe['cook_time']) ?></span>
                                <span><?= e($recipe['servings']) ?></span>
                            </div>

                            <p class="myrecipe-card__summary"><?= e($summary) ?></p>
                        </div>

                        <details class="myrecipe-card__actions">
                            <summary aria-label="Aksi resep <?= e($recipe['title']) ?>">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M4 17.5V21h3.5L18.1 10.4l-3.5-3.5L4 17.5Zm12-12 3.5 3.5 1.2-1.2a1.8 1.8 0 0 0 0-2.6l-1.9-1.9a1.8 1.8 0 0 0-2.6 0L16 5.5Z" />
                                </svg>
                            </summary>
                            <div class="myrecipe-card__menu">
                                <a href="<?= e($detailHref) ?>">Lihat</a>
                                <a href="<?= e($editHref) ?>">Edit</a>
                                <form method="post" onsubmit="return confirm('Hapus resep ini?');">
                                    <input type="hidden" name="_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="recipe_id" value="<?= e((string) $recipeId) ?>">
                                    <button type="submit">Hapus</button>
                                </form>
                            </div>
                        </details>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>
