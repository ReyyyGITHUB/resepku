<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

if (empty($_SESSION['user'])) {
    redirectTo('../auth/login.php');
}

$user = $_SESSION['user'];
$recipeId = (int) ($_GET['id'] ?? $_POST['recipe_id'] ?? 0);
$recipe = $recipeId > 0 ? recipe_find_owned_db($recipeId, (int) $user['id']) : null;

if ($recipe === null) {
    redirectTo('../resep/myresep.php');
}

$errors = [];
$success = null;

$old = [
    'nama_resep' => $recipe['title'],
    'deskripsi' => $recipe['summary'],
    'langkah_resep' => implode("\n", $recipe['steps'] ?? []),
    'waktu_memasak' => preg_replace('/[^0-9]/', '', (string) $recipe['cook_time']),
    'porsi' => preg_replace('/[^0-9]/', '', (string) $recipe['servings']),
    'kategori' => $recipe['category'],
    'tingkat_kesulitan' => strtolower((string) $recipe['difficulty']),
];

$ingredients = array_fill(0, 3, [
    'nama_bahan' => '',
    'jumlah' => '',
    'satuan' => '',
    'keterangan' => '',
]);

foreach (array_values($recipe['ingredients'] ?? []) as $index => $ingredientText) {
    if ($index >= 3) {
        break;
    }

    $ingredients[$index]['nama_bahan'] = $ingredientText;
}

$tools = array_fill(0, 3, ['nama_peralatan' => '']);

foreach (array_values($recipe['tools'] ?? []) as $index => $toolText) {
    if ($index >= 3) {
        break;
    }

    $tools[$index]['nama_peralatan'] = $toolText;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['_token'] ?? null)) {
        $errors[] = 'Token form tidak valid. Silakan muat ulang halaman.';
    }

    $old['nama_resep'] = trim((string) ($_POST['nama_resep'] ?? ''));
    $old['deskripsi'] = trim((string) ($_POST['deskripsi'] ?? ''));
    $old['langkah_resep'] = trim((string) ($_POST['langkah_resep'] ?? ''));
    $old['waktu_memasak'] = trim((string) ($_POST['waktu_memasak'] ?? ''));
    $old['porsi'] = trim((string) ($_POST['porsi'] ?? ''));
    $old['kategori'] = trim((string) ($_POST['kategori'] ?? ''));
    $old['tingkat_kesulitan'] = (string) ($_POST['tingkat_kesulitan'] ?? 'sedang');

    $ingredientRows = $_POST['ingredients'] ?? [];
    $toolRows = $_POST['tools'] ?? [];

    if ($old['nama_resep'] === '') {
        $errors[] = 'Nama resep wajib diisi.';
    }
    if ($old['deskripsi'] === '') {
        $errors[] = 'Deskripsi resep wajib diisi.';
    }
    if ($old['langkah_resep'] === '') {
        $errors[] = 'Langkah memasak wajib diisi.';
    }
    if ($old['kategori'] === '') {
        $errors[] = 'Kategori resep wajib diisi.';
    }
    if (!in_array($old['tingkat_kesulitan'], ['mudah', 'sedang', 'sulit'], true)) {
        $errors[] = 'Tingkat kesulitan tidak valid.';
    }
    if ($old['waktu_memasak'] === '' || !ctype_digit($old['waktu_memasak']) || (int) $old['waktu_memasak'] <= 0) {
        $errors[] = 'Waktu memasak harus berupa angka menit yang valid.';
    }
    if ($old['porsi'] === '' || !ctype_digit($old['porsi']) || (int) $old['porsi'] <= 0) {
        $errors[] = 'Porsi harus berupa angka yang valid.';
    }

    $normalizedIngredients = [];
    foreach ($ingredientRows as $row) {
        $name = trim((string) ($row['nama_bahan'] ?? ''));
        if ($name === '') {
            continue;
        }

        $normalizedIngredients[] = [
            'nama_bahan' => $name,
            'jumlah' => trim((string) ($row['jumlah'] ?? '')),
            'satuan' => trim((string) ($row['satuan'] ?? '')),
            'keterangan' => trim((string) ($row['keterangan'] ?? '')),
        ];
    }

    $normalizedTools = [];
    foreach ($toolRows as $row) {
        $name = trim((string) ($row['nama_peralatan'] ?? ''));
        if ($name !== '') {
            $normalizedTools[] = $name;
        }
    }

    if ($normalizedIngredients === []) {
        $errors[] = 'Minimal satu bahan wajib diisi.';
    }

    if ($normalizedTools === []) {
        $errors[] = 'Minimal satu peralatan wajib diisi.';
    }

    $imagePath = null;
    if (!empty($_FILES['foto_resep']['name'])) {
        $upload = $_FILES['foto_resep'];
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload foto resep gagal.';
        } else {
            $mimeType = mime_content_type($upload['tmp_name']);
            if (!isset($allowedTypes[$mimeType])) {
                $errors[] = 'Foto resep harus berformat JPG, PNG, atau WEBP.';
            } else {
                $uploadDir = __DIR__ . '/../uploads/recipes';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $fileName = 'recipe-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowedTypes[$mimeType];
                $target = $uploadDir . '/' . $fileName;

                if (!move_uploaded_file($upload['tmp_name'], $target)) {
                    $errors[] = 'Foto resep tidak bisa disimpan.';
                } else {
                    $imagePath = 'uploads/recipes/' . $fileName;
                }
            }
        }
    }

    if ($errors === []) {
        try {
            $updated = recipe_update_db(
                $recipeId,
                (int) $user['id'],
                [
                    'nama_resep' => $old['nama_resep'],
                    'deskripsi' => $old['deskripsi'],
                    'langkah_resep' => $old['langkah_resep'],
                    'waktu_memasak' => (int) $old['waktu_memasak'],
                    'porsi' => (int) $old['porsi'],
                    'foto_resep' => $imagePath,
                    'kategori' => $old['kategori'],
                    'tingkat_kesulitan' => $old['tingkat_kesulitan'],
                ],
                $normalizedIngredients,
                $normalizedTools
            );

            if ($updated) {
                redirectTo('../resep/myresep.php?updated=1');
            }

            $errors[] = 'Resep tidak bisa diperbarui.';
        } catch (Throwable $throwable) {
            $errors[] = 'Gagal memperbarui resep. Coba lagi.';
        }
    }
}

