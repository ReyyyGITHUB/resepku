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
