<?php

require_once __DIR__ . '/../config/db.php';

function recipe_asset_path(?string $path): string
{
    $path = trim((string) $path);

    if ($path === '') {
        return '../assets/img/recipe-salad-card.png';
    }

    if (preg_match('~^(?:https?:)?//~i', $path)) {
        return $path;
    }

    if (str_starts_with($path, '/')) {
        return $path;
    }

    if (str_starts_with($path, '../') || str_starts_with($path, './')) {
        return $path;
    }

    return '../' . ltrim($path, '/');
}

function recipe_row_to_card(array $row): array
{
    $id = (int) ($row['resep_id'] ?? 0);
    $title = (string) ($row['nama_resep'] ?? '');
    $image = recipe_asset_path($row['foto_resep'] ?? null);

    return [
        'id' => $id,
        'user_id' => (int) ($row['user_id'] ?? 0),
        'title' => $title !== '' ? $title : 'Untitled Recipe',
        'slug' => $row['slug'] ?? ($id > 0 ? 'recipe-' . $id : 'recipe-preview'),
        'image' => $image,
        'author' => $row['author'] ?? 'ResepKu Team',
        'author_avatar' => recipe_asset_path($row['author_avatar'] ?? '../assets/img/home-profile.png'),
        'cook_time' => recipe_format_cook_time($row['waktu_memasak'] ?? null),
        'servings' => recipe_format_servings($row['porsi'] ?? null),
        'difficulty' => recipe_format_difficulty($row['tingkat_kesulitan'] ?? null),
        'rating' => isset($row['rating_value']) ? (float) $row['rating_value'] : 0.0,
        'likes_count' => (int) ($row['likes_count'] ?? 0),
        'favorites_count' => (int) ($row['favorites_count'] ?? 0),
        'summary' => (string) ($row['summary'] ?? ''),
        'description' => (string) ($row['deskripsi'] ?? ''),
        'ingredients' => $row['ingredients'] ?? [],
        'tools' => $row['tools'] ?? [],
        'steps' => $row['steps'] ?? [],
        'category' => (string) ($row['kategori'] ?? ''),
        'related' => $row['related'] ?? [],
    ];
}

function recipe_format_cook_time(mixed $minutes): string
{
    if ($minutes === null || $minutes === '') {
        return '-';
    }

    return ((int) $minutes) . ' mins';
}

function recipe_format_servings(mixed $servings): string
{
    if ($servings === null || $servings === '') {
        return '-';
    }

    $servings = (int) $servings;
    return $servings . ($servings === 1 ? ' serving' : ' servings');
}

function recipe_format_difficulty(mixed $difficulty): string
{
    return match ((string) $difficulty) {
        'mudah' => 'Easy',
        'sedang' => 'Medium',
        'sulit' => 'Hard',
        default => '-',
    };
}

function recipe_parse_list(?string $value): array
{
    $value = trim((string) $value);

    if ($value === '') {
        return [];
    }

    $parts = preg_split('/\r\n|\r|\n/', $value);
    $items = [];

    foreach ($parts as $part) {
        $item = trim(preg_replace('/^\s*[-•\d.\)]\s*/', '', (string) $part));
        if ($item !== '') {
            $items[] = $item;
        }
    }

    return $items;
}

