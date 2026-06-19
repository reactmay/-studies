<?php

declare(strict_types=1);

/** @var string $submitLabel */
/** @var string $cancelHref */
/** @var string $title */
/** @var string $content */
/** @var string $visibility */
/** @var string $error */
?>
<?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<form id="post-form" method="post">
    <div class="form-group">
        <label for="title">Заголовок</label>
        <input type="text" id="title" name="title" value="<?= e($title) ?>" required>
    </div>

    <div class="form-group post-editor-wrap">
        <label for="post-editor">Текст поста</label>
        <div id="post-editor"></div>
        <textarea id="content" name="content" hidden><?= e($content) ?></textarea>
        <p class="form-hint post-editor-hint">Используйте панель инструментов для форматирования. Кнопка «Изображение» позволяет добавить несколько картинок.</p>
    </div>

    <div class="form-group">
        <label for="visibility">Видимость</label>
        <select id="visibility" name="visibility">
            <option value="<?= e(POST_VISIBILITY_PUBLIC) ?>" <?= $visibility === POST_VISIBILITY_PUBLIC ? 'selected' : '' ?>>
                Публичный — виден всем
            </option>
            <option value="<?= e(POST_VISIBILITY_ON_REQUEST) ?>" <?= $visibility === POST_VISIBILITY_ON_REQUEST ? 'selected' : '' ?>>
                Только по запросу — скрыт, доступ по ссылке с кодом
            </option>
        </select>
        <?php if (!isset($hideVisibilityHint) || !$hideVisibilityHint): ?>
            <p class="form-hint">Скрытые посты не отображаются в общем списке. Ссылку с кодом вы получите после публикации.</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($showAccessLink) && !empty($accessLinkPost)): ?>
        <?php renderOwnerAccessLink($accessLinkPost); ?>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button>
        <a href="<?= e($cancelHref) ?>" class="btn btn-outline">Отмена</a>
    </div>
</form>
