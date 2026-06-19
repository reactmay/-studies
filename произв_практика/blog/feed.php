<?php

declare(strict_types=1);

$pageTitle = 'Лента подписок';
require_once __DIR__ . '/includes/header.php';

$currentUser = requireAuth();
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$filterAuthorId = isset($_GET['author']) ? (int) $_GET['author'] : null;

if ($filterAuthorId !== null && $filterAuthorId <= 0) {
    $filterAuthorId = null;
}

$feed = generateSubscriptionFeedList(
    (int) $currentUser['id'],
    $page,
    10,
    $filterAuthorId
);

$meta = $feed['meta'];
$authors = $feed['authors'];
$items = $feed['items'];

$filterAuthor = null;
if ($filterAuthorId !== null) {
    foreach ($authors as $author) {
        if ((int) $author['id'] === $filterAuthorId) {
            $filterAuthor = $author;
            break;
        }
    }
}
?>

<h1 class="page-title">Лента подписок</h1>
<p class="page-subtitle">
    Список сформирован на основе ваших подписок
    · <?= e(formatDate($meta['generated_at'])) ?>
</p>

<div class="feed-layout">
    <aside class="card feed-sidebar">
        <h2>Мои подписки (<?= (int) $meta['following_count'] ?>)</h2>

        <?php if ($authors === []): ?>
            <p class="card-meta">Вы ни на кого не подписаны.</p>
            <p><a href="index.php">Найти авторов на главной</a></p>
        <?php else: ?>
            <div class="subscription-list">
                <a class="subscription-list-item <?= $filterAuthorId === null ? 'is-active' : '' ?>"
                   href="<?= e(subscriptionFeedUrl()) ?>">
                    <span class="subscription-list-name">Все подписки</span>
                    <span class="subscription-list-meta"><?= (int) $meta['total_all_items'] ?> постов</span>
                </a>

                <?php foreach ($authors as $author): ?>
                    <a class="subscription-list-item <?= $filterAuthorId === (int) $author['id'] ? 'is-active' : '' ?>"
                       href="<?= e(subscriptionFeedUrl((int) $author['id'])) ?>">
                        <?= renderAvatar($author, 'sm') ?>
                        <span class="subscription-list-body">
                            <span class="subscription-list-name"><?= e($author['username']) ?></span>
                            <span class="subscription-list-meta">
                                <?= (int) $author['posts_count'] ?> постов
                                <?php if (!empty($author['latest_post_at'])): ?>
                                    · <?= e(formatDate($author['latest_post_at'])) ?>
                                <?php endif; ?>
                            </span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>

    <section class="feed-content">
        <?php if ($filterAuthor !== null): ?>
            <div class="feed-filter-banner card">
                <p>Показаны посты пользователя <strong><?= e($filterAuthor['username']) ?></strong></p>
                <a href="<?= e(subscriptionFeedUrl()) ?>" class="btn btn-outline">Показать всех</a>
            </div>
        <?php endif; ?>

        <?php if (!$meta['has_subscriptions']): ?>
            <div class="card empty-state">
                <p>Лента пуста — у вас пока нет подписок.</p>
                <p>Подпишитесь на авторов, чтобы здесь появился персональный список их публикаций.</p>
                <p><a href="index.php" class="btn btn-primary">Перейти к постам</a></p>
            </div>
        <?php elseif ($items === []): ?>
            <div class="card empty-state">
                <p>У выбранных авторов пока нет постов.</p>
                <?php if ($filterAuthorId !== null): ?>
                    <p><a href="<?= e(subscriptionFeedUrl()) ?>">Вернуться ко всем подпискам</a></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="feed-summary card-meta">
                Найдено <?= (int) $meta['total_items'] ?>
                <?= (int) $meta['total_items'] === 1 ? 'публикация' : 'публикаций' ?>
            </p>

            <?php foreach ($items as $post): ?>
                <?php renderPostCard($post); ?>
            <?php endforeach; ?>

            <?php renderPagination($meta, $filterAuthorId); ?>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
