<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/subscriptions.php';
require_once __DIR__ . '/includes/public_posts.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$post = $id > 0 ? getPublicPostById($id) : null;

if ($post === null) {
    http_response_code(404);
    $pageTitle = 'Пост не найден';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="card empty-state">
        <h1>Пост не найден</h1>
        <p>Публичная публикация недоступна или была удалена.</p>
        <p><a href="index.php">Вернуться к списку постов</a></p>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $post['title'];
$currentUser = currentUser();
$updated = isset($_GET['updated']);

require_once __DIR__ . '/includes/header.php';

renderPublicPostView($post, $currentUser, $updated);
?>

<p><a href="index.php">← Все публичные посты</a></p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
