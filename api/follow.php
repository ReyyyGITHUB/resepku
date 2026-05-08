<?php

require_once __DIR__ . '/_social.php';

$user = social_require_user();
social_require_csrf();

$targetUserId = (int) ($_POST['user_id'] ?? $_GET['user_id'] ?? 0);

if ($targetUserId <= 0) {
    social_json(['message' => 'User tidak valid.'], 422);
}

try {
    $state = recipe_toggle_follow_db($targetUserId, (int) $user['id']);
    social_json([
        'message' => $state['following'] ? 'Berhasil mengikuti pengguna.' : 'Berhasil berhenti mengikuti pengguna.',
        'data' => $state,
    ]);
} catch (InvalidArgumentException $exception) {
    social_json(['message' => $exception->getMessage()], 422);
}
