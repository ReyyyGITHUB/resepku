<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/admin_repository.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

$isAdmin = isAdmin();
$isGuest = empty($_SESSION['user']);
$userName = $_SESSION['user']['name'] ?? 'Tamu';
$currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
$sidebarProfile = $currentUserId > 0 ? recipe_user_profile_db($currentUserId) : null;
$recipeId = (int) ($_GET['id'] ?? 1);
$recipe = recipe_find_db($recipeId);

if ($recipe === null) {
    $recipe = [
        'id' => 0,
        'title' => 'Resep tidak tersedia',
        'image' => '../assets/img/recipe-salad-hero.png',
        'user_id' => 0,
        'author' => 'ResepKu',
        'author_avatar' => '../assets/img/home-profile.png',
        'cook_time' => '-',
        'servings' => '-',
        'difficulty' => '-',
        'rating' => 0,
        'summary' => 'Data resep belum tersedia.',
        'description' => 'Data resep belum tersedia.',
        'ingredients' => [],
        'tools' => [],
        'steps' => [],
        'category' => '',
    ];
}

$socialState = $recipe['id'] > 0 ? recipe_social_state_db($recipe['id'], $currentUserId > 0 ? $currentUserId : null) : [
    'recipe_id' => 0,
    'likes_count' => 0,
    'favorites_count' => 0,
    'ratings_count' => 0,
    'rating_average' => 0.0,
    'liked' => false,
    'favorited' => false,
    'user_rating' => null,
];
$reportCategoryOptions = report_category_options();
$relatedRecipes = $recipe['id'] > 0 ? recipe_related_db($recipe, 4, $currentUserId > 0 ? $currentUserId : null) : [];
$comments = $recipe['id'] > 0 ? recipe_comments_db($recipe['id']) : [];
$commentCount = count($comments);
$summaryText = trim((string) ($recipe['summary'] ?? '')) !== '' ? (string) $recipe['summary'] : (string) ($recipe['description'] ?? '');
$categoryLabel = trim((string) ($recipe['category'] ?? '')) !== '' ? ucfirst((string) $recipe['category']) : 'Resep';
$shareUrl = (string) (($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI']);
$relatedBrowseUrl = '../cari.php';
$authorId = (int) ($recipe['user_id'] ?? 0);
$authorName = trim((string) ($recipe['author'] ?? '')) !== '' ? (string) $recipe['author'] : 'ResepKu';
$authorAvatar = trim((string) ($recipe['author_avatar'] ?? '')) !== '' ? (string) $recipe['author_avatar'] : '../assets/img/home-profile.png';
$authorProfileUrl = $authorId > 0 ? '../profil/?' . http_build_query(['id' => $authorId]) : '';
$isOwner = $currentUserId > 0 && $authorId > 0 && $currentUserId === $authorId;
$canUseRecipeActions = (int) ($recipe['id'] ?? 0) > 0;

if (trim((string) ($recipe['category'] ?? '')) !== '') {
    $relatedBrowseUrl .= '?' . http_build_query(['category' => (string) $recipe['category']]);
}

function detail_group_ingredients(array $ingredients): array
{
    $groups = [];
    $currentGroup = [
        'title' => '',
        'items' => [],
    ];

    foreach ($ingredients as $ingredient) {
        $item = trim((string) $ingredient);
        if ($item === '') {
            continue;
        }

        if (preg_match('/[:：]\s*$/u', $item) === 1) {
            if ($currentGroup['title'] !== '' || $currentGroup['items'] !== []) {
                $groups[] = $currentGroup;
            }

            $currentGroup = [
                'title' => rtrim($item, " :：\t\n\r\0\x0B"),
                'items' => [],
            ];
            continue;
        }

        $currentGroup['items'][] = $item;
    }

    if ($currentGroup['title'] !== '' || $currentGroup['items'] !== []) {
        $groups[] = $currentGroup;
    }

    return $groups;
}

function detail_format_step_content(string $text, int $index, string $title = ''): array
{
    $normalized = trim((string) preg_replace('/\r\n|\r/u', "\n", $text));
    $numberLabel = 'Langkah ' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
    $title = trim($title);

    if ($title !== '') {
        return [
            'label' => $numberLabel,
            'title' => $title,
            'body' => $normalized,
        ];
    }

    if ($normalized === '') {
        return [
            'label' => $numberLabel,
            'title' => 'Langkah memasak',
            'body' => '',
        ];
    }

    $lines = array_values(array_filter(array_map('trim', explode("\n", $normalized)), static fn (string $line): bool => $line !== ''));
    if (count($lines) > 1) {
        return [
            'label' => $numberLabel,
            'title' => array_shift($lines),
            'body' => implode(' ', $lines),
        ];
    }

    if (preg_match('/^([^:]{3,72}):\s*(.+)$/u', $normalized, $matches) === 1) {
        return [
            'label' => $numberLabel,
            'title' => trim($matches[1]),
            'body' => trim($matches[2]),
        ];
    }

    return [
        'label' => $numberLabel,
        'title' => $numberLabel,
        'body' => $normalized,
    ];
}

$ingredientGroups = detail_group_ingredients($recipe['ingredients'] ?? []);
$guestLockTitle = 'Konten resep terkunci';
$guestLockText = 'Masuk atau buat akun untuk membuka bahan, alat, langkah memasak, dan interaksi resep.';
$previewNoteText = $summaryText !== ''
    ? (function_exists('mb_strimwidth') ? mb_strimwidth($summaryText, 0, 96, '...') : substr($summaryText, 0, 93) . (strlen($summaryText) > 93 ? '...' : ''))
    : 'Buka bahan, alat, dan langkah memasak di bagian kanan halaman ini.';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($recipe['title']) ?> - Resepku</title>
        <?= sidebarInitialStateScript() ?>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="detail-page<?= $isGuest ? ' detail-page--locked' : '' ?>" data-guest-mode="<?= $isGuest ? '1' : '0' ?>" data-csrf-token="<?= e(csrfToken()) ?>" data-api-base="../api/" data-login-url="../auth/login.php" data-recipe-id="<?= e((string) $recipe['id']) ?>">
    <?= renderGeneralSidebar([
        'basePath' => '../',
        'asideClass' => 'detail-sidebar',
        'activeKey' => 'home',
        'searchAction' => '../cari.php',
        'userContext' => [
            'isLoggedIn' => $currentUserId > 0,
            'isGuest' => $isGuest,
            'isAdmin' => $isAdmin,
            'name' => $sidebarProfile['name'] ?? $userName,
            'avatar' => $sidebarProfile['avatar'] ?? '',
        ],
    ]) ?>

    <main class="detail-main recipe-create-main">
        <a class="detail-back" href="../home/" aria-label="Kembali ke beranda">
            <span aria-hidden="true">←</span>
            <span>Kembali</span>
        </a>

        <div class="recipe-create detail-recipe">
            <section class="detail-hero detail-card recipe-create__hero detail-recipe__hero">
                <div class="detail-hero__media recipe-create__preview">
                    <div class="recipe-create__preview-frame has-image">
                        <img src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>" class="recipe-create__preview-image">
                        <div class="recipe-create__preview-badge">Detail Resep</div>
                    </div>
                    <div class="detail-recipe__preview-note">
                        <strong><?= e($categoryLabel) ?></strong>
                        <span><?= e($previewNoteText) ?></span>
                    </div>
                </div>

                <div class="detail-hero__panel">
                    <p class="detail-hero__eyebrow">Detail Resep</p>
                    <h1><?= e($recipe['title']) ?></h1>

                    <?php if ($authorProfileUrl !== ''): ?>
                        <a class="detail-author-link detail-recipe__author" href="<?= e($authorProfileUrl) ?>" aria-label="Lihat profil <?= e($authorName) ?>">
                            <img class="detail-author-link__avatar" src="<?= e($authorAvatar) ?>" alt="">
                            <span>
                                <span class="detail-author-link__label">Pembuat resep</span>
                                <span class="detail-author-link__identity">
                                    <strong><?= e($authorName) ?></strong>
                                    <?= userAdminBadge((string) ($recipe['author_role'] ?? 'pengguna')) ?>
                                </span>
                            </span>
                        </a>
                    <?php else: ?>
                        <div class="detail-author-link detail-author-link--static detail-recipe__author">
                            <img class="detail-author-link__avatar" src="<?= e($authorAvatar) ?>" alt="">
                            <span>
                                <span class="detail-author-link__label">Pembuat resep</span>
                                <span class="detail-author-link__identity">
                                    <strong><?= e($authorName) ?></strong>
                                    <?= userAdminBadge((string) ($recipe['author_role'] ?? 'pengguna')) ?>
                                </span>
                            </span>
                        </div>
                    <?php endif; ?>

                    <p class="recipe-create__lede detail-summary"><?= e($summaryText) ?></p>

                    <div class="detail-meta recipe-create__meta">
                        <span><?= e(str_replace(['mins', 'minutes'], ['menit', 'menit'], $recipe['cook_time'])) ?></span>
                        <span><?= e(str_replace(['servings', 'serving'], ['porsi', 'porsi'], $recipe['servings'])) ?></span>
                        <span><?= e($recipe['difficulty']) ?></span>
                        <span><?= e($categoryLabel) ?></span>
                    </div>

                    <div class="detail-actions<?= $isOwner ? ' detail-actions--owner' : '' ?>" aria-label="Aksi resep">
                        <?php if ($isOwner && $canUseRecipeActions): ?>
                            <a class="detail-action detail-action--primary detail-action--owner" href="../resep/edit.php?id=<?= e((string) $recipe['id']) ?>">
                                <span>Edit Resep</span>
                            </a>
                        <?php elseif ($canUseRecipeActions): ?>
                            <button type="button" class="detail-action detail-action--favorite<?= $socialState['favorited'] ? ' is-active' : '' ?>" data-guest-gate data-social-action="favorite" data-recipe-id="<?= e((string) $recipe['id']) ?>" aria-label="Simpan resep">
                                <svg class="detail-action__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M7 4.75c0-.97.78-1.75 1.75-1.75h6.5c.97 0 1.75.78 1.75 1.75v15L12 16.8l-5 2.95v-15Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path>
                                </svg>
                                <span>Favorit</span>
                            </button>
                            <button type="button" class="detail-action detail-action--metric<?= $socialState['liked'] ? ' is-active' : '' ?>" data-guest-gate data-social-action="like" data-recipe-id="<?= e((string) $recipe['id']) ?>" aria-label="Suka">
                                <svg class="detail-action__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M12 20.1s-6.8-4.2-8.9-8.2C1.6 9 2.5 5.7 5.3 4.5c2-.9 4.2-.3 5.5 1.4L12 7.4l1.2-1.5c1.3-1.7 3.6-2.3 5.5-1.4 2.8 1.2 3.7 4.5 2.2 7.4-2.1 4-8.9 8.2-8.9 8.2Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path>
                                </svg>
                                <span class="detail-action__value" data-like-count><?= e((string) $socialState['likes_count']) ?></span>
                            </button>
                            <button type="button" class="detail-action detail-action--rate<?= $socialState['user_rating'] !== null ? ' is-active' : '' ?>" data-guest-gate data-social-action="rate" data-recipe-id="<?= e((string) $recipe['id']) ?>">
                                <svg class="detail-action__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="m12 3.5 2.42 4.9 5.4.78-3.91 3.82.92 5.38L12 15.84l-4.83 2.54.92-5.38-3.91-3.82 5.4-.78L12 3.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path>
                                </svg>
                                <span>Nilai</span>
                                <span class="detail-action__value" data-user-rating><?= $socialState['user_rating'] !== null ? e(number_format((float) $socialState['user_rating'], 1)) : '0.0' ?></span>
                            </button>
                        <?php endif; ?>

                        <button type="button" class="detail-action detail-action--share" data-social-action="share" data-share-url="<?= e($shareUrl) ?>">
                            <svg class="detail-action__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M8.5 12.6 15.7 16.8M15.6 7.2 8.5 11.4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                                <circle cx="6.5" cy="12" r="2.4" fill="none" stroke="currentColor" stroke-width="1.8"></circle>
                                <circle cx="17.5" cy="6" r="2.4" fill="none" stroke="currentColor" stroke-width="1.8"></circle>
                                <circle cx="17.5" cy="18" r="2.4" fill="none" stroke="currentColor" stroke-width="1.8"></circle>
                            </svg>
                            <span>Bagikan</span>
                        </button>

                        <button type="button" class="detail-action detail-action--print" data-social-action="print">
                            <svg class="detail-action__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9V3h12v6"></path>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                <path d="M6 14h12v7H6z"></path>
                            </svg>
                            <span>Cetak</span>
                        </button>

                        <?php if (!$isOwner && $canUseRecipeActions): ?>
                            <button type="button" class="detail-action detail-action--report" aria-label="Laporkan" data-guest-gate data-report-open data-report-target-type="resep" data-report-target-id="<?= e((string) $recipe['id']) ?>" data-report-target-label="<?= e($recipe['title']) ?>">
                                <svg class="detail-action__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M5 12h.01M12 12h.01M19 12h.01" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"></path>
                                </svg>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="recipe-create__body detail-layout<?= $isGuest ? ' detail-layout--locked' : '' ?>">
                <div class="recipe-create__sticky-column detail-layout__aside">
                    <section class="detail-panel recipe-create__section recipe-create__section--materials detail-panel--ingredients">
                        <div class="recipe-create__section-head recipe-create__section-head--materials">
                            <h2>Bahan &amp; Peralatan</h2>
                        </div>

                        <div class="recipe-create__tabs" role="tablist" aria-label="Bahan dan peralatan">
                            <button type="button" class="recipe-create__tab is-active" data-detail-material-tab="ingredients" aria-selected="true">Bahan <span>(<?= e((string) count($recipe['ingredients'])) ?>)</span></button>
                            <button type="button" class="recipe-create__tab" data-detail-material-tab="tools" aria-selected="false">Peralatan <span>(<?= e((string) count($recipe['tools'])) ?>)</span></button>
                        </div>

                        <div class="recipe-create__tab-panel is-active" data-detail-material-panel="ingredients">
                            <?php if ($ingredientGroups === []): ?>
                                <p class="detail-panel__empty">Belum ada daftar bahan untuk resep ini.</p>
                            <?php else: ?>
                                <div class="detail-material-groups">
                                    <?php foreach ($ingredientGroups as $group): ?>
                                        <section class="detail-material-group">
                                            <?php if ($group['title'] !== ''): ?>
                                                <h3><?= e($group['title']) ?></h3>
                                            <?php endif; ?>
                                            <div class="recipe-create__table recipe-create__table--ingredients detail-material-table">
                                                <div class="recipe-create__table-head detail-material-head" aria-hidden="true">
                                                    <span>No</span>
                                                    <span>Bahan</span>
                                                </div>
                                                <div class="recipe-create__rows recipe-create__rows--table">
                                                    <?php foreach ($group['items'] as $index => $ingredient): ?>
                                                        <div class="recipe-create__row detail-material-row">
                                                            <span class="recipe-create__row-number"><?= e((string) ($index + 1)) ?></span>
                                                            <div class="detail-material-cell"><?= e($ingredient) ?></div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </section>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="recipe-create__tab-panel" data-detail-material-panel="tools" hidden>
                            <?php if ($recipe['tools'] === []): ?>
                                <p class="detail-panel__empty">Peralatan belum dicantumkan.</p>
                            <?php else: ?>
                                <div class="recipe-create__table recipe-create__table--tools detail-material-table">
                                    <div class="recipe-create__table-head detail-material-head" aria-hidden="true">
                                        <span>No</span>
                                        <span>Nama Peralatan</span>
                                    </div>
                                    <div class="recipe-create__rows recipe-create__rows--table">
                                        <?php foreach ($recipe['tools'] as $index => $tool): ?>
                                            <div class="recipe-create__row detail-material-row">
                                                <span class="recipe-create__row-number"><?= e((string) ($index + 1)) ?></span>
                                                <div class="detail-material-cell"><?= e($tool) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <div class="recipe-create__content-column detail-layout__main">
                    <section class="detail-panel recipe-create__section recipe-create__section--steps detail-panel--steps">
                        <div class="recipe-create__section-head">
                            <div>
                                <h2>Langkah memasak</h2>
                                <p class="recipe-create__steps-note">Urutan memasak disajikan dengan layout yang sama seperti halaman tambah resep, tanpa mode edit.</p>
                            </div>
                        </div>

                        <?php if ($recipe['steps'] === []): ?>
                            <p class="detail-panel__empty">Belum ada langkah memasak untuk resep ini.</p>
                        <?php else: ?>
                            <ol class="detail-steps">
                                <?php foreach ($recipe['steps'] as $index => $step): ?>
                                    <?php $stepData = is_array($step) ? $step : ['text' => (string) $step, 'image' => '']; ?>
                                    <?php $stepImage = trim((string) ($stepData['image'] ?? '')); ?>
                                    <?php $stepCopy = detail_format_step_content((string) ($stepData['text'] ?? ''), $index, (string) ($stepData['title'] ?? '')); ?>
                                    <li class="detail-step<?= $stepImage === '' ? ' detail-step--text-only' : '' ?>">
                                        <div class="recipe-create__step-marker detail-step__marker">
                                            <span><?= e((string) ($index + 1)) ?></span>
                                        </div>

                                        <?php if ($stepImage !== ''): ?>
                                            <button
                                                type="button"
                                                class="recipe-create__step-media detail-step__media detail-step__image-button"
                                                data-detail-image-open
                                                data-detail-image-src="<?= e(recipe_asset_path($stepImage)) ?>"
                                                data-detail-image-alt="<?= e('Ilustrasi langkah memasak ' . (string) ($index + 1)) ?>"
                                                data-detail-image-caption="<?= e($stepCopy['label']) ?>"
                                                aria-haspopup="dialog"
                                                aria-label="<?= e('Lihat gambar langkah ' . (string) ($index + 1) . ' lebih detail') ?>"
                                            >
                                                <div class="recipe-create__step-preview has-image detail-step__preview">
                                                    <img src="<?= e(recipe_asset_path($stepImage)) ?>" alt="Ilustrasi langkah memasak <?= e((string) ($index + 1)) ?>">
                                                </div>
                                            </button>
                                        <?php endif; ?>

                                        <div class="recipe-create__step-body detail-step__content">
                                            <h3 class="detail-step__title"><?= e($stepCopy['title']) ?></h3>
                                            <?php if ($stepCopy['body'] !== ''): ?>
                                                <p class="detail-step__text"><?= e($stepCopy['body']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>
                    </section>
                </div>

                <?php if ($isGuest): ?>
                    <div class="detail-lock-overlay">
                        <div class="detail-lock">
                            <span class="detail-lock__icon" aria-hidden="true">
                                <svg viewBox="0 0 32 32" focusable="false">
                                    <rect x="7.5" y="14" width="17" height="11" rx="3" fill="none" stroke="currentColor" stroke-width="1.8"></rect>
                                    <path d="M11.5 14v-2.25C11.5 9.13 13.63 7 16.25 7s4.75 2.13 4.75 4.75V14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                                    <circle cx="16" cy="19" r="1.5" fill="currentColor"></circle>
                                </svg>
                            </span>
                            <p class="detail-lock__eyebrow">Mode Tamu</p>
                            <h3 class="detail-lock__title"><?= e($guestLockTitle) ?></h3>
                            <p class="detail-lock__text"><?= e($guestLockText) ?></p>
                            <div class="detail-lock__actions">
                                <button type="button" class="detail-action detail-action--primary" data-guest-gate>Buat akun</button>
                                <a class="detail-lock__link" href="../auth/login.php">Masuk</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="detail-panel recipe-create__section detail-panel--related">
                <div class="recipe-create__section-head detail-related__header">
                    <div>
                        <h2>Kamu mungkin juga suka</h2>
                    </div>
                    <a class="detail-related__browse" href="<?= e($relatedBrowseUrl) ?>">Lihat semua</a>
                </div>

                <?php if ($relatedRecipes === []): ?>
                    <p class="detail-panel__empty">Belum ada resep terkait untuk ditampilkan.</p>
                <?php else: ?>
                    <div class="detail-related">
                        <?php foreach ($relatedRecipes as $relatedRecipe): ?>
                            <article class="detail-related-card">
                                <a class="detail-related-card__link" href="detail.php?id=<?= e((string) $relatedRecipe['id']) ?>">
                                    <span class="sr-only">Buka resep <?= e($relatedRecipe['title']) ?></span>
                                </a>
                                <div class="detail-related-card__media">
                                    <img src="<?= e($relatedRecipe['image']) ?>" alt="<?= e($relatedRecipe['title']) ?>">
                                </div>
                                <button class="detail-related-card__bookmark<?= !empty($relatedRecipe['favorited']) ? ' is-active' : '' ?>" type="button" aria-label="Simpan resep" aria-pressed="<?= !empty($relatedRecipe['favorited']) ? 'true' : 'false' ?>" data-card-favorite data-recipe-id="<?= e((string) $relatedRecipe['id']) ?>">
                                    <img src="../assets/img/icon-bookmark.svg" alt="">
                                </button>
                                <div class="detail-related-card__body">
                                    <h3><?= e($relatedRecipe['title']) ?></h3>
                                    <div class="detail-related-card__meta">
                                        <span>★ <?= e(number_format((float) ($relatedRecipe['rating'] ?? 0), 1)) ?></span>
                                        <span><?= e($relatedRecipe['cook_time']) ?></span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <article class="detail-panel recipe-create__section detail-comments<?= $isGuest ? ' detail-panel--locked' : '' ?>" id="comments-section">
                <div class="recipe-create__section-head detail-comments__head">
                    <div>
                        <h2>Komentar <span data-comment-count><?= e((string) $commentCount) ?></span></h2>
                    </div>
                </div>

                <div class="detail-comments__list" data-comment-list>
                    <?php if ($comments === []): ?>
                        <div class="detail-comments__empty" data-comment-empty>
                            <h3>Belum ada komentar</h3>
                            <p>Jadilah yang pertama memberi komentar pada resep ini.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <article class="detail-comment">
                                <img class="detail-comment__avatar" src="<?= e($comment['avatar']) ?>" alt="<?= e($comment['author']) ?>">
                                <div class="detail-comment__body">
                                    <div class="detail-comment__bubble">
                                        <div class="detail-comment__meta">
                                            <span class="detail-comment__author">
                                                <strong><?= e($comment['author']) ?></strong>
                                                <?= userAdminBadge((string) ($comment['author_role'] ?? 'pengguna')) ?>
                                            </span>
                                            <span class="detail-comment__time"><?= e($comment['created_at_label']) ?></span>
                                        </div>
                                        <p><?= e($comment['content']) ?></p>
                                    </div>
                                    <div class="detail-comment__actions" aria-label="Aksi komentar">
                                        <button type="button" class="detail-comment__action" disabled>Suka</button>
                                        <button type="button" class="detail-comment__action" disabled>Balas</button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (!$isGuest): ?>
                    <div class="detail-comments__composer-wrap">
                        <p class="detail-comments__hint">Komentar baru akan muncul di daftar setelah dikirim.</p>
                        <div class="detail-comments__composer-shell">
                            <form class="detail-comments__form" data-comment-form>
                                <input type="hidden" name="recipe_id" value="<?= e((string) $recipe['id']) ?>">
                                <div class="detail-comments__composer">
                                    <img class="detail-comments__composer-avatar" src="<?= e($sidebarProfile['avatar'] ?? '../assets/img/home-profile.png') ?>" alt="<?= e($sidebarProfile['name'] ?? $userName) ?>">
                                    <label class="detail-comments__composer-field" for="comment-content">
                                        <span class="sr-only">Tulis komentar</span>
                                        <textarea id="comment-content" name="content" rows="1" placeholder="Tambahkan komentar..." required></textarea>
                                    </label>
                                    <button type="submit" class="detail-action detail-action--primary detail-comments__submit">
                                        <span class="detail-comments__submit-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" focusable="false">
                                                <path d="M4.75 11.5 19 4.75l-4.3 14.5-3.2-4.55L4.75 11.5Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"></path>
                                                <path d="m10.9 13.95 4.8-4.8" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"></path>
                                            </svg>
                                        </span>
                                        <span>Kirim</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </article>
        </div>
    </main>

    <?php if ($recipe['id'] > 0): ?>
        <div class="report-modal" data-report-modal aria-hidden="true">
            <div class="report-modal__backdrop" data-report-close></div>
            <div class="report-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="report-modal-title">
                <p class="report-modal__eyebrow">Pengaduan</p>
                <h2 id="report-modal-title">Laporkan resep</h2>
                <p data-report-target-preview>Pengaduan akan dikirim untuk resep ini.</p>
                <div class="report-modal__summary">
                    <span>Diproses admin</span>
                    <span>Status awal: menunggu</span>
                </div>

                <form class="report-form" data-report-form>
                    <input type="hidden" name="target_type" value="resep">
                    <input type="hidden" name="target_id" value="<?= e((string) $recipe['id']) ?>">

                    <p class="report-form__note">Gunakan pengaduan ini untuk spam, konten tidak pantas, penipuan, pelanggaran hak cipta, atau pelecehan.</p>

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
    <?php endif; ?>

    <div class="guest-modal" data-guest-modal aria-hidden="true">
        <div class="guest-modal__backdrop" data-guest-close></div>
        <div class="guest-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="guest-modal-title">
            <p class="guest-modal__eyebrow">Mode Tamu</p>
            <h2 id="guest-modal-title">Daftar untuk membuka aksi</h2>
            <p>Suka, favorit, dan rating tersedia setelah kamu masuk.</p>
            <div class="guest-modal__actions">
                <a class="guest-modal__primary" href="../auth/register.php">Buat akun</a>
                <button type="button" class="guest-modal__secondary" data-guest-close>Nanti saja</button>
            </div>
        </div>
    </div>

    <div class="detail-image-lightbox" data-detail-image-lightbox aria-hidden="true">
        <button type="button" class="detail-image-lightbox__backdrop" data-detail-image-close aria-label="Tutup pratinjau gambar"></button>
        <div class="detail-image-lightbox__dialog" role="dialog" aria-modal="true" aria-labelledby="detail-image-lightbox-title">
            <div class="detail-image-lightbox__toolbar">
                <p id="detail-image-lightbox-title" class="detail-image-lightbox__title">Pratinjau gambar langkah</p>
                <button type="button" class="detail-image-lightbox__close" data-detail-image-close aria-label="Tutup pratinjau gambar">
                    <span aria-hidden="true">×</span>
                    <span>Tutup</span>
                </button>
            </div>
            <figure class="detail-image-lightbox__figure">
                <img src="" alt="" data-detail-image-preview>
                <figcaption class="detail-image-lightbox__caption" data-detail-image-caption></figcaption>
            </figure>
        </div>
    </div>

    <script>
        (() => {
            const tabs = document.querySelectorAll('[data-detail-material-tab]');
            const panels = document.querySelectorAll('[data-detail-material-panel]');

            if (tabs.length === 0 || panels.length === 0) {
                return;
            }

            function activateTab(name) {
                tabs.forEach((tab) => {
                    const active = tab.dataset.detailMaterialTab === name;
                    tab.classList.toggle('is-active', active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                panels.forEach((panel) => {
                    const active = panel.dataset.detailMaterialPanel === name;
                    panel.classList.toggle('is-active', active);
                    panel.hidden = !active;
                });
            }

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => activateTab(tab.dataset.detailMaterialTab || 'ingredients'));
            });
        })();
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
