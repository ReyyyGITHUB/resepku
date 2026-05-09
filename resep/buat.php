<?php

require_once __DIR__ . "/../config/helpers.php";
require_once __DIR__ . "/../config/db.php";

startSession();

if (empty($_SESSION["user"])) {
    redirectTo("../auth/login.php");
}

$user = $_SESSION["user"];
$isAdmin = isAdmin();
$errors = [];
$success = null;

$old = [
    "nama_resep" => "",
    "deskripsi" => "",
    "langkah_resep" => "",
    "waktu_memasak" => "",
    "porsi" => "",
    "kategori" => "",
    "tingkat_kesulitan" => "sedang",
];

$ingredients = array_fill(0, 3, [
    "nama_bahan" => "",
    "jumlah" => "",
    "satuan" => "",
    "keterangan" => "",
]);
$tools = array_fill(0, 3, ["nama_peralatan" => ""]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrf($_POST["_token"] ?? null)) {
        $errors[] = "Token form tidak valid. Silakan muat ulang halaman.";
    }

    $old["nama_resep"] = trim((string) ($_POST["nama_resep"] ?? ""));
    $old["deskripsi"] = trim((string) ($_POST["deskripsi"] ?? ""));
    $old["langkah_resep"] = trim((string) ($_POST["langkah_resep"] ?? ""));
    $old["waktu_memasak"] = trim((string) ($_POST["waktu_memasak"] ?? ""));
    $old["porsi"] = trim((string) ($_POST["porsi"] ?? ""));
    $old["kategori"] = trim((string) ($_POST["kategori"] ?? ""));
    $old["tingkat_kesulitan"] =
        (string) ($_POST["tingkat_kesulitan"] ?? "sedang");

    $ingredientRows = $_POST["ingredients"] ?? [];
    $toolRows = $_POST["tools"] ?? [];

    if ($old["nama_resep"] === "") {
        $errors[] = "Nama resep wajib diisi.";
    }

    if ($old["deskripsi"] === "") {
        $errors[] = "Deskripsi resep wajib diisi.";
    }

    if ($old["langkah_resep"] === "") {
        $errors[] = "Langkah memasak wajib diisi.";
    }

    if ($old["kategori"] === "") {
        $errors[] = "Kategori resep wajib diisi.";
    }

    if (
        !in_array($old["tingkat_kesulitan"], ["mudah", "sedang", "sulit"], true)
    ) {
        $errors[] = "Tingkat kesulitan tidak valid.";
    }

    if (
        $old["waktu_memasak"] === "" ||
        !ctype_digit($old["waktu_memasak"]) ||
        (int) $old["waktu_memasak"] <= 0
    ) {
        $errors[] = "Waktu memasak harus berupa angka menit yang valid.";
    }

    if (
        $old["porsi"] === "" ||
        !ctype_digit($old["porsi"]) ||
        (int) $old["porsi"] <= 0
    ) {
        $errors[] = "Porsi harus berupa angka yang valid.";
    }

    $normalizedIngredients = [];
    foreach ($ingredientRows as $row) {
        $name = trim((string) ($row["nama_bahan"] ?? ""));
        if ($name === "") {
            continue;
        }

        $normalizedIngredients[] = [
            "nama_bahan" => $name,
            "jumlah" => trim((string) ($row["jumlah"] ?? "")),
            "satuan" => trim((string) ($row["satuan"] ?? "")),
            "keterangan" => trim((string) ($row["keterangan"] ?? "")),
        ];
    }

    $normalizedTools = [];
    foreach ($toolRows as $row) {
        $name = trim((string) ($row["nama_peralatan"] ?? ""));
        if ($name !== "") {
            $normalizedTools[] = $name;
        }
    }

    if ($normalizedIngredients === []) {
        $errors[] = "Minimal satu bahan wajib diisi.";
    }

    if ($normalizedTools === []) {
        $errors[] = "Minimal satu peralatan wajib diisi.";
    }

    $imagePath = null;
    if (!empty($_FILES["foto_resep"]["name"])) {
        $upload = $_FILES["foto_resep"];
        $allowedTypes = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp",
        ];

        if (($upload["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = "Upload foto resep gagal.";
        } else {
            $mimeType = mime_content_type($upload["tmp_name"]);
            if (!isset($allowedTypes[$mimeType])) {
                $errors[] = "Foto resep harus berformat JPG, PNG, atau WEBP.";
            } else {
                $uploadDir = __DIR__ . "/../uploads/recipes";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $fileName =
                    "recipe-" .
                    time() .
                    "-" .
                    bin2hex(random_bytes(4)) .
                    "." .
                    $allowedTypes[$mimeType];
                $target = $uploadDir . "/" . $fileName;

                if (!move_uploaded_file($upload["tmp_name"], $target)) {
                    $errors[] = "Foto resep tidak bisa disimpan.";
                } else {
                    $imagePath = "uploads/recipes/" . $fileName;
                }
            }
        }
    }

    if ($errors === []) {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO recipes (pengguna_id, nama_resep, deskripsi, langkah_resep, waktu_memasak, porsi, foto_resep, kategori, tingkat_kesulitan)
                 VALUES (:pengguna_id, :nama_resep, :deskripsi, :langkah_resep, :waktu_memasak, :porsi, :foto_resep, :kategori, :tingkat_kesulitan)',
            );
            $stmt->execute([
                ":pengguna_id" => (int) $user["id"],
                ":nama_resep" => $old["nama_resep"],
                ":deskripsi" => $old["deskripsi"],
                ":langkah_resep" => $old["langkah_resep"],
                ":waktu_memasak" => (int) $old["waktu_memasak"],
                ":porsi" => (int) $old["porsi"],
                ":foto_resep" => $imagePath,
                ":kategori" => $old["kategori"],
                ":tingkat_kesulitan" => $old["tingkat_kesulitan"],
            ]);

            $recipeId = (int) $pdo->lastInsertId();

            $ingredientStmt = $pdo->prepare(
                'INSERT INTO bahan_resep (resep_id, nama_bahan, jumlah, satuan, keterangan)
                 VALUES (:resep_id, :nama_bahan, :jumlah, :satuan, :keterangan)',
            );
            foreach ($normalizedIngredients as $ingredient) {
                $amount =
                    $ingredient["jumlah"] === "" ? null : $ingredient["jumlah"];
                $unit =
                    $ingredient["satuan"] === "" ? null : $ingredient["satuan"];
                $note =
                    $ingredient["keterangan"] === ""
                        ? null
                        : $ingredient["keterangan"];

                $ingredientStmt->execute([
                    ":resep_id" => $recipeId,
                    ":nama_bahan" => $ingredient["nama_bahan"],
                    ":jumlah" => $amount,
                    ":satuan" => $unit,
                    ":keterangan" => $note,
                ]);
            }

            $toolStmt = $pdo->prepare(
                "INSERT INTO peralatan_resep (resep_id, nama_peralatan) VALUES (:resep_id, :nama_peralatan)",
            );
            foreach ($normalizedTools as $tool) {
                $toolStmt->execute([
                    ":resep_id" => $recipeId,
                    ":nama_peralatan" => $tool,
                ]);
            }

            $pdo->commit();
            redirectTo("../resep/detail.php?id=" . $recipeId);
        } catch (Throwable $throwable) {
            $pdo->rollBack();
            $errors[] = "Gagal menyimpan resep. Coba lagi.";
        }
    }
}

