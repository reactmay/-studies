<?php

declare(strict_types=1);

function publicPostUrl(int $postId): string
{
    return 'post.php?id=' . $postId;
}

function publicPostsUrl(
    int $page = 1,
    ?int $authorId = null,
    ?string $search = null,
    ?string $tagSlug = null,
    string $sort = POST_SORT_NEWEST,
    ?string $date = null,
    ?string $calMonth = null
): string {
    $query = [];

    if ($page > 1) {
        $query['page'] = $page;
    }

    if ($authorId !== null && $authorId > 0) {
        $query['author'] = $authorId;
    }

    if ($search !== null && trim($search) !== '') {
        $query['q'] = trim($search);
    }

    if ($tagSlug !== null && trim($tagSlug) !== '') {
        $query['tag'] = trim($tagSlug);
    }

    $sort = normalizePostSort($sort);
    if ($sort !== POST_SORT_NEWEST) {
        $query['sort'] = $sort;
    }

    if ($date !== null && $date !== '') {
        $query['date'] = $date;
    }

    if ($calMonth !== null && $calMonth !== '') {
        $query['cal_month'] = $calMonth;
    }

    if ($query === []) {
        return 'index.php';
    }

    return 'index.php?' . http_build_query($query);
}

/** @param array<string, mixed> $post */
function enrichPublicPostItem(array $post, bool $withPreview = true): array
{
    $post['id'] = (int) $post['id'];
    $post['user_id'] = (int) $post['user_id'];

    if ($withPreview) {
        $post['preview'] = postContentPreview($post['content']);
    }

    if (!isset($post['tags'])) {
        $post['tags'] = getPostTags((int) $post['id']);
    }

    return $post;
}

function getPublicPostById(int $id): ?array
{
    $post = getPostWithAuthorById($id);

    if ($post === null || isPostOnRequest($post)) {
        return null;
    }

    return enrichPublicPostItem($post, false);
}

/**
 * @return array{
 *     meta: array<string, mixed>,
 *     items: array<int, array<string, mixed>>,
 *     tags: array<int, array<string, mixed>>
 * }
 */
function generatePublicPostsList(
    int $page = 1,
    int $perPage = 10,
    ?int $authorId = null,
    ?string $search = null,
    ?string $tagSlug = null,
    string $sort = POST_SORT_NEWEST,
    ?string $filterDate = null
): array {
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;
    $sort = normalizePostSort($sort);

    $conditions = [publicPostsVisibilitySql()];
    $params = [];
    $joins = '';

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

    $activeTag = null;
    if ($tagSlug !== null && trim($tagSlug) !== '') {
        $activeTag = getTagBySlug($tagSlug);
        if ($activeTag !== null) {
            $joins .= ' JOIN post_tags ON post_tags.post_id = posts.id';
            $joins .= ' JOIN tags ON tags.id = post_tags.tag_id AND tags.slug = ?';
            $params[] = $activeTag['slug'];
        }
    }

    $filterDate = normalizeFilterDate($filterDate);
    if ($filterDate !== null) {
        $conditions[] = 'DATE(posts.created_at) = ?';
        $params[] = $filterDate;
    }

    $whereSql = implode(' AND ', $conditions);
    $orderSql = postSortOrderSql($sort);

    $countSql = '
        SELECT COUNT(DISTINCT posts.id)
        FROM posts
        JOIN users ON users.id = posts.user_id
        ' . $joins . '
        WHERE ' . $whereSql;

    $countStmt = getDb()->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = (int) $countStmt->fetchColumn();

    $items = [];

    if ($totalItems > 0) {
        $listSql = '
            SELECT DISTINCT posts.*, users.username, users.avatar
            FROM posts
            JOIN users ON users.id = posts.user_id
            ' . $joins . '
            WHERE ' . $whereSql . '
            ORDER BY ' . $orderSql . '
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

        $items = attachTagsToPosts(array_map(
            static fn (array $post): array => enrichPublicPostItem($post),
            $listStmt->fetchAll()
        ));
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
            'tag_slug' => $activeTag['slug'] ?? null,
            'tag_name' => $activeTag['name'] ?? null,
            'sort' => $sort,
            'filter_date' => $filterDate,
        ],
        'items' => $items,
        'tags' => getPopularTags(),
    ];
}

