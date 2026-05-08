<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/admin_repository.php';
require_once __DIR__ . '/_layout.php';

$adminUser = requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        admin_flash('error', 'Sesi form tidak valid.');
        redirectTo('resep.php');
    }

    $action = (string) ($_POST['action'] ?? '');
    $recipeId = (int) ($_POST['recipe_id'] ?? 0);

    if ($action === 'delete') {
        $ok = admin_delete_recipe_db($recipeId);
        admin_flash($ok ? 'success' : 'error', $ok ? 'Resep berhasil dihapus.' : 'Resep gagal dihapus.');
    }

    redirectTo('resep.php');
}

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'category' => trim((string) ($_GET['category'] ?? '')),
    'difficulty' => trim((string) ($_GET['difficulty'] ?? '')),
    'sort' => trim((string) ($_GET['sort'] ?? 'newest')),
];
$recipes = admin_recipes_db($filters);
$categories = admin_recipe_categories_db();

admin_header('Kelola Resep', $adminUser, 'resep');
?>
<section class="admin-panel">
    <form class="admin-filters" method="get">
        <label>
            <span>Cari</span>
            <input type="search" name="q" placeholder="Judul atau author" value="<?= e($filters['q']) ?>">
        </label>
        <label>
            <span>Kategori</span>
            <select name="category">
                <option value="">Semua kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category) ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Kesulitan</span>
            <select name="difficulty">
                <option value="">Semua kesulitan</option>
                <option value="mudah" <?= $filters['difficulty'] === 'mudah' ? 'selected' : '' ?>>Mudah</option>
                <option value="sedang" <?= $filters['difficulty'] === 'sedang' ? 'selected' : '' ?>>Sedang</option>
                <option value="sulit" <?= $filters['difficulty'] === 'sulit' ? 'selected' : '' ?>>Sulit</option>
            </select>
        </label>
        <label>
            <span>Sort</span>
            <select name="sort">
                <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Terbaru</option>
                <option value="oldest" <?= $filters['sort'] === 'oldest' ? 'selected' : '' ?>>Terlama</option>
                <option value="popular" <?= $filters['sort'] === 'popular' ? 'selected' : '' ?>>Populer</option>
            </select>
        </label>
        <button type="submit">Terapkan</button>
        <a href="resep.php">Reset</a>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Author</th>
                    <th>Kategori</th>
                    <th>Kesulitan</th>
                    <th>Waktu</th>
                    <th>Likes</th>
                    <th>Komentar</th>
                    <th>Rating</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recipes as $recipe): ?>
                    <tr>
                        <td><?= e($recipe['nama_resep']) ?></td>
                        <td><?= e($recipe['nama_pengguna']) ?></td>
                        <td><?= e($recipe['kategori'] ?? '-') ?></td>
                        <td><?= admin_badge((string) $recipe['tingkat_kesulitan']) ?></td>
                        <td><?= e($recipe['waktu_memasak'] !== null ? $recipe['waktu_memasak'] . ' menit' : '-') ?></td>
                        <td><?= e((string) $recipe['likes_count']) ?></td>
                        <td><?= e((string) $recipe['comments_count']) ?></td>
                        <td><?= e(number_format((float) $recipe['rating_average'], 1)) ?></td>
                        <td><?= e((string) $recipe['dibuat_pada']) ?></td>
                        <td>
                            <div class="admin-actions">
                                <a href="../resep/detail.php?id=<?= e((string) $recipe['resep_id']) ?>">Lihat</a>
                                <form method="post" onsubmit="return confirm('Hapus resep ini?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="recipe_id" value="<?= e((string) $recipe['resep_id']) ?>">
                                    <button class="admin-danger" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($recipes === []): ?>
                    <tr><td colspan="10">Resep tidak ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_footer(); ?>

