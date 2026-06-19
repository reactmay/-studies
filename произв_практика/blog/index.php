<?php

declare(strict_types=1);

$pageTitle = 'Публичные посты';
require_once __DIR__ . '/includes/header.php';

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$authorId = isset($_GET['author']) ? (int) $_GET['author'] : null;
$search = isset($_GET['q']) ? trim((string) $_GET['q']) : null;
$tagSlug = isset($_GET['tag']) ? trim((string) $_GET['tag']) : null;
$sort = normalizePostSort($_GET['sort'] ?? POST_SORT_NEWEST);

if ($authorId !== null && $authorId <= 0) {
    $authorId = null;
}

if ($tagSlug === '') {
    $tagSlug = null;
}

$publicPosts = generatePublicPostsList($page, 10, $authorId, $search, $tagSlug, $sort);
$meta = $publicPosts['meta'];
$posts = $publicPosts['items'];
$popularTags = $publicPosts['tags'];

$filterAuthor = $authorId !== null ? getUserById($authorId) : null;
$hasFilters = ($meta['search'] ?? '') !== '' || $authorId !== null || ($meta['tag_slug'] ?? null) !== null;
?>

<h1 class="page-title">Публичные посты</h1>
<p class="page-subtitle">
    Сортировка и фильтрация по тегам
    · обновлено <?= e(formatDate($meta['generated_at'])) ?>
</p>

<div class="feed-layout">
    <?php renderTagFilterPanel($popularTags, $meta); ?>

    <section class="feed-content">
        <form class="card public-search-form" method="get" action="index.php">
            <?php if ($authorId !== null): ?>
                <input type="hidden" name="author" value="<?= (int) $authorId ?>">
            <?php endif; ?>
            <?php if ($tagSlug !== null): ?>
                <input type="hidden" name="tag" value="<?= e($tagSlug) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="q">Поиск по заголовку и тексту</label>
                <input type="search" id="q" name="q" value="<?= e($meta['search'] ?? '') ?>" placeholder="Введите запрос...">
            </div>

            <div class="form-group">
                <label for="sort">Сортировка</label>
                <select id="sort" name="sort">
                    <option value="<?= e(POST_SORT_NEWEST) ?>" <?= $sort === POST_SORT_NEWEST ? 'selected' : '' ?>>Сначала новые</option>
                    <option value="<?= e(POST_SORT_OLDEST) ?>" <?= $sort === POST_SORT_OLDEST ? 'selected' : '' ?>>Сначала старые</option>
                    <option value="<?= e(POST_SORT_TITLE_ASC) ?>" <?= $sort === POST_SORT_TITLE_ASC ? 'selected' : '' ?>>По названию (А–Я)</option>
                    <option value="<?= e(POST_SORT_TITLE_DESC) ?>" <?= $sort === POST_SORT_TITLE_DESC ? 'selected' : '' ?>>По названию (Я–А)</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Применить</button>
                <?php if ($hasFilters): ?>
                    <a href="index.php" class="btn btn-outline">Сбросить</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($filterAuthor !== null): ?>
            <div class="feed-filter-banner card">
                <p>Публичные посты пользователя <strong><?= e($filterAuthor['username']) ?></strong></p>
                <a href="<?= e(publicPostsUrl(1, null, $meta['search'] ?? null, $meta['tag_slug'] ?? null, $sort)) ?>" class="btn btn-outline">Показать всех</a>
            </div>
        <?php elseif ($filterAuthor === null && $authorId !== null): ?>
            <div class="alert alert-error">Автор не найден.</div>
        <?php endif; ?>

        <?php if (($meta['tag_name'] ?? '') !== ''): ?>
            <div class="feed-filter-banner card">
                <p>Тег: <strong>#<?= e($meta['tag_name']) ?></strong></p>
                <a href="<?= e(publicPostsUrl(1, $authorId, $meta['search'] ?? null, null, $sort)) ?>" class="btn btn-outline">Все теги</a>
            </div>
        <?php elseif ($tagSlug !== null && ($meta['tag_name'] ?? '') === ''): ?>
            <div class="alert alert-error">Тег не найден.</div>
        <?php endif; ?>

        <?php if ($posts === []): ?>
            <div class="card empty-state">
                <?php if ($hasFilters): ?>
                    <p>По заданным фильтрам ничего не найдено.</p>
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
                · <?= e(postSortLabel($sort)) ?>
            </p>

            <?php foreach ($posts as $post): ?>
                <?php renderPostCard($post); ?>
            <?php endforeach; ?>

            <?php renderPublicPostsPagination($meta); ?>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