function recipe_following_user_ids_db(int $userId): array
{
    if ($userId <= 0) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT following_id_user FROM following WHERE follower_id = :user_id ORDER BY dibuat_pada DESC, following_id DESC'
    );
    $stmt->execute([':user_id' => $userId]);

    return array_map(static fn ($value) => (int) $value, $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function recipe_catalog_from_db(?int $limit = null, ?int $viewerUserId = null): array
{
    $priorityJoin = '';
    $priorityOrder = 'r.dibuat_pada DESC, r.resep_id DESC';

    if ($viewerUserId !== null && $viewerUserId > 0) {
        $followingIds = recipe_following_user_ids_db($viewerUserId);

        if ($followingIds !== []) {
            $priorityJoin = 'LEFT JOIN (' . implode(' UNION ALL ', array_map(
                static fn (int $followingId): string => 'SELECT ' . $followingId . ' AS following_user_id',
                $followingIds
            )) . ') fh ON fh.following_user_id = r.pengguna_id';
            $priorityOrder = 'CASE WHEN fh.following_user_id IS NULL THEN 1 ELSE 0 END, ' . $priorityOrder;
        }
    }

    $sql = <<<SQL
        SELECT
            r.resep_id,
            r.pengguna_id AS user_id,
            r.nama_resep,
            r.foto_resep,
            r.waktu_memasak,
            r.porsi,
            r.tingkat_kesulitan,
            r.kategori,
            r.deskripsi,
            r.langkah_resep,
            p.nama_pengguna AS author_name,
            p.foto_profil AS author_avatar
        FROM recipes r
        INNER JOIN pengguna p ON p.pengguna_id = r.pengguna_id
        $priorityJoin
        ORDER BY $priorityOrder
    SQL;

    if ($limit !== null) {
        $sql .= ' LIMIT :limit';
    }

    $stmt = db()->prepare($sql);
    if ($limit !== null) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();

    $recipes = [];

    foreach ($stmt->fetchAll() as $row) {
        $recipes[] = recipe_row_to_card([
            'resep_id' => $row['resep_id'],
            'nama_resep' => $row['nama_resep'],
            'foto_resep' => $row['foto_resep'],
            'waktu_memasak' => $row['waktu_memasak'],
            'porsi' => $row['porsi'],
            'tingkat_kesulitan' => $row['tingkat_kesulitan'],
            'kategori' => $row['kategori'],
            'summary' => $row['deskripsi'] ?? '',
            'deskripsi' => $row['deskripsi'] ?? '',
            'author' => $row['author_name'] ?? 'ResepKu Team',
            'author_avatar' => $row['author_avatar'] ?? '../assets/img/home-profile.png',
            'ingredients' => [],
            'tools' => [],
            'steps' => recipe_parse_list($row['langkah_resep'] ?? ''),
        ]);
    }

    return $recipes;
}

function recipe_user_profile_db(int $userId): ?array
{
    $sql = <<<SQL
        SELECT
            p.pengguna_id,
            p.nama_pengguna,
            p.email,
            p.foto_profil,
            p.bio,
            p.role,
            p.status,
            p.dibuat_pada,
            (
                SELECT COUNT(*)
                FROM recipes r
                WHERE r.pengguna_id = p.pengguna_id
            ) AS recipe_count,
            (
                SELECT COUNT(*)
                FROM following f
                WHERE f.following_id_user = p.pengguna_id
            ) AS follower_count,
            (
                SELECT COUNT(*)
                FROM following f
                WHERE f.follower_id = p.pengguna_id
            ) AS following_count
        FROM pengguna p
        WHERE p.pengguna_id = :user_id
        LIMIT 1
    SQL;

    $stmt = db()->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    return [
        'id' => (int) $row['pengguna_id'],
        'name' => (string) $row['nama_pengguna'],
        'email' => (string) $row['email'],
        'avatar' => recipe_asset_path($row['foto_profil'] ?? '../assets/img/home-profile.png'),
        'bio' => trim((string) ($row['bio'] ?? '')),
        'role' => (string) ($row['role'] ?? 'pengguna'),
        'status' => (string) ($row['status'] ?? 'aktif'),
        'joined_at' => (string) ($row['dibuat_pada'] ?? ''),
        'recipe_count' => (int) ($row['recipe_count'] ?? 0),
        'follower_count' => (int) ($row['follower_count'] ?? 0),
        'following_count' => (int) ($row['following_count'] ?? 0),
    ];
}

function recipe_update_user_profile_db(int $userId, string $name, string $bio, ?string $newPassword = null): bool
{
    $name = trim($name);
    $bio = trim($bio);
    $newPassword = $newPassword !== null ? trim($newPassword) : null;

    if ($userId <= 0 || $name === '') {
        return false;
    }

    $fields = [
        'nama_pengguna = :name',
        'bio = :bio',
    ];
    $params = [
        ':name' => $name,
        ':bio' => $bio !== '' ? $bio : null,
        ':user_id' => $userId,
    ];

    if ($newPassword !== null && $newPassword !== '') {
        $fields[] = 'kata_sandi = :password';
        $params[':password'] = $newPassword;
    }

    $sql = 'UPDATE pengguna SET ' . implode(', ', $fields) . ' WHERE pengguna_id = :user_id';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return true;
}

function recipe_format_datetime_label(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d M Y, H:i', $timestamp);
}

function recipe_user_recipes_db(int $userId, int $limit = 12): array
{
    $sql = <<<SQL
        SELECT
            r.resep_id,
            r.pengguna_id AS user_id,
            r.nama_resep,
            r.foto_resep,
            r.waktu_memasak,
            r.porsi,
            r.tingkat_kesulitan,
            r.kategori,
            r.deskripsi,
            r.langkah_resep,
            p.nama_pengguna AS author_name,
            p.foto_profil AS author_avatar
        FROM recipes r
        INNER JOIN pengguna p ON p.pengguna_id = r.pengguna_id
        WHERE r.pengguna_id = :user_id
        ORDER BY r.dibuat_pada DESC, r.resep_id DESC
        LIMIT :limit
    SQL;

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $recipes = [];
    foreach ($stmt->fetchAll() as $row) {
        $recipes[] = recipe_row_to_card([
            'resep_id' => $row['resep_id'],
            'nama_resep' => $row['nama_resep'],
            'foto_resep' => $row['foto_resep'],
            'waktu_memasak' => $row['waktu_memasak'],
            'porsi' => $row['porsi'],
            'tingkat_kesulitan' => $row['tingkat_kesulitan'],
            'kategori' => $row['kategori'],
            'summary' => $row['deskripsi'] ?? '',
            'deskripsi' => $row['deskripsi'] ?? '',
            'author' => $row['author_name'] ?? 'ResepKu Team',
            'author_avatar' => $row['author_avatar'] ?? '../assets/img/home-profile.png',
            'ingredients' => [],
            'tools' => [],
            'steps' => recipe_parse_list($row['langkah_resep'] ?? ''),
        ]);
    }

    return $recipes;
}

function recipe_user_favorites_db(int $userId, int $limit = 100): array
{
    $sql = <<<SQL
        SELECT
            r.resep_id,
            r.pengguna_id AS user_id,
            r.nama_resep,
            r.foto_resep,
            r.waktu_memasak,
            r.porsi,
            r.tingkat_kesulitan,
            r.kategori,
            r.deskripsi,
            r.langkah_resep,
            f.dibuat_pada AS favorited_at,
            p.nama_pengguna AS author_name,
            p.foto_profil AS author_avatar,
            COALESCE(l.like_count, 0) AS likes_count,
            COALESCE(rt.rating_average, 0) AS rating_average,
            COALESCE(ft.favorite_count, 0) AS favorites_count
        FROM favorite f
        INNER JOIN recipes r ON r.resep_id = f.resep_id
        INNER JOIN pengguna p ON p.pengguna_id = r.pengguna_id
        LEFT JOIN (
            SELECT resep_id, COUNT(*) AS like_count
            FROM likes
            GROUP BY resep_id
        ) l ON l.resep_id = r.resep_id
        LEFT JOIN (
            SELECT resep_id, AVG(rating_value) AS rating_average
            FROM ratings
            GROUP BY resep_id
        ) rt ON rt.resep_id = r.resep_id
        LEFT JOIN (
            SELECT resep_id, COUNT(*) AS favorite_count
            FROM favorite
            GROUP BY resep_id
        ) ft ON ft.resep_id = r.resep_id
        WHERE f.pengguna_id = :user_id
        ORDER BY f.dibuat_pada DESC, f.favorite_id DESC
        LIMIT :limit
    SQL;

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $recipes = [];
    foreach ($stmt->fetchAll() as $row) {
        $recipes[] = recipe_row_to_card([
            'resep_id' => $row['resep_id'],
            'nama_resep' => $row['nama_resep'],
            'foto_resep' => $row['foto_resep'],
            'waktu_memasak' => $row['waktu_memasak'],
            'porsi' => $row['porsi'],
            'tingkat_kesulitan' => $row['tingkat_kesulitan'],
            'kategori' => $row['kategori'],
            'rating_value' => $row['rating_average'],
            'likes_count' => $row['likes_count'],
            'favorites_count' => $row['favorites_count'],
            'summary' => $row['deskripsi'] ?? '',
            'deskripsi' => $row['deskripsi'] ?? '',
            'author' => $row['author_name'] ?? 'ResepKu Team',
            'author_avatar' => $row['author_avatar'] ?? '../assets/img/home-profile.png',
            'ingredients' => [],
            'tools' => [],
            'steps' => recipe_parse_list($row['langkah_resep'] ?? ''),
        ]);
    }

    return $recipes;
}

function recipe_find_owned_db(int $id, int $userId): ?array
{
    $recipe = recipe_find_db($id);

    if ($recipe === null || (int) ($recipe['user_id'] ?? 0) !== $userId) {
        return null;
    }

    return $recipe;
}

function recipe_sync_related_rows(PDO $pdo, int $recipeId, array $ingredients, array $tools): void
{
    $pdo->prepare('DELETE FROM bahan_resep WHERE resep_id = :id')->execute([':id' => $recipeId]);
    $pdo->prepare('DELETE FROM peralatan_resep WHERE resep_id = :id')->execute([':id' => $recipeId]);

    $ingredientStmt = $pdo->prepare(
        'INSERT INTO bahan_resep (resep_id, nama_bahan, jumlah, satuan, keterangan)
         VALUES (:resep_id, :nama_bahan, :jumlah, :satuan, :keterangan)'
    );
    foreach ($ingredients as $ingredient) {
        $ingredientStmt->execute([
            ':resep_id' => $recipeId,
            ':nama_bahan' => $ingredient['nama_bahan'],
            ':jumlah' => $ingredient['jumlah'] !== '' ? $ingredient['jumlah'] : null,
            ':satuan' => $ingredient['satuan'] !== '' ? $ingredient['satuan'] : null,
            ':keterangan' => $ingredient['keterangan'] !== '' ? $ingredient['keterangan'] : null,
        ]);
    }

    $toolStmt = $pdo->prepare(
        'INSERT INTO peralatan_resep (resep_id, nama_peralatan) VALUES (:resep_id, :nama_peralatan)'
    );
    foreach ($tools as $tool) {
        $toolStmt->execute([
            ':resep_id' => $recipeId,
            ':nama_peralatan' => $tool,
        ]);
    }
}

function recipe_create_db(int $userId, array $payload, array $ingredients, array $tools): int
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO recipes (pengguna_id, nama_resep, deskripsi, langkah_resep, waktu_memasak, porsi, foto_resep, kategori, tingkat_kesulitan)
             VALUES (:pengguna_id, :nama_resep, :deskripsi, :langkah_resep, :waktu_memasak, :porsi, :foto_resep, :kategori, :tingkat_kesulitan)'
        );
        $stmt->execute([
            ':pengguna_id' => $userId,
            ':nama_resep' => $payload['nama_resep'],
            ':deskripsi' => $payload['deskripsi'],
            ':langkah_resep' => $payload['langkah_resep'],
            ':waktu_memasak' => $payload['waktu_memasak'],
            ':porsi' => $payload['porsi'],
            ':foto_resep' => $payload['foto_resep'],
            ':kategori' => $payload['kategori'],
            ':tingkat_kesulitan' => $payload['tingkat_kesulitan'],
        ]);

        $recipeId = (int) $pdo->lastInsertId();
        recipe_sync_related_rows($pdo, $recipeId, $ingredients, $tools);
        $pdo->commit();

        return $recipeId;
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        throw $throwable;
    }
}

