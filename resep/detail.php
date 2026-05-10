<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/admin_repository.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

$isAdmin = isAdmin();
$isGuest = empty($_SESSION['user']);
$userName = $_SESSION['user']['name'] ?? 'Tamu';
$currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
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

function detail_format_step_content(string $text, int $index): array
{
    $normalized = trim((string) preg_replace('/\r\n|\r/u', "\n", $text));
    $numberLabel = 'Langkah ' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

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

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($recipe['title']) ?> - Resepku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="detail-page<?= $isGuest ? ' detail-page--locked' : '' ?>" data-guest-mode="<?= $isGuest ? '1' : '0' ?>" data-csrf-token="<?= e(csrfToken()) ?>" data-api-base="../api/" data-login-url="../auth/login.php">
    <aside class="home-sidebar detail-sidebar">
        <div class="home-sidebar__profile">
            <div class="home-sidebar__brand">
                <img src="../assets/img/resepku-logo.png" alt="" class="home-sidebar__logo">
                <div>
                    <p class="home-sidebar__name">Resepku</p>
                    <p class="home-sidebar__status"><?= $isGuest ? 'Mode tamu' : 'Sudah masuk' ?></p>
                </div>
            </div>

            <div class="home-sidebar__identity">
                <img src="../assets/img/home-profile.png" alt="" class="home-sidebar__avatar">
                <div class="home-sidebar__welcome">
                    <strong><?= e($userName) ?></strong>
                <span><?= $isGuest ? 'Masuk untuk mengikuti resep dan profil.' : 'Lanjutkan baca detail dan aksi resep.' ?></span>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <a href="../admin/" class="home-sidebar__admin-panel">Panel Admin</a>
            <?php endif; ?>

            <?php if ($currentUserId > 0): ?>
                <a href="../profil/laporan.php">Pengaduan Saya</a>
            <?php endif; ?>

            <a href="../auth/logout.php" class="home-sidebar__logout">Keluar</a>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi Resep">
            <a href="../home/">Beranda</a>
            <a href="../profil/">Profil</a>
            <a href="../resep/myresep.php">Resep Saya</a>
            <a href="../resep/buat.php">Tambah Resep</a>
            <a href="../resep/favorite.php">Favorit</a>
            <a href="<?= e(reportInboxHref('../profil/laporan.php', '../auth/login.php')) ?>">Pengaduan Saya</a>
            <?php if ($isAdmin): ?>
                <a href="../admin/">Admin</a>
            <?php endif; ?>
            <a href="../cari.php">Cari</a>
        </nav>

        <img src="../assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="detail-main">
        <a class="detail-back" href="../home/" aria-label="Kembali ke beranda">
            <span aria-hidden="true">←</span>
            <span>Kembali</span>
        </a>

        <section class="detail-hero detail-card">
            <div class="detail-hero__media">
                <img src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
            </div>

            <div class="detail-hero__panel">
                <div class="detail-hero__badges">
                    <span class="detail-badge detail-badge--category"><?= e($categoryLabel) ?></span>
                    <span class="detail-badge">
                        <img class="detail-badge__icon" src="../assets/img/icon-portions.svg" alt="">
                        <span><?= e(str_replace(['servings', 'serving'], ['porsi', 'porsi'], $recipe['servings'])) ?></span>
                    </span>
                    <span class="detail-badge">
                        <img class="detail-badge__icon" src="../assets/img/icon-clock.svg" alt="">
                        <span><?= e(str_replace(['mins', 'minutes'], ['menit', 'menit'], $recipe['cook_time'])) ?></span>
                    </span>
                    <span class="detail-badge">
                        <img class="detail-badge__icon" src="../assets/img/icon-easy.svg" alt="">
                        <span><?= e($recipe['difficulty']) ?></span>
                    </span>
                </div>

                <div class="detail-hero__copy">
                    <h1><?= e($recipe['title']) ?></h1>
                    <?php if ($authorProfileUrl !== ''): ?>
                        <a class="detail-author-link" href="<?= e($authorProfileUrl) ?>" aria-label="Lihat profil <?= e($authorName) ?>">
                            <img class="detail-author-link__avatar" src="<?= e($authorAvatar) ?>" alt="">
                            <span>
                                <span class="detail-author-link__label">Pembuat resep</span>
                                <strong><?= e($authorName) ?></strong>
                            </span>
                        </a>
                    <?php else: ?>
                        <div class="detail-author-link detail-author-link--static">
                            <img class="detail-author-link__avatar" src="<?= e($authorAvatar) ?>" alt="">
                            <span>
                                <span class="detail-author-link__label">Pembuat resep</span>
                                <strong><?= e($authorName) ?></strong>
                            </span>
                        </div>
                    <?php endif; ?>
                    <p class="detail-summary"><?= e($summaryText) ?></p>
                </div>

                <div class="detail-actions" aria-label="Aksi resep">
                    <button type="button" class="detail-action<?= $socialState['liked'] ? ' is-active' : '' ?>" data-guest-gate data-social-action="like" data-recipe-id="<?= e((string) $recipe['id']) ?>">
                        <span>Suka</span>
                        <span data-like-count><?= e((string) $socialState['likes_count']) ?></span>
                    </button>
                    <button type="button" class="detail-action detail-action--primary<?= $socialState['favorited'] ? ' is-active' : '' ?>" data-guest-gate data-social-action="favorite" data-recipe-id="<?= e((string) $recipe['id']) ?>">
                        <img class="detail-action__icon" src="../assets/img/icon-bookmark.svg" alt="">
                        <span>Favorit</span>
                        <span data-favorite-count><?= e((string) $socialState['favorites_count']) ?></span>
                    </button>
                    <button type="button" class="detail-action" data-guest-gate data-social-action="rate" data-recipe-id="<?= e((string) $recipe['id']) ?>">
                        <span>Nilai</span>
                        <span data-user-rating><?= $socialState['user_rating'] !== null ? e(number_format((float) $socialState['user_rating'], 1)) : '0.0' ?></span>
                    </button>
                    <button type="button" class="detail-action" data-social-action="share" data-share-url="<?= e($shareUrl) ?>">
                        <img class="detail-action__icon" src="../assets/img/icon-share.svg" alt="">
                        <span>Bagikan</span>
                    </button>
                    <?php if ($recipe['id'] > 0): ?>
                        <button type="button" class="detail-action detail-action--icon detail-action--report" aria-label="Opsi lainnya" data-guest-gate data-report-open data-report-target-type="resep" data-report-target-id="<?= e((string) $recipe['id']) ?>" data-report-target-label="<?= e($recipe['title']) ?>">
                            <span class="detail-action__dots" aria-hidden="true">
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="detail-layout<?= $isGuest ? ' detail-layout--locked' : '' ?>">
            <aside class="detail-layout__aside">
                <article class="detail-panel detail-panel--ingredients">
                    <div class="detail-panel__heading detail-panel__heading--ingredients">
                        <span class="detail-panel__heading-icon" aria-hidden="true">
                            <svg viewBox="0 0 32 32" focusable="false">
                                <path d="M8 14.25c0-1.8 1.46-3.25 3.25-3.25h9.5c1.79 0 3.25 1.45 3.25 3.25v.75H8v-.75Z" fill="currentColor"></path>
                                <path d="M10.25 16.5h11.5v2.25c0 2.62-2.13 4.75-4.75 4.75h-2c-2.62 0-4.75-2.13-4.75-4.75V16.5Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"></path>
                                <path d="M6.75 14.5h18.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"></path>
                                <path d="M15.25 8.5c0-1.52 1.23-2.75 2.75-2.75S20.75 6.98 20.75 8.5" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"></path>
                                <path d="M11.5 9.5c0-1.24 1.01-2.25 2.25-2.25" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"></path>
                            </svg>
                        </span>
                        <h2>Bahan</h2>
                    </div>

                    <?php if ($ingredientGroups === []): ?>
                        <p class="detail-panel__empty">Belum ada daftar bahan untuk resep ini.</p>
                    <?php else: ?>
                        <div class="detail-ingredient-groups">
                            <?php foreach ($ingredientGroups as $group): ?>
                                <section class="detail-ingredient-group">
                                    <?php if ($group['title'] !== ''): ?>
                                        <h3><?= e($group['title']) ?></h3>
                                    <?php endif; ?>
                                    <ul class="detail-list detail-list--ingredients">
                                        <?php foreach ($group['items'] as $ingredient): ?>
                                            <li><?= e($ingredient) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </section>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>

                <article class="detail-panel detail-panel--tools">
                    <div class="detail-panel__heading">
                        <h2>Peralatan</h2>
                    </div>

                    <?php if ($recipe['tools'] === []): ?>
                        <p class="detail-panel__empty">Peralatan belum dicantumkan.</p>
                    <?php else: ?>
                        <ul class="detail-list detail-list--tools">
                            <?php foreach ($recipe['tools'] as $tool): ?>
                                <li><?= e($tool) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </article>

            </aside>

            <div class="detail-layout__main">
                <article class="detail-panel detail-panel--steps">
                    <div class="detail-panel__heading detail-panel__heading--steps">
                        <h2>Langkah Memasak</h2>
                    </div>

                    <?php if ($recipe['steps'] === []): ?>
                        <p class="detail-panel__empty">Belum ada langkah memasak untuk resep ini.</p>
                    <?php else: ?>
                        <ol class="detail-steps">
                            <?php foreach ($recipe['steps'] as $index => $step): ?>
                                <?php $stepData = is_array($step) ? $step : ['text' => (string) $step, 'image' => '']; ?>
                                <?php $stepImage = trim((string) ($stepData['image'] ?? '')); ?>
                                <?php $stepCopy = detail_format_step_content((string) ($stepData['text'] ?? ''), $index); ?>
                                <li class="detail-step<?= $stepImage === '' ? ' detail-step--text-only' : '' ?>">
                                    <?php if ($stepImage !== ''): ?>
                                        <div class="detail-step__media">
                                            <img src="<?= e(recipe_asset_path($stepImage)) ?>" alt="Ilustrasi langkah memasak <?= e((string) ($index + 1)) ?>">
                                        </div>
                                    <?php endif; ?>
                                    <div class="detail-step__content">
                                        <h3 class="detail-step__title"><?= e($stepCopy['title']) ?></h3>
                                        <?php if ($stepCopy['body'] !== ''): ?>
                                            <p class="detail-step__text"><?= e($stepCopy['body']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </article>
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

        <section class="detail-panel detail-panel--related">
            <div class="detail-related__header">
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

        <article class="detail-panel detail-comments<?= $isGuest ? ' detail-panel--locked' : '' ?>" id="comments-section">
            <div class="detail-comments__head">
                <div>
                    <h2>Komentar <span data-comment-count><?= e((string) $commentCount) ?></span></h2>
                </div>
            </div>

            <?php if (!$isGuest): ?>
                <form class="detail-comments__form" data-comment-form>
                    <input type="hidden" name="recipe_id" value="<?= e((string) $recipe['id']) ?>">
                    <label class="sr-only" for="comment-content">Tulis komentar</label>
                    <textarea id="comment-content" name="content" rows="4" placeholder="Tulis komentar kamu..." required></textarea>
                    <div class="detail-comments__actions">
                        <p class="detail-comments__hint">Komentar akan tampil di atas setelah dikirim.</p>
                        <button type="submit" class="detail-action detail-action--primary detail-comments__submit">Kirim Komentar</button>
                    </div>
                </form>
            <?php endif; ?>

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
                                <div class="detail-comment__meta">
                                    <strong><?= e($comment['author']) ?></strong>
                                    <span><?= e($comment['created_at_label']) ?></span>
                                </div>
                                <p><?= e($comment['content']) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </article>
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

    <script src="../assets/js/main.js"></script>
</body>
</html>
