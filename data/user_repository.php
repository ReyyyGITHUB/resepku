<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

function user_find_by_email_db(string $email): ?array
{
    $stmt = db()->prepare(
        'SELECT pengguna_id, nama_pengguna, email, kata_sandi, status FROM pengguna WHERE email = :email LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    return [
        'id' => (int) $row['pengguna_id'],
        'name' => (string) $row['nama_pengguna'],
        'email' => (string) $row['email'],
        'password' => (string) $row['kata_sandi'],
        'status' => (string) $row['status'],
    ];
}

function user_find_by_reset_token_db(string $token): ?array
{
    $tokenHash = hash('sha256', $token);

    $stmt = db()->prepare(
        'SELECT pr.password_reset_id, pr.pengguna_id, pr.email, pr.token_hash, pr.expires_at, pr.used_at, p.nama_pengguna, p.email AS user_email, p.status
         FROM password_resets pr
         INNER JOIN pengguna p ON p.pengguna_id = pr.pengguna_id
         WHERE pr.token_hash = :token_hash
         LIMIT 1'
    );
    $stmt->execute([':token_hash' => $tokenHash]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    $expiresAt = strtotime((string) $row['expires_at']);
    $isExpired = $expiresAt !== false && $expiresAt < time();

    if ($row['used_at'] !== null || $isExpired || ($row['status'] ?? '') !== 'aktif') {
        return null;
    }

    return [
        'reset_id' => (int) $row['password_reset_id'],
        'user_id' => (int) $row['pengguna_id'],
        'email' => (string) $row['email'],
        'token_hash' => (string) $row['token_hash'],
        'expires_at' => (string) $row['expires_at'],
        'name' => (string) $row['nama_pengguna'],
        'user_email' => (string) $row['user_email'],
    ];
}

function user_create_password_reset_db(int $userId, string $email): array
{
    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    $stmt = db()->prepare(
        'INSERT INTO password_resets (pengguna_id, email, token_hash, expires_at)
         VALUES (:user_id, :email, :token_hash, :expires_at)'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':email' => $email,
        ':token_hash' => $tokenHash,
        ':expires_at' => $expiresAt,
    ]);

    return [
        'token' => $rawToken,
        'expires_at' => $expiresAt,
    ];
}

function user_mark_password_reset_used_db(int $resetId): bool
{
    $stmt = db()->prepare(
        'UPDATE password_resets SET used_at = NOW() WHERE password_reset_id = :reset_id AND used_at IS NULL'
    );
    $stmt->execute([':reset_id' => $resetId]);

    return $stmt->rowCount() > 0;
}

function user_update_password_db(int $userId, string $newPassword): bool
{
    $stmt = db()->prepare(
        'UPDATE pengguna SET kata_sandi = :password WHERE pengguna_id = :user_id'
    );
    $stmt->execute([
        ':password' => $newPassword,
        ':user_id' => $userId,
    ]);

    return true;
}

function user_reset_password_db(int $resetId, int $userId, string $newPassword): bool
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $updateStmt = $pdo->prepare(
            'UPDATE pengguna SET kata_sandi = :password WHERE pengguna_id = :user_id'
        );
        $updateStmt->execute([
            ':password' => $newPassword,
            ':user_id' => $userId,
        ]);

        $resetStmt = $pdo->prepare(
            'UPDATE password_resets SET used_at = NOW() WHERE password_reset_id = :reset_id AND used_at IS NULL'
        );
        $resetStmt->execute([':reset_id' => $resetId]);

        if ($resetStmt->rowCount() <= 0) {
            throw new RuntimeException('Token atur ulang tidak dapat dipakai.');
        }

        $pdo->commit();
        return true;
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        throw $throwable;
    }
}