function recipe_update_db(int $id, int $userId, array $payload, array $ingredients, array $tools): bool
{
    if (recipe_find_owned_db($id, $userId) === null) {
        return false;
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'UPDATE recipes
             SET nama_resep = :nama_resep,
                 deskripsi = :deskripsi,
                 langkah_resep = :langkah_resep,
                 waktu_memasak = :waktu_memasak,
                 porsi = :porsi,
                 foto_resep = COALESCE(:foto_resep, foto_resep),
                 kategori = :kategori,
                 tingkat_kesulitan = :tingkat_kesulitan
             WHERE resep_id = :id AND pengguna_id = :pengguna_id'
        );
        $stmt->execute([
            ':nama_resep' => $payload['nama_resep'],
            ':deskripsi' => $payload['deskripsi'],
            ':langkah_resep' => $payload['langkah_resep'],
            ':waktu_memasak' => $payload['waktu_memasak'],
            ':porsi' => $payload['porsi'],
            ':foto_resep' => $payload['foto_resep'],
            ':kategori' => $payload['kategori'],
            ':tingkat_kesulitan' => $payload['tingkat_kesulitan'],
            ':id' => $id,
            ':pengguna_id' => $userId,
        ]);

        recipe_sync_related_rows($pdo, $id, $ingredients, $tools);
        $pdo->commit();

        return true;
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        throw $throwable;
    }
}

