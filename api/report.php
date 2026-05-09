<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../data/admin_repository.php';
require_once __DIR__ . '/_social.php';

social_require_user();
social_require_csrf();

$user = currentUser();
$reporterId = (int) ($user['id'] ?? 0);
$targetType = trim((string) ($_POST['target_type'] ?? ''));
$targetId = (int) ($_POST['target_id'] ?? 0);
$category = trim((string) ($_POST['category'] ?? ''));
$note = trim((string) ($_POST['note'] ?? ''));

try {
    $ticketId = report_create_db($reporterId, $targetType, $targetId, $category, $note);
    social_json([
        'message' => 'Laporan berhasil dikirim.',
        'data' => [
            'ticket_id' => $ticketId,
            'status' => 'menunggu',
        ],
    ]);
} catch (InvalidArgumentException $exception) {
    social_json(['message' => $exception->getMessage()], 422);
} catch (Throwable $throwable) {
    social_json(['message' => 'Gagal mengirim laporan.'], 500);
}
