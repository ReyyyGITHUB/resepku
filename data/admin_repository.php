<?php

require_once __DIR__ . '/../config/db.php';

function admin_dashboard_stats_db(): array
{
    $row = db()->query(
        'SELECT
            (SELECT COUNT(*) FROM pengguna) AS total_users,
            (SELECT COUNT(*) FROM pengguna WHERE status = "aktif") AS active_users,
            (SELECT COUNT(*) FROM pengguna WHERE status = "nonaktif") AS inactive_users,
            (SELECT COUNT(*) FROM recipes) AS total_recipes,
            (SELECT COUNT(*) FROM komentar) AS total_comments,
            (SELECT COUNT(*) FROM cs WHERE status = "menunggu") AS pending_reports,
            (SELECT COUNT(*) FROM likes) AS total_likes,
            (SELECT COALESCE(AVG(rating_value), 0) FROM ratings) AS average_rating'
    )->fetch() ?: [];

    return [
        'total_users' => (int) ($row['total_users'] ?? 0),
        'active_users' => (int) ($row['active_users'] ?? 0),
        'inactive_users' => (int) ($row['inactive_users'] ?? 0),
        'total_recipes' => (int) ($row['total_recipes'] ?? 0),
        'total_comments' => (int) ($row['total_comments'] ?? 0),
        'pending_reports' => (int) ($row['pending_reports'] ?? 0),
        'total_likes' => (int) ($row['total_likes'] ?? 0),
        'average_rating' => round((float) ($row['average_rating'] ?? 0), 1),
    ];
}

