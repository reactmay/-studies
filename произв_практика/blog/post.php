<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$post = $id > 0 ? getPostById($id) : null;

if ($post === null) {
    http_response_code(404);
    $pageTitle = 'Пост не найден';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="card empty-state">
        <h1>Пост не найден</h1>
        <p><a href="index.php">Вернуться на главную</a></p>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $post['title'];
$user = currentUser();
$updated = isset($_GET['updated']);
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($updated): ?>
    <div class="alert alert-success">Пост успешно обновлён.</div>
<?php endif; ?>

<article class="card">
    <h1 class="page-title"><?= e($post['title']) ?></h1>
    <p class="card-meta author-row">
        <?= renderAvatar(['username' => $post['username'], 'avatar' => $post['avatar'] ?? null], 'sm') ?>
        <span>
            <a href="<?= e(profileUrl((int) $post['user_id'])) ?>"><?= e($post['username']) ?></a>
            · <?= e(formatDate($post['created_at'])) ?>
            <?php if (!empty($post['updated_at'])): ?>
                · изменён <?= e(formatDate($post['updated_at'])) ?>
            <?php endif; ?>
        </span>
    </p>
    <div class="card-content"><?= e($post['content']) ?></div>

    <?php if ($user && (int) $user['id'] !== (int) $post['user_id']): ?>
        <div class="form-actions" style="margin-top: 1rem;">
            <?= renderSubscribeButton(['id' => $post['user_id'], 'username' => $post['username']], $user) ?>
        </div>
    <?php endif; ?>

    <?php if ($user && (int) $user['id'] === (int) $post['user_id']): ?>
        <div class="form-actions" style="margin-top: 1.5rem;">
            <a href="edit_post.php?id=<?= (int) $post['id'] ?>" class="btn btn-primary">Редактировать</a>
            <a href="dashboard.php" class="btn btn-outline">К моим постам</a>
            <a href="delete_post.php?id=<?= (int) $post['id'] ?>" class="btn btn-danger" data-confirm="Удалить этот пост?">Удалить</a>
        </div>
    <?php endif; ?>
</article>

<p><a href="index.php">← Все посты</a></p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
