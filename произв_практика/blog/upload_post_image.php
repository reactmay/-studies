<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/content.php';

header('Content-Type: application/json; charset=utf-8');

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Метод не поддерживается.']);
    exit;
}

$result = uploadPostImage((int) $user['id'], $_FILES['image'] ?? []);

if (!$result['ok']) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $result['error']]);
    exit;
}

echo json_encode([
    'ok' => true,
    'url' => $result['url'],
]);