function admin_recent_users_db(int $limit = 5): array
{
    $stmt = db()->prepare(
        'SELECT pengguna_id, nama_pengguna, email, role, status, dibuat_pada
         FROM pengguna
         ORDER BY dibuat_pada DESC, pengguna_id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function admin_recent_recipes_db(int $limit = 5): array
{
    $stmt = db()->prepare(
        'SELECT r.resep_id, r.nama_resep, r.kategori, r.tingkat_kesulitan, r.dibuat_pada, p.nama_pengguna
         FROM recipes r
         INNER JOIN pengguna p ON p.pengguna_id = r.pengguna_id
         ORDER BY r.dibuat_pada DESC, r.resep_id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function admin_reports_db(array $filters = [], int $limit = 100): array
{
    $where = [];
    $params = [];

    $status = trim((string) ($filters['status'] ?? ''));
    $targetType = trim((string) ($filters['target_type'] ?? ''));
    $category = trim((string) ($filters['category'] ?? ''));
    $query = trim((string) ($filters['q'] ?? ''));

    if (in_array($status, ['menunggu', 'ditolak', 'selesai'], true)) {
        $where[] = 'c.status = :status';
        $params[':status'] = $status;
    }

    if (in_array($targetType, ['resep', 'pengguna'], true)) {
        $where[] = 'c.target_tipe = :target_type';
        $params[':target_type'] = $targetType;
    }

    if ($category !== '') {
        $where[] = 'c.kategori_laporan = :category';
        $params[':category'] = $category;
    }

    if ($query !== '') {
        $where[] = '(pelapor.nama_pengguna LIKE :query OR r.nama_resep LIKE :query OR target_user.nama_pengguna LIKE :query OR c.alasan LIKE :query OR c.catatan_laporan LIKE :query)';
        $params[':query'] = '%' . $query . '%';
    }

    $sql = 'SELECT
            c.ticket_id,
            c.pelapor_id,
            c.target_tipe,
            c.target_resep_id,
            c.target_pengguna_id,
            c.kategori_laporan,
            c.catatan_laporan,
            c.alasan,
            c.status,
            c.dibuat_pada,
            pelapor.nama_pengguna AS pelapor_nama,
            r.nama_resep AS target_resep_nama,
            target_user.nama_pengguna AS target_pengguna_nama
         FROM cs c
         LEFT JOIN pengguna pelapor ON pelapor.pengguna_id = c.pelapor_id
         LEFT JOIN recipes r ON r.resep_id = c.target_resep_id
         LEFT JOIN pengguna target_user ON target_user.pengguna_id = c.target_pengguna_id';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY c.dibuat_pada DESC, c.ticket_id DESC LIMIT :limit';

    $stmt = db()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function admin_users_db(array $filters = [], int $limit = 100): array
{
    $where = [];
    $params = [];

    $query = trim((string) ($filters['q'] ?? ''));
    $role = trim((string) ($filters['role'] ?? ''));
    $status = trim((string) ($filters['status'] ?? ''));

    if ($query !== '') {
        $where[] = '(p.nama_pengguna LIKE :query OR p.email LIKE :query)';
        $params[':query'] = '%' . $query . '%';
    }

    if (in_array($role, ['admin', 'pengguna'], true)) {
        $where[] = 'p.role = :role';
        $params[':role'] = $role;
    }

    if (in_array($status, ['aktif', 'nonaktif'], true)) {
        $where[] = 'p.status = :status';
        $params[':status'] = $status;
    }

    $sql = 'SELECT
            p.pengguna_id,
            p.nama_pengguna,
            p.email,
            p.role,
            p.status,
            p.dibuat_pada,
            COUNT(r.resep_id) AS recipe_count
         FROM pengguna p
         LEFT JOIN recipes r ON r.pengguna_id = p.pengguna_id';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' GROUP BY p.pengguna_id, p.nama_pengguna, p.email, p.role, p.status, p.dibuat_pada
              ORDER BY p.dibuat_pada DESC, p.pengguna_id DESC
              LIMIT :limit';

    $stmt = db()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function admin_update_user_status_db(int $userId, string $status, int $currentAdminId): bool
{
    if (!in_array($status, ['aktif', 'nonaktif'], true) || $userId <= 0 || $userId === $currentAdminId) {
        return false;
    }

    $stmt = db()->prepare('UPDATE pengguna SET status = :status WHERE pengguna_id = :id');
    $stmt->execute([
        ':status' => $status,
        ':id' => $userId,
    ]);

    return $stmt->rowCount() > 0;
}

function admin_delete_user_db(int $userId, int $currentAdminId): bool
{
    if ($userId <= 0 || $userId === $currentAdminId) {
        return false;
    }

    $stmt = db()->prepare('DELETE FROM pengguna WHERE pengguna_id = :id AND role <> "admin"');
    $stmt->execute([':id' => $userId]);

    return $stmt->rowCount() > 0;
}

function admin_recipes_db(array $filters = [], int $limit = 100): array
{
    $where = [];
    $params = [];
    $orderBy = 'ORDER BY r.dibuat_pada DESC, r.resep_id DESC';

    $query = trim((string) ($filters['q'] ?? ''));
    $category = trim((string) ($filters['category'] ?? ''));
    $difficulty = trim((string) ($filters['difficulty'] ?? ''));
    $sort = trim((string) ($filters['sort'] ?? 'newest'));

    if ($query !== '') {
        $where[] = '(r.nama_resep LIKE :query OR p.nama_pengguna LIKE :query)';
        $params[':query'] = '%' . $query . '%';
    }

    if ($category !== '') {
        $where[] = 'LOWER(r.kategori) = :category';
        $params[':category'] = mb_strtolower($category);
    }

    if (in_array($difficulty, ['mudah', 'sedang', 'sulit'], true)) {
        $where[] = 'r.tingkat_kesulitan = :difficulty';
        $params[':difficulty'] = $difficulty;
    }

    if ($sort === 'oldest') {
        $orderBy = 'ORDER BY r.dibuat_pada ASC, r.resep_id ASC';
    } elseif ($sort === 'popular') {
        $orderBy = 'ORDER BY COALESCE(l.like_count, 0) DESC, COALESCE(rt.rating_average, 0) DESC, r.dibuat_pada DESC';
    }

    $sql = 'SELECT
            r.resep_id,
            r.nama_resep,
            r.kategori,
            r.tingkat_kesulitan,
            r.waktu_memasak,
            r.dibuat_pada,
            p.nama_pengguna,
            COALESCE(l.like_count, 0) AS likes_count,
            COALESCE(k.comment_count, 0) AS comments_count,
            COALESCE(rt.rating_average, 0) AS rating_average
         FROM recipes r
         INNER JOIN pengguna p ON p.pengguna_id = r.pengguna_id
         LEFT JOIN (SELECT resep_id, COUNT(*) AS like_count FROM likes GROUP BY resep_id) l ON l.resep_id = r.resep_id
         LEFT JOIN (SELECT resep_id, COUNT(*) AS comment_count FROM komentar GROUP BY resep_id) k ON k.resep_id = r.resep_id
         LEFT JOIN (SELECT resep_id, AVG(rating_value) AS rating_average FROM ratings GROUP BY resep_id) rt ON rt.resep_id = r.resep_id';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ' . $orderBy . ' LIMIT :limit';

    $stmt = db()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function admin_recipe_categories_db(): array
{
    $stmt = db()->query(
        'SELECT DISTINCT kategori
         FROM recipes
         WHERE kategori IS NOT NULL AND kategori <> ""
         ORDER BY kategori ASC'
    );

    return array_column($stmt->fetchAll(), 'kategori');
}

function admin_delete_recipe_db(int $recipeId): bool
{
    if ($recipeId <= 0) {
        return false;
    }

    $stmt = db()->prepare('DELETE FROM recipes WHERE resep_id = :id');
    $stmt->execute([':id' => $recipeId]);

    return $stmt->rowCount() > 0;
}

function admin_update_report_status_db(int $ticketId, string $status): bool
{
    if ($ticketId <= 0 || !in_array($status, ['menunggu', 'ditolak', 'selesai'], true)) {
        return false;
    }

    $stmt = db()->prepare('UPDATE cs SET status = :status WHERE ticket_id = :id');
    $stmt->execute([
        ':status' => $status,
        ':id' => $ticketId,
    ]);

    return $stmt->rowCount() > 0;
}

function report_category_options(): array
{
    return [
        'spam' => 'Spam / promosi',
        'konten_tidak_pantas' => 'Konten tidak pantas',
        'penipuan' => 'Penipuan / scam',
        'hak_cipta' => 'Pelanggaran hak cipta',
        'pelecehan' => 'Pelecehan / ujaran kebencian',
        'lainnya' => 'Lainnya',
    ];
}

function report_category_label(string $value): string
{
    $options = report_category_options();
    return $options[$value] ?? 'Lainnya';
}

function report_create_db(int $reporterId, string $targetType, int $targetId, string $category, string $note): int
{
    if ($reporterId <= 0) {
        throw new InvalidArgumentException('Target laporan tidak valid.');
    }

    $targetType = trim($targetType);
    $category = trim($category);
    $note = trim($note);

    if (!in_array($targetType, ['resep', 'pengguna'], true)) {
        throw new InvalidArgumentException('Tipe target tidak valid.');
    }

    $isGeneralSupport = $targetType === 'pengguna' && $targetId <= 0;

    if (!$isGeneralSupport && $targetId <= 0) {
        throw new InvalidArgumentException('Target laporan tidak valid.');
    }

    $options = report_category_options();
    if (!array_key_exists($category, $options)) {
        throw new InvalidArgumentException('Kategori laporan tidak valid.');
    }

    if ($note === '') {
        throw new InvalidArgumentException('Catatan laporan wajib diisi.');
    }

    $pdo = db();

    if ($targetType === 'resep') {
        $targetStmt = $pdo->prepare('SELECT pengguna_id, nama_resep FROM recipes WHERE resep_id = :id LIMIT 1');
        $targetStmt->execute([':id' => $targetId]);
        $target = $targetStmt->fetch();

        if (!$target) {
            throw new InvalidArgumentException('Resep tidak ditemukan.');
        }

        if ((int) ($target['pengguna_id'] ?? 0) === $reporterId) {
            throw new InvalidArgumentException('Kamu tidak bisa melaporkan resep milik sendiri.');
        }
    } elseif (!$isGeneralSupport) {
        $targetStmt = $pdo->prepare('SELECT nama_pengguna FROM pengguna WHERE pengguna_id = :id LIMIT 1');
        $targetStmt->execute([':id' => $targetId]);
        $target = $targetStmt->fetch();

        if (!$target) {
            throw new InvalidArgumentException('Pengguna tidak ditemukan.');
        }

        if ($targetId === $reporterId) {
            throw new InvalidArgumentException('Kamu tidak bisa melaporkan akun sendiri.');
        }
    }

    $summary = ($isGeneralSupport ? 'Customer Support' : report_category_label($category)) . ': ' . $note;

    $stmt = $pdo->prepare(
        'INSERT INTO cs (pelapor_id, target_tipe, target_resep_id, target_pengguna_id, kategori_laporan, catatan_laporan, alasan, status)
         VALUES (:pelapor_id, :target_tipe, :target_resep_id, :target_pengguna_id, :kategori_laporan, :catatan_laporan, :alasan, "menunggu")'
    );
    $stmt->execute([
        ':pelapor_id' => $reporterId,
        ':target_tipe' => $targetType,
        ':target_resep_id' => $targetType === 'resep' ? $targetId : null,
        ':target_pengguna_id' => $targetType === 'pengguna' && $targetId > 0 ? $targetId : null,
        ':kategori_laporan' => $category,
        ':catatan_laporan' => $note,
        ':alasan' => $summary,
    ]);

    return (int) $pdo->lastInsertId();
}

function report_user_reports_db(int $userId, int $limit = 50): array
{
    $stmt = db()->prepare(
        'SELECT
            c.ticket_id,
            c.target_tipe,
            c.target_resep_id,
            c.target_pengguna_id,
            c.kategori_laporan,
            c.catatan_laporan,
            c.alasan,
            c.status,
            c.dibuat_pada,
            r.nama_resep AS target_resep_nama,
            target_user.nama_pengguna AS target_pengguna_nama
         FROM cs c
         LEFT JOIN recipes r ON r.resep_id = c.target_resep_id
         LEFT JOIN pengguna target_user ON target_user.pengguna_id = c.target_pengguna_id
         WHERE c.pelapor_id = :user_id
         ORDER BY c.dibuat_pada DESC, c.ticket_id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}