function renderPublicPostsPagination(array $meta): void
{
    $totalPages = (int) ($meta['total_pages'] ?? 0);
    $page = (int) ($meta['page'] ?? 1);
    $authorId = isset($meta['author_id']) ? (int) $meta['author_id'] : null;
    $search = $meta['search'] ?? null;
    $tagSlug = $meta['tag_slug'] ?? null;
    $sort = $meta['sort'] ?? POST_SORT_NEWEST;
    $filterDate = $meta['filter_date'] ?? null;

    if ($totalPages <= 1) {
        return;
    }
    ?>
    <nav class="pagination" aria-label="Навигация по страницам">
        <?php if ($page > 1): ?>
            <a class="btn btn-outline" href="<?= e(publicPostsUrl($page - 1, $authorId ?: null, $search, $tagSlug, $sort, $filterDate, $meta['cal_month'] ?? null)) ?>">← Назад</a>
        <?php endif; ?>

        <span class="pagination-info">Страница <?= $page ?> из <?= $totalPages ?></span>

        <?php if ($page < $totalPages): ?>
            <a class="btn btn-outline" href="<?= e(publicPostsUrl($page + 1, $authorId ?: null, $search, $tagSlug, $sort, $filterDate, $meta['cal_month'] ?? null)) ?>">Вперёд →</a>
        <?php endif; ?>
    </nav>
    <?php
}

/** @param array<string, mixed> $post */
function renderPublicPostView(
    array $post,
    ?array $currentUser,
    bool $showUpdatedNotice = false,
    ?string $accessToken = null,
    bool $commentAdded = false,
    string $commentError = ''
): void {
    if (!isset($post['tags'])) {
        $post['tags'] = getPostTags((int) $post['id']);
    }
    ?>
    <?php if ($showUpdatedNotice): ?>
        <div class="alert alert-success">Пост успешно обновлён.</div>
    <?php endif; ?>

    <article class="card">
        <h1 class="page-title">
            <?= e($post['title']) ?>
            <?php renderPostVisibilityBadge($post); ?>
        </h1>
        <?php renderPostTags($post['tags']); ?>
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
        <div class="card-content post-content-html"><?php renderPostContentHtml($post['content']); ?></div>

        <?php if ($currentUser && (int) $currentUser['id'] === (int) $post['user_id'] && isPostOnRequest($post)): ?>
            <?php renderOwnerAccessLink($post); ?>
        <?php endif; ?>

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

    <?php if ($commentError !== ''): ?>
        <div class="alert alert-error"><?= e($commentError) ?></div>
    <?php endif; ?>

    <?php renderPostCommentsSection($post, $currentUser, $accessToken, $commentAdded); ?>
    <?php
}

function renderTagFilterPanel(array $popularTags, array $meta): void
{
    $activeSlug = $meta['tag_slug'] ?? null;
    $sort = $meta['sort'] ?? POST_SORT_NEWEST;
    $filterDate = $meta['filter_date'] ?? null;
    $calMonth = $meta['cal_month'] ?? null;
    ?>
    <aside class="card tag-panel feed-sidebar">
        <h2>Теги</h2>
        <?php if ($popularTags === []): ?>
            <p class="card-meta">Тегов пока нет.</p>
        <?php else: ?>
            <div class="post-tags tag-panel-list">
                <a class="post-tag <?= $activeSlug === null ? 'is-active' : '' ?>"
                   href="<?= e(publicPostsUrl(1, $meta['author_id'] ?? null, $meta['search'] ?? null, null, $sort, $filterDate, $calMonth)) ?>">
                    Все
                </a>
                <?php foreach ($popularTags as $tag): ?>
                    <a class="post-tag <?= $activeSlug === $tag['slug'] ? 'is-active' : '' ?>"
                       href="<?= e(publicPostsUrl(1, $meta['author_id'] ?? null, $meta['search'] ?? null, $tag['slug'], $sort, $filterDate, $calMonth)) ?>">
                        #<?= e($tag['name']) ?> (<?= (int) $tag['posts_count'] ?>)
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>
    <?php
}
