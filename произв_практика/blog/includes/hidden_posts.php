<?php

declare(strict_types=1);

const POST_VISIBILITY_PUBLIC = 'public';
const POST_VISIBILITY_ON_REQUEST = 'on_request';

function normalizePostVisibility(string $visibility): string
{
    return $visibility === POST_VISIBILITY_ON_REQUEST
        ? POST_VISIBILITY_ON_REQUEST
        : POST_VISIBILITY_PUBLIC;
}

function generatePostAccessToken(): string
{
    return bin2hex(random_bytes(16));
}

function isPostOnRequest(array $post): bool
{
    return ($post['visibility'] ?? POST_VISIBILITY_PUBLIC) === POST_VISIBILITY_ON_REQUEST;
}

function postVisibilityLabel(string $visibility): string
{
    return $visibility === POST_VISIBILITY_ON_REQUEST ? 'Только по запросу' : 'Публичный';
}

function hiddenPostAccessUrl(int $postId, string $token): string
{
    return 'post.php?id=' . $postId . '&token=' . urlencode($token);
}

function getPostWithAuthorById(int $id): ?array
{
    $stmt = getDb()->prepare('
        SELECT posts.*, users.username, users.avatar
        FROM posts
        JOIN users ON users.id = posts.user_id
        WHERE posts.id = ?
    ');
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post) {
        return null;
    }

    $post['id'] = (int) $post['id'];
    $post['user_id'] = (int) $post['user_id'];
    $post['visibility'] = $post['visibility'] ?? POST_VISIBILITY_PUBLIC;

    return $post;
}

function isPostAccessible(array $post, ?array $currentUser, ?string $accessToken): bool
{
    if (!isPostOnRequest($post)) {
        return true;
    }

    if ($currentUser !== null && (int) $currentUser['id'] === (int) $post['user_id']) {
        return true;
    }

    $token = trim($accessToken ?? '');
    $storedToken = (string) ($post['access_token'] ?? '');

    return $token !== '' && $storedToken !== '' && hash_equals($storedToken, $token);
}

/**
 * @return array{
 *     found: bool,
 *     accessible: bool,
 *     post: ?array<string, mixed>,
 *     is_owner: bool
 * }
 */
function viewPostOnRequest(int $id, ?array $currentUser, ?string $accessToken): array
{
    $post = getPostWithAuthorById($id);

    if ($post === null) {
        return [
            'found' => false,
            'accessible' => false,
            'post' => null,
            'is_owner' => false,
        ];
    }

    $isOwner = $currentUser !== null && (int) $currentUser['id'] === (int) $post['user_id'];
    $accessible = isPostAccessible($post, $currentUser, $accessToken);

    return [
        'found' => true,
        'accessible' => $accessible,
        'post' => $post,
        'is_owner' => $isOwner,
    ];
}

function renderHiddenPostRequestForm(int $postId, bool $tokenInvalid = false): void
{
    ?>
    <div class="card hidden-post-request">
        <h1 class="page-title">Скрытый пост</h1>
        <p class="page-subtitle">Публикация доступна только по запросу — введите код доступа от автора</p>

        <?php if ($tokenInvalid): ?>
            <div class="alert alert-error">Неверный код доступа. Проверьте ссылку или запросите новую у автора.</div>
        <?php endif; ?>

        <form method="get" action="post.php" class="access-token-form">
            <input type="hidden" name="id" value="<?= (int) $postId ?>">
            <div class="form-group">
                <label for="token">Код доступа</label>
                <input type="text" id="token" name="token" required placeholder="Вставьте код из ссылки" autocomplete="off">
                <p class="form-hint">Код передаётся автором вместе со ссылкой на пост</p>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Открыть пост</button>
                <a href="index.php" class="btn btn-outline">На главную</a>
            </div>
        </form>
    </div>
    <?php
}

function renderPostVisibilityBadge(array $post): void
{
    if (!isPostOnRequest($post)) {
        return;
    }
    ?>
    <span class="post-badge post-badge-hidden">Только по запросу</span>
    <?php
}

function renderOwnerAccessLink(array $post): void
{
    if (!$post || !isPostOnRequest($post) || empty($post['access_token'])) {
        return;
    }

    $url = hiddenPostAccessUrl((int) $post['id'], (string) $post['access_token']);
    ?>
    <div class="access-link-box">
        <p class="form-hint">Ссылка для просмотра скрытого поста:</p>
        <input type="text" class="access-link-input" readonly value="<?= e($url) ?>" onclick="this.select()">
    </div>
    <?php
}

function publicPostsVisibilitySql(string $postsAlias = 'posts'): string
{
    return $postsAlias . ".visibility = '" . POST_VISIBILITY_PUBLIC . "'";
}