function recipe_delete_db(int $id, int $userId): bool
{
    $stmt = db()->prepare('DELETE FROM recipes WHERE resep_id = :id AND pengguna_id = :pengguna_id');
    $stmt->execute([
        ':id' => $id,
        ':pengguna_id' => $userId,
    ]);

    return $stmt->rowCount() > 0;
}

function recipe_catalog_filtered_db(array $filters = [], int $limit = 24, ?int $viewerUserId = null): array
{
    $where = [];
    $params = [];
    $joins = '';
    $priorityJoin = '';
    $priorityOrder = 'r.dibuat_pada DESC, r.resep_id DESC';

    $query = trim((string) ($filters['q'] ?? ''));
    $category = trim((string) ($filters['category'] ?? ''));
    $difficulty = trim((string) ($filters['difficulty'] ?? ''));
    $maxTime = trim((string) ($filters['max_time'] ?? ''));
    $sort = trim((string) ($filters['sort'] ?? 'newest'));
    $categoryMap = [
        'food' => ['ayam', 'vegetarian', 'seafood'],
        'salad' => ['salad'],
        'dessert' => ['dessert'],
        'drinks' => ['drinks'],
    ];

    if ($query !== '') {
        $where[] = 'r.nama_resep LIKE :query';
        $params[':query'] = '%' . $query . '%';
    }

    if ($category !== '') {
        $normalizedCategory = mb_strtolower($category);

        if (isset($categoryMap[$normalizedCategory])) {
            $placeholders = [];
            foreach ($categoryMap[$normalizedCategory] as $index => $value) {
                $placeholder = ':kategori_' . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $value;
            }

            $where[] = 'LOWER(r.kategori) IN (' . implode(', ', $placeholders) . ')';
        } else {
            $where[] = 'LOWER(r.kategori) = :kategori';
            $params[':kategori'] = $normalizedCategory;
        }
    }

    if ($difficulty !== '') {
        $where[] = 'r.tingkat_kesulitan = :difficulty';
        $params[':difficulty'] = $difficulty;
    }

    if ($maxTime !== '' && ctype_digit($maxTime)) {
        $where[] = 'r.waktu_memasak <= :max_time';
        $params[':max_time'] = (int) $maxTime;
    }

    if ($sort === 'popular') {
        $joins = <<<SQL
            LEFT JOIN (
                SELECT resep_id, COUNT(*) AS like_count
                FROM likes
                GROUP BY resep_id
            ) l ON l.resep_id = r.resep_id
            LEFT JOIN (
                SELECT resep_id, AVG(rating_value) AS avg_rating
                FROM ratings
                GROUP BY resep_id
            ) rt ON rt.resep_id = r.resep_id
        SQL;

        $priorityOrder = 'COALESCE(l.like_count, 0) DESC, COALESCE(rt.avg_rating, 0) DESC, r.dibuat_pada DESC, r.resep_id DESC';
    } elseif ($sort === 'oldest') {
        $priorityOrder = 'r.dibuat_pada ASC, r.resep_id ASC';
    }

    if ($viewerUserId !== null && $viewerUserId > 0) {
        $followingIds = recipe_following_user_ids_db($viewerUserId);

        if ($followingIds !== []) {
            $priorityJoin = 'LEFT JOIN (' . implode(' UNION ALL ', array_map(
                static fn (int $followingId): string => 'SELECT ' . $followingId . ' AS following_user_id',
                $followingIds
            )) . ') fh ON fh.following_user_id = r.pengguna_id';
            $priorityOrder = 'CASE WHEN fh.following_user_id IS NULL THEN 1 ELSE 0 END, ' . $priorityOrder;
        }
    }

    $sql = <<<SQL
        SELECT
            r.resep_id,
            r.nama_resep,
            r.foto_resep,
            r.waktu_memasak,
            r.porsi,
            r.tingkat_kesulitan,
            r.kategori,
            r.deskripsi,
            r.langkah_resep,
            p.nama_pengguna AS author_name,
            p.foto_profil AS author_avatar
        FROM recipes r
        INNER JOIN pengguna p ON p.pengguna_id = r.pengguna_id
        $joins
        $priorityJoin
    SQL;

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY ' . $priorityOrder . ' LIMIT :limit';

    $stmt = db()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $recipes = [];
    foreach ($stmt->fetchAll() as $row) {
        $recipes[] = recipe_row_to_card([
            'resep_id' => $row['resep_id'],
            'nama_resep' => $row['nama_resep'],
            'foto_resep' => $row['foto_resep'],
            'waktu_memasak' => $row['waktu_memasak'],
            'porsi' => $row['porsi'],
            'tingkat_kesulitan' => $row['tingkat_kesulitan'],
            'kategori' => $row['kategori'],
            'summary' => $row['deskripsi'] ?? '',
            'deskripsi' => $row['deskripsi'] ?? '',
            'author' => $row['author_name'] ?? 'ResepKu Team',
            'author_avatar' => $row['author_avatar'] ?? '../assets/img/home-profile.png',
            'ingredients' => [],
            'tools' => [],
            'steps' => recipe_parse_list($row['langkah_resep'] ?? ''),
        ]);
    }

    return $recipes;
}

