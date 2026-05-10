<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/recipe_repository.php';

function social_json(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function social_require_user(): array
{
    startSession();

    if (empty($_SESSION['user'])) {
        social_json(['message' => 'Silakan masuk terlebih dahulu.'], 401);
    }

    return $_SESSION['user'];
}

function social_require_csrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);

    if (!verifyCsrf($token)) {
        social_json(['message' => 'Token form tidak valid.'], 419);
    }
}

function social_recipe_id(): int
{
    $recipeId = (int) ($_POST['recipe_id'] ?? $_GET['recipe_id'] ?? 0);

    if ($recipeId <= 0) {
        social_json(['message' => 'Resep tidak valid.'], 422);
    }

    return $recipeId;
}
