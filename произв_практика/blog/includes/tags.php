<?php

declare(strict_types=1);

const POST_SORT_NEWEST = 'newest';
const POST_SORT_OLDEST = 'oldest';
const POST_SORT_TITLE_ASC = 'title_asc';
const POST_SORT_TITLE_DESC = 'title_desc';

function normalizePostSort(string $sort): string
{
    return match ($sort) {
        POST_SORT_OLDEST, POST_SORT_TITLE_ASC, POST_SORT_TITLE_DESC => $sort,
        default => POST_SORT_NEWEST,
    };
}

function postSortLabel(string $sort): string
{
    return match (normalizePostSort($sort)) {
        POST_SORT_OLDEST => 'Сначала старые',
        POST_SORT_TITLE_ASC => 'По названию (А–Я)',
        POST_SORT_TITLE_DESC => 'По названию (Я–А)',
        default => 'Сначала новые',
    };
}

function postSortOrderSql(string $sort, string $postsAlias = 'posts'): string
{
    return match (normalizePostSort($sort)) {
        POST_SORT_OLDEST => $postsAlias . '.created_at ASC',
        POST_SORT_TITLE_ASC => $postsAlias . '.title ASC',
        POST_SORT_TITLE_DESC => $postsAlias . '.title DESC',
        default => $postsAlias . '.created_at DESC',
    };
}

function normalizeTagName(string $name): string
{
    $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

    return mb_substr($name, 0, 50);
}

function makeTagSlug(string $name): string
{
    $slug = mb_strtolower(normalizeTagName($name));
    $slug = preg_replace('/\s+/u', '-', $slug) ?? '';
    $slug = preg_replace('/[^\p{L}\p{N}-]+/u', '', $slug) ?? '';
    $slug = trim($slug, '-');

    if ($slug === '') {
        $slug = 'tag-' . substr(md5($name), 0, 8);
    }

    return mb_substr($slug, 0, 60);
}

/** @return list<string> */
function parseTagsInput(string $input): array
{
    if (trim($input) === '') {
        return [];
    }

    $parts = preg_split('/[,;]+/u', $input) ?: [];
    $tags = [];

    foreach ($parts as $part) {
        $name = normalizeTagName($part);

        if ($name === '' || mb_strlen($name) < 2) {
            continue;
        }

        $key = mb_strtolower($name);
        if (!isset($tags[$key])) {
            $tags[$key] = $name;
        }
    }

    return array_values(array_slice($tags, 0, 10));
}

function findOrCreateTag(string $name): int
{
    $name = normalizeTagName($name);
    $slug = makeTagSlug($name);

    $existing = getDb()->prepare('SELECT id FROM tags WHERE name = ? OR slug = ? LIMIT 1');
    $existing->execute([$name, $slug]);
    $row = $existing->fetch();

    if ($row) {
        return (int) $row['id'];
    }

    $baseSlug = $slug;
    $suffix = 1;

    while (true) {
        $check = getDb()->prepare('SELECT id FROM tags WHERE slug = ?');
        $check->execute([$slug]);

        if (!$check->fetch()) {
            break;
        }

        $slug = mb_substr($baseSlug, 0, 55) . '-' . $suffix;
        $suffix++;
    }

    $insert = getDb()->prepare('INSERT INTO tags (name, slug) VALUES (?, ?)');
    $insert->execute([$name, $slug]);

    return (int) getDb()->lastInsertId();
}

/** @param list<string> $tagNames */
function syncPostTags(int $postId, array $tagNames): void
{
    getDb()->prepare('DELETE FROM post_tags WHERE post_id = ?')->execute([$postId]);

    foreach ($tagNames as $index => $tagName) {
        $tagId = findOrCreateTag($tagName);
        $link = getDb()->prepare('INSERT INTO post_tags (post_id, tag_id, sort_order) VALUES (?, ?, ?)');
        $link->execute([$postId, $tagId, $index]);
    }
}