function recipe_find_db(int $id): ?array
{
    $sql = <<<SQL
        SELECT
            r.resep_id,
            r.pengguna_id,
            r.nama_resep,
            r.deskripsi,
            r.langkah_resep,
            r.waktu_memasak,
            r.porsi,
            r.foto_resep,
            r.kategori,
            r.tingkat_kesulitan,
            p.nama_pengguna AS author_name,
            p.foto_profil AS author_avatar
        FROM recipes r
        INNER JOIN pengguna p ON p.pengguna_id = r.pengguna_id
        WHERE r.resep_id = :id
        LIMIT 1
    SQL;

    $stmt = db()->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    $ingredientsStmt = db()->prepare(
        'SELECT nama_bahan, jumlah, satuan, keterangan FROM bahan_resep WHERE resep_id = :id ORDER BY bahan_resep_id ASC'
    );
    $ingredientsStmt->execute([':id' => $id]);
    $ingredients = [];
    foreach ($ingredientsStmt->fetchAll() as $ingredientRow) {
        $ingredient = trim((string) ($ingredientRow['nama_bahan'] ?? ''));
        $amount = trim((string) ($ingredientRow['jumlah'] ?? ''));
        $unit = trim((string) ($ingredientRow['satuan'] ?? ''));
        $note = trim((string) ($ingredientRow['keterangan'] ?? ''));

        if ($amount !== '') {
            $ingredient .= ' ' . $amount;
        }

        if ($unit !== '') {
            $ingredient .= ' ' . $unit;
        }

        if ($note !== '') {
            $ingredient .= ' ' . $note;
        }

        $ingredients[] = trim($ingredient);
    }

    $toolsStmt = db()->prepare(
        'SELECT nama_peralatan FROM peralatan_resep WHERE resep_id = :id ORDER BY peralatan_id ASC'
    );
    $toolsStmt->execute([':id' => $id]);
    $tools = [];
    foreach ($toolsStmt->fetchAll() as $toolRow) {
        $tool = trim((string) ($toolRow['nama_peralatan'] ?? ''));
        if ($tool !== '') {
            $tools[] = $tool;
        }
    }

    $steps = recipe_parse_list((string) ($row['langkah_resep'] ?? ''));
    $summary = trim((string) ($row['deskripsi'] ?? ''));

    return recipe_row_to_card([
        'resep_id' => $row['resep_id'],
        'user_id' => (int) ($row['pengguna_id'] ?? 0),
        'nama_resep' => $row['nama_resep'],
        'foto_resep' => $row['foto_resep'],
        'waktu_memasak' => $row['waktu_memasak'],
        'porsi' => $row['porsi'],
        'tingkat_kesulitan' => $row['tingkat_kesulitan'],
        'kategori' => $row['kategori'],
        'summary' => $summary,
        'deskripsi' => $row['deskripsi'] ?? '',
        'author' => $row['author_name'] ?? 'ResepKu Team',
        'author_avatar' => $row['author_avatar'] ?? '../assets/img/home-profile.png',
        'ingredients' => $ingredients,
        'tools' => $tools,
        'steps' => $steps,
        'related' => [],
    ]);
}

