<?php

declare(strict_types=1);

require_once __DIR__ . '/bbcode.php';

function postContentPlainText(string $html): string
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text ?? '') ?? '';

    return trim($text);
}

function postContentPreview(string $html, int $length = 220): string
{
    $text = postContentPlainText($html);

    if ($text === '') {
        return 'В посте только изображения';
    }

    return mb_strimwidth($text, 0, $length, '…');
}

function postContentHasImages(string $html): bool
{
    return (bool) preg_match('/<img\b/i', $html);
}

/** @return list<string> */
function postEditorFonts(): array
{
    return ['sans', 'serif', 'mono', 'comic', 'garamond', 'georgia'];
}

function sanitizePostHtml(string $html): string
{
    $html = trim($html);

    if ($html === '' || $html === '<p><br></p>' || $html === '<p></p>') {
        return '';
    }

    $allowed = '<p><br><strong><b><em><i><u><s><h2><h3><ul><ol><li><a><img><blockquote><pre><code><span>';
    $html = strip_tags($html, $allowed);

    $html = preg_replace_callback('/<pre\b[^>]*>(.*?)<\/pre>/is', static function (array $matches): string {
        $code = htmlspecialchars(strip_tags($matches[1]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<pre class="post-code-block"><code>' . $code . '</code></pre>';
    }, $html) ?? $html;

    [$html, $fontPlaceholders] = extractPostFontSpans($html);

    $html = preg_replace('/\s(on\w+|style|class)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? $html;
    $html = str_replace(array_keys($fontPlaceholders), array_values($fontPlaceholders), $html);
    $html = preg_replace('/href\s*=\s*("\s*javascript:[^"]*"|\'\s*javascript:[^\']*\')/iu', 'href="#"', $html) ?? $html;

    $html = preg_replace_callback('/<img\b([^>]*)>/iu', static function (array $matches): string {
        if (!preg_match('/src=(["\'])([^"\']+)\1/i', $matches[1], $srcMatch)) {
            return '';
        }

        $src = $srcMatch[2];
        if (!isAllowedPostImagePath($src)) {
            return '';
        }

        return '<img class="post-gallery-image" src="' . htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" alt="" loading="lazy">';
    }, $html) ?? $html;

    $html = preg_replace_callback('/<a\b([^>]*)>/iu', static function (array $matches): string {
        if (!preg_match('/href=(["\'])([^"\']+)\1/i', $matches[1], $hrefMatch)) {
            return '<a href="#" rel="noopener noreferrer" target="_blank">';
        }

        $href = $hrefMatch[2];
        if (!preg_match('#^https?://#i', $href)) {
            return '<a href="#" rel="noopener noreferrer" target="_blank">';
        }

        return '<a href="' . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" rel="noopener noreferrer" target="_blank">';
    }, $html) ?? $html;

    return $html;
}

/**
 * @return array{0: string, 1: array<string, string>}
 */
function extractPostFontSpans(string $html): array
{
    $placeholders = [];
    $allowed = postEditorFonts();

    $html = preg_replace_callback('/<span\b([^>]*)>(.*?)<\/span>/is', static function (array $matches) use (&$placeholders, $allowed): string {
        if (!preg_match('/\b(?:ql-font-|post-font-)(sans|serif|mono|comic|garamond|georgia)\b/i', $matches[1], $fontMatch)) {
            return $matches[0];
        }

        $font = strtolower($fontMatch[1]);
        if (!in_array($font, $allowed, true)) {
            return $matches[2];
        }

        $key = '%%POSTFONT' . count($placeholders) . '%%';
        $placeholders[$key] = '<span class="post-font-' . $font . '">' . $matches[2] . '</span>';

        return $key;
    }, $html) ?? $html;

    $html = preg_replace_callback('/<p\b([^>]*)>(.*?)<\/p>/is', static function (array $matches) use (&$placeholders, $allowed): string {
        if (!preg_match('/\b(?:ql-font-|post-font-)(sans|serif|mono|comic|garamond|georgia)\b/i', $matches[1], $fontMatch)) {
            return $matches[0];
        }

        $font = strtolower($fontMatch[1]);
        if (!in_array($font, $allowed, true)) {
            return '<p>' . $matches[2] . '</p>';
        }

        $key = '%%POSTFONT' . count($placeholders) . '%%';
        $placeholders[$key] = '<span class="post-font-' . $font . '">' . $matches[2] . '</span>';

        return '<p>' . $key . '</p>';
    }, $html) ?? $html;

    return [$html, $placeholders];
}

function isAllowedPostImagePath(string $path): bool
{
    return (bool) preg_match('#^uploads/posts/user_\d+/[a-zA-Z0-9._-]+$#', $path);
}

function validatePostContent(string $html, string $editorMode = 'visual'): array
{
    $html = preparePostRawContent($html, $editorMode);
    $content = sanitizePostHtml($html);
    $plain = postContentPlainText($content);

    if (mb_strlen($plain) < 10 && !postContentHasImages($content)) {
        return ['ok' => false, 'error' => 'Добавьте текст (минимум 10 символов) или хотя бы одно изображение.'];
    }

    return ['ok' => true, 'content' => $content];
}

/** @return list<string> */
function extractPostImagePaths(string $html): array
{
    preg_match_all('/<img\b[^>]*src=(["\'])([^"\']+)\1/i', $html, $matches);

    $paths = [];
    foreach ($matches[2] ?? [] as $path) {
        if (isAllowedPostImagePath($path)) {
            $paths[] = $path;
        }
    }

    return array_values(array_unique($paths));
}

function deletePostImagesFromContent(string $html): void
{
    $root = dirname(__DIR__);

    foreach (extractPostImagePaths($html) as $relativePath) {
        $absolutePath = $root . '/' . $relativePath;
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }
}

function postImagesUploadDir(int $userId): string
{
    $dir = dirname(__DIR__) . '/uploads/posts/user_' . $userId;

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

function uploadPostImage(int $userId, array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Файл не выбран.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Не удалось загрузить изображение.'];
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Размер изображения не должен превышать 5 МБ.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name'] ?? '');

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Допустимы только JPG, PNG, WEBP и GIF.'];
    }

    $filename = sprintf('img_%s.%s', bin2hex(random_bytes(8)), $allowed[$mime]);
    $uploadDir = postImagesUploadDir($userId);
    $destination = $uploadDir . '/' . $filename;
    $relativePath = 'uploads/posts/user_' . $userId . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['ok' => false, 'error' => 'Не удалось сохранить изображение.'];
    }

    return ['ok' => true, 'url' => $relativePath];
}

function renderPostContentHtml(string $html): void
{
    echo sanitizePostHtml($html);
}
