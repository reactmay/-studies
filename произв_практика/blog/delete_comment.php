<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/hidden_posts.php';
require_once __DIR__ . '/includes/comments.php';

$user = requireAuth();

$commentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$postId = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
$accessToken = isset($_GET['token']) ? trim((string) $_GET['token']) : null;

if ($commentId <= 0 || $postId <= 0) {
    header('Location: index.php');
    exit;
}

$post = getPostWithAuthorById($postId);

if ($post === null) {
    header('Location: index.php');
    exit;
}

deleteComment($commentId, (int) $user['id'], (int) $post['user_id']);

header('Location: ' . postCommentUrl($postId, $accessToken) . '#comments');
exit;