function recipe_comments_db(int $recipeId, int $limit = 50): array
{
    $sql = <<<SQL
        SELECT
            k.komentar_id,
            k.pengguna_id,
            k.resep_id,
            k.isi_komentar,
            k.dibuat_pada,
            p.nama_pengguna,
            p.foto_profil
        FROM komentar k
        INNER JOIN pengguna p ON p.pengguna_id = k.pengguna_id
        WHERE k.resep_id = :recipe_id
        ORDER BY k.dibuat_pada DESC, k.komentar_id DESC
        LIMIT :limit
    SQL;

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':recipe_id', $recipeId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $comments = [];
    foreach ($stmt->fetchAll() as $row) {
        $comments[] = [
            'id' => (int) $row['komentar_id'],
            'user_id' => (int) $row['pengguna_id'],
            'recipe_id' => (int) $row['resep_id'],
            'content' => (string) $row['isi_komentar'],
            'created_at' => (string) $row['dibuat_pada'],
            'created_at_label' => recipe_format_datetime_label($row['dibuat_pada'] ?? ''),
            'author' => (string) $row['nama_pengguna'],
            'avatar' => recipe_asset_path($row['foto_profil'] ?? '../assets/img/home-profile.png'),
        ];
    }

    return $comments;
}

function recipe_add_comment_db(int $recipeId, int $userId, string $content): array
{
    $content = trim($content);
    if ($content === '') {
        throw new InvalidArgumentException('Komentar tidak boleh kosong.');
    }

    $stmt = db()->prepare(
        'INSERT INTO komentar (pengguna_id, resep_id, isi_komentar)
         VALUES (:pengguna_id, :resep_id, :isi_komentar)'
    );
    $stmt->execute([
        ':pengguna_id' => $userId,
        ':resep_id' => $recipeId,
        ':isi_komentar' => $content,
    ]);

    $commentId = (int) db()->lastInsertId();
    $comment = recipe_comments_db($recipeId, 1);

    if ($comment !== []) {
        return $comment[0];
    }

    return [
        'id' => $commentId,
        'user_id' => $userId,
        'recipe_id' => $recipeId,
        'content' => $content,
        'created_at' => date('Y-m-d H:i:s'),
        'created_at_label' => date('d M Y, H:i'),
        'author' => 'User',
        'avatar' => '../assets/img/home-profile.png',
    ];
}

function recipe_social_state_db(int $recipeId, ?int $userId = null): array
{
    $pdo = db();

    $countStmt = $pdo->prepare(
        'SELECT
            (SELECT COUNT(*) FROM likes WHERE resep_id = :likes_recipe_id) AS likes_count,
            (SELECT COUNT(*) FROM favorite WHERE resep_id = :favorites_recipe_id) AS favorites_count,
            (SELECT COUNT(*) FROM ratings WHERE resep_id = :ratings_recipe_id) AS ratings_count,
            (SELECT AVG(rating_value) FROM ratings WHERE resep_id = :average_recipe_id) AS rating_average'
    );
    $countStmt->execute([
        ':likes_recipe_id' => $recipeId,
        ':favorites_recipe_id' => $recipeId,
        ':ratings_recipe_id' => $recipeId,
        ':average_recipe_id' => $recipeId,
    ]);
    $counts = $countStmt->fetch() ?: [];

    $state = [
        'recipe_id' => $recipeId,
        'likes_count' => (int) ($counts['likes_count'] ?? 0),
        'favorites_count' => (int) ($counts['favorites_count'] ?? 0),
        'ratings_count' => (int) ($counts['ratings_count'] ?? 0),
        'rating_average' => round((float) ($counts['rating_average'] ?? 0), 1),
        'liked' => false,
        'favorited' => false,
        'user_rating' => null,
    ];

    if ($userId !== null && $userId > 0) {
        $userStmt = $pdo->prepare(
            'SELECT
                EXISTS(SELECT 1 FROM likes WHERE pengguna_id = :liked_user_id AND resep_id = :liked_recipe_id) AS liked,
                EXISTS(SELECT 1 FROM favorite WHERE pengguna_id = :favorite_user_id AND resep_id = :favorite_recipe_id) AS favorited,
                (SELECT rating_value FROM ratings WHERE pengguna_id = :rating_user_id AND resep_id = :rating_recipe_id LIMIT 1) AS user_rating'
        );
        $userStmt->execute([
            ':liked_user_id' => $userId,
            ':liked_recipe_id' => $recipeId,
            ':favorite_user_id' => $userId,
            ':favorite_recipe_id' => $recipeId,
            ':rating_user_id' => $userId,
            ':rating_recipe_id' => $recipeId,
        ]);
        $user = $userStmt->fetch() ?: [];

        $state['liked'] = (bool) ($user['liked'] ?? false);
        $state['favorited'] = (bool) ($user['favorited'] ?? false);
        $state['user_rating'] = isset($user['user_rating']) ? (float) $user['user_rating'] : null;
    }

    return $state;
}

