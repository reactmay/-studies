<?php

declare(strict_types=1);

$pageTitle = 'Главная';
require_once __DIR__ . '/includes/header.php';

$posts = getAllPosts();
?>

<h1 class="page-title">Все посты</h1>
<p class="page-subtitle">Публикации пользователей сайта</p>

<?php if ($posts === []): ?>
    <div class="card empty-state">
        <p>Пока нет ни одного поста.</p>
        <?php if ($user): ?>
            <p><a href="create_post.php" class="btn btn-primary">Создать первый пост</a></p>
        <?php else: ?>
            <p><a href="register.php">Зарегистрируйтесь</a>, чтобы начать публиковать.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <?php renderPostCard($post); ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
