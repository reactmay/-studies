<?php

declare(strict_types=1);

function containsBbcode(string $text): bool
{
    return (bool) preg_match('/\[(b|i|u|s|url|img|quote|code|h2|h3|list)(?:=[^\]]+)?\]/i', $text);
}

function preparePostRawContent(string $raw, string $editorMode = 'visual'): string
{
    $raw = trim($raw);

    if ($raw === '') {
        return '';
    }

    if ($editorMode === 'bbcode' || ($editorMode !== 'visual' && containsBbcode($raw))) {
        return parseBbcode($raw);
    }

    return $raw;
}

function parseBbcode(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));
    $blocks = [];
    $index = 0;

    $text = preg_replace_callback(
        '/\[code\](.*?)\[\/code\]/si',
        static function (array $m) use (&$blocks, &$index): string {
            $code = htmlspecialchars($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $key = '%%CODEBLOCK' . $index . '%%';
            $blocks[$key] = '<pre class="post-code-block"><code>' . $code . '</code></pre>';
            $index++;
            return $key;
        },
        $text
    ) ?? $text;

    $text = preg_replace_callback(
        '/\[quote\](.*?)\[\/quote\]/si',
        static function (array $m): string {
            return '<blockquote>' . parseBbcodeInline($m[1]) . '</blockquote>';
        },
        $text
    ) ?? $text;

    $text = preg_replace_callback(
        '/\[list\](.*?)\[\/list\]/si',
        static function (array $m): string {
            $items = preg_split('/\[\*\]/', $m[1]) ?: [];
            $html = '<ul>';
            foreach ($items as $item) {
                $item = trim($item);
                if ($item === '') {
                    continue;
                }
                $html .= '<li>' . parseBbcodeInline($item) . '</li>';
            }
            $html .= '</ul>';
            return $html;
        },
        $text
    ) ?? $text;

    $text = preg_replace_callback(
        '/\[h2\](.*?)\[\/h2\]/si',
        static fn (array $m): string => '<h2>' . parseBbcodeInline($m[1]) . '</h2>',
        $text
    ) ?? $text;

    $text = preg_replace_callback(
        '/\[h3\](.*?)\[\/h3\]/si',
        static fn (array $m): string => '<h3>' . parseBbcodeInline($m[1]) . '</h3>',
        $text
    ) ?? $text;

    $paragraphs = preg_split("/\n{2,}/", $text) ?: [];
    $htmlParts = [];

    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph === '') {
            continue;
        }

        if (preg_match('/%%CODEBLOCK\d+%%/', $paragraph)) {
            $parts = preg_split('/(%%CODEBLOCK\d+%%)/', $paragraph, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
            foreach ($parts as $part) {
                if ($part === '') {
                    continue;
                }
                if (preg_match('/^%%CODEBLOCK\d+%%$/', $part)) {
                    $htmlParts[] = $part;
                    continue;
                }
                $inline = parseBbcodeInline(trim($part));
                if ($inline !== '') {
                    $inline = str_replace("\n", '<br>', $inline);
                    $htmlParts[] = '<p>' . $inline . '</p>';
                }
            }
            continue;
        }

        if (preg_match('/^%%CODEBLOCK\d+%%$/', $paragraph)) {
            $htmlParts[] = $paragraph;
            continue;
        }

        if (preg_match('/^<(blockquote|ul|h2|h3)\b/i', $paragraph)) {
            $htmlParts[] = $paragraph;
            continue;
        }

        $inline = parseBbcodeInline($paragraph);
        $inline = str_replace("\n", '<br>', $inline);
        $htmlParts[] = '<p>' . $inline . '</p>';
    }

    $html = implode("\n", $htmlParts);

    return str_replace(array_keys($blocks), array_values($blocks), $html);
}

function parseBbcodeInline(string $text): string
{
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $text = preg_replace_callback(
        '/\[url=(["\']?)([^"\]\s]+)\1\](.*?)\[\/url\]/si',
        static function (array $m): string {
            $href = bbcodeSanitizeUrl($m[2]);
            if ($href === null) {
                return $m[3];
            }
            return '<a href="' . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" rel="noopener noreferrer" target="_blank">' . $m[3] . '</a>';
        },
        $text
    ) ?? $text;

    $text = preg_replace_callback(
        '/\[url\](.*?)\[\/url\]/si',
        static function (array $m): string {
            $href = bbcodeSanitizeUrl($m[1]);
            if ($href === null) {
                return $m[1];
            }
            $safe = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return '<a href="' . $safe . '" rel="noopener noreferrer" target="_blank">' . $safe . '</a>';
        },
        $text
    ) ?? $text;

    $text = preg_replace_callback(
        '/\[img\](.*?)\[\/img\]/si',
        static function (array $m): string {
            $src = trim($m[1]);
            if (!preg_match('#^uploads/posts/user_\d+/[a-zA-Z0-9._-]+$#', $src)) {
                return '';
            }
            $safe = htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return '<img class="post-gallery-image" src="' . $safe . '" alt="" loading="lazy">';
        },
        $text
    ) ?? $text;

    $pairs = [
        '/\[b\](.*?)\[\/b\]/si' => '<strong>$1</strong>',
        '/\[i\](.*?)\[\/i\]/si' => '<em>$1</em>',
        '/\[u\](.*?)\[\/u\]/si' => '<u>$1</u>',
        '/\[s\](.*?)\[\/s\]/si' => '<s>$1</s>',
    ];

    foreach ($pairs as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text) ?? $text;
    }

    return $text;
}

function bbcodeSanitizeUrl(string $url): ?string
{
    $url = trim($url);
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return null;
    }

    return $url;
}

function htmlToBbcode(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    $html = preg_replace_callback(
        '/<pre\b[^>]*>\s*(?:<code[^>]*>)?(.*?)(?:<\/code>)?\s*<\/pre>/si',
        static fn (array $m): string => '[code]' . html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '[/code]',
        $html
    ) ?? $html;

    $replacements = [
        '/<blockquote[^>]*>(.*?)<\/blockquote>/si' => '[quote]$1[/quote]',
        '/<h2[^>]*>(.*?)<\/h2>/si' => '[h2]$1[/h2]',
        '/<h3[^>]*>(.*?)<\/h3>/si' => '[h3]$1[/h3]',
        '/<strong[^>]*>(.*?)<\/strong>/si' => '[b]$1[/b]',
        '/<b[^>]*>(.*?)<\/b>/si' => '[b]$1[/b]',
        '/<em[^>]*>(.*?)<\/em>/si' => '[i]$1[/i]',
        '/<i[^>]*>(.*?)<\/i>/si' => '[i]$1[/i]',
        '/<u[^>]*>(.*?)<\/u>/si' => '[u]$1[/u]',
        '/<s[^>]*>(.*?)<\/s>/si' => '[s]$1[/s]',
        '/<a[^>]*href=(["\'])([^"\']+)\1[^>]*>(.*?)<\/a>/si' => '[url=$2]$3[/url]',
        '/<img[^>]*src=(["\'])([^"\']+)\1[^>]*>/si' => '[img]$2[/img]',
        '/<br\s*\/?>/i' => "\n",
        '/<\/p>\s*<p[^>]*>/i' => "\n\n",
        '/<\/?p[^>]*>/i' => "\n",
        '/<li[^>]*>(.*?)<\/li>/si' => '[*]$1',
        '/<ul[^>]*>(.*?)<\/ul>/si' => '[list]$1[/list]',
    ];

    foreach ($replacements as $pattern => $replacement) {
        $html = preg_replace($pattern, $replacement, $html) ?? $html;
    }

    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

    return trim($text);
}