function old(string $key, array $old): string
{
    return e($old[$key] ?? "");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Resep - Resepku</title>
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
                <img src="../assets/img/home-profile.png" alt="" class="home-sidebar__avatar">
                <div class="home-sidebar__welcome">
                    <strong><?= e((string) ($user["name"] ?? "User")) ?></strong>
                    <span>Buat resep baru dan simpan ke koleksi publik.</span>
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
            <a href="../resep/myresep.php">My Recipes</a>
            <a class="is-active" href="../resep/buat.php">Add Recipe</a>
            <a href="../resep/favorite.php">Favorite</a>
            <a href="../home/#recipe-search">Search</a>
        </nav>

        <img src="../assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="detail-main recipe-create-main">
        <a class="detail-back" href="../home/" aria-label="Kembali ke halaman home">
            <span aria-hidden="true">←</span>
            <span>Back</span>
        </a>

        <form class="recipe-create" id="recipe-create-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?= e(csrfToken()) ?>">

            <div class="detail-hero recipe-create__hero">
                <div class="detail-hero__media recipe-create__preview">
                    <div class="recipe-create__preview-frame">
                        <img src="../assets/img/recipe-salad-hero.png" alt="Preview resep" class="recipe-create__preview-image" data-preview-image>
                        <div class="recipe-create__preview-badge">Preview</div>
                    </div>
                    <label class="recipe-create__upload">
                        <strong data-preview-upload-label>Upload foto resep</strong>
                        <input type="file" name="foto_resep" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    </label>
                </div>

                <div class="detail-hero__panel">
                    <p class="detail-hero__eyebrow">Create Recipe</p>
                    <h1 data-preview-title>Buat resep baru</h1>
                    <p class="recipe-create__lede">Layout ini disusun seperti halaman detail: visual di kiri, informasi inti di kanan, lalu panel isi resep di bawah.</p>

                    <?php if ($errors !== []): ?>
                        <div class="recipe-create__alert recipe-create__alert--error">
                            <?php foreach ($errors as $error): ?>
                                <p><?= e($error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success !== null): ?>
                        <div class="recipe-create__alert recipe-create__alert--success"><?= e(
                            $success,
                        ) ?></div>
                    <?php endif; ?>

                    <div class="detail-meta recipe-create__meta">
                        <span data-preview-time><?= $old["waktu_memasak"] !== ""
                            ? e($old["waktu_memasak"]) . " mins"
                            : "Cook time" ?></span>
                        <span data-preview-porsi><?= $old["porsi"] !== ""
                            ? e($old["porsi"]) . " servings"
                            : "Servings" ?></span>
                        <span data-preview-difficulty><?= e(
                            ucfirst($old["tingkat_kesulitan"]),
                        ) ?></span>
                        <span data-preview-category><?= $old["kategori"] !== ""
                            ? e($old["kategori"])
                            : "Category" ?></span>
                    </div>

                    <div class="recipe-create__grid recipe-create__grid--two">
                        <label>
                            <span>Nama resep</span>
                            <input type="text" name="nama_resep" value="<?= old(
                                "nama_resep",
                                $old,
                            ) ?>" placeholder="Contoh: Chicken Salad" required data-preview-input="title">
                        </label>
                        <label>
                            <span>Kategori</span>
                            <input type="text" name="kategori" value="<?= old(
                                "kategori",
                                $old,
                            ) ?>" placeholder="salad, dessert, ayam" required data-preview-input="category">
                        </label>
                        <label>
                            <span>Waktu memasak (menit)</span>
                            <input type="number" name="waktu_memasak" min="1" step="1" value="<?= old(
                                "waktu_memasak",
                                $old,
                            ) ?>" required data-preview-input="time">
                        </label>
                        <label>
                            <span>Porsi</span>
                            <input type="number" name="porsi" min="1" step="1" value="<?= old(
                                "porsi",
                                $old,
                            ) ?>" required data-preview-input="porsi">
                        </label>
                        <label>
                            <span>Tingkat kesulitan</span>
                            <select name="tingkat_kesulitan" required data-preview-input="difficulty">
                                <option value="mudah" <?= $old[
                                    "tingkat_kesulitan"
                                ] === "mudah"
                                    ? "selected"
                                    : "" ?>>Mudah</option>
                                <option value="sedang" <?= $old[
                                    "tingkat_kesulitan"
                                ] === "sedang"
                                    ? "selected"
                                    : "" ?>>Sedang</option>
                                <option value="sulit" <?= $old[
                                    "tingkat_kesulitan"
                                ] === "sulit"
                                    ? "selected"
                                    : "" ?>>Sulit</option>
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
                        <textarea name="deskripsi" rows="5" required data-preview-input="description"><?= old(
                            "deskripsi",
                            $old,
                        ) ?></textarea>
                    </label>
                    <p class="recipe-create__preview-text" data-preview-description>Your recipe description will appear here.</p>
                </section>

            <div class="detail-duo recipe-create__duo">
                <section class="detail-panel recipe-create__section">
                    <p class="detail-panel__label">Ingredients</p>
                    <div class="recipe-create__section-head">
                        <h2>Bahan</h2>
                        <button type="button" class="recipe-create__ghost" data-add-row="ingredient">Tambah bahan</button>
                    </div>
                    <div class="recipe-create__rows" data-rows="ingredients">
                        <?php foreach (
                            $ingredients
                            as $index => $ingredient
                        ): ?>
                            <div class="recipe-create__row recipe-create__row--ingredient">
                                <input type="text" name="ingredients[<?= $index ?>][nama_bahan]" placeholder="Nama bahan" value="<?= e(
    $ingredient["nama_bahan"],
) ?>" required>
                                <input type="text" name="ingredients[<?= $index ?>][jumlah]" placeholder="Jumlah" value="<?= e(
    $ingredient["jumlah"],
) ?>">
                                <input type="text" name="ingredients[<?= $index ?>][satuan]" placeholder="Satuan" value="<?= e(
    $ingredient["satuan"],
) ?>">
                                <input type="text" name="ingredients[<?= $index ?>][keterangan]" placeholder="Keterangan" value="<?= e(
    $ingredient["keterangan"],
) ?>">
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
                                <input type="text" name="tools[<?= $index ?>][nama_peralatan]" placeholder="Nama peralatan" value="<?= e(
    $tool["nama_peralatan"],
) ?>" required>
                                <button type="button" class="recipe-create__remove" data-remove-row aria-label="Hapus peralatan">Hapus</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <section class="detail-panel recipe-create__section">
                <p class="detail-panel__label">Steps</p>
                <h2>Langkah memasak</h2>
                <label class="recipe-create__full">
                    <span>Tulis langkah per baris</span>
                    <textarea name="langkah_resep" rows="8" placeholder="1. Panaskan minyak&#10;2. Tumis bawang" required><?= old(
                        "langkah_resep",
                        $old,
                    ) ?></textarea>
                </label>
            </section>

            <div class="recipe-create__actions">
                <a class="recipe-create__secondary" href="../home/">Batal</a>
                <button type="submit" class="recipe-create__primary">Simpan resep</button>
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
                    setText(previewTitle, titleInput.value, 'Buat resep baru');
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
                    previewImage.src = '../assets/img/recipe-salad-hero.png';
                    previewUploadLabel.textContent = 'Upload foto resep';
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

            const addRowButtons = document.querySelectorAll('[data-add-row]');

            addRowButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const type = button.dataset.addRow;
                    const template = document.getElementById(`${type}-template`);
                    const container = document.querySelector(`[data-rows="${type === 'ingredient' ? 'ingredients' : 'tools'}"]`);

                    if (!template || !container) {
                        return;
                    }

                    const index = container.children.length;
                    const html = template.innerHTML.replaceAll('__INDEX__', String(index));
                    container.insertAdjacentHTML('beforeend', html);
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
                    row.querySelectorAll('input').forEach((input) => {
                        input.value = '';
                    });
                    return;
                }

                row.remove();
            });
        })();

    </script>
</body>
</html>
