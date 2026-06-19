<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$result = uploadUserAvatar((int) $user['id'], $_FILES['avatar'] ?? []);

if ($result['ok']) {
    header('Location: dashboard.php?avatar=1');
    exit;
}

header('Location: dashboard.php?avatar_error=' . urlencode($result['error']));
exit;
