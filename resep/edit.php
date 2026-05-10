<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

if (empty($_SESSION['user'])) {
    redirectTo('../auth/login.php');
}

$user = $_SESSION['user'];
$isAdmin = isAdmin();
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
    'waktu_memasak' => preg_replace('/[^0-9]/', '', (string) $recipe['cook_time']),
    'porsi' => preg_replace('/[^0-9]/', '', (string) $recipe['servings']),
    'kategori' => $recipe['category'],
    'tingkat_kesulitan' => strtolower((string) $recipe['difficulty']),
];

$ingredients = [];

foreach (array_values($recipe['ingredients'] ?? []) as $index => $ingredientText) {
    $ingredients[] = [
        'nama_bahan' => $ingredientText,
        'jumlah' => '',
        'satuan' => '',
        'keterangan' => '',
    ];
}

if ($ingredients === []) {
    $ingredients[] = [
        'nama_bahan' => '',
        'jumlah' => '',
        'satuan' => '',
        'keterangan' => '',
    ];
}

$tools = [];

foreach (array_values($recipe['tools'] ?? []) as $index => $toolText) {
    $tools[] = ['nama_peralatan' => $toolText];
}

if ($tools === []) {
    $tools[] = ['nama_peralatan' => ''];
}

$steps = [];
foreach (array_values($recipe['steps'] ?? []) as $step) {
    if (!is_array($step)) {
        $text = trim((string) $step);
        if ($text !== '') {
            $steps[] = ['text' => $text, 'existing_image' => ''];
        }
        continue;
    }

    $text = trim((string) ($step['text'] ?? ''));
    $image = trim((string) ($step['image'] ?? ''));
    if ($text !== '') {
        $steps[] = ['text' => $text, 'existing_image' => $image];
    }
}

if ($steps === []) {
    $steps[] = ['text' => '', 'existing_image' => ''];
}

function recipe_form_step_rows(array $steps): array
{
    $rows = [];

    foreach ($steps as $step) {
        if (is_array($step)) {
            $rows[] = [
                'text' => trim((string) ($step['text'] ?? '')),
                'existing_image' => trim((string) ($step['existing_image'] ?? $step['image'] ?? '')),
            ];
            continue;
        }

        $text = trim((string) $step);
        if ($text !== '') {
            $rows[] = ['text' => $text, 'existing_image' => ''];
        }
    }

    return $rows !== [] ? $rows : [['text' => '', 'existing_image' => '']];
}

function recipe_uploaded_file_at(?array $files, int $index): ?array
{
    if (
        $files === null ||
        !isset($files['name']) ||
        !is_array($files['name']) ||
        !array_key_exists($index, $files['name'])
    ) {
        return null;
    }

    return [
        'name' => (string) ($files['name'][$index] ?? ''),
        'type' => (string) ($files['type'][$index] ?? ''),
        'tmp_name' => (string) ($files['tmp_name'][$index] ?? ''),
        'error' => (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE),
        'size' => (int) ($files['size'][$index] ?? 0),
    ];
}

