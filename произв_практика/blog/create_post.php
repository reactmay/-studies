<?php

declare(strict_types=1);

$pageTitle = 'Новый пост';
require_once __DIR__ . '/includes/header.php';

$user = requireAuth();

$error = '';
$title = '';
$content = '';
$visibility = POST_VISIBILITY_PUBLIC;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $visibility = normalizePostVisibility($_POST['visibility'] ?? POST_VISIBILITY_PUBLIC);

    $result = createPost((int) $user['id'], $title, $content, $visibility);

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

        <div class="form-group">
            <label for="visibility">Видимость</label>
            <select id="visibility" name="visibility">
                <option value="<?= e(POST_VISIBILITY_PUBLIC) ?>" <?= $visibility === POST_VISIBILITY_PUBLIC ? 'selected' : '' ?>>
                    Публичный — виден всем
                </option>
                <option value="<?= e(POST_VISIBILITY_ON_REQUEST) ?>" <?= $visibility === POST_VISIBILITY_ON_REQUEST ? 'selected' : '' ?>>
                    Только по запросу — скрыт, доступ по ссылке с кодом
                </option>
            </select>
            <p class="form-hint">Скрытые посты не отображаются в общем списке. Ссылку с кодом вы получите после публикации.</p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Опубликовать</button>
            <a href="dashboard.php" class="btn btn-outline">Отмена</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
