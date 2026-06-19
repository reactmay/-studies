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

function getUserPosts(int $userId): array
{
    $stmt = getDb()->prepare('
        SELECT * FROM posts
        WHERE user_id = ?
        ORDER BY created_at DESC
    ');
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

function createPost(int $userId, string $title, string $content): array
{
    $title = trim($title);
    $content = trim($content);

    if ($title === '' || mb_strlen($title) < 3) {
        return ['ok' => false, 'error' => 'Заголовок должен быть не короче 3 символов.'];
    }

    if ($content === '' || mb_strlen($content) < 10) {
        return ['ok' => false, 'error' => 'Текст поста должен быть не короче 10 символов.'];
    }

    $stmt = getDb()->prepare('INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $title, $content]);

    return ['ok' => true, 'post_id' => (int) getDb()->lastInsertId()];
}

function updatePost(int $postId, int $userId, string $title, string $content): array
{
    $title = trim($title);
    $content = trim($content);

    if ($title === '' || mb_strlen($title) < 3) {
        return ['ok' => false, 'error' => 'Заголовок должен быть не короче 3 символов.'];
    }

    if ($content === '' || mb_strlen($content) < 10) {
        return ['ok' => false, 'error' => 'Текст поста должен быть не короче 10 символов.'];
    }

    $post = getUserPostById($postId, $userId);

    if ($post === null) {
        return ['ok' => false, 'error' => 'Пост не найден или у вас нет прав на редактирование.'];
    }

    $stmt = getDb()->prepare('
        UPDATE posts
        SET title = ?, content = ?, updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ');
    $stmt->execute([$title, $content, $postId, $userId]);

    return ['ok' => true, 'post_id' => $postId];
}

function deletePost(int $postId, int $userId): bool
{
    $stmt = getDb()->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
    $stmt->execute([$postId, $userId]);

    return $stmt->rowCount() > 0;
}