function recipe_store_uploaded_image(
    ?array $upload,
    string $prefix,
    string $label,
    array &$errors,
): ?string {
    if (
        $upload === null ||
        (($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) ||
        trim((string) ($upload['name'] ?? '')) === ''
    ) {
        return null;
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors[] = $label . ' gagal diupload.';
        return null;
    }

    $mimeType = mime_content_type((string) $upload['tmp_name']);
    if (!isset($allowedTypes[$mimeType])) {
        $errors[] = $label . ' harus berformat JPG, PNG, atau WEBP.';
        return null;
    }

    $uploadDir = __DIR__ . '/../uploads/recipes';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $fileName = $prefix . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowedTypes[$mimeType];
    $target = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file((string) $upload['tmp_name'], $target)) {
        $errors[] = $label . ' tidak bisa disimpan.';
        return null;
    }

    return 'uploads/recipes/' . $fileName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['_token'] ?? null)) {
        $errors[] = 'Token form tidak valid. Silakan muat ulang halaman.';
    }

    $old['nama_resep'] = trim((string) ($_POST['nama_resep'] ?? ''));
    $old['deskripsi'] = trim((string) ($_POST['deskripsi'] ?? ''));
    $old['waktu_memasak'] = trim((string) ($_POST['waktu_memasak'] ?? ''));
    $old['porsi'] = trim((string) ($_POST['porsi'] ?? ''));
    $old['kategori'] = trim((string) ($_POST['kategori'] ?? ''));
    $old['tingkat_kesulitan'] = (string) ($_POST['tingkat_kesulitan'] ?? 'sedang');

    $ingredientRows = $_POST['ingredients'] ?? [];
    $toolRows = $_POST['tools'] ?? [];
    $stepRows = $_POST['steps'] ?? [];
    $steps = recipe_form_step_rows($stepRows);

    if ($old['nama_resep'] === '') {
        $errors[] = 'Nama resep wajib diisi.';
    }
    if ($old['deskripsi'] === '') {
        $errors[] = 'Deskripsi resep wajib diisi.';
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

    $normalizedSteps = [];
    $stepFormIndex = 0;
    foreach ($stepRows as $index => $row) {
        $stepNumber = (int) $index + 1;
        $text = trim((string) ($row['text'] ?? ''));
        $existingImage = trim((string) ($row['existing_image'] ?? ''));
        $stepImage = recipe_uploaded_file_at($_FILES['step_images'] ?? null, (int) $index);

        $hasUploadedFile =
            $stepImage !== null &&
            (($stepImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) &&
            trim((string) ($stepImage['name'] ?? '')) !== '';

        if ($text === '' && $existingImage === '' && !$hasUploadedFile) {
            continue;
        }

        if ($text === '') {
            $errors[] = 'Deskripsi langkah ' . $stepNumber . ' wajib diisi.';
            continue;
        }

        $storedImage = recipe_store_uploaded_image(
            $stepImage,
            'recipe-step',
            'Gambar langkah ' . $stepNumber,
            $errors
        );
        if ($storedImage !== null) {
            $existingImage = $storedImage;
            if (isset($steps[$stepFormIndex])) {
                $steps[$stepFormIndex]['existing_image'] = $storedImage;
            }
        }

        $normalizedSteps[] = [
            'text' => $text,
            'image' => $existingImage !== '' ? $existingImage : null,
        ];
        $stepFormIndex++;
    }

    if ($normalizedSteps === []) {
        $errors[] = 'Minimal satu langkah memasak wajib diisi.';
    }

    $ingredients = $normalizedIngredients !== []
        ? $normalizedIngredients
        : [[
            'nama_bahan' => '',
            'jumlah' => '',
            'satuan' => '',
            'keterangan' => '',
        ]];
    $tools = $normalizedTools !== []
        ? array_map(fn (string $tool): array => ['nama_peralatan' => $tool], $normalizedTools)
        : [['nama_peralatan' => '']];

    $imagePath = recipe_store_uploaded_image(
        $_FILES['foto_resep'] ?? null,
        'recipe',
        'Foto resep',
        $errors
    );

    if ($errors === []) {
        try {
            $updated = recipe_update_db(
                $recipeId,
                (int) $user['id'],
                [
                    'nama_resep' => $old['nama_resep'],
                    'deskripsi' => $old['deskripsi'],
                    'langkah_resep' => recipe_encode_steps($normalizedSteps),
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

            <?php if ($isAdmin): ?>
                <a href="../admin/" class="home-sidebar__admin-panel">Admin Panel</a>
            <?php endif; ?>

            <a href="../auth/logout.php" class="home-sidebar__logout">Log Out</a>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi Resep">
            <a href="../home/">Home</a>
            <a href="../profil/">Profile</a>
            <a class="is-active" href="../resep/myresep.php">My Recipes</a>
            <a href="../resep/buat.php">Add Recipe</a>
            <a href="../resep/favorite.php">Favorite</a>
            <a href="../cari.php">Search</a>
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
                    <p class="recipe-create__lede">Perbarui informasi resep dengan layout yang sama seperti halaman tambah resep: foto dan ringkasan di atas, lalu detail bahan, alat, dan langkah memasak di bawah.</p>

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
                            <input type="number" name="porsi" min="1" step="1" value="<?= old('porsi', $old) ?>" required data-preview-input="porsi">
                        </label>
                        <label>
                            <span>Tingkat kesulitan</span>
                            <select name="tingkat_kesulitan" required data-preview-input="difficulty">
                                <option value="mudah"<?= $old['tingkat_kesulitan'] === 'mudah' ? ' selected' : '' ?>>Mudah</option>
                                <option value="sedang"<?= $old['tingkat_kesulitan'] === 'sedang' ? ' selected' : '' ?>>Sedang</option>
                                <option value="sulit"<?= $old['tingkat_kesulitan'] === 'sulit' ? ' selected' : '' ?>>Sulit</option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>

            <section class="detail-panel recipe-create__section">
                <p class="detail-panel__label">About</p>
                <h2>Deskripsi</h2>
                <label class="recipe-create__full">
                    <span>Jelaskan rasa, isi, atau karakter resep</span>
                    <textarea name="deskripsi" rows="5" required data-preview-input="description"><?= old('deskripsi', $old) ?></textarea>
                </label>
                <p class="recipe-create__preview-text" data-preview-description><?= old('deskripsi', $old) !== '' ? old('deskripsi', $old) : 'Your recipe description will appear here.' ?></p>
            </section>

            <div class="detail-duo recipe-create__duo">
                <section class="detail-panel recipe-create__section">
                    <p class="detail-panel__label">Ingredients</p>
                    <div class="recipe-create__section-head">
                        <h2>Bahan</h2>
                        <button type="button" class="recipe-create__ghost" data-add-row="ingredient">Tambah bahan</button>
                    </div>
                    <div class="recipe-create__rows" data-rows="ingredients">
                        <?php foreach ($ingredients as $index => $ingredient): ?>
                            <div class="recipe-create__row recipe-create__row--ingredient">
                                <input type="text" name="ingredients[<?= $index ?>][nama_bahan]" placeholder="Nama bahan" value="<?= e($ingredient['nama_bahan']) ?>" required>
                                <input type="text" name="ingredients[<?= $index ?>][jumlah]" placeholder="Jumlah" value="<?= e($ingredient['jumlah']) ?>">
                                <input type="text" name="ingredients[<?= $index ?>][satuan]" placeholder="Satuan" value="<?= e($ingredient['satuan']) ?>">
                                <input type="text" name="ingredients[<?= $index ?>][keterangan]" placeholder="Keterangan" value="<?= e($ingredient['keterangan']) ?>">
                                <button type="button" class="recipe-create__remove" data-remove-row aria-label="Hapus bahan">Hapus</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="detail-panel recipe-create__section">
                    <p class="detail-panel__label">Tools</p>
                    <div class="recipe-create__section-head">
                        <h2>Peralatan</h2>
                        <button type="button" class="recipe-create__ghost" data-add-row="tool">Tambah peralatan</button>
                    </div>
                    <div class="recipe-create__rows" data-rows="tools">
                        <?php foreach ($tools as $index => $tool): ?>
                            <div class="recipe-create__row recipe-create__row--tool">
                                <input type="text" name="tools[<?= $index ?>][nama_peralatan]" placeholder="Nama peralatan" value="<?= e($tool['nama_peralatan']) ?>" required>
                                <button type="button" class="recipe-create__remove" data-remove-row aria-label="Hapus peralatan">Hapus</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <section class="detail-panel recipe-create__section">
                <p class="detail-panel__label">Steps</p>
                <div class="recipe-create__section-head">
                    <div>
                        <h2>Langkah memasak</h2>
                        <p class="recipe-create__steps-note">Setiap langkah bisa punya gambar sendiri dan penjelasan terpisah.</p>
                    </div>
                    <button type="button" class="recipe-create__ghost" data-add-row="step">Tambah langkah</button>
                </div>
                <div class="recipe-create__rows recipe-create__rows--steps" data-rows="steps">
                    <?php foreach ($steps as $index => $step): ?>
                        <div class="recipe-create__row recipe-create__row--step">
                            <div class="recipe-create__step-media">
                                <div class="recipe-create__step-preview<?= $step['existing_image'] !== '' ? ' has-image' : '' ?>">
                                    <?php if ($step['existing_image'] !== ''): ?>
                                        <img src="<?= e(recipe_asset_path($step['existing_image'])) ?>" alt="Preview langkah <?= e((string) ($index + 1)) ?>" data-step-preview>
                                    <?php else: ?>
                                        <span data-step-empty>Belum ada gambar</span>
                                        <img src="" alt="" hidden data-step-preview>
                                    <?php endif; ?>
                                </div>
                                <label class="recipe-create__step-upload">
                                    <span>Gambar langkah</span>
                                    <input type="hidden" name="steps[<?= $index ?>][existing_image]" value="<?= e($step['existing_image']) ?>">
                                    <input type="file" name="step_images[<?= $index ?>]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-step-file>
                                </label>
                            </div>
                            <div class="recipe-create__step-body">
                                <span class="recipe-create__step-label">Step <span data-step-number><?= e((string) ($index + 1)) ?></span></span>
                                <textarea name="steps[<?= $index ?>][text]" rows="5" placeholder="Jelaskan apa yang harus dilakukan pada langkah ini..." required><?= e($step['text']) ?></textarea>
                            </div>
                            <button type="button" class="recipe-create__remove recipe-create__remove--step" data-remove-row aria-label="Hapus langkah">Hapus</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="recipe-create__actions">
                <a class="recipe-create__secondary" href="../resep/myresep.php">Batal</a>
                <button type="submit" class="recipe-create__primary">Simpan perubahan</button>
            </div>
        </form>
    </main>

    <template id="ingredient-template">
        <div class="recipe-create__row recipe-create__row--ingredient">
            <input type="text" name="ingredients[__INDEX__][nama_bahan]" placeholder="Nama bahan" required>
            <input type="text" name="ingredients[__INDEX__][jumlah]" placeholder="Jumlah">
            <input type="text" name="ingredients[__INDEX__][satuan]" placeholder="Satuan">
            <input type="text" name="ingredients[__INDEX__][keterangan]" placeholder="Keterangan">
            <button type="button" class="recipe-create__remove" data-remove-row aria-label="Hapus bahan">Hapus</button>
        </div>
    </template>

    <template id="tool-template">
        <div class="recipe-create__row recipe-create__row--tool">
            <input type="text" name="tools[__INDEX__][nama_peralatan]" placeholder="Nama peralatan" required>
            <button type="button" class="recipe-create__remove" data-remove-row aria-label="Hapus peralatan">Hapus</button>
        </div>
    </template>

    <template id="step-template">
        <div class="recipe-create__row recipe-create__row--step">
            <div class="recipe-create__step-media">
                <div class="recipe-create__step-preview">
                    <span data-step-empty>Belum ada gambar</span>
                    <img src="" alt="" hidden data-step-preview>
                </div>
                <label class="recipe-create__step-upload">
                    <span>Gambar langkah</span>
                    <input type="hidden" name="steps[__INDEX__][existing_image]" value="">
                    <input type="file" name="step_images[__INDEX__]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-step-file>
                </label>
            </div>
            <div class="recipe-create__step-body">
                <span class="recipe-create__step-label">Step <span data-step-number>__NUMBER__</span></span>
                <textarea name="steps[__INDEX__][text]" rows="5" placeholder="Jelaskan apa yang harus dilakukan pada langkah ini..." required></textarea>
            </div>
            <button type="button" class="recipe-create__remove recipe-create__remove--step" data-remove-row aria-label="Hapus langkah">Hapus</button>
        </div>
    </template>

    <script>
        (() => {
            const previewImage = document.querySelector('[data-preview-image]');
            const previewTitle = document.querySelector('[data-preview-title]');
            const previewTime = document.querySelector('[data-preview-time]');
            const previewPorsi = document.querySelector('[data-preview-porsi]');
            const previewDifficulty = document.querySelector('[data-preview-difficulty]');
            const previewCategory = document.querySelector('[data-preview-category]');
            const previewDescription = document.querySelector('[data-preview-description]');
            const previewUploadLabel = document.querySelector('[data-preview-upload-label]');
            const titleInput = document.querySelector('[data-preview-input="title"]');
            const timeInput = document.querySelector('[data-preview-input="time"]');
            const porsiInput = document.querySelector('[data-preview-input="porsi"]');
            const difficultyInput = document.querySelector('[data-preview-input="difficulty"]');
            const categoryInput = document.querySelector('[data-preview-input="category"]');
            const descriptionInput = document.querySelector('[data-preview-input="description"]');
            const fileInput = document.querySelector('input[name="foto_resep"]');
            const originalImageSrc = previewImage ? previewImage.src : '';
            let currentObjectUrl = null;

            function setText(target, value, fallback) {
                if (!target) {
                    return;
                }

                target.textContent = value.trim() !== '' ? value : fallback;
            }

            function formatDifficulty(value) {
                const normalized = value.trim();
                switch (normalized) {
                    case 'mudah':
                        return 'Easy';
                    case 'sedang':
                        return 'Medium';
                    case 'sulit':
                        return 'Hard';
                    default:
                        return 'Difficulty';
                }
            }

            function syncPreview() {
                if (titleInput) {
                    setText(previewTitle, titleInput.value, 'Edit resep');
                }
                if (timeInput) {
                    setText(previewTime, timeInput.value ? `${timeInput.value} mins` : '', 'Cook time');
                }
                if (porsiInput) {
                    setText(previewPorsi, porsiInput.value ? `${porsiInput.value} servings` : '', 'Servings');
                }
                if (difficultyInput) {
                    setText(previewDifficulty, formatDifficulty(difficultyInput.value), 'Difficulty');
                }
                if (categoryInput) {
                    setText(previewCategory, categoryInput.value, 'Category');
                }
                if (descriptionInput) {
                    setText(previewDescription, descriptionInput.value, 'Your recipe description will appear here.');
                }
            }

            function updatePreviewImage(file) {
                if (!previewImage || !previewUploadLabel) {
                    return;
                }

                if (currentObjectUrl) {
                    URL.revokeObjectURL(currentObjectUrl);
                    currentObjectUrl = null;
                }

                if (!file) {
                    previewImage.src = originalImageSrc;
                    previewUploadLabel.textContent = 'Ganti foto resep';
                    return;
                }

                currentObjectUrl = URL.createObjectURL(file);
                previewImage.src = currentObjectUrl;
                previewUploadLabel.textContent = file.name;
            }

            [titleInput, timeInput, porsiInput, difficultyInput, categoryInput, descriptionInput].forEach((input) => {
                if (!input) {
                    return;
                }
                input.addEventListener('input', syncPreview);
                input.addEventListener('change', syncPreview);
            });

            if (fileInput) {
                fileInput.addEventListener('change', () => {
                    updatePreviewImage(fileInput.files && fileInput.files[0] ? fileInput.files[0] : null);
                });
            }

            syncPreview();

            const rowTargets = {
                ingredient: 'ingredients',
                tool: 'tools',
                step: 'steps',
            };

            function syncStepNumbers() {
                document.querySelectorAll('[data-rows="steps"] .recipe-create__row--step').forEach((row, index) => {
                    const numberTarget = row.querySelector('[data-step-number]');
                    if (numberTarget) {
                        numberTarget.textContent = String(index + 1);
                    }
                });
            }

            function bindStepPreview(row) {
                const fileInput = row.querySelector('[data-step-file]');
                const preview = row.querySelector('[data-step-preview]');
                const emptyState = row.querySelector('[data-step-empty]');

                if (!fileInput || !preview) {
                    return;
                }

                fileInput.addEventListener('change', () => {
                    const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                    if (!file) {
                        if (preview.dataset.objectUrl) {
                            URL.revokeObjectURL(preview.dataset.objectUrl);
                            delete preview.dataset.objectUrl;
                        }
                        preview.hidden = true;
                        preview.removeAttribute('src');
                        if (emptyState) {
                            emptyState.hidden = false;
                        }
                        preview.closest('.recipe-create__step-preview')?.classList.remove('has-image');
                        return;
                    }

                    if (preview.dataset.objectUrl) {
                        URL.revokeObjectURL(preview.dataset.objectUrl);
                    }

                    const objectUrl = URL.createObjectURL(file);
                    preview.src = objectUrl;
                    preview.hidden = false;
                    preview.dataset.objectUrl = objectUrl;
                    if (emptyState) {
                        emptyState.hidden = true;
                    }
                    preview.closest('.recipe-create__step-preview')?.classList.add('has-image');
                });
            }

            document.querySelectorAll('[data-rows="steps"] .recipe-create__row--step').forEach(bindStepPreview);
            syncStepNumbers();

            document.querySelectorAll('[data-add-row]').forEach((button) => {
                button.addEventListener('click', () => {
                    const type = button.dataset.addRow;
                    const template = document.getElementById(`${type}-template`);
                    const container = document.querySelector(`[data-rows="${rowTargets[type] || type}"]`);

                    if (!template || !container) {
                        return;
                    }

                    const index = container.children.length;
                    const html = template.innerHTML
                        .replaceAll('__INDEX__', String(index))
                        .replaceAll('__NUMBER__', String(index + 1));
                    container.insertAdjacentHTML('beforeend', html);
                    const newRow = container.lastElementChild;
                    if (newRow && type === 'step') {
                        bindStepPreview(newRow);
                        syncStepNumbers();
                    }
                });
            });

            document.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-row]');
                if (!removeButton) {
                    return;
                }

                const row = removeButton.closest('.recipe-create__row');
                const container = row ? row.parentElement : null;
                if (!row || !container) {
                    return;
                }

                if (container.children.length <= 1) {
                    row.querySelectorAll('input, textarea').forEach((input) => {
                        input.value = '';
                    });
                    row.querySelectorAll('[data-step-preview]').forEach((preview) => {
                        if (preview.dataset.objectUrl) {
                            URL.revokeObjectURL(preview.dataset.objectUrl);
                            delete preview.dataset.objectUrl;
                        }
                        preview.hidden = true;
                        preview.removeAttribute('src');
                    });
                    row.querySelectorAll('[data-step-empty]').forEach((emptyState) => {
                        emptyState.hidden = false;
                    });
                    row.querySelectorAll('.recipe-create__step-preview').forEach((wrapper) => {
                        wrapper.classList.remove('has-image');
                    });
                    return;
                }

                row.remove();
                if (container.dataset.rows === 'steps') {
                    syncStepNumbers();
                }
            });
        })();
    </script>
</body>
</html>
