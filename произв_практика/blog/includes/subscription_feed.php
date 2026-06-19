<?php

declare(strict_types=1);

/**
 * @return array{
 *     id: int,
 *     username: string,
 *     avatar: ?string,
 *     subscribed_at: string,
 *     posts_count: int,
 *     latest_post_at: ?string
 * }
 */
function generateSubscriptionAuthorsList(int $userId): array
{
    $stmt = getDb()->prepare('
        SELECT
            users.id,
            users.username,
            users.avatar,
            subscriptions.created_at AS subscribed_at,
            COUNT(posts.id) AS posts_count,
            MAX(posts.created_at) AS latest_post_at
        FROM subscriptions
        JOIN users ON users.id = subscriptions.following_id
        LEFT JOIN posts ON posts.user_id = users.id AND ' . publicPostsVisibilitySql('posts') . '
        WHERE subscriptions.follower_id = ?
        GROUP BY users.id, users.username, users.avatar, subscriptions.created_at
        ORDER BY subscriptions.created_at DESC
    ');
    $stmt->execute([$userId]);

    $authors = $stmt->fetchAll();

    foreach ($authors as &$author) {
        $author['id'] = (int) $author['id'];
        $author['posts_count'] = (int) $author['posts_count'];
    }
    unset($author);

    return $authors;
}

/**
 * @return array{
 *     meta: array<string, mixed>,
 *     authors: array<int, array<string, mixed>>,
 *     items: array<int, array<string, mixed>>
 * }
 */
function generateSubscriptionFeedList(
    int $userId,
    int $page = 1,
    int $perPage = 20,
    ?int $filterAuthorId = null
): array {
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;

    $authors = generateSubscriptionAuthorsList($userId);
    $followingCount = count($authors);

    $params = [$userId];
    $authorFilterSql = '';

    if ($filterAuthorId !== null) {
        $authorFilterSql = ' AND posts.user_id = ?';
        $params[] = $filterAuthorId;
    }

    $visibilitySql = ' AND ' . publicPostsVisibilitySql();

    $countSql = '
        SELECT COUNT(*)
        FROM posts
        JOIN subscriptions ON subscriptions.following_id = posts.user_id
        WHERE subscriptions.follower_id = ?
        ' . $visibilitySql . $authorFilterSql;

    $countStmt = getDb()->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = (int) $countStmt->fetchColumn();

    $totalAllItems = $totalItems;
    if ($filterAuthorId !== null) {
        $allCountStmt = getDb()->prepare('
            SELECT COUNT(*)
            FROM posts
            JOIN subscriptions ON subscriptions.following_id = posts.user_id
            WHERE subscriptions.follower_id = ?
            AND ' . publicPostsVisibilitySql() . '
        ');
        $allCountStmt->execute([$userId]);
        $totalAllItems = (int) $allCountStmt->fetchColumn();
    }

    $items = [];

    if ($totalItems > 0) {
        $listSql = '
            SELECT
                posts.*,
                users.username,
                users.avatar,
                subscriptions.created_at AS author_subscribed_at
            FROM posts
            JOIN users ON users.id = posts.user_id
            JOIN subscriptions ON subscriptions.following_id = posts.user_id
                AND subscriptions.follower_id = ?
            WHERE 1=1
            ' . $visibilitySql . $authorFilterSql . '
            ORDER BY posts.created_at DESC
            LIMIT ? OFFSET ?
        ';

        $listParams = $params;
        $listParams[] = $perPage;
        $listParams[] = $offset;

        $listStmt = getDb()->prepare($listSql);
        foreach ($listParams as $index => $value) {
            $listStmt->bindValue($index + 1, $value, PDO::PARAM_INT);
        }
        $listStmt->execute();

        $items = $listStmt->fetchAll();

        foreach ($items as &$item) {
            $item['id'] = (int) $item['id'];
            $item['user_id'] = (int) $item['user_id'];
            $item['preview'] = mb_strimwidth($item['content'], 0, 220, '…');
        }
        unset($item);
    }

    $totalPages = $totalItems > 0 ? (int) ceil($totalItems / $perPage) : 0;

    return [
        'meta' => [
            'generated_at' => date('Y-m-d H:i:s'),
            'following_count' => $followingCount,
            'total_items' => $totalItems,
            'total_all_items' => $totalAllItems,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'filter_user_id' => $filterAuthorId,
            'has_subscriptions' => $followingCount > 0,
        ],
        'authors' => $authors,
        'items' => $items,
    ];
}

function subscriptionFeedUrl(?int $authorId = null, int $page = 1): string
{
    $query = ['page' => max(1, $page)];

    if ($authorId !== null) {
        $query['author'] = $authorId;
    }

    return 'feed.php?' . http_build_query($query);
}