function old(string $key, array $old): string
{
    return e($old[$key] ?? '');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Recipe - Resepku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="recipe-create-page">
    <aside class="home-sidebar detail-sidebar">
        <div class="home-sidebar__profile">
            <div class="home-sidebar__brand">
                <img src="../assets/img/resepku-logo.png" alt="" class="home-sidebar__logo">
                <div>
                    <p class="home-sidebar__name">Resepku</p>
                    <p class="home-sidebar__status">Signed in</p>
                </div>
            </div>

            <div class="home-sidebar__identity">
                <img src="<?= e($recipe['author_avatar']) ?>" alt="<?= e($recipe['author']) ?>" class="home-sidebar__avatar">
                <div class="home-sidebar__welcome">
                    <strong><?= e($recipe['author']) ?></strong>
                    <span>Update resep milikmu tanpa keluar dari alur kerja.</span>
                </div>
            </div>

            <a href="../auth/logout.php" class="home-sidebar__logout">Log Out</a>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi Resep">
            <a href="../home/">Home</a>
            <a href="../profil/">Profile</a>
            <a href="../resep/myresep.php">My Recipes</a>
            <a class="is-active" href="../resep/buat.php">Add Recipe</a>
            <a href="../home/?sort=popular">Favorite</a>
            <a href="../home/#recipe-search">Search</a>
        </nav>

        <p class="home-sidebar__label home-sidebar__label--compact">kategori</p>
        <nav class="home-sidebar__nav home-sidebar__nav--categories" aria-label="Kategori resep">
            <a href="#">Food</a>
            <a href="#">Salad</a>
            <a href="#">Dessert</a>
            <a href="#">Drinks</a>
        </nav>

        <img src="../assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="detail-main recipe-create-main">
        <a class="detail-back" href="../resep/myresep.php" aria-label="Kembali ke halaman my recipes">
            <span aria-hidden="true">←</span>
            <span>Back</span>
        </a>

        <form class="recipe-create" id="recipe-create-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="recipe_id" value="<?= e((string) $recipeId) ?>">

            <div class="detail-hero recipe-create__hero">
                <div class="detail-hero__media recipe-create__preview">
                    <div class="recipe-create__preview-frame">
                        <img src="<?= e($recipe['image']) ?>" alt="Preview resep" class="recipe-create__preview-image" data-preview-image>
                        <div class="recipe-create__preview-badge">Preview</div>
                    </div>
                    <label class="recipe-create__upload">
                        <strong data-preview-upload-label>Ganti foto resep</strong>
                        <input type="file" name="foto_resep" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    </label>
                </div>

                <div class="detail-hero__panel">
                    <p class="detail-hero__eyebrow">Edit Recipe</p>
                    <h1 data-preview-title><?= old('nama_resep', $old) ?></h1>

                    <?php if ($errors !== []): ?>
                        <div class="recipe-create__alert recipe-create__alert--error">
                            <?php foreach ($errors as $error): ?>
                                <p><?= e($error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="detail-meta recipe-create__meta">
                        <span data-preview-time><?= $old['waktu_memasak'] !== '' ? e($old['waktu_memasak']) . ' mins' : 'Cook time' ?></span>
                        <span data-preview-porsi><?= $old['porsi'] !== '' ? e($old['porsi']) . ' servings' : 'Servings' ?></span>
                        <span data-preview-difficulty><?= e(ucfirst($old['tingkat_kesulitan'])) ?></span>
                        <span data-preview-category><?= $old['kategori'] !== '' ? e($old['kategori']) : 'Category' ?></span>
                    </div>

                    <div class="recipe-create__grid recipe-create__grid--two">
                        <label>
                            <span>Nama resep</span>
                            <input type="text" name="nama_resep" value="<?= old('nama_resep', $old) ?>" required data-preview-input="title">
                        </label>
                        <label>
                            <span>Kategori</span>
                            <input type="text" name="kategori" value="<?= old('kategori', $old) ?>" required data-preview-input="category">
                        </label>
                        <label>
                            <span>Waktu memasak (menit)</span>
                            <input type="number" name="waktu_memasak" min="1" step="1" value="<?= old('waktu_memasak', $old) ?>" required data-preview-input="time">
                        </label>
                        <label>
                            <span>Porsi</span>
                            <input type="number" name="porsi" min="1" step="1" value="<?= old('porsi', $old) ?>" required data-preview-input="servings">
                        </label>
                        <label>
                            <span>Tingkat kesulitan</span>
                            <select name="tingkat_kesulitan" required data-preview-input="difficulty">
                                <option value="mudah"<?= $old['tingkat_kesulitan'] === 'mudah' ? ' selected' : '' ?>>mudah</option>
                                <option value="sedang"<?= $old['tingkat_kesulitan'] === 'sedang' ? ' selected' : '' ?>>sedang</option>
                                <option value="sulit"<?= $old['tingkat_kesulitan'] === 'sulit' ? ' selected' : '' ?>>sulit</option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>

            <section class="recipe-create__panel">
                <label>
                    <span>Deskripsi</span>
                    <textarea name="deskripsi" rows="4" required><?= old('deskripsi', $old) ?></textarea>
                </label>
                <label>
                    <span>Langkah memasak</span>
                    <textarea name="langkah_resep" rows="8" required><?= old('langkah_resep', $old) ?></textarea>
                </label>

                <div class="recipe-create__lists">
                    <div class="recipe-create__list">
                        <h2>Bahan</h2>
                        <div class="recipe-create__dynamic" data-list="ingredients">
                            <?php for ($i = 0; $i < 3; $i++): ?>
                                <div class="recipe-create__row">
                                    <input type="text" name="ingredients[<?= $i ?>][nama_bahan]" placeholder="Nama bahan" value="<?= e($ingredients[$i]['nama_bahan']) ?>">
                                    <input type="text" name="ingredients[<?= $i ?>][jumlah]" placeholder="Jumlah" value="<?= e($ingredients[$i]['jumlah']) ?>">
                                    <input type="text" name="ingredients[<?= $i ?>][satuan]" placeholder="Satuan" value="<?= e($ingredients[$i]['satuan']) ?>">
                                    <input type="text" name="ingredients[<?= $i ?>][keterangan]" placeholder="Keterangan" value="<?= e($ingredients[$i]['keterangan']) ?>">
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="recipe-create__list">
                        <h2>Peralatan</h2>
                        <div class="recipe-create__dynamic" data-list="tools">
                            <?php for ($i = 0; $i < 3; $i++): ?>
                                <div class="recipe-create__row recipe-create__row--tool">
                                    <input type="text" name="tools[<?= $i ?>][nama_peralatan]" placeholder="Nama peralatan" value="<?= e($tools[$i]['nama_peralatan']) ?>">
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <div class="recipe-create__actions">
                    <a class="home-controls__clear" href="../resep/myresep.php">Cancel</a>
                    <button class="home-controls__apply" type="submit">Save changes</button>
                </div>
            </section>
        </form>
    </main>
</body>
</html>
