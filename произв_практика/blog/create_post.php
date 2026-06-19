<?php

declare(strict_types=1);

$pageTitle = 'Новый пост';
require_once __DIR__ . '/includes/header.php';

$user = requireAuth();

$error = '';
$title = '';
$content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    $result = createPost((int) $user['id'], $title, $content);

    if ($result['ok']) {
        header('Location: dashboard.php?created=1');
        exit;
    }

    $error = $result['error'];
}
?>

<div class="card">
    <h1 class="page-title">Создание поста</h1>
    <p class="page-subtitle">Поделитесь своими мыслями с другими пользователями</p>

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
            <button type="submit" class="btn btn-primary">Опубликовать</button>
            <a href="dashboard.php" class="btn btn-outline">Отмена</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
