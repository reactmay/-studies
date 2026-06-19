<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
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
$error = '';
$title = $post['title'];
$content = $post['content'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    $result = updatePost($id, (int) $user['id'], $title, $content);

    if ($result['ok']) {
        header('Location: post.php?id=' . $id . '&updated=1');
        exit;
    }

    $error = $result['error'];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h1 class="page-title">Редактирование поста</h1>
    <p class="page-subtitle">Измените заголовок или текст публикации</p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" data-validate novalidate>
        <div class="form-group">
            <label for="title">Заголовок</label>
            <input type="text" id="title" name="title" value="<?= e($title) ?>" required data-validate-field="title">
            <div class="field-error"></div>
        </div>

        <div class="form-group">
            <label for="content">Текст поста</label>
            <textarea id="content" name="content" required data-validate-field="content"><?= e($content) ?></textarea>
            <div class="field-error"></div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a href="post.php?id=<?= (int) $post['id'] ?>" class="btn btn-outline">Отмена</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
