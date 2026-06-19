<?php

declare(strict_types=1);

$pageTitle = 'Публичные посты';
require_once __DIR__ . '/includes/header.php';

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$authorId = isset($_GET['author']) ? (int) $_GET['author'] : null;
$search = isset($_GET['q']) ? trim((string) $_GET['q']) : null;

if ($authorId !== null && $authorId <= 0) {
    $authorId = null;
}

$publicPosts = generatePublicPostsList($page, 10, $authorId, $search);
$meta = $publicPosts['meta'];
$posts = $publicPosts['items'];

$filterAuthor = $authorId !== null ? getUserById($authorId) : null;
?>

<h1 class="page-title">Публичные посты</h1>
<p class="page-subtitle">
    Все публикации доступны для просмотра без авторизации
    · обновлено <?= e(formatDate($meta['generated_at'])) ?>
</p>

<form class="card public-search-form" method="get" action="index.php">
    <?php if ($authorId !== null): ?>
        <input type="hidden" name="author" value="<?= (int) $authorId ?>">
    <?php endif; ?>
    <div class="form-group">
        <label for="q">Поиск по заголовку и тексту</label>
        <input type="search" id="q" name="q" value="<?= e($meta['search'] ?? '') ?>" placeholder="Введите запрос...">
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Найти</button>
        <?php if (($meta['search'] ?? '') !== '' || $authorId !== null): ?>
            <a href="index.php" class="btn btn-outline">Сбросить</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($filterAuthor !== null): ?>
    <div class="feed-filter-banner card">
        <p>Публичные посты пользователя <strong><?= e($filterAuthor['username']) ?></strong></p>
        <a href="index.php" class="btn btn-outline">Показать всех</a>
    </div>
<?php elseif ($filterAuthor === null && $authorId !== null): ?>
    <div class="alert alert-error">Автор не найден.</div>
<?php endif; ?>

<?php if ($posts === []): ?>
    <div class="card empty-state">
        <?php if (($meta['search'] ?? '') !== ''): ?>
            <p>По запросу «<?= e($meta['search']) ?>» ничего не найдено.</p>
        <?php else: ?>
            <p>Пока нет ни одного публичного поста.</p>
        <?php endif; ?>
        <?php if ($user): ?>
            <p><a href="create_post.php" class="btn btn-primary">Создать первый пост</a></p>
        <?php else: ?>
            <p><a href="register.php">Зарегистрируйтесь</a>, чтобы начать публиковать.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="feed-summary card-meta">
        Найдено <?= (int) $meta['total_items'] ?>
        <?= (int) $meta['total_items'] === 1 ? 'публикация' : 'публикаций' ?>
    </p>

    <?php foreach ($posts as $post): ?>
        <?php renderPostCard($post); ?>
    <?php endforeach; ?>

    <?php renderPublicPostsPagination($meta); ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
