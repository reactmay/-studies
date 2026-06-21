<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/hidden_posts.php';
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/tags.php';
require_once __DIR__ . '/includes/functions.php';

$user = requireAuth();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$post = $id > 0 ? getUserPostById($id, (int) $user['id']) : null;

if ($post === null) {
    http_response_code(404);
    $pageTitle = 'Пост не найден';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="card empty-state">
        <h1>Пост не найден</h1>
        <p>Нельзя редактировать этот пост.</p>
        <p><a href="dashboard.php">Вернуться в кабинет</a></p>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = 'Редактирование поста';
$pageStyles = [
    'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css',
    'assets/css/post-editor.css',
];
$pageScripts = [
    'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js',
    'assets/js/post-editor.js',
];

$error = '';
$title = $post['title'];
$content = $post['content'];
$visibility = $post['visibility'] ?? POST_VISIBILITY_PUBLIC;
$tagsInput = tagsInputFromPost($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $visibility = normalizePostVisibility($_POST['visibility'] ?? POST_VISIBILITY_PUBLIC);
    $tagsInput = $_POST['tags'] ?? '';

    $result = updatePost($id, (int) $user['id'], $title, $content, $visibility, $tagsInput, $_POST['editor_mode'] ?? 'visual');

    if ($result['ok']) {
        header('Location: post.php?id=' . $id . '&updated=1');
        exit;
    }

    $error = $result['error'];
    $post = getUserPostById($id, (int) $user['id']) ?? $post;
}

require_once __DIR__ . '/includes/header.php';

$submitLabel = 'Сохранить';
$cancelHref = 'post.php?id=' . (int) $post['id'];
$hideVisibilityHint = true;
$showAccessLink = isPostOnRequest($post);
$accessLinkPost = $post;
?>

<div class="card">
    <h1 class="page-title">Редактирование поста</h1>
    <p class="page-subtitle">Измените текст, изображения или видимость публикации</p>

    <?php require __DIR__ . '/includes/partials/post-form.php'; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
