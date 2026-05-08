<?php

require_once __DIR__ . '/_social.php';

$user = social_require_user();
social_require_csrf();
$recipeId = social_recipe_id();

$ratingValue = (float) ($_POST['rating_value'] ?? 0);

if ($ratingValue < 1 || $ratingValue > 5) {
    social_json(['message' => 'Rating harus antara 1 sampai 5.'], 422);
}

$state = recipe_set_rating_db($recipeId, (int) $user['id'], $ratingValue);
social_json([
    'message' => 'Rating berhasil disimpan.',
    'data' => $state,
]);
