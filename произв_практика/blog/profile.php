<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/subscriptions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$profileUser = $id > 0 ? getUserById($id) : null;

if ($profileUser === null) {
    http_response_code(404);
    $pageTitle = 'Пользователь не найден';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="card empty-state">
        <h1>Пользователь не найден</h1>
        <p><a href="index.php">Вернуться на главную</a></p>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $profileUser['username'];
$currentUser = currentUser();
$posts = getUserPosts((int) $profileUser['id']);
$followersCount = getFollowersCount((int) $profileUser['id']);
$followingCount = getFollowingCount((int) $profileUser['id']);
$view = $_GET['view'] ?? 'posts';
$subscribed = isset($_GET['subscribed']);
$unsubscribed = isset($_GET['unsubscribed']);
$subscribeError = $_GET['subscribe_error'] ?? '';

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Профиль</h1>

<?php if ($subscribed): ?>
    <div class="alert alert-success">Вы подписались на пользователя.</div>
<?php endif; ?>

<?php if ($unsubscribed): ?>
    <div class="alert alert-success">Вы отписались от пользователя.</div>
<?php endif; ?>

<?php if ($subscribeError !== ''): ?>
    <div class="alert alert-error"><?= e($subscribeError) ?></div>
<?php endif; ?>

<section class="card profile-info">
    <div class="profile-header">
        <?= renderAvatar($profileUser, 'lg') ?>
        <div class="profile-summary">
            <h2><?= e($profileUser['username']) ?></h2>
            <p class="card-meta">На сайте с <?= e(formatDate($profileUser['created_at'])) ?></p>
            <div class="profile-stats">
                <span><strong><?= count($posts) ?></strong> постов</span>
                <a href="profile.php?id=<?= (int) $profileUser['id'] ?>&view=followers">
                    <strong><?= $followersCount ?></strong> подписчиков
                </a>
                <a href="profile.php?id=<?= (int) $profileUser['id'] ?>&view=following">
                    <strong><?= $followingCount ?></strong> подписок
                </a>
            </div>
            <?= renderSubscribeButton($profileUser, $currentUser) ?>
        </div>
    </div>
</section>

<?php if ($view === 'followers'): ?>
    <section>
        <h2>Подписчики</h2>
        <?php $followers = getFollowers((int) $profileUser['id']); ?>
        <?php if ($followers === []): ?>
            <div class="card empty-state"><p>Подписчиков пока нет.</p></div>
        <?php else: ?>
            <div class="user-list">
                <?php foreach ($followers as $follower): ?>
                    <a class="card user-list-item" href="<?= e(profileUrl((int) $follower['id'])) ?>">
                        <?= renderAvatar($follower, 'sm') ?>
                        <span><?= e($follower['username']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php elseif ($view === 'following'): ?>
    <section>
        <h2>Подписки</h2>
        <?php $following = getFollowing((int) $profileUser['id']); ?>
        <?php if ($following === []): ?>
            <div class="card empty-state"><p>Пользователь ни на кого не подписан.</p></div>
        <?php else: ?>
            <div class="user-list">
                <?php foreach ($following as $followed): ?>
                    <a class="card user-list-item" href="<?= e(profileUrl((int) $followed['id'])) ?>">
                        <?= renderAvatar($followed, 'sm') ?>
                        <span><?= e($followed['username']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section>
        <div class="section-header-row">
            <h2>Посты пользователя (<?= count($posts) ?>)</h2>
            <a href="<?= e(publicPostsUrl(1, (int) $profileUser['id'])) ?>" class="btn btn-outline">Все публичные посты</a>
        </div>
        <?php if ($posts === []): ?>
            <div class="card empty-state"><p>У пользователя пока нет постов.</p></div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <article class="card">
                    <h3 class="card-title">
                        <a href="post.php?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a>
                    </h3>
                    <p class="card-meta"><?= e(formatDate($post['created_at'])) ?></p>
                    <div class="card-content"><?= e(mb_strimwidth($post['content'], 0, 220, '…')) ?></div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<p><a href="index.php">← На главную</a></p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
