<?php

function admin_flash(?string $type = null, ?string $message = null): ?array
{
    startSession();

    if ($type !== null && $message !== null) {
        $_SESSION['admin_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
        return null;
    }

    $flash = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);

    return is_array($flash) ? $flash : null;
}

function admin_header(string $title, array $adminUser, string $active): void
{
    $items = [
        'dashboard' => ['label' => 'Dashboard', 'href' => 'index.php'],
        'pengguna' => ['label' => 'Pengguna', 'href' => 'pengguna.php'],
        'resep' => ['label' => 'Resep', 'href' => 'resep.php'],
        'laporan' => ['label' => 'Pengaduan', 'href' => 'laporan.php'],
    ];
    $flash = admin_flash();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - Admin ResepKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-page">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <img src="../assets/img/resepku-logo.png" alt="" class="admin-brand__logo">
            <div>
                <strong>ResepKu</strong>
                <span>Admin Panel</span>
            </div>
        </div>

        <nav class="admin-nav" aria-label="Navigasi admin">
            <?php foreach ($items as $key => $item): ?>
                <a class="<?= $active === $key ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar__footer">
            <a href="../home/">Kembali ke Home</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <p>Admin ResepKu</p>
                <h1><?= e($title) ?></h1>
            </div>
            <div class="admin-user">
                <span><?= e($adminUser['name'] ?? 'Admin') ?></span>
                <strong><?= e($adminUser['role'] ?? 'admin') ?></strong>
            </div>
        </header>

        <?php if ($flash !== null): ?>
            <div class="admin-alert admin-alert--<?= e((string) ($flash['type'] ?? 'success')) ?>" role="status">
                <?= e((string) ($flash['message'] ?? '')) ?>
            </div>
        <?php endif; ?>
    <?php
}

function admin_footer(): void
{
    ?>
    </main>
</body>
</html>
    <?php
}

function admin_badge(string $value): string
{
    $class = preg_replace('/[^a-z0-9_-]/i', '-', strtolower($value));

    return '<span class="admin-badge admin-badge--' . e($class) . '">' . e($value) . '</span>';
}
