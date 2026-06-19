<?php

declare(strict_types=1);

$pageTitle = 'Лента подписок';
require_once __DIR__ . '/includes/header.php';

$currentUser = requireAuth();
$posts = getSubscribedPosts((int) $currentUser['id']);
?>

<h1 class="page-title">Лента подписок</h1>
<p class="page-subtitle">Посты пользователей, на которых вы подписаны</p>

<?php if ($posts === []): ?>
    <div class="card empty-state">
        <p>В ленте пока пусто.</p>
        <p>Подпишитесь на авторов на их <a href="index.php">страницах профиля</a>.</p>
    </div>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <article class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <a href="post.php?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a>
                </h2>
            </div>
            <p class="card-meta author-row">
                <?= renderAvatar(['username' => $post['username'], 'avatar' => $post['avatar'] ?? null], 'sm') ?>
                <span>
                    <a href="<?= e(profileUrl((int) $post['user_id'])) ?>"><?= e($post['username']) ?></a>
                    · <?= e(formatDate($post['created_at'])) ?>
                </span>
            </p>
            <div class="card-content"><?= e(mb_strimwidth($post['content'], 0, 220, '…')) ?></div>
        </article>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
