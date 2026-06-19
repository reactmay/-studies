<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/hidden_posts.php';
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/functions.php';

$user = requireAuth();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

deletePost($id, (int) $user['id']);

header('Location: dashboard.php');
exit;
