<?php

declare(strict_types=1);

/** @param array<string, mixed> $post */
function renderPostCard(array $post, bool $showPreview = true): void
{
    $previewLength = 220;
    $content = $showPreview
        ? ($post['preview'] ?? mb_strimwidth($post['content'], 0, $previewLength, '…'))
        : $post['content'];
    ?>
    <article class="card">
        <div class="card-header">
            <h2 class="card-title">
                <a href="post.php?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a>
            </h2>
        </div>
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
        <div class="card-content"><?= e($content) ?></div>
    </article>
    <?php
}

function renderPagination(array $meta, ?int $filterAuthorId = null): void
{
    $totalPages = (int) ($meta['total_pages'] ?? 0);
    $page = (int) ($meta['page'] ?? 1);

    if ($totalPages <= 1) {
        return;
    }
    ?>
    <nav class="pagination" aria-label="Навигация по страницам">
        <?php if ($page > 1): ?>
            <a class="btn btn-outline" href="<?= e(subscriptionFeedUrl($filterAuthorId, $page - 1)) ?>">← Назад</a>
        <?php endif; ?>

        <span class="pagination-info">Страница <?= $page ?> из <?= $totalPages ?></span>

        <?php if ($page < $totalPages): ?>
            <a class="btn btn-outline" href="<?= e(subscriptionFeedUrl($filterAuthorId, $page + 1)) ?>">Вперёд →</a>
        <?php endif; ?>
    </nav>
    <?php
}
