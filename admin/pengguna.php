<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/admin_repository.php';
require_once __DIR__ . '/_layout.php';

$adminUser = requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        admin_flash('error', 'Sesi form tidak valid.');
        redirectTo('pengguna.php');
    }

    $action = (string) ($_POST['action'] ?? '');
    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($action === 'status') {
        $status = (string) ($_POST['status'] ?? '');
        $ok = admin_update_user_status_db($userId, $status, (int) $adminUser['id']);
        admin_flash($ok ? 'success' : 'error', $ok ? 'Status pengguna diperbarui.' : 'Status pengguna gagal diperbarui.');
    } elseif ($action === 'delete') {
        $ok = admin_delete_user_db($userId, (int) $adminUser['id']);
        admin_flash($ok ? 'success' : 'error', $ok ? 'Pengguna berhasil dihapus.' : 'Pengguna gagal dihapus atau akun admin tidak boleh dihapus.');
    }

    redirectTo('pengguna.php');
}

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'role' => trim((string) ($_GET['role'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];
$users = admin_users_db($filters);

admin_header('Kelola Pengguna', $adminUser, 'pengguna');
?>
<section class="admin-panel">
    <form class="admin-filters" method="get">
        <label>
            <span>Cari</span>
            <input type="search" name="q" placeholder="Nama atau email" value="<?= e($filters['q']) ?>">
        </label>
        <label>
            <span>Role</span>
            <select name="role">
                <option value="">Semua role</option>
                <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="pengguna" <?= $filters['role'] === 'pengguna' ? 'selected' : '' ?>>Pengguna</option>
            </select>
        </label>
        <label>
            <span>Status</span>
            <select name="status">
                <option value="">Semua status</option>
                <option value="aktif" <?= $filters['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= $filters['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </label>
        <button type="submit">Terapkan</button>
        <a href="pengguna.php">Reset</a>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Resep</th>
                    <th>Tanggal Daftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php
                    $isSelf = (int) $user['pengguna_id'] === (int) $adminUser['id'];
                    $isUserAdmin = $user['role'] === 'admin';
                    ?>
                    <tr>
                        <td><?= e($user['nama_pengguna']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= admin_badge((string) $user['role']) ?></td>
                        <td><?= admin_badge((string) $user['status']) ?></td>
                        <td><?= e((string) $user['recipe_count']) ?></td>
                        <td><?= e((string) $user['dibuat_pada']) ?></td>
                        <td>
                            <div class="admin-actions">
                                <a href="../profil/?id=<?= e((string) $user['pengguna_id']) ?>">Profil</a>
                                <?php if (!$isSelf): ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="user_id" value="<?= e((string) $user['pengguna_id']) ?>">
                                        <input type="hidden" name="status" value="<?= $user['status'] === 'aktif' ? 'nonaktif' : 'aktif' ?>">
                                        <button type="submit"><?= $user['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if (!$isUserAdmin): ?>
                                    <form method="post" onsubmit="return confirm('Hapus pengguna ini?')">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= e((string) $user['pengguna_id']) ?>">
                                        <button class="admin-danger" type="submit">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($users === []): ?>
                    <tr><td colspan="7">Pengguna tidak ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_footer(); ?>
