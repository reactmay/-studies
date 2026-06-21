<?php



declare(strict_types=1);



/** @var string $submitLabel */

/** @var string $cancelHref */

/** @var string $title */

/** @var string $content */

/** @var string $visibility */

/** @var string $error */

/** @var string $tagsInput */

$tagsInput = $tagsInput ?? '';

$bbcodeContent = htmlToBbcode($content ?? '');

?>

<?php if ($error !== ''): ?>

    <div class="alert alert-error"><?= e($error) ?></div>

<?php endif; ?>



<form id="post-form" method="post">

    <input type="hidden" name="editor_mode" id="editor_mode" value="visual">



    <div class="form-group">

        <label for="title">Заголовок</label>

        <input type="text" id="title" name="title" value="<?= e($title) ?>" required>

    </div>



    <div class="form-group post-editor-wrap">

        <div class="editor-mode-tabs" role="tablist" aria-label="Режим редактора">

            <button type="button" class="editor-mode-tab is-active" data-editor-mode="visual" role="tab" aria-selected="true">

                Визуальный

            </button>

            <button type="button" class="editor-mode-tab" data-editor-mode="bbcode" role="tab" aria-selected="false">

                BB-код

            </button>

        </div>



        <label for="post-editor">Текст поста</label>



        <div id="visual-editor-panel" class="editor-panel is-active">

            <div id="post-editor"></div>

        </div>



        <div id="bbcode-editor-panel" class="editor-panel" hidden>

            <div class="bbcode-toolbar" aria-label="Вставка BB-кодов">

                <button type="button" class="bbcode-btn" data-bbcode="b" title="Жирный">B</button>

                <button type="button" class="bbcode-btn" data-bbcode="i" title="Курсив"><em>I</em></button>

                <button type="button" class="bbcode-btn" data-bbcode="u" title="Подчёркнутый"><u>U</u></button>

                <button type="button" class="bbcode-btn" data-bbcode="s" title="Зачёркнутый"><s>S</s></button>

                <button type="button" class="bbcode-btn" data-bbcode="url" title="Ссылка">URL</button>

                <button type="button" class="bbcode-btn" data-bbcode="img" title="Изображение">IMG</button>

                <button type="button" class="bbcode-btn" data-bbcode="quote" title="Цитата">« »</button>

                <button type="button" class="bbcode-btn" data-bbcode="code" title="Блок кода">{ }</button>

                <button type="button" class="bbcode-btn" data-bbcode="h2" title="Заголовок 2">H2</button>

                <button type="button" class="bbcode-btn" data-bbcode="list" title="Список">•</button>

            </div>

            <textarea id="bbcode-editor" class="bbcode-textarea" rows="14" placeholder="Текст с BB-кодами, например: [b]жирный[/b], [code]echo 'Hello';[/code]"><?= e($bbcodeContent) ?></textarea>

        </div>



        <textarea id="content" name="content" hidden><?= e($content) ?></textarea>

        <p class="form-hint post-editor-hint">
            Визуальный режим: шрифт, эмодзи, форматирование и изображения.
            BB-код: [b], [i], [code], [url], [img] и другие теги.
        </p>

    </div>



    <div class="form-group">

        <label for="tags">Теги</label>

        <input type="text" id="tags" name="tags" value="<?= e($tagsInput) ?>" placeholder="php, блог, новости">

        <p class="form-hint">До 10 тегов через запятую. Порядок слева направо — порядок отображения на посте.</p>

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


