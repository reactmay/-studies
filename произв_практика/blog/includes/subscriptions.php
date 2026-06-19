<?php

declare(strict_types=1);

function getUserById(int $id): ?array
{
    $stmt = getDb()->prepare('
        SELECT id, username, email, avatar, created_at
        FROM users
        WHERE id = ?
    ');
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function subscribeToUser(int $followerId, int $followingId): array
{
    if ($followerId === $followingId) {
        return ['ok' => false, 'error' => 'Нельзя подписаться на самого себя.'];
    }

    if (getUserById($followingId) === null) {
        return ['ok' => false, 'error' => 'Пользователь не найден.'];
    }

    if (isSubscribed($followerId, $followingId)) {
        return ['ok' => false, 'error' => 'Вы уже подписаны на этого пользователя.'];
    }

    $stmt = getDb()->prepare('INSERT INTO subscriptions (follower_id, following_id) VALUES (?, ?)');
    $stmt->execute([$followerId, $followingId]);

    return ['ok' => true, 'subscribed' => true];
}

function unsubscribeFromUser(int $followerId, int $followingId): array
{
    $stmt = getDb()->prepare('
        DELETE FROM subscriptions
        WHERE follower_id = ? AND following_id = ?
    ');
    $stmt->execute([$followerId, $followingId]);

    if ($stmt->rowCount() === 0) {
        return ['ok' => false, 'error' => 'Подписка не найдена.'];
    }

    return ['ok' => true, 'subscribed' => false];
}

function toggleSubscription(int $followerId, int $followingId): array
{
    if (isSubscribed($followerId, $followingId)) {
        return unsubscribeFromUser($followerId, $followingId);
    }

    return subscribeToUser($followerId, $followingId);
}

function isSubscribed(int $followerId, int $followingId): bool
{
    $stmt = getDb()->prepare('
        SELECT 1 FROM subscriptions
        WHERE follower_id = ? AND following_id = ?
    ');
    $stmt->execute([$followerId, $followingId]);

    return (bool) $stmt->fetchColumn();
}

function getFollowersCount(int $userId): int
{
    $stmt = getDb()->prepare('SELECT COUNT(*) FROM subscriptions WHERE following_id = ?');
    $stmt->execute([$userId]);

    return (int) $stmt->fetchColumn();
}

function getFollowingCount(int $userId): int
{
    $stmt = getDb()->prepare('SELECT COUNT(*) FROM subscriptions WHERE follower_id = ?');
    $stmt->execute([$userId]);

    return (int) $stmt->fetchColumn();
}

function getFollowers(int $userId): array
{
    $stmt = getDb()->prepare('
        SELECT users.id, users.username, users.avatar, subscriptions.created_at AS subscribed_at
        FROM subscriptions
        JOIN users ON users.id = subscriptions.follower_id
        WHERE subscriptions.following_id = ?
        ORDER BY subscriptions.created_at DESC
    ');
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function getFollowing(int $userId): array
{
    $stmt = getDb()->prepare('
        SELECT users.id, users.username, users.avatar, subscriptions.created_at AS subscribed_at
        FROM subscriptions
        JOIN users ON users.id = subscriptions.following_id
        WHERE subscriptions.follower_id = ?
        ORDER BY subscriptions.created_at DESC
    ');
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function getSubscribedPosts(int $userId, int $limit = 50): array
{
    $stmt = getDb()->prepare('
        SELECT posts.*, users.username, users.avatar
        FROM posts
        JOIN users ON users.id = posts.user_id
        JOIN subscriptions ON subscriptions.following_id = posts.user_id
        WHERE subscriptions.follower_id = ?
        ORDER BY posts.created_at DESC
        LIMIT ?
    ');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function profileUrl(int $userId): string
{
    return 'profile.php?id=' . $userId;
}

function renderSubscribeButton(array $profileUser, ?array $currentUser): string
{
    $profileId = (int) $profileUser['id'];

    if ($currentUser === null) {
        return '<a href="login.php" class="btn btn-primary">Войти, чтобы подписаться</a>';
    }

    if ((int) $currentUser['id'] === $profileId) {
        return '';
    }

    $subscribed = isSubscribed((int) $currentUser['id'], $profileId);
    $label = $subscribed ? 'Отписаться' : 'Подписаться';
    $class = $subscribed ? 'btn btn-outline' : 'btn btn-primary';

    return sprintf(
        '<form class="subscribe-form" method="post" action="toggle_subscribe.php">
            <input type="hidden" name="user_id" value="%d">
            <input type="hidden" name="redirect" value="%s">
            <button type="submit" class="%s">%s</button>
        </form>',
        $profileId,
        e($_SERVER['REQUEST_URI'] ?? profileUrl($profileId)),
        e($class),
        e($label)
    );
}