/** @return list<array{id: int, name: string, slug: string, sort_order: int}> */
function getPostTags(int $postId): array
{
    $stmt = getDb()->prepare('
        SELECT tags.id, tags.name, tags.slug, post_tags.sort_order
        FROM post_tags
        JOIN tags ON tags.id = post_tags.tag_id
        WHERE post_tags.post_id = ?
        ORDER BY post_tags.sort_order ASC, tags.name ASC
    ');
    $stmt->execute([$postId]);

    $tags = $stmt->fetchAll();

    foreach ($tags as &$tag) {
        $tag['id'] = (int) $tag['id'];
        $tag['sort_order'] = (int) $tag['sort_order'];
    }
    unset($tag);

    return $tags;
}

function tagsInputFromPost(int $postId): string
{
    $names = array_column(getPostTags($postId), 'name');

    return implode(', ', $names);
}

/** @param array<int, array<string, mixed>> $posts */
function attachTagsToPosts(array $posts): array
{
    if ($posts === []) {
        return $posts;
    }

    $ids = array_map(static fn (array $post): int => (int) $post['id'], $posts);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = getDb()->prepare("
        SELECT post_tags.post_id, tags.id, tags.name, tags.slug, post_tags.sort_order
        FROM post_tags
        JOIN tags ON tags.id = post_tags.tag_id
        WHERE post_tags.post_id IN ($placeholders)
        ORDER BY post_tags.sort_order ASC, tags.name ASC
    ");
    $stmt->execute($ids);

    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $postId = (int) $row['post_id'];
        $map[$postId][] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'sort_order' => (int) $row['sort_order'],
        ];
    }

    foreach ($posts as &$post) {
        $post['tags'] = $map[(int) $post['id']] ?? [];
    }
    unset($post);

    return attachCommentsCountToPosts($posts);
}

/** @param array<int, array<string, mixed>> $posts */
function attachCommentsCountToPosts(array $posts): array
{
    if ($posts === []) {
        return $posts;
    }

    $ids = array_map(static fn (array $post): int => (int) $post['id'], $posts);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = getDb()->prepare("
        SELECT post_id, COUNT(*) AS comments_count
        FROM comments
        WHERE post_id IN ($placeholders)
        GROUP BY post_id
    ");
    $stmt->execute($ids);

    $counts = [];
    foreach ($stmt->fetchAll() as $row) {
        $counts[(int) $row['post_id']] = (int) $row['comments_count'];
    }

    foreach ($posts as &$post) {
        $post['comments_count'] = $counts[(int) $post['id']] ?? 0;
    }
    unset($post);

    return $posts;
}

/** @return list<array{id: int, name: string, slug: string, posts_count: int}> */
function getPopularTags(int $limit = 20): array
{
    $stmt = getDb()->prepare('
        SELECT tags.id, tags.name, tags.slug, COUNT(post_tags.post_id) AS posts_count
        FROM tags
        JOIN post_tags ON post_tags.tag_id = tags.id
        JOIN posts ON posts.id = post_tags.post_id
        WHERE ' . publicPostsVisibilitySql('posts') . '
        GROUP BY tags.id, tags.name, tags.slug
        ORDER BY posts_count DESC, tags.name ASC
        LIMIT ?
    ');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    $tags = $stmt->fetchAll();

    foreach ($tags as &$tag) {
        $tag['id'] = (int) $tag['id'];
        $tag['posts_count'] = (int) $tag['posts_count'];
    }
    unset($tag);

    return $tags;
}

function getTagBySlug(string $slug): ?array
{
    $stmt = getDb()->prepare('SELECT id, name, slug FROM tags WHERE slug = ?');
    $stmt->execute([trim($slug)]);
    $tag = $stmt->fetch();

    if (!$tag) {
        return null;
    }

    $tag['id'] = (int) $tag['id'];

    return $tag;
}

function tagUrl(string $slug, string $sort = POST_SORT_NEWEST): string
{
    return publicPostsUrl(1, null, null, $slug, $sort);
}

/** @param list<array{id: int, name: string, slug: string, sort_order?: int}> $tags */
function renderPostTags(array $tags, ?string $activeSlug = null): void
{
    if ($tags === []) {
        return;
    }
    ?>
    <div class="post-tags">
        <?php foreach ($tags as $tag): ?>
            <a class="post-tag <?= ($activeSlug ?? '') === $tag['slug'] ? 'is-active' : '' ?>"
               href="<?= e(tagUrl($tag['slug'])) ?>">
                #<?= e($tag['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
}

function validateTagsInput(string $input): array
{
    $tags = parseTagsInput($input);

    if (count($tags) > 10) {
        return ['ok' => false, 'error' => 'Можно добавить не более 10 тегов.'];
    }

    return ['ok' => true, 'tags' => $tags];
}
