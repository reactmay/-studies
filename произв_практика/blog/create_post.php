<?php

declare(strict_types=1);

$pageTitle = 'Новый пост';
$pageStyles = [
    'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css',
    'assets/css/post-editor.css',
];
$pageScripts = [
    'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js',
    'assets/js/post-editor.js',
];

require_once __DIR__ . '/includes/header.php';

$user = requireAuth();

$error = '';
$title = '';
$content = '';
$visibility = POST_VISIBILITY_PUBLIC;
$tagsInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $visibility = normalizePostVisibility($_POST['visibility'] ?? POST_VISIBILITY_PUBLIC);
    $tagsInput = $_POST['tags'] ?? '';

    $result = createPost((int) $user['id'], $title, $content, $visibility, $tagsInput, $_POST['editor_mode'] ?? 'visual');

    if ($result['ok']) {
        header('Location: dashboard.php?created=1');
        exit;
    }

    $error = $result['error'];
}

$submitLabel = 'Опубликовать';
$cancelHref = 'dashboard.php';
?>

<div class="card">
    <h1 class="page-title">Создание поста</h1>
    <p class="page-subtitle">Визуальный редактор, BB-коды и блоки кода</p>

    <?php require __DIR__ . '/includes/partials/post-form.php'; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
