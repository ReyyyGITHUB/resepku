<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/admin_repository.php';
require_once __DIR__ . '/_layout.php';

$adminUser = requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        admin_flash('error', 'Sesi form tidak valid.');
        redirectTo('laporan.php');
    }

    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');
    $ok = admin_update_report_status_db($ticketId, $status);
    admin_flash($ok ? 'success' : 'error', $ok ? 'Status laporan diperbarui.' : 'Status laporan gagal diperbarui.');
    redirectTo('laporan.php');
}

$filters = [
    'status' => trim((string) ($_GET['status'] ?? '')),
    'target_type' => trim((string) ($_GET['target_type'] ?? '')),
    'category' => trim((string) ($_GET['category'] ?? '')),
    'q' => trim((string) ($_GET['q'] ?? '')),
];
$reports = admin_reports_db($filters);
$reportCategories = report_category_options();

admin_header('Kelola Laporan', $adminUser, 'laporan');
?>
<section class="admin-panel">
    <form class="admin-filters" method="get">
        <label>
            <span>Status</span>
            <select name="status">
                <option value="">Semua status</option>
                <option value="menunggu" <?= $filters['status'] === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                <option value="selesai" <?= $filters['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                <option value="ditolak" <?= $filters['status'] === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
            </select>
        </label>
        <label>
            <span>Target</span>
            <select name="target_type">
                <option value="">Semua target</option>
                <option value="resep" <?= $filters['target_type'] === 'resep' ? 'selected' : '' ?>>Resep</option>
                <option value="pengguna" <?= $filters['target_type'] === 'pengguna' ? 'selected' : '' ?>>Pengguna</option>
            </select>
        </label>
        <label>
            <span>Kategori</span>
            <select name="category">
                <option value="">Semua kategori</option>
                <?php foreach ($reportCategories as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $filters['category'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Cari</span>
            <input type="search" name="q" placeholder="Pelapor, target, catatan" value="<?= e($filters['q']) ?>">
        </label>
        <button type="submit">Terapkan</button>
        <a href="laporan.php">Reset</a>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Pelapor</th>
                    <th>Target</th>
                    <th>Kategori</th>
                    <th>Alasan</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <?php
                    $targetLabel = 'Target sudah dihapus';
                    $targetUrl = null;
                    if ($report['target_tipe'] === 'resep' && !empty($report['target_resep_nama'])) {
                        $targetLabel = $report['target_resep_nama'];
                        $targetUrl = '../resep/detail.php?id=' . (int) $report['target_resep_id'];
                    } elseif ($report['target_tipe'] === 'pengguna' && !empty($report['target_pengguna_nama'])) {
                        $targetLabel = $report['target_pengguna_nama'];
                        $targetUrl = '../profil/?id=' . (int) $report['target_pengguna_id'];
                    }
                    ?>
                    <tr>
                        <td><?= e($report['pelapor_nama'] ?? 'Anonim') ?></td>
                        <td>
                            <div>
                                <strong><?= e($report['target_tipe']) ?></strong><br>
                                <?php if ($targetUrl !== null): ?>
                                    <a href="<?= e($targetUrl) ?>"><?= e($targetLabel) ?></a>
                                <?php else: ?>
                                    <span><?= e($targetLabel) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= e(report_category_label((string) ($report['kategori_laporan'] ?? 'lainnya'))) ?></td>
                        <td><?= e((string) ($report['catatan_laporan'] ?: $report['alasan'])) ?></td>
                        <td><?= admin_badge((string) $report['status']) ?></td>
                        <td><?= e((string) $report['dibuat_pada']) ?></td>
                        <td>
                            <div class="admin-actions">
                                <?php if ($report['status'] !== 'selesai'): ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="ticket_id" value="<?= e((string) $report['ticket_id']) ?>">
                                        <input type="hidden" name="status" value="selesai">
                                        <button type="submit">Selesai</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($report['status'] !== 'ditolak'): ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="ticket_id" value="<?= e((string) $report['ticket_id']) ?>">
                                        <input type="hidden" name="status" value="ditolak">
                                        <button class="admin-danger" type="submit">Tolak</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($reports === []): ?>
                    <tr><td colspan="7">Laporan tidak ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_footer(); ?>
