<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatDate(string $date): string
{
    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('d.m.Y H:i', $timestamp);
}

function avatarPath(?string $avatar): ?string
{
    if ($avatar === null || $avatar === '') {
        return null;
    }

    return $avatar;
}

function renderAvatar(array $user, string $size = 'md'): string
{
    $class = 'avatar avatar-' . $size;
    $username = $user['username'] ?? '?';
    $path = avatarPath($user['avatar'] ?? null);

    if ($path !== null) {
        return sprintf(
            '<img class="%s" src="%s" alt="%s">',
            e($class),
            e($path),
            e($username)
        );
    }

    $initial = mb_strtoupper(mb_substr($username, 0, 1));

    return sprintf(
        '<span class="%s avatar-placeholder" aria-hidden="true">%s</span>',
        e($class),
        e($initial)
    );
}

function getAllPosts(int $limit = 50): array
{
    return generatePublicPostsList(1, $limit)['items'];
}

function getPostById(int $id): ?array
{
    return getPublicPostById($id);
}

function getUserPosts(int $userId, bool $publicOnly = false): array
{
    $sql = '
        SELECT * FROM posts
        WHERE user_id = ?
    ';

    if ($publicOnly) {
        $sql .= " AND visibility = 'public'";
    }

    $sql .= ' ORDER BY created_at DESC';

    $stmt = getDb()->prepare($sql);
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function getUserPostById(int $postId, int $userId): ?array
{
    $stmt = getDb()->prepare('
        SELECT * FROM posts
        WHERE id = ? AND user_id = ?
    ');
    $stmt->execute([$postId, $userId]);
    $post = $stmt->fetch();

    return $post ?: null;
}

function createPost(
    int $userId,
    string $title,
    string $content,
    string $visibility = POST_VISIBILITY_PUBLIC,
    string $tagsInput = '',
    string $editorMode = 'visual'
): array {
    $title = trim($title);
    $visibility = normalizePostVisibility($visibility);

    if ($title === '' || mb_strlen($title) < 3) {
        return ['ok' => false, 'error' => 'Заголовок должен быть не короче 3 символов.'];
    }

    $contentResult = validatePostContent($content, $editorMode);
    if (!$contentResult['ok']) {
        return $contentResult;
    }
    $content = $contentResult['content'];

    $tagsResult = validateTagsInput($tagsInput);
    if (!$tagsResult['ok']) {
        return $tagsResult;
    }

    $accessToken = $visibility === POST_VISIBILITY_ON_REQUEST ? generatePostAccessToken() : null;

    $stmt = getDb()->prepare('
        INSERT INTO posts (user_id, title, content, visibility, access_token)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([$userId, $title, $content, $visibility, $accessToken]);

    $postId = (int) getDb()->lastInsertId();
    syncPostTags($postId, $tagsResult['tags']);

    return ['ok' => true, 'post_id' => $postId];
}

function updatePost(
    int $postId,
    int $userId,
    string $title,
    string $content,
    string $visibility = POST_VISIBILITY_PUBLIC,
    string $tagsInput = '',
    string $editorMode = 'visual'
): array {
    $title = trim($title);
    $visibility = normalizePostVisibility($visibility);

    if ($title === '' || mb_strlen($title) < 3) {
        return ['ok' => false, 'error' => 'Заголовок должен быть не короче 3 символов.'];
    }

    $contentResult = validatePostContent($content, $editorMode);
    if (!$contentResult['ok']) {
        return $contentResult;
    }
    $content = $contentResult['content'];

    $tagsResult = validateTagsInput($tagsInput);
    if (!$tagsResult['ok']) {
        return $tagsResult;
    }

    $post = getUserPostById($postId, $userId);

    if ($post === null) {
        return ['ok' => false, 'error' => 'Пост не найден или у вас нет прав на редактирование.'];
    }

    $accessToken = $post['access_token'] ?? null;
    if ($visibility === POST_VISIBILITY_ON_REQUEST && ($accessToken === null || $accessToken === '')) {
        $accessToken = generatePostAccessToken();
    }
    if ($visibility === POST_VISIBILITY_PUBLIC) {
        $accessToken = null;
    }

    $oldContent = (string) ($post['content'] ?? '');
    $oldImages = extractPostImagePaths($oldContent);
    $newImages = extractPostImagePaths($content);

    $stmt = getDb()->prepare('
        UPDATE posts
        SET title = ?, content = ?, visibility = ?, access_token = ?, updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ');
    $stmt->execute([$title, $content, $visibility, $accessToken, $postId, $userId]);

    foreach (array_diff($oldImages, $newImages) as $removedImage) {
        $absolutePath = dirname(__DIR__) . '/' . $removedImage;
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    syncPostTags($postId, $tagsResult['tags']);

    return ['ok' => true, 'post_id' => $postId];
}

function deletePost(int $postId, int $userId): bool
{
    $post = getUserPostById($postId, $userId);

    if ($post === null) {
        return false;
    }

    deletePostImagesFromContent((string) ($post['content'] ?? ''));

    $stmt = getDb()->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
    $stmt->execute([$postId, $userId]);

    return $stmt->rowCount() > 0;
}
