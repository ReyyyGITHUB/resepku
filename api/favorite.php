<?php

require_once __DIR__ . '/_social.php';

$user = social_require_user();
social_require_csrf();
$recipeId = social_recipe_id();

$state = recipe_toggle_favorite_db($recipeId, (int) $user['id']);
social_json([
    'message' => 'Favorite berhasil diperbarui.',
    'data' => $state,
]);
