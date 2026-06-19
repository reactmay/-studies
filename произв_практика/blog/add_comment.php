<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/hidden_posts.php';
require_once __DIR__ . '/includes/comments.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
$accessToken = isset($_POST['token']) ? trim((string) $_POST['token']) : null;

if ($postId <= 0) {
    header('Location: index.php');
    exit;
}

$post = getPostWithAuthorById($postId);

if ($post === null || !isPostAccessible($post, $user, $accessToken)) {
    header('Location: index.php');
    exit;
}

$content = $_POST['content'] ?? '';
$result = createComment($postId, (int) $user['id'], $content);

$redirect = postCommentUrl($postId, $accessToken);

if ($result['ok']) {
    header('Location: ' . $redirect . '&commented=1#comments');
    exit;
}

header('Location: ' . $redirect . '&comment_error=' . urlencode($result['error']) . '#comments');
exit;
