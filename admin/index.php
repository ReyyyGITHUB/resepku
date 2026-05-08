<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/admin_repository.php';
require_once __DIR__ . '/_layout.php';

$adminUser = requireAdmin();
$stats = admin_dashboard_stats_db();
$recentUsers = admin_recent_users_db(5);
$recentRecipes = admin_recent_recipes_db(5);
$recentReports = admin_reports_db([], 5);

admin_header('Dashboard', $adminUser, 'dashboard');
?>
<section class="admin-stats" aria-label="Statistik platform">
    <?php
    $cards = [
        ['label' => 'Total Pengguna', 'value' => $stats['total_users']],
        ['label' => 'Pengguna Aktif', 'value' => $stats['active_users']],
        ['label' => 'Pengguna Nonaktif', 'value' => $stats['inactive_users']],
        ['label' => 'Total Resep', 'value' => $stats['total_recipes']],
        ['label' => 'Total Komentar', 'value' => $stats['total_comments']],
        ['label' => 'Laporan Menunggu', 'value' => $stats['pending_reports']],
        ['label' => 'Total Likes', 'value' => $stats['total_likes']],
        ['label' => 'Rata-rata Rating', 'value' => number_format((float) $stats['average_rating'], 1)],
    ];
    ?>
    <?php foreach ($cards as $card): ?>
        <article class="admin-stat-card">
            <span><?= e($card['label']) ?></span>
            <strong><?= e((string) $card['value']) ?></strong>
        </article>
    <?php endforeach; ?>
</section>

<section class="admin-grid">
    <article class="admin-panel">
        <div class="admin-panel__head">
            <h2>Resep Terbaru</h2>
            <a href="resep.php">Kelola resep</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Author</th>
                        <th>Kategori</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentRecipes as $recipe): ?>
                        <tr>
                            <td><a href="../resep/detail.php?id=<?= e((string) $recipe['resep_id']) ?>"><?= e($recipe['nama_resep']) ?></a></td>
                            <td><?= e($recipe['nama_pengguna']) ?></td>
                            <td><?= e($recipe['kategori'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($recentRecipes === []): ?>
                        <tr><td colspan="3">Belum ada resep.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="admin-panel">
        <div class="admin-panel__head">
            <h2>Laporan Terbaru</h2>
            <a href="laporan.php">Kelola laporan</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Target</th>
                        <th>Pelapor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentReports as $report): ?>
                        <tr>
                            <td><?= e($report['target_tipe']) ?></td>
                            <td><?= e($report['pelapor_nama'] ?? 'Anonim') ?></td>
                            <td><?= admin_badge((string) $report['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($recentReports === []): ?>
                        <tr><td colspan="3">Belum ada laporan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="admin-panel admin-panel--wide">
        <div class="admin-panel__head">
            <h2>Pengguna Terbaru</h2>
            <a href="pengguna.php">Kelola pengguna</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td><?= e($user['nama_pengguna']) ?></td>
                            <td><?= e($user['email']) ?></td>
                            <td><?= admin_badge((string) $user['role']) ?></td>
                            <td><?= admin_badge((string) $user['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($recentUsers === []): ?>
                        <tr><td colspan="4">Belum ada pengguna.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php admin_footer(); ?>

