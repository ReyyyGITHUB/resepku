<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/admin_repository.php';
require_once __DIR__ . '/../data/recipe_repository.php';

startSession();

$currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
if ($currentUserId <= 0) {
    redirectTo('../auth/login.php');
}

$profile = recipe_user_profile_db($currentUserId);
if ($profile === null) {
    redirectTo('../profil/');
}

$reports = report_user_reports_db($currentUserId, 50);
$selectedTicketId = (int) ($_GET['ticket_id'] ?? ($reports[0]['ticket_id'] ?? 0));
$selectedReport = null;
foreach ($reports as $report) {
    if ((int) ($report['ticket_id'] ?? 0) === $selectedTicketId) {
        $selectedReport = $report;
        break;
    }
}
if ($selectedReport === null && $reports !== []) {
    $selectedReport = $reports[0];
    $selectedTicketId = (int) ($selectedReport['ticket_id'] ?? 0);
}

$pageTitle = 'Pengaduan Saya - Resepku';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="profile-page" data-guest-mode="0" data-csrf-token="<?= e(csrfToken()) ?>">
    <aside class="home-sidebar profile-sidebar">
        <div class="home-sidebar__profile">
            <div class="home-sidebar__brand">
                <img src="../assets/img/resepku-logo.png" alt="" class="home-sidebar__logo">
                <div>
                    <p class="home-sidebar__name">Resepku</p>
                    <p class="home-sidebar__status">Signed in</p>
                </div>
            </div>

            <div class="home-sidebar__identity">
                <img src="<?= e($profile['avatar']) ?>" alt="<?= e($profile['name']) ?>" class="home-sidebar__avatar">
                <div class="home-sidebar__welcome">
                    <strong><?= e($profile['name']) ?></strong>
                    <span><?= e($profile['bio'] !== '' ? $profile['bio'] : 'Kelola laporan kamu seperti inbox pesan.') ?></span>
                </div>
            </div>

            <a class="home-sidebar__report-link" href="laporan.php" aria-label="Pengaduan Saya">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22zm8-6V11a8 8 0 1 0-16 0v5L2 18v1h20v-1l-2-2zm-2 1H6v-6a6 6 0 1 1 12 0v6z" fill="currentColor"></path>
                </svg>
                <span class="sr-only">Pengaduan Saya</span>
            </a>

            <a href="../auth/logout.php" class="home-sidebar__logout">Log Out</a>
        </div>

        <div class="home-sidebar__divider"></div>

        <p class="home-sidebar__label">Navigasi utama</p>
        <nav class="home-sidebar__nav home-sidebar__nav--primary" aria-label="Navigasi Profil">
            <a href="../home/">Home</a>
            <a href="../profil/">Profile</a>
            <a href="../resep/myresep.php">My Recipes</a>
            <a href="../resep/buat.php">Add Recipe</a>
            <a href="../resep/favorite.php">Favorite</a>
            <a class="is-active" href="laporan.php">Pengaduan Saya</a>
        </nav>

        <img src="../assets/img/chef-illustration.png" alt="" class="home-sidebar__chef">
    </aside>

    <main class="profile-main profile-report-page">
        <section class="profile-report-shell" aria-label="Laporan saya">
            <header class="profile-report-hero">
                <p class="profile-section__kicker">Inbox Pengaduan</p>
                <h1>Pengaduan Saya</h1>
                <p>Semua pengaduan tampil seperti inbox. Klik satu item untuk lihat detail dan statusnya.</p>
            </header>

            <div class="profile-report-layout">
                <section class="profile-report-listpanel" aria-label="Daftar laporan">
                    <?php if ($reports === []): ?>
                        <div class="profile-panel__empty">Belum ada pengaduan yang kamu kirim.</div>
                    <?php else: ?>
                        <div class="profile-message-list">
                            <?php foreach ($reports as $report): ?>
                                <?php
                                $isActive = (int) $report['ticket_id'] === $selectedTicketId;
                                $targetLabel = 'Target sudah dihapus';
                                if ($report['target_tipe'] === 'resep' && !empty($report['target_resep_nama'])) {
                                    $targetLabel = $report['target_resep_nama'];
                                } elseif ($report['target_tipe'] === 'pengguna' && !empty($report['target_pengguna_nama'])) {
                                    $targetLabel = $report['target_pengguna_nama'];
                                }
                                $preview = (string) ($report['catatan_laporan'] ?: $report['alasan']);
                                ?>
                                <a class="profile-message-item<?= $isActive ? ' is-active' : '' ?>" href="laporan.php?ticket_id=<?= e((string) $report['ticket_id']) ?>">
                                    <div class="profile-message-item__meta">
                                        <div class="profile-message-item__subject">
                                            <strong><?= e(report_category_label((string) $report['kategori_laporan'])) ?></strong>
                                            <span><?= e(ucfirst((string) $report['target_tipe'])) ?></span>
                                        </div>
                                        <span><?= e((string) $report['dibuat_pada']) ?></span>
                                    </div>
                                    <h2><?= e($targetLabel) ?></h2>
                                    <p><?= e($preview) ?></p>
                                    <div class="profile-message-item__footer">
                                        <span><?= e((string) $report['status']) ?></span>
                                        <strong><?= e((string) $report['ticket_id']) ?></strong>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <aside class="profile-report-detailpanel" aria-label="Detail laporan">
                    <?php if ($selectedReport === null): ?>
                        <div class="profile-panel__empty">Pilih satu laporan untuk melihat detail.</div>
                    <?php else: ?>
                        <?php
                        $targetLabel = 'Target sudah dihapus';
                        $targetUrl = null;
                        if ($selectedReport['target_tipe'] === 'resep' && !empty($selectedReport['target_resep_nama'])) {
                            $targetLabel = $selectedReport['target_resep_nama'];
                            $targetUrl = '../resep/detail.php?id=' . (int) $selectedReport['target_resep_id'];
                        } elseif ($selectedReport['target_tipe'] === 'pengguna' && !empty($selectedReport['target_pengguna_nama'])) {
                            $targetLabel = $selectedReport['target_pengguna_nama'];
                            $targetUrl = '../profil/?id=' . (int) $selectedReport['target_pengguna_id'];
                        }
                        ?>
                        <article class="profile-report-detail">
                            <div class="profile-report-detail__head">
                                <div>
                                    <p class="profile-panel__kicker">Pengaduan terpilih</p>
                                    <h2><?= e(report_category_label((string) $selectedReport['kategori_laporan'])) ?></h2>
                                    <p><?= e(ucfirst((string) $selectedReport['target_tipe'])) ?> detail</p>
                                </div>
                                <span><?= e((string) $selectedReport['status']) ?></span>
                            </div>

                            <dl class="profile-report-detail__meta">
                                <div>
                                    <dt>Target</dt>
                                    <dd>
                                        <?php if ($targetUrl !== null): ?>
                                            <a href="<?= e($targetUrl) ?>"><?= e($targetLabel) ?></a>
                                        <?php else: ?>
                                            <span><?= e($targetLabel) ?></span>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Tanggal</dt>
                                    <dd><?= e((string) $selectedReport['dibuat_pada']) ?></dd>
                                </div>
                                <div>
                                    <dt>Status</dt>
                                    <dd><?= e((string) $selectedReport['status']) ?></dd>
                                </div>
                            </dl>

                            <div class="profile-report-detail__body">
                                <p><?= e((string) ($selectedReport['catatan_laporan'] ?: $selectedReport['alasan'])) ?></p>
                            </div>
                        </article>
                    <?php endif; ?>
                </aside>
            </div>
        </section>
    </main>
</body>
</html>