function recipe_toggle_like_db(int $recipeId, int $userId): array
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $existsStmt = $pdo->prepare(
            'SELECT like_id FROM likes WHERE pengguna_id = :user_id AND resep_id = :recipe_id LIMIT 1'
        );
        $existsStmt->execute([
            ':user_id' => $userId,
            ':recipe_id' => $recipeId,
        ]);
        $existing = $existsStmt->fetch();

        if ($existing) {
            $deleteStmt = $pdo->prepare(
                'DELETE FROM likes WHERE pengguna_id = :user_id AND resep_id = :recipe_id'
            );
            $deleteStmt->execute([
                ':user_id' => $userId,
                ':recipe_id' => $recipeId,
            ]);
            $liked = false;
        } else {
            $insertStmt = $pdo->prepare(
                'INSERT INTO likes (pengguna_id, resep_id) VALUES (:user_id, :recipe_id)'
            );
            $insertStmt->execute([
                ':user_id' => $userId,
                ':recipe_id' => $recipeId,
            ]);
            $liked = true;
        }

        $pdo->commit();

        $state = recipe_social_state_db($recipeId, $userId);
        $state['liked'] = $liked;
        return $state;
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        throw $throwable;
    }
}

function recipe_toggle_favorite_db(int $recipeId, int $userId): array
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $existsStmt = $pdo->prepare(
            'SELECT favorite_id FROM favorite WHERE pengguna_id = :user_id AND resep_id = :recipe_id LIMIT 1'
        );
        $existsStmt->execute([
            ':user_id' => $userId,
            ':recipe_id' => $recipeId,
        ]);
        $existing = $existsStmt->fetch();

        if ($existing) {
            $deleteStmt = $pdo->prepare(
                'DELETE FROM favorite WHERE pengguna_id = :user_id AND resep_id = :recipe_id'
            );
            $deleteStmt->execute([
                ':user_id' => $userId,
                ':recipe_id' => $recipeId,
            ]);
            $favorited = false;
        } else {
            $insertStmt = $pdo->prepare(
                'INSERT INTO favorite (pengguna_id, resep_id) VALUES (:user_id, :recipe_id)'
            );
            $insertStmt->execute([
                ':user_id' => $userId,
                ':recipe_id' => $recipeId,
            ]);
            $favorited = true;
        }

        $pdo->commit();

        $state = recipe_social_state_db($recipeId, $userId);
        $state['favorited'] = $favorited;
        return $state;
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        throw $throwable;
    }
}

function recipe_is_following_db(int $followerId, int $targetUserId): bool
{
    if ($followerId <= 0 || $targetUserId <= 0 || $followerId === $targetUserId) {
        return false;
    }

    $stmt = db()->prepare(
        'SELECT 1 FROM following WHERE follower_id = :follower_id AND following_id_user = :target_user_id LIMIT 1'
    );
    $stmt->execute([
        ':follower_id' => $followerId,
        ':target_user_id' => $targetUserId,
    ]);

    return (bool) $stmt->fetchColumn();
}

function recipe_follow_state_db(int $targetUserId, ?int $followerId = null): array
{
    $profile = recipe_user_profile_db($targetUserId);

    if ($profile === null || $profile['status'] !== 'aktif') {
        throw new InvalidArgumentException('Profil pengguna tidak tersedia.');
    }

    return [
        'target_user_id' => $targetUserId,
        'following' => $followerId !== null ? recipe_is_following_db($followerId, $targetUserId) : false,
        'follower_count' => (int) $profile['follower_count'],
        'following_count' => (int) $profile['following_count'],
    ];
}

