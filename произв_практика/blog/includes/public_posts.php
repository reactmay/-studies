<?php

declare(strict_types=1);

function publicPostUrl(int $postId): string
{
    return 'post.php?id=' . $postId;
}

function publicPostsUrl(int $page = 1, ?int $authorId = null, ?string $search = null): string
{
    $query = ['page' => max(1, $page)];

    if ($authorId !== null && $authorId > 0) {
        $query['author'] = $authorId;
    }

    if ($search !== null && trim($search) !== '') {
        $query['q'] = trim($search);
    }

    $path = 'index.php';

    if ($query === ['page' => 1] && !isset($query['author']) && !isset($query['q'])) {
        return $path;
    }

    return $path . '?' . http_build_query($query);
}

/** @param array<string, mixed> $post */
function enrichPublicPostItem(array $post, bool $withPreview = true): array
{
    $post['id'] = (int) $post['id'];
    $post['user_id'] = (int) $post['user_id'];

    if ($withPreview) {
        $post['preview'] = mb_strimwidth($post['content'], 0, 220, '…');
    }

    return $post;
}

function getPublicPostById(int $id): ?array
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

    return enrichPublicPostItem($post, false);
}

/**
 * @return array{
 *     meta: array<string, mixed>,
 *     items: array<int, array<string, mixed>>
 * }
 */
function generatePublicPostsList(
    int $page = 1,
    int $perPage = 10,
    ?int $authorId = null,
    ?string $search = null
): array {
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;

    $conditions = ['1=1'];
    $params = [];

    if ($authorId !== null && $authorId > 0) {
        $conditions[] = 'posts.user_id = ?';
        $params[] = $authorId;
    }

    $search = $search !== null ? trim($search) : '';
    if ($search !== '') {
        $conditions[] = '(posts.title LIKE ? OR posts.content LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $whereSql = implode(' AND ', $conditions);

    $countSql = '
        SELECT COUNT(*)
        FROM posts
        JOIN users ON users.id = posts.user_id
        WHERE ' . $whereSql;

    $countStmt = getDb()->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = (int) $countStmt->fetchColumn();

    $items = [];

    if ($totalItems > 0) {
        $listSql = '
            SELECT posts.*, users.username, users.avatar
            FROM posts
            JOIN users ON users.id = posts.user_id
            WHERE ' . $whereSql . '
            ORDER BY posts.created_at DESC
            LIMIT ? OFFSET ?
        ';

        $listStmt = getDb()->prepare($listSql);

        $bindIndex = 1;
        foreach ($params as $param) {
            $listStmt->bindValue($bindIndex++, $param);
        }
        $listStmt->bindValue($bindIndex++, $perPage, PDO::PARAM_INT);
        $listStmt->bindValue($bindIndex, $offset, PDO::PARAM_INT);
        $listStmt->execute();

        $items = array_map(
            static fn (array $post): array => enrichPublicPostItem($post),
            $listStmt->fetchAll()
        );
    }

    $totalPages = $totalItems > 0 ? (int) ceil($totalItems / $perPage) : 0;

    return [
        'meta' => [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_items' => $totalItems,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'author_id' => $authorId,
            'search' => $search !== '' ? $search : null,
        ],
        'items' => $items,
    ];
}

function renderPublicPostsPagination(array $meta): void
{
    $totalPages = (int) ($meta['total_pages'] ?? 0);
    $page = (int) ($meta['page'] ?? 1);
    $authorId = isset($meta['author_id']) ? (int) $meta['author_id'] : null;
    $search = $meta['search'] ?? null;

    if ($totalPages <= 1) {
        return;
    }
    ?>
    <nav class="pagination" aria-label="Навигация по страницам">
        <?php if ($page > 1): ?>
            <a class="btn btn-outline" href="<?= e(publicPostsUrl($page - 1, $authorId ?: null, $search)) ?>">← Назад</a>
        <?php endif; ?>

        <span class="pagination-info">Страница <?= $page ?> из <?= $totalPages ?></span>

        <?php if ($page < $totalPages): ?>
            <a class="btn btn-outline" href="<?= e(publicPostsUrl($page + 1, $authorId ?: null, $search)) ?>">Вперёд →</a>
        <?php endif; ?>
    </nav>
    <?php
}

/** @param array<string, mixed> $post */
function renderPublicPostView(array $post, ?array $currentUser, bool $showUpdatedNotice = false): void
{
    ?>
    <?php if ($showUpdatedNotice): ?>
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

        <?php if ($currentUser && (int) $currentUser['id'] !== (int) $post['user_id']): ?>
            <div class="form-actions" style="margin-top: 1rem;">
                <?= renderSubscribeButton(['id' => $post['user_id'], 'username' => $post['username']], $currentUser) ?>
            </div>
        <?php endif; ?>

        <?php if ($currentUser && (int) $currentUser['id'] === (int) $post['user_id']): ?>
            <div class="form-actions" style="margin-top: 1.5rem;">
                <a href="edit_post.php?id=<?= (int) $post['id'] ?>" class="btn btn-primary">Редактировать</a>
                <a href="dashboard.php" class="btn btn-outline">К моим постам</a>
                <a href="delete_post.php?id=<?= (int) $post['id'] ?>" class="btn btn-danger" data-confirm="Удалить этот пост?">Удалить</a>
            </div>
        <?php endif; ?>
    </article>
    <?php
}
