<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/subscriptions.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$followingId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$redirect = $_POST['redirect'] ?? profileUrl($followingId);

if ($followingId <= 0) {
    header('Location: index.php');
    exit;
}

$result = toggleSubscription((int) $user['id'], $followingId);

$separator = str_contains($redirect, '?') ? '&' : '?';
$query = $result['ok']
    ? ($result['subscribed'] ? 'subscribed=1' : 'unsubscribed=1')
    : 'subscribe_error=' . urlencode($result['error']);

header('Location: ' . $redirect . $separator . $query);
exit;
