<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/admin_repository.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

$isAdmin = isAdmin();
$isGuest = empty($_SESSION['user']);
$userName = $_SESSION['user']['name'] ?? 'Guest';
$currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
$recipeId = (int) ($_GET['id'] ?? 1);
$recipe = recipe_find_db($recipeId);

if ($recipe === null) {
    $recipe = [
        'id' => 0,
        'title' => 'Recipe not available',
        'image' => '../assets/img/recipe-salad-hero.png',
        'user_id' => 0,
        'author' => 'ResepKu',
        'author_avatar' => '../assets/img/home-profile.png',
        'cook_time' => '-',
        'servings' => '-',
        'difficulty' => '-',
        'rating' => 0,
        'summary' => 'No recipe data is available yet.',
        'description' => 'No recipe data is available yet.',
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

$relatedRecipes = $recipe['id'] > 0 ? recipe_related_db($recipe, 3) : [];
$comments = $recipe['id'] > 0 ? recipe_comments_db($recipe['id']) : [];
$commentCount = count($comments);

if ($recipe === null) {
    $recipe = [
        'id' => 0,
        'title' => 'Recipe not available',
        'image' => '/assets/img/recipe-salad-hero.png',
        'user_id' => 0,
        'author' => 'ResepKu',
        'author_avatar' => '/assets/img/home-profile.png',
        'cook_time' => '-',
        'servings' => '-',
        'difficulty' => '-',
        'rating' => 0,
        'summary' => 'No recipe data is available yet.',
        'description' => 'No recipe data is available yet.',
        'ingredients' => [],
        'tools' => [],
        'steps' => [],
        'related' => [],
    ];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($recipe['title']) ?> - Resepku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="detail-page" data-guest-mode="<?= $isGuest ? '1' : '0' ?>" data-csrf-token="<?= e(csrfToken()) ?>" data-api-base="../api/" data-login-url="../auth/login.php">
    <aside class="home-sidebar detail-sidebar">
        <div class="home-sidebar__profile">
            <div class="home-sidebar__brand">
                <img src="../assets/img/resepku-logo.png" alt="" class="home-sidebar__logo">
                <div>
                    <p class="home-sidebar__name">Resepku</p>
                    <p class="home-sidebar__status"><?= $isGuest ? 'Guest mode' : 'Signed in' ?></p>
                </div>
            </div>

            <div class="home-sidebar__identity">
                <img src="../assets/img/home-profile.png" alt="" class="home-sidebar__avatar">
                <div class="home-sidebar__welcome">
                    <strong><?= e($userName) ?></strong>
                    <span><?= $isGuest ? 'Login untuk mengikuti resep dan profil.' : 'Lanjutkan baca detail dan aksi resep.' ?></span>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <a href="../admin/" class="home-sidebar__admin-panel">Admin Panel</a>
            <?php endif; ?>

            <?php if ($currentUserId > 0): ?>
                <a href="../profil/laporan.php">Pengaduan Saya</a>
            <?php endif; ?>

            <a href="../auth/logout.php" class="home-sidebar__logout">Log Out</a>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi Resep">
            <a href="../home/">Home</a>
            <a href="../profil/">Profile</a>
            <a href="../resep/myresep.php">My Recipes</a>
            <a href="../resep/buat.php">Add Recipe</a>
            <a href="../resep/favorite.php">Favorite</a>
            <a href="<?= e(reportInboxHref('../profil/laporan.php', '../auth/login.php')) ?>">Pengaduan Saya</a>
            <?php if ($isAdmin): ?>
                <a href="../admin/">Admin</a>
            <?php endif; ?>
            <a href="../cari.php">Search</a>
        </nav>

        <img src="../assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="detail-main">
        <a class="detail-back" href="../home/" aria-label="Kembali ke halaman home">
            <span aria-hidden="true">←</span>
            <span>Back</span>
        </a>

        <section class="detail-hero">
            <div class="detail-hero__media">
                <img src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
            </div>

            <div class="detail-hero__panel">
                <p class="detail-hero__eyebrow">Recipe Detail</p>
                <h1><?= e($recipe['title']) ?></h1>

                <?php if ((int) ($recipe['user_id'] ?? 0) > 0): ?>
                    <a class="detail-author detail-author--link" href="../profil/?id=<?= e((string) $recipe['user_id']) ?>">
                        <img src="<?= e($recipe['author_avatar']) ?>" alt="">
                        <div>
                            <strong><?= e($recipe['author']) ?></strong>
                            <span>Shared on ResepKu</span>
                        </div>
                    </a>
                <?php else: ?>
                    <div class="detail-author">
                        <img src="<?= e($recipe['author_avatar']) ?>" alt="">
                        <div>
                            <strong><?= e($recipe['author']) ?></strong>
                            <span>Shared on ResepKu</span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="detail-meta">
                    <span><?= e($recipe['cook_time']) ?></span>
                    <span><?= e($recipe['servings']) ?></span>
                    <span><?= e($recipe['difficulty']) ?></span>
                    <span data-rating-average><?= number_format((float) $socialState['rating_average'], 1) ?> ★</span>
                </div>

                <p class="detail-summary"><?= e($recipe['summary']) ?></p>

                <div class="detail-actions" aria-label="Aksi resep">
                    <button type="button" class="detail-action<?= $socialState['liked'] ? ' is-active' : '' ?>" data-guest-gate data-social-action="like" data-recipe-id="<?= e((string) $recipe['id']) ?>">
                        <span>Like</span>
                        <span data-like-count><?= e((string) $socialState['likes_count']) ?></span>
                    </button>
                    <button type="button" class="detail-action<?= $socialState['favorited'] ? ' is-active' : '' ?>" data-guest-gate data-social-action="favorite" data-recipe-id="<?= e((string) $recipe['id']) ?>">
                        <span>Favorite</span>
                        <span data-favorite-count><?= e((string) $socialState['favorites_count']) ?></span>
                    </button>
                    <button type="button" class="detail-action" data-guest-gate data-social-action="rate" data-recipe-id="<?= e((string) $recipe['id']) ?>">
                        <span>Rate</span>
                        <span data-user-rating><?= $socialState['user_rating'] !== null ? e(number_format((float) $socialState['user_rating'], 1)) : '0.0' ?></span>
                    </button>
                    <button type="button" class="detail-action" data-social-action="share" data-share-url="<?= e((string) (($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI'])) ?>">
                        <span>Share</span>
                    </button>
                    <?php if ($recipe['id'] > 0): ?>
                        <button type="button" class="detail-action detail-action--report" data-guest-gate data-report-open data-report-target-type="resep" data-report-target-id="<?= e((string) $recipe['id']) ?>" data-report-target-label="<?= e($recipe['title']) ?>">
                            <span>Laporkan</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="detail-grid">
            <div class="detail-column">
                <article class="detail-panel">
                    <p class="detail-panel__label">About</p>
                    <h2>Description</h2>
                    <p class="detail-panel__text"><?= e($recipe['description']) ?></p>
                </article>

                <div class="detail-duo">
                    <article class="detail-panel">
                        <p class="detail-panel__label">Ingredients</p>
                        <h2>Bahan</h2>
                        <ul class="detail-list">
                            <?php foreach ($recipe['ingredients'] as $ingredient): ?>
                                <li><?= e($ingredient) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </article>

                    <article class="detail-panel">
                        <p class="detail-panel__label">Tools</p>
                        <h2>Peralatan</h2>
                        <ul class="detail-list">
                            <?php foreach ($recipe['tools'] as $tool): ?>
                                <li><?= e($tool) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                </div>

                <article class="detail-panel">
                    <p class="detail-panel__label">Steps</p>
                    <h2>Langkah Memasak</h2>
                    <ol class="detail-steps">
                        <?php foreach ($recipe['steps'] as $step): ?>
                            <li><?= e($step) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </article>

                <article class="detail-panel detail-comments" id="comments-section">
                    <div class="detail-comments__head">
                        <div>
                            <p class="detail-panel__label">Comments</p>
                            <h2>Komentar <span data-comment-count><?= e((string) $commentCount) ?></span></h2>
                        </div>
                    </div>

                    <?php if ($isGuest): ?>
                        <div class="detail-comments__guest">
                            <p>Login untuk menulis komentar. Kamu tetap bisa membaca komentar yang sudah ada.</p>
                            <a href="../auth/login.php">Login</a>
                        </div>
                    <?php else: ?>
                        <form class="detail-comments__form" data-comment-form>
                            <input type="hidden" name="recipe_id" value="<?= e((string) $recipe['id']) ?>">
                            <label class="sr-only" for="comment-content">Tulis komentar</label>
                            <textarea id="comment-content" name="content" rows="4" placeholder="Tulis komentar kamu..." required></textarea>
                            <div class="detail-comments__actions">
                                <p class="detail-comments__hint">Komentar akan tampil di atas setelah dikirim.</p>
                                <button type="submit" class="detail-action detail-comments__submit">Kirim Komentar</button>
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
            </div>

            <aside class="detail-side">
                <article class="detail-panel detail-panel--compact">
                    <p class="detail-panel__label">Recommended</p>
                    <h2>Resep Terkait</h2>
                    <div class="detail-related">
                        <?php foreach ($relatedRecipes as $relatedRecipe): ?>
                            <article class="detail-related__item">
                                <img src="<?= e($relatedRecipe['image']) ?>" alt="<?= e($relatedRecipe['title']) ?>">
                                <div>
                                    <h3><?= e($relatedRecipe['title']) ?></h3>
                                    <p><?= e($relatedRecipe['cook_time']) ?></p>
                                    <a href="detail.php?id=<?= e((string) $relatedRecipe['id']) ?>">Get the recipe</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>

                <?php if ($isGuest): ?>
                    <article class="detail-panel detail-panel--guest">
                        <p class="detail-panel__label">Guest Mode</p>
                        <h2>Unlock actions</h2>
                        <p class="detail-panel__text">Like, favorite, dan rating hanya tersedia setelah login.</p>
                        <button type="button" class="detail-action detail-action--full" data-guest-gate>Create account</button>
                    </article>
                <?php endif; ?>
            </aside>
        </section>
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
            <p class="guest-modal__eyebrow">Guest Mode</p>
            <h2 id="guest-modal-title">Register to unlock actions</h2>
            <p>Like, favorite, dan rating tersedia setelah kamu login.</p>
            <div class="guest-modal__actions">
                <a class="guest-modal__primary" href="../auth/register.php">Create account</a>
                <button type="button" class="guest-modal__secondary" data-guest-close>Maybe later</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
