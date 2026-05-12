<?php

function startSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function csrfToken(): string
{
    startSession();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrf(?string $token): bool
{
    startSession();

    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function ratingStars($rating, int $max = 5): string
{
    $active = max(0, min($max, (int) round((float) $rating)));

    return trim(str_repeat('★ ', $active) . str_repeat('☆ ', $max - $active));
}

function ratingStarsHtml($rating, string $className = 'rating-stars', int $max = 5): string
{
    $active = max(0, min($max, (int) round((float) $rating)));
    $label = e(number_format((float) $rating, 1) . ' dari ' . $max);
    $stars = '';

    for ($i = 1; $i <= $max; $i++) {
        $state = $i <= $active ? 'is-active' : 'is-disabled';
        $stars .= '<span class="rating-stars__star ' . $state . '" aria-hidden="true"></span>';
    }

    return '<span class="' . e($className) . ' rating-stars" aria-label="Rating ' . $label . '">' . $stars . '</span>';
}

function sidebarIconSvg(string $name): string
{
    $icons = [
        'home' => '<path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'user' => '<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4.5 21a7.5 7.5 0 0 1 15 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'book' => '<path d="M5 4.5h10a4 4 0 0 1 4 4V20H8a3 3 0 0 1-3-3V4.5Zm3 0V17a3 3 0 0 0 3 3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 8h5M10 11h5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'plus' => '<path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.8"/>',
        'bookmark' => '<path d="M7 4.5h10a1 1 0 0 1 1 1V21l-6-3-6 3V5.5a1 1 0 0 1 1-1Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'bell' => '<path d="M18 10a6 6 0 1 0-12 0c0 7-2 7-2 8h16c0-1-2-1-2-8ZM10 21h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'search' => '<circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'admin' => '<path d="M5 5h14v14H5V5Zm4 4h6M9 13h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'logout' => '<path d="M10 6H6a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h4M14 8l4 4-4 4M18 12H9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'login' => '<path d="M14 6h4a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1h-4M10 8l4 4-4 4M14 12H5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'chevron' => '<path d="m15 6-6 6 6 6M20 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . ($icons[$name] ?? $icons['book']) . '</svg>';
}

function sidebarToggleButton(): string
{
    return '<button class="home-sidebar__toggle" type="button" data-sidebar-toggle aria-expanded="true" aria-label="Tutup sidebar">' . sidebarIconSvg('chevron') . '</button>';
}

function sidebarInitialStateScript(): string
{
    return '<script>(function(){try{if(window.localStorage.getItem("resepku.sidebarCollapsed")==="1"){document.documentElement.classList.add("sidebar-collapsed");}}catch(error){}})();</script>';
}

function sidebarLink(string $href, string $label, string $icon, string $className = '', bool $active = false): string
{
    $classes = trim($className . ($active ? ' is-active' : ''));

    return '<a class="' . e($classes) . '" href="' . e($href) . '" title="' . e($label) . '" aria-label="' . e($label) . '">' .
        '<span class="home-sidebar__nav-icon">' . sidebarIconSvg($icon) . '</span>' .
        '<span class="home-sidebar__nav-text">' . e($label) . '</span>' .
        '</a>';
}

function sidebarSearchForm(string $action, string $value = ''): string
{
    return '<form class="home-sidebar__search" method="get" action="' . e($action) . '" role="search">' .
        '<label>' .
        '<span class="home-sidebar__nav-icon">' . sidebarIconSvg('search') . '</span>' .
        '<input type="search" name="q" value="' . e($value) . '" placeholder="Cari resep" aria-label="Cari resep">' .
        '</label>' .
        '</form>';
}

function redirectTo(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function currentUser(): ?array
{
    startSession();

    return isset($_SESSION['user']) && is_array($_SESSION['user'])
        ? $_SESSION['user']
        : null;
}

function isAdmin(): bool
{
    $user = currentUser();

    return ($user['role'] ?? '') === 'admin';
}

function requireLogin(string $loginPath = '../auth/login.php'): array
{
    $user = currentUser();

    if ($user === null) {
        redirectTo($loginPath);
    }

    return $user;
}

function requireAdmin(string $loginPath = '../auth/login.php', string $fallbackPath = '../home/'): array
{
    $user = requireLogin($loginPath);

    if (($user['role'] ?? '') !== 'admin') {
        redirectTo($fallbackPath);
    }

    return $user;
}

function reportInboxHref(string $profilePath, string $loginPath = '../auth/login.php'): string
{
    return currentUser() !== null ? $profilePath : $loginPath;
}

function appUrl(string $path = ''): string
{
    $baseUrl = rtrim((string) env('APP_URL', ''), '/');
    $path = ltrim($path, '/');

    if ($baseUrl === '') {
        return '/' . $path;
    }

    return $path === '' ? $baseUrl : $baseUrl . '/' . $path;
}