function recipe_toggle_follow_db(int $targetUserId, int $followerId): array
{
    if ($targetUserId <= 0) {
        throw new InvalidArgumentException('User tidak valid.');
    }

    if ($followerId <= 0) {
        throw new InvalidArgumentException('Silakan login terlebih dahulu.');
    }

    if ($targetUserId === $followerId) {
        throw new InvalidArgumentException('Kamu tidak bisa follow profile sendiri.');
    }

    $profile = recipe_user_profile_db($targetUserId);
    if ($profile === null || $profile['status'] !== 'aktif') {
        throw new InvalidArgumentException('Profil pengguna tidak tersedia.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $existsStmt = $pdo->prepare(
            'SELECT following_id FROM following WHERE follower_id = :follower_id AND following_id_user = :target_user_id LIMIT 1'
        );
        $existsStmt->execute([
            ':follower_id' => $followerId,
            ':target_user_id' => $targetUserId,
        ]);
        $existing = $existsStmt->fetch();

        if ($existing) {
            $deleteStmt = $pdo->prepare(
                'DELETE FROM following WHERE follower_id = :follower_id AND following_id_user = :target_user_id'
            );
            $deleteStmt->execute([
                ':follower_id' => $followerId,
                ':target_user_id' => $targetUserId,
            ]);
            $following = false;
        } else {
            $insertStmt = $pdo->prepare(
                'INSERT INTO following (follower_id, following_id_user) VALUES (:follower_id, :target_user_id)'
            );
            $insertStmt->execute([
                ':follower_id' => $followerId,
                ':target_user_id' => $targetUserId,
            ]);
            $following = true;
        }

        $pdo->commit();

        $state = recipe_follow_state_db($targetUserId, $followerId);
        $state['following'] = $following;
        return $state;
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        throw $throwable;
    }
}

function recipe_remove_favorite_db(int $recipeId, int $userId): bool
{
    $stmt = db()->prepare(
        'DELETE FROM favorite WHERE pengguna_id = :user_id AND resep_id = :recipe_id'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':recipe_id' => $recipeId,
    ]);

    return $stmt->rowCount() > 0;
}

function recipe_set_rating_db(int $recipeId, int $userId, float $ratingValue): array
{
    $ratingValue = round($ratingValue, 1);
    if ($ratingValue < 1 || $ratingValue > 5) {
        throw new InvalidArgumentException('Rating harus berada di antara 1 sampai 5.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO ratings (pengguna_id, resep_id, rating_value)
             VALUES (:user_id, :id, :rating_value)
             ON DUPLICATE KEY UPDATE rating_value = VALUES(rating_value)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':id' => $recipeId,
            ':rating_value' => $ratingValue,
        ]);

        $pdo->commit();

        return recipe_social_state_db($recipeId, $userId);
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        throw $throwable;
    }
}

function recipe_related_db(array $recipe, int $limit = 3): array
{
    $category = (string) ($recipe['category'] ?? '');
    $excludeId = (int) ($recipe['id'] ?? 0);

    if ($category !== '') {
        $sql = <<<SQL
            SELECT
                r.resep_id,
                r.nama_resep,
                r.foto_resep,
                r.waktu_memasak,
                r.porsi,
                r.tingkat_kesulitan,
                r.kategori,
                p.nama_pengguna AS author_name,
                p.foto_profil AS author_avatar
            FROM recipes r
            INNER JOIN pengguna p ON p.pengguna_id = r.pengguna_id
            WHERE r.kategori = :kategori
              AND r.resep_id <> :exclude_id
            ORDER BY r.dibuat_pada DESC, r.resep_id DESC
            LIMIT :limit
        SQL;

        $stmt = db()->prepare($sql);
        $stmt->bindValue(':kategori', $category, PDO::PARAM_STR);
        $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = recipe_row_to_card([
        'resep_id' => $row['resep_id'],
        'user_id' => (int) ($row['pengguna_id'] ?? 0),
        'nama_resep' => $row['nama_resep'],
        'foto_resep' => $row['foto_resep'],
        'waktu_memasak' => $row['waktu_memasak'],
                'porsi' => $row['porsi'],
                'tingkat_kesulitan' => $row['tingkat_kesulitan'],
                'kategori' => $row['kategori'],
                'author' => $row['author_name'] ?? 'ResepKu Team',
                'author_avatar' => $row['author_avatar'] ?? '../assets/img/home-profile.png',
            ]);
        }

        if ($items !== []) {
            return $items;
        }
    }

    $sql = <<<SQL
        SELECT
            r.resep_id,
            r.nama_resep,
            r.foto_resep,
            r.waktu_memasak,
            r.porsi,
            r.tingkat_kesulitan,
            r.kategori,
            p.nama_pengguna AS author_name,
            p.foto_profil AS author_avatar
        FROM recipes r
        INNER JOIN pengguna p ON p.pengguna_id = r.pengguna_id
        WHERE r.resep_id <> :exclude_id
        ORDER BY r.dibuat_pada DESC, r.resep_id DESC
        LIMIT :limit
    SQL;

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $items[] = recipe_row_to_card([
            'resep_id' => $row['resep_id'],
            'nama_resep' => $row['nama_resep'],
            'foto_resep' => $row['foto_resep'],
            'waktu_memasak' => $row['waktu_memasak'],
            'porsi' => $row['porsi'],
            'tingkat_kesulitan' => $row['tingkat_kesulitan'],
            'kategori' => $row['kategori'],
            'author' => $row['author_name'] ?? 'ResepKu Team',
            'author_avatar' => $row['author_avatar'] ?? '../assets/img/home-profile.png',
        ]);
    }

    return $items;
}
