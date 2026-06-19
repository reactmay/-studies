<?php



declare(strict_types=1);



$pageTitle = 'Личный кабинет';

require_once __DIR__ . '/includes/header.php';



$user = requireAuth();

$posts = getUserPosts((int) $user['id']);

$created = isset($_GET['created']);

$updated = isset($_GET['updated']);

$avatarUploaded = isset($_GET['avatar']);

$avatarError = $_GET['avatar_error'] ?? '';

?>



<h1 class="page-title">Личный кабинет</h1>

<p class="page-subtitle">Управление профилем и вашими публикациями</p>



<?php if ($created): ?>

    <div class="alert alert-success">Пост успешно опубликован.</div>

<?php endif; ?>



<?php if ($updated): ?>

    <div class="alert alert-success">Пост успешно обновлён.</div>

<?php endif; ?>



<?php if ($avatarUploaded): ?>

    <div class="alert alert-success">Аватар успешно загружен.</div>

<?php endif; ?>



<?php if ($avatarError !== ''): ?>

    <div class="alert alert-error"><?= e($avatarError) ?></div>

<?php endif; ?>



<div class="profile-grid">

    <section class="card profile-info">

        <div class="profile-header">

            <?= renderAvatar($user, 'lg') ?>

            <div>

                <h2><?= e($user['username']) ?></h2>

                <p class="card-meta"><?= e($user['email']) ?></p>

            </div>

        </div>



        <p><strong>Дата регистрации:</strong> <?= e(formatDate($user['created_at'])) ?></p>

        <div class="profile-stats">
            <a href="profile.php?id=<?= (int) $user['id'] ?>&view=followers">
                <strong><?= getFollowersCount((int) $user['id']) ?></strong> подписчиков
            </a>
            <a href="profile.php?id=<?= (int) $user['id'] ?>&view=following">
                <strong><?= getFollowingCount((int) $user['id']) ?></strong> подписок
            </a>
            <a href="profile.php?id=<?= (int) $user['id'] ?>">Мой профиль</a>
        </div>

        <form class="avatar-form" method="post" action="upload_avatar.php" enctype="multipart/form-data">

            <div class="form-group">

                <label for="avatar">Загрузить аватар</label>

                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" required>

                <p class="form-hint">JPG, PNG, WEBP или GIF, до 2 МБ</p>

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-outline">Обновить аватар</button>

                <a href="create_post.php" class="btn btn-primary">Создать пост</a>

            </div>

        </form>

    </section>



    <section>

        <h2>Мои посты (<?= count($posts) ?>)</h2>



        <?php if ($posts === []): ?>

            <div class="card empty-state">

                <p>У вас пока нет постов.</p>

                <p><a href="create_post.php" class="btn btn-primary">Написать первый пост</a></p>

            </div>

        <?php else: ?>

            <?php foreach ($posts as $post): ?>

                <article class="card">

                    <div class="card-header">

                        <h3 class="card-title">

                            <a href="post.php?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a>

                        </h3>

                        <div class="post-actions">

                            <a href="post.php?id=<?= (int) $post['id'] ?>" class="btn btn-outline">Открыть</a>

                            <a href="edit_post.php?id=<?= (int) $post['id'] ?>" class="btn btn-outline">Изменить</a>

                            <a href="delete_post.php?id=<?= (int) $post['id'] ?>" class="btn btn-danger" data-confirm="Удалить этот пост?">Удалить</a>

                        </div>

                    </div>

                    <p class="card-meta">

                        <?= e(formatDate($post['created_at'])) ?>

                        <?php if (!empty($post['updated_at'])): ?>

                            · изменён <?= e(formatDate($post['updated_at'])) ?>

                        <?php endif; ?>

                    </p>

                    <div class="card-content"><?= e(mb_strimwidth($post['content'], 0, 180, '…')) ?></div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</div>



<?php require_once __DIR__ . '/includes/footer.php'; ?>

