<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/hidden_posts.php';
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/tags.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/subscriptions.php';
require_once __DIR__ . '/includes/public_posts.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$accessToken = isset($_GET['token']) ? trim((string) $_GET['token']) : null;
$currentUser = currentUser();

$view = viewPostOnRequest($id, $currentUser, $accessToken);

if (!$view['found']) {
    http_response_code(404);
    $pageTitle = 'Пост не найден';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="card empty-state">
        <h1>Пост не найден</h1>
        <p>Публикация недоступна или была удалена.</p>
        <p><a href="index.php">Вернуться к списку постов</a></p>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$post = enrichPublicPostItem($view['post'], false);
$updated = isset($_GET['updated']);
$tokenInvalid = !$view['accessible'] && $accessToken !== null && $accessToken !== '';

$pageTitle = $view['accessible'] ? $post['title'] : 'Скрытый пост';

require_once __DIR__ . '/includes/header.php';

if (!$view['accessible']) {
    renderHiddenPostRequestForm($id, $tokenInvalid);
} else {
    renderPublicPostView($post, $currentUser, $updated);
}
?>

<p><a href="index.php">← Все публичные посты</a></p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
