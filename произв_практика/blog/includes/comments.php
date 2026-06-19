<?php

declare(strict_types=1);

function normalizeCommentContent(string $content): string
{
    $content = trim(strip_tags($content));
    $content = preg_replace('/\s+/u', ' ', $content) ?? '';

    return trim($content);
}

function createComment(int $postId, int $userId, string $content): array
{
    $content = normalizeCommentContent($content);

    if ($content === '' || mb_strlen($content) < 2) {
        return ['ok' => false, 'error' => 'Комментарий должен быть не короче 2 символов.'];
    }

    if (mb_strlen($content) > 2000) {
        return ['ok' => false, 'error' => 'Комментарий не должен превышать 2000 символов.'];
    }

    $post = getPostWithAuthorById($postId);
    if ($post === null) {
        return ['ok' => false, 'error' => 'Пост не найден.'];
    }

    $stmt = getDb()->prepare('
        INSERT INTO comments (post_id, user_id, content)
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$postId, $userId, $content]);

    return ['ok' => true, 'comment_id' => (int) getDb()->lastInsertId()];
}

/** @return list<array<string, mixed>> */
function getPostComments(int $postId): array
{
    $stmt = getDb()->prepare('
        SELECT comments.*, users.username, users.avatar
        FROM comments
        JOIN users ON users.id = comments.user_id
        WHERE comments.post_id = ?
        ORDER BY comments.created_at ASC
    ');
    $stmt->execute([$postId]);

    $comments = $stmt->fetchAll();

    foreach ($comments as &$comment) {
        $comment['id'] = (int) $comment['id'];
        $comment['post_id'] = (int) $comment['post_id'];
        $comment['user_id'] = (int) $comment['user_id'];
    }
    unset($comment);

    return $comments;
}

function getPostCommentsCount(int $postId): int
{
    $stmt = getDb()->prepare('SELECT COUNT(*) FROM comments WHERE post_id = ?');
    $stmt->execute([$postId]);

    return (int) $stmt->fetchColumn();
}

function canDeleteComment(array $comment, ?array $currentUser, int $postOwnerId): bool
{
    if ($currentUser === null) {
        return false;
    }

    $userId = (int) $currentUser['id'];

    return $userId === (int) $comment['user_id'] || $userId === $postOwnerId;
}

function deleteComment(int $commentId, int $userId, int $postOwnerId): bool
{
    $stmt = getDb()->prepare('SELECT * FROM comments WHERE id = ?');
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();

    if (!$comment || !canDeleteComment($comment, ['id' => $userId], $postOwnerId)) {
        return false;
    }

    $delete = getDb()->prepare('DELETE FROM comments WHERE id = ?');
    $delete->execute([$commentId]);

    return $delete->rowCount() > 0;
}

function postCommentUrl(int $postId, ?string $accessToken = null): string
{
    $url = 'post.php?id=' . $postId;

    if ($accessToken !== null && $accessToken !== '') {
        $url .= '&token=' . urlencode($accessToken);
    }

    return $url;
}

/** @param array<string, mixed> $post */
function renderPostCommentsSection(array $post, ?array $currentUser, ?string $accessToken = null, bool $commentAdded = false): void
{
    $postId = (int) $post['id'];
    $comments = getPostComments($postId);
    $commentsCount = count($comments);
    $postOwnerId = (int) $post['user_id'];
    ?>
    <section class="card comments-section" id="comments">
        <h2>Комментарии (<?= $commentsCount ?>)</h2>

        <?php if ($commentAdded): ?>
            <div class="alert alert-success">Комментарий добавлен.</div>
        <?php endif; ?>

        <?php if ($currentUser): ?>
            <form class="comment-form" method="post" action="add_comment.php">
                <input type="hidden" name="post_id" value="<?= $postId ?>">
                <?php if ($accessToken !== null && $accessToken !== ''): ?>
                    <input type="hidden" name="token" value="<?= e($accessToken) ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="comment-content">Ваш комментарий</label>
                    <textarea id="comment-content" name="content" rows="4" required maxlength="2000" placeholder="Напишите комментарий..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Отправить</button>
                </div>
            </form>
        <?php else: ?>
            <p class="card-meta"><a href="login.php">Войдите</a>, чтобы оставить комментарий.</p>
        <?php endif; ?>

        <?php if ($comments === []): ?>
            <div class="empty-state comments-empty">
                <p>Комментариев пока нет. Будьте первым!</p>
            </div>
        <?php else: ?>
            <div class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <article class="comment-item">
                        <div class="comment-header">
                            <?= renderAvatar([
                                'username' => $comment['username'],
                                'avatar' => $comment['avatar'] ?? null,
                            ], 'sm') ?>
                            <div class="comment-meta">
                                <a href="<?= e(profileUrl((int) $comment['user_id'])) ?>"><?= e($comment['username']) ?></a>
                                <span>· <?= e(formatDate($comment['created_at'])) ?></span>
                            </div>
                            <?php if (canDeleteComment($comment, $currentUser, $postOwnerId)): ?>
                                <?php
                                $deleteUrl = 'delete_comment.php?id=' . (int) $comment['id'] . '&post_id=' . $postId;
                                if ($accessToken !== null && $accessToken !== '') {
                                    $deleteUrl .= '&token=' . urlencode($accessToken);
                                }
                                ?>
                                <a href="<?= e($deleteUrl) ?>"
                                   class="btn btn-outline btn-sm"
                                   data-confirm="Удалить комментарий?">Удалить</a>
                            <?php endif; ?>
                        </div>
                        <div class="comment-content"><?= e($comment['content']) ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
}
