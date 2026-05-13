<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

if (empty($_SESSION['user'])) {
    redirectTo('../auth/login.php');
}

$user = $_SESSION['user'];
$isAdmin = isAdmin();
$profile = recipe_user_profile_db((int) ($user['id'] ?? 0)) ?? [
    'name' => (string) ($user['name'] ?? 'Pengguna'),
    'avatar' => '../assets/img/home-profile.png',
];
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
            $steps[] = ['title' => '', 'text' => $text, 'existing_image' => ''];
        }
        continue;
    }

    $title = trim((string) ($step['title'] ?? ''));
    $text = trim((string) ($step['text'] ?? ''));
    $image = trim((string) ($step['image'] ?? ''));
    if ($text !== '') {
        $steps[] = ['title' => $title, 'text' => $text, 'existing_image' => $image];
    }
}

if ($steps === []) {
    $steps[] = ['title' => '', 'text' => '', 'existing_image' => ''];
}

function recipe_form_step_rows(array $steps): array
{
    $rows = [];

    foreach ($steps as $step) {
        if (is_array($step)) {
            $rows[] = [
                'title' => trim((string) ($step['title'] ?? '')),
                'text' => trim((string) ($step['text'] ?? '')),
                'existing_image' => trim((string) ($step['existing_image'] ?? $step['image'] ?? '')),
            ];
            continue;
        }

        $text = trim((string) $step);
        if ($text !== '') {
            $rows[] = ['title' => '', 'text' => $text, 'existing_image' => ''];
        }
    }

    return $rows !== [] ? $rows : [['title' => '', 'text' => '', 'existing_image' => '']];
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
        $errors[] = $label . ' gagal diunggah.';
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
        $title = trim((string) ($row['title'] ?? ''));
        $text = trim((string) ($row['text'] ?? ''));
        $existingImage = trim((string) ($row['existing_image'] ?? ''));
        $stepImage = recipe_uploaded_file_at($_FILES['step_images'] ?? null, (int) $index);

        $hasUploadedFile =
            $stepImage !== null &&
            (($stepImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) &&
            trim((string) ($stepImage['name'] ?? '')) !== '';

        if ($title === '' && $text === '' && $existingImage === '' && !$hasUploadedFile) {
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
            'title' => $title,
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
    <title>Edit Resep - Resepku</title>
        <?= sidebarInitialStateScript() ?>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="recipe-create-page">
    <?= renderGeneralSidebar([
        'basePath' => '../',
        'asideClass' => 'detail-sidebar',
        'activeKey' => 'myrecipes',
        'searchAction' => '../cari.php',
        'userContext' => [
            'isLoggedIn' => true,
            'isGuest' => false,
            'isAdmin' => $isAdmin,
            'name' => $profile['name'] ?? (string) ($recipe['author'] ?? 'Pengguna'),
            'avatar' => $profile['avatar'] ?? ($recipe['author_avatar'] ?? ''),
        ],
    ]) ?>

    <main class="detail-main recipe-create-main">
        <a class="detail-back" href="../resep/myresep.php" aria-label="Kembali ke halaman resep saya">
            <span aria-hidden="true">←</span>
            <span>Kembali</span>
        </a>

        <form class="recipe-create" id="recipe-create-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="recipe_id" value="<?= e((string) $recipeId) ?>">

            <div class="detail-hero recipe-create__hero">
                <div class="detail-hero__media recipe-create__preview">
                    <div class="recipe-create__preview-frame has-image">
                        <img src="<?= e($recipe['image']) ?>" alt="Pratinjau resep" class="recipe-create__preview-image" data-preview-image>
                        <div class="recipe-create__preview-badge">Pratinjau</div>
                    </div>
                    <label class="recipe-create__upload">
                        <span class="recipe-create__upload-prompt">
                            <svg class="recipe-create__upload-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path fill="currentColor" d="M11 16V7.85l-2.6 2.6L7 9l5-5 5 5-1.4 1.45-2.6-2.6V16h-2ZM6 20c-.55 0-1.02-.2-1.41-.59C4.2 19.02 4 18.55 4 18v-3h2v3h12v-3h2v3c0 .55-.2 1.02-.59 1.41-.39.39-.86.59-1.41.59H6Z" />
                            </svg>
                            <strong data-preview-upload-label>Ganti foto resep</strong>
                            <span>Drag-and-drop ke sini</span>
                        </span>
                        <input type="file" name="foto_resep" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    </label>
                </div>

                <div class="detail-hero__panel">
                    <p class="detail-hero__eyebrow">Edit Resep</p>
                    <h1 data-preview-title><?= old('nama_resep', $old) ?></h1>
                    <a class="detail-author-link recipe-create__author" href="../profil/" aria-label="Lihat profil <?= e($profile['name']) ?>">
                        <img class="detail-author-link__avatar" src="<?= e($profile['avatar']) ?>" alt="">
                        <span>
                            <span class="detail-author-link__label">Pembuat resep</span>
                            <strong><?= e($profile['name']) ?></strong>
                        </span>
                    </a>
                    <label class="recipe-create__full recipe-create__hero-description">
                        <span>Deskripsi</span>
                        <textarea name="deskripsi" rows="4" required data-preview-input="description"><?= old('deskripsi', $old) ?></textarea>
                    </label>

                    <?php if ($errors !== []): ?>
                        <div class="recipe-create__alert recipe-create__alert--error">
                            <?php foreach ($errors as $error): ?>
                                <p><?= e($error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="detail-meta recipe-create__meta">
                    <span data-preview-time><?= $old['waktu_memasak'] !== '' ? e($old['waktu_memasak']) . ' menit' : 'Waktu memasak' ?></span>
                    <span data-preview-porsi><?= $old['porsi'] !== '' ? e($old['porsi']) . ' porsi' : 'Porsi' ?></span>
                        <span data-preview-difficulty><?= e(ucfirst($old['tingkat_kesulitan'])) ?></span>
                    <span data-preview-category><?= $old['kategori'] !== '' ? e($old['kategori']) : 'Kategori' ?></span>
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

            <div class="recipe-create__body">
                <div class="recipe-create__sticky-column">
                    <section class="detail-panel recipe-create__section recipe-create__section--materials" data-materials-group>
                        <div class="recipe-create__section-head recipe-create__section-head--materials">
                            <h2>Bahan &amp; Peralatan</h2>
                        </div>

                        <div class="recipe-create__tabs" role="tablist" aria-label="Bahan dan peralatan">
                            <button type="button" class="recipe-create__tab is-active" data-material-tab="ingredients" aria-selected="true">Bahan <span data-material-count="ingredients">(<?= e((string) count($ingredients)) ?>)</span></button>
                            <button type="button" class="recipe-create__tab" data-material-tab="tools" aria-selected="false">Peralatan <span data-material-count="tools">(<?= e((string) count($tools)) ?>)</span></button>
                        </div>

                        <div class="recipe-create__tab-panel is-active" data-material-panel="ingredients">
                            <div class="recipe-create__table recipe-create__table--ingredients">
                                <div class="recipe-create__table-head" aria-hidden="true">
                                    <span>No</span>
                                    <span></span>
                                    <span>Nama</span>
                                    <span>Hapus</span>
                                </div>
                                <div class="recipe-create__rows recipe-create__rows--table" data-rows="ingredients">
                                    <?php foreach ($ingredients as $index => $ingredient): ?>
                                        <div class="recipe-create__row recipe-create__row--ingredient" data-sortable-row>
                                            <span class="recipe-create__row-number" data-row-number><?= e((string) ($index + 1)) ?></span>
                                            <span class="recipe-create__drag-handle" draggable="true" data-drag-handle aria-hidden="true">☰</span>
                                            <input type="text" name="ingredients[<?= $index ?>][nama_bahan]" placeholder="Nama" value="<?= e($ingredient['nama_bahan']) ?>" required>
                                            <input type="hidden" name="ingredients[<?= $index ?>][jumlah]" value="">
                                            <input type="hidden" name="ingredients[<?= $index ?>][satuan]" value="">
                                            <input type="hidden" name="ingredients[<?= $index ?>][keterangan]" value="">
                                            <button type="button" class="recipe-create__remove" data-remove-row aria-label="Hapus bahan">Hapus</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="recipe-create__ghost recipe-create__add-under" data-add-row="ingredient">Tambah bahan</button>
                            </div>
                        </div>

                        <div class="recipe-create__tab-panel" data-material-panel="tools" hidden>
                            <div class="recipe-create__table recipe-create__table--tools">
                                <div class="recipe-create__table-head" aria-hidden="true">
                                    <span>No</span>
                                    <span></span>
                                    <span>Nama Peralatan</span>
                                    <span>Hapus</span>
                                </div>
                                <div class="recipe-create__rows recipe-create__rows--table" data-rows="tools">
                                    <?php foreach ($tools as $index => $tool): ?>
                                        <div class="recipe-create__row recipe-create__row--tool" data-sortable-row>
                                            <span class="recipe-create__row-number" data-row-number><?= e((string) ($index + 1)) ?></span>
                                            <span class="recipe-create__drag-handle" draggable="true" data-drag-handle aria-hidden="true">☰</span>
                                            <input type="text" name="tools[<?= $index ?>][nama_peralatan]" placeholder="Nama peralatan" value="<?= e($tool['nama_peralatan']) ?>" required>
                                            <button type="button" class="recipe-create__remove" data-remove-row aria-label="Hapus peralatan">Hapus</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="recipe-create__ghost recipe-create__add-under" data-add-row="tool">Tambah peralatan</button>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="recipe-create__content-column">
                    <section class="detail-panel recipe-create__section recipe-create__section--steps">
                        <div class="recipe-create__section-head">
                            <div>
                                <h2>Langkah memasak</h2>
                                <p class="recipe-create__steps-note">Setiap langkah bisa punya gambar sendiri dan penjelasan terpisah.</p>
                            </div>
                        </div>
                        <div class="recipe-create__rows recipe-create__rows--steps" data-rows="steps">
                            <?php foreach ($steps as $index => $step): ?>
                                <div class="recipe-create__row recipe-create__row--step" data-sortable-row>
                                    <div class="recipe-create__step-marker" draggable="true" data-drag-handle aria-hidden="true">
                                        <span data-step-number><?= e((string) ($index + 1)) ?></span>
                                    </div>
                                    <span class="recipe-create__drag-handle recipe-create__drag-handle--step" draggable="true" data-drag-handle aria-hidden="true">☰</span>
                                    <div class="recipe-create__step-media">
                                        <label class="recipe-create__step-preview<?= $step['existing_image'] !== '' ? ' has-image' : '' ?>">
                                            <?php if ($step['existing_image'] !== ''): ?>
                                                <img src="<?= e(recipe_asset_path($step['existing_image'])) ?>" alt="Pratinjau langkah <?= e((string) ($index + 1)) ?>" data-step-preview>
                                            <?php else: ?>
                                                <span data-step-empty>Belum ada gambar</span>
                                                <img src="" alt="" hidden data-step-preview>
                                            <?php endif; ?>
                                            <input type="hidden" name="steps[<?= $index ?>][existing_image]" value="<?= e($step['existing_image']) ?>">
                                            <input type="file" name="step_images[<?= $index ?>]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-step-file>
                                        </label>
                                    </div>
                                    <div class="recipe-create__step-body">
                                        <input type="text" name="steps[<?= $index ?>][title]" value="<?= e($step['title'] ?? '') ?>" placeholder="Judul langkah...">
                                        <textarea name="steps[<?= $index ?>][text]" rows="5" placeholder="Jelaskan apa yang harus dilakukan pada langkah ini..." required><?= e($step['text']) ?></textarea>
                                    </div>
                                    <button type="button" class="recipe-create__remove recipe-create__remove--step" data-remove-row aria-label="Hapus langkah">Hapus</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="recipe-create__ghost recipe-create__add-under" data-add-row="step">Tambah langkah</button>
                    </section>
                </div>
            </div>

            <div class="recipe-create__actions">
                <a class="recipe-create__secondary" href="../resep/myresep.php">Batal</a>
                <button type="submit" class="recipe-create__primary">Simpan perubahan</button>
            </div>
        </form>
    </main>

    <template id="ingredient-template">
        <div class="recipe-create__row recipe-create__row--ingredient" data-sortable-row>
            <span class="recipe-create__row-number" data-row-number>__NUMBER__</span>
            <span class="recipe-create__drag-handle" draggable="true" data-drag-handle aria-hidden="true">☰</span>
            <input type="text" name="ingredients[__INDEX__][nama_bahan]" placeholder="Nama" required>
            <input type="hidden" name="ingredients[__INDEX__][jumlah]" value="">
            <input type="hidden" name="ingredients[__INDEX__][satuan]" value="">
            <input type="hidden" name="ingredients[__INDEX__][keterangan]" value="">
            <button type="button" class="recipe-create__remove" data-remove-row aria-label="Hapus bahan">Hapus</button>
        </div>
    </template>

    <template id="tool-template">
        <div class="recipe-create__row recipe-create__row--tool" data-sortable-row>
            <span class="recipe-create__row-number" data-row-number>__NUMBER__</span>
            <span class="recipe-create__drag-handle" draggable="true" data-drag-handle aria-hidden="true">☰</span>
            <input type="text" name="tools[__INDEX__][nama_peralatan]" placeholder="Nama peralatan" required>
            <button type="button" class="recipe-create__remove" data-remove-row aria-label="Hapus peralatan">Hapus</button>
        </div>
    </template>

    <template id="step-template">
        <div class="recipe-create__row recipe-create__row--step" data-sortable-row>
            <div class="recipe-create__step-marker" draggable="true" data-drag-handle aria-hidden="true">
                <span data-step-number>__NUMBER__</span>
            </div>
            <span class="recipe-create__drag-handle recipe-create__drag-handle--step" draggable="true" data-drag-handle aria-hidden="true">☰</span>
            <div class="recipe-create__step-media">
                <label class="recipe-create__step-preview">
                    <span data-step-empty>Belum ada gambar</span>
                    <img src="" alt="" hidden data-step-preview>
                    <input type="hidden" name="steps[__INDEX__][existing_image]" value="">
                    <input type="file" name="step_images[__INDEX__]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-step-file>
                </label>
            </div>
            <div class="recipe-create__step-body">
                <input type="text" name="steps[__INDEX__][title]" placeholder="Judul langkah...">
                <textarea name="steps[__INDEX__][text]" rows="5" placeholder="Jelaskan apa yang harus dilakukan pada langkah ini..." required></textarea>
            </div>
            <button type="button" class="recipe-create__remove recipe-create__remove--step" data-remove-row aria-label="Hapus langkah">Hapus</button>
        </div>
    </template>

    <script>
        (() => {
            const previewImage = document.querySelector('[data-preview-image]');
            const previewFrame = document.querySelector('.recipe-create__preview-frame');
            const uploadDrop = document.querySelector('.recipe-create__upload');
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
                        return 'Mudah';
                    case 'sedang':
                        return 'Sedang';
                    case 'sulit':
                        return 'Sulit';
                    default:
                        return 'Kesulitan';
                }
            }

            function syncPreview() {
                if (titleInput) {
                    setText(previewTitle, titleInput.value, 'Edit resep');
                }
                if (timeInput) {
                    setText(previewTime, timeInput.value ? `${timeInput.value} menit` : '', 'Waktu memasak');
                }
                if (porsiInput) {
                    setText(previewPorsi, porsiInput.value ? `${porsiInput.value} porsi` : '', 'Porsi');
                }
                if (difficultyInput) {
                    setText(previewDifficulty, formatDifficulty(difficultyInput.value), 'Kesulitan');
                }
                if (categoryInput) {
                    setText(previewCategory, categoryInput.value, 'Kategori');
                }
                if (descriptionInput) {
                    setText(previewDescription, descriptionInput.value, 'Deskripsi resepmu akan tampil di sini.');
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
                    previewImage.hidden = false;
                    previewFrame?.classList.add('has-image');
                    previewUploadLabel.textContent = 'Ganti foto resep';
                    return;
                }

                currentObjectUrl = URL.createObjectURL(file);
                previewImage.src = currentObjectUrl;
                previewImage.hidden = false;
                previewFrame?.classList.add('has-image');
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

            if (uploadDrop && fileInput) {
                ['dragenter', 'dragover'].forEach((eventName) => {
                    uploadDrop.addEventListener(eventName, (event) => {
                        event.preventDefault();
                        uploadDrop.classList.add('is-dragover');
                    });
                });
                ['dragleave', 'drop'].forEach((eventName) => {
                    uploadDrop.addEventListener(eventName, () => {
                        uploadDrop.classList.remove('is-dragover');
                    });
                });
                uploadDrop.addEventListener('drop', (event) => {
                    event.preventDefault();
                    const file = event.dataTransfer.files && event.dataTransfer.files[0] ? event.dataTransfer.files[0] : null;
                    if (!file) {
                        return;
                    }
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    fileInput.files = transfer.files;
                    updatePreviewImage(file);
                });
            }

            syncPreview();

            const materialTabs = document.querySelectorAll('[data-material-tab]');
            const materialPanels = document.querySelectorAll('[data-material-panel]');
            const rowTargets = {
                ingredient: 'ingredients',
                tool: 'tools',
                step: 'steps',
            };

            function nextRowIndex(container) {
                let maxIndex = -1;
                container.querySelectorAll('[name]').forEach((field) => {
                    const match = field.name.match(/\[(\d+)\]/);
                    if (match) {
                        maxIndex = Math.max(maxIndex, Number(match[1]));
                    }
                });
                return maxIndex + 1;
            }

            function syncContainer(container) {
                const rows = Array.from(container.querySelectorAll(':scope > .recipe-create__row'));
                rows.forEach((row, index) => {
                    row.querySelectorAll('[data-row-number], [data-step-number]').forEach((target) => {
                        target.textContent = String(index + 1);
                    });
                    row.querySelectorAll('[name]').forEach((field) => {
                        field.name = field.name.replace(/(ingredients|tools|steps|step_images)\[\d+\]/, `$1[${index}]`);
                    });
                });
            }

            function syncAllSortable() {
                document.querySelectorAll('[data-rows]').forEach(syncContainer);
                syncMaterialCounts();
            }

            function syncMaterialCounts() {
                ['ingredients', 'tools'].forEach((name) => {
                    const container = document.querySelector(`[data-rows="${name}"]`);
                    const target = document.querySelector(`[data-material-count="${name}"]`);
                    if (container && target) {
                        target.textContent = `(${container.querySelectorAll(':scope > .recipe-create__row').length})`;
                    }
                });
            }

            function syncStepNumbers() {
                const stepsContainer = document.querySelector('[data-rows="steps"]');
                if (stepsContainer) {
                    syncContainer(stepsContainer);
                }
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
            syncAllSortable();

            function addRow(type) {
                const template = document.getElementById(`${type}-template`);
                const container = document.querySelector(`[data-rows="${rowTargets[type] || type}"]`);

                if (!template || !container) {
                    return;
                }

                const index = nextRowIndex(container);
                const number = container.querySelectorAll('.recipe-create__row').length + 1;
                const html = template.innerHTML
                    .replaceAll('__INDEX__', String(index))
                    .replaceAll('__NUMBER__', String(number));
                container.insertAdjacentHTML('beforeend', html);
                const newRow = container.lastElementChild;

                if (newRow && type === 'step') {
                    bindStepPreview(newRow);
                }

                syncContainer(container);
                syncMaterialCounts();
                newRow?.querySelector('input, textarea')?.focus();
            }

            function setMaterialTab(panelName) {
                materialTabs.forEach((tab) => {
                    const selected = tab.dataset.materialTab === panelName;
                    tab.classList.toggle('is-active', selected);
                    tab.setAttribute('aria-selected', selected ? 'true' : 'false');
                });

                materialPanels.forEach((panel) => {
                    const selected = panel.dataset.materialPanel === panelName;
                    panel.hidden = !selected;
                    panel.classList.toggle('is-active', selected);
                });

            }

            materialTabs.forEach((button) => {
                button.addEventListener('click', () => {
                    setMaterialTab(button.dataset.materialTab || 'ingredients');
                });
            });

            document.querySelectorAll('[data-add-row]').forEach((button) => {
                button.addEventListener('click', () => {
                    addRow(button.dataset.addRow || '');
                });
            });

            let draggedRow = null;
            let dragImage = null;

            function getDragAfterElement(container, y) {
                return Array.from(container.querySelectorAll(':scope > [data-sortable-row]:not(.is-dragging)')).reduce((closest, child) => {
                    const box = child.getBoundingClientRect();
                    const offset = y - box.top - box.height / 2;
                    if (offset < 0 && offset > closest.offset) {
                        return { offset, element: child };
                    }
                    return closest;
                }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
            }

            document.addEventListener('dragstart', (event) => {
                const row = event.target.closest('[data-sortable-row]');
                if (!row) {
                    return;
                }

                if (!event.target.closest('[data-drag-handle]')) {
                    event.preventDefault();
                    return;
                }

                draggedRow = row;
                row.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', '');
                const field = row.querySelector('input[type="text"], textarea');
                const value = field?.value || field?.placeholder || '';
                const image = row.querySelector('[data-step-preview]:not([hidden])');
                dragImage = document.createElement('div');
                dragImage.className = image ? 'recipe-create__drag-preview recipe-create__drag-preview--step' : 'recipe-create__drag-preview';
                const safeValue = value.replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
                const imageHtml = image ? `<img src="${image.getAttribute('src')}" alt="">` : '';
                dragImage.innerHTML = `<span class="recipe-create__drag-handle">☰</span>${imageHtml}<span>${safeValue}</span>`;
                document.body.appendChild(dragImage);
                event.dataTransfer.setDragImage(dragImage, 14, 18);
            });

            document.addEventListener('dragover', (event) => {
                if (!draggedRow) {
                    return;
                }

                const container = event.target.closest('[data-rows]');
                if (!container || draggedRow.parentElement !== container) {
                    return;
                }

                event.preventDefault();
                const afterElement = getDragAfterElement(container, event.clientY);
                if (afterElement) {
                    container.insertBefore(draggedRow, afterElement);
                } else {
                    container.appendChild(draggedRow);
                }
            });

            document.addEventListener('dragend', () => {
                if (!draggedRow) {
                    return;
                }

                const container = draggedRow.parentElement;
                draggedRow.classList.remove('is-dragging');
                draggedRow = null;
                dragImage?.remove();
                dragImage = null;
                if (container) {
                    syncContainer(container);
                }
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
                    syncContainer(container);
                    syncMaterialCounts();
                    return;
                }

                row.remove();
                syncContainer(container);
                syncMaterialCounts();
            });
        })();
    </script>
</body>
</html>
