<?php

require_once __DIR__ . '/_social.php';

$user = social_require_user();
social_require_csrf();
$recipeId = social_recipe_id();
$content = trim((string) ($_POST['content'] ?? ''));

if ($content === '') {
    social_json(['message' => 'Komentar tidak boleh kosong.'], 422);
}

try {
    $comment = recipe_add_comment_db($recipeId, (int) $user['id'], $content);
    $comments = recipe_comments_db($recipeId);

    social_json([
        'message' => 'Komentar berhasil ditambahkan.',
        'data' => [
            'comment' => $comment,
            'comments' => $comments,
            'comments_count' => count($comments),
        ],
    ]);
} catch (InvalidArgumentException $exception) {
    social_json(['message' => $exception->getMessage()], 422);
} catch (Throwable $throwable) {
    social_json(['message' => 'Gagal menyimpan komentar.'], 500);
}
