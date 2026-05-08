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
        'title' => $title !== '' ? $title : 'Untitled Recipe',
        'slug' => $row['slug'] ?? ($id > 0 ? 'recipe-' . $id : 'recipe-preview'),
        'image' => $image,
        'author' => $row['author'] ?? 'ResepKu Team',
        'author_avatar' => recipe_asset_path($row['author_avatar'] ?? '../assets/img/home-profile.png'),
        'cook_time' => recipe_format_cook_time($row['waktu_memasak'] ?? null),
        'servings' => recipe_format_servings($row['porsi'] ?? null),
        'difficulty' => recipe_format_difficulty($row['tingkat_kesulitan'] ?? null),
        'rating' => isset($row['rating_value']) ? (float) $row['rating_value'] : 0.0,
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

function recipe_catalog_from_db(?int $limit = null): array
{
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
        ORDER BY r.dibuat_pada ASC, r.resep_id ASC
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

function recipe_user_recipes_db(int $userId, int $limit = 12): array
{
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

function recipe_catalog_filtered_db(array $filters = [], int $limit = 24): array
{
    $where = [];
    $params = [];
    $joins = '';
    $orderBy = 'ORDER BY r.dibuat_pada DESC, r.resep_id DESC';

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

        $orderBy = 'ORDER BY COALESCE(l.like_count, 0) DESC, COALESCE(rt.avg_rating, 0) DESC, r.dibuat_pada DESC, r.resep_id DESC';
    } elseif ($sort === 'oldest') {
        $orderBy = 'ORDER BY r.dibuat_pada ASC, r.resep_id ASC';
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
    SQL;

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ' . $orderBy . ' LIMIT :limit';

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
