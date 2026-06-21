const POST_EDITOR_FONTS = ['sans', 'serif', 'mono', 'comic', 'garamond', 'georgia'];

const EMOJI_GROUPS = [
    {
        title: 'Смайлы',
        items: ['😀', '😃', '😄', '😁', '😅', '😂', '🙂', '😉', '😊', '😍', '🤩', '😎', '🤔', '😮', '😢', '😭', '😡', '👍', '👎', '👏', '🙏'],
    },
    {
        title: 'Сердца',
        items: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '💔', '💕', '💖', '✨', '⭐', '🔥', '💯', '✅', '❌', '⚠️', '❓', '❗', '💡'],
    },
    {
        title: 'Животные и еда',
        items: ['🐱', '🐶', '🐻', '🦊', '🐼', '🐸', '🐵', '🌸', '🌻', '🌈', '☀️', '🌙', '☕', '🍕', '🍔', '🍰', '🎂', '🍺', '🎉', '🎁'],
    },
];

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('post-form');
    const editorElement = document.getElementById('post-editor');
    const contentInput = document.getElementById('content');
    const bbcodeEditor = document.getElementById('bbcode-editor');
    const editorModeInput = document.getElementById('editor_mode');
    const visualPanel = document.getElementById('visual-editor-panel');
    const bbcodePanel = document.getElementById('bbcode-editor-panel');

    if (!form || !editorElement || !contentInput || typeof Quill === 'undefined') {
        return;
    }

    const Font = Quill.import('formats/font');
    Font.whitelist = POST_EDITOR_FONTS;
    Quill.register(Font, true);

    let currentMode = 'visual';
    let emojiPicker = null;

    const quill = new Quill('#post-editor', {
        theme: 'snow',
        placeholder: 'Напишите текст поста и добавьте изображения...',
        modules: {
            toolbar: {
                container: [
                    [{ font: POST_EDITOR_FONTS }],
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['blockquote', 'link', 'image', 'code-block', 'emoji'],
                    ['clean'],
                ],
                handlers: {
                    image: () => selectAndUploadImages(quill),
                    emoji: () => toggleEmojiPicker(quill),
                },
            },
        },
    });

    emojiPicker = createEmojiPicker(quill);

    if (contentInput.value.trim() !== '') {
        quill.root.innerHTML = contentInput.value;
    }

    document.querySelectorAll('.editor-mode-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const mode = tab.dataset.editorMode;
            if (mode === currentMode) {
                return;
            }

            closeEmojiPicker();

            if (mode === 'bbcode') {
                if (bbcodeEditor) {
                    bbcodeEditor.value = htmlToBbcode(quill.root.innerHTML);
                }
            } else {
                quill.root.innerHTML = bbcodeToHtml(bbcodeEditor?.value ?? '');
            }

            setEditorMode(mode);
        });
    });

    document.querySelectorAll('.bbcode-btn').forEach((button) => {
        button.addEventListener('click', () => {
            if (!bbcodeEditor) {
                return;
            }
            insertBbcodeTag(bbcodeEditor, button.dataset.bbcode);
        });
    });

    document.addEventListener('click', (event) => {
        if (!emojiPicker || emojiPicker.hidden) {
            return;
        }

        const target = event.target;
        if (target instanceof Node && emojiPicker.contains(target)) {
            return;
        }

        if (target instanceof Element && target.closest('.ql-emoji')) {
            return;
        }

        closeEmojiPicker();
    });

    form.addEventListener('submit', (event) => {
        closeEmojiPicker();

        if (currentMode === 'bbcode') {
            const bbText = bbcodeEditor?.value.trim() ?? '';
            if (bbText.length < 10 && !bbText.includes('[img]')) {
                event.preventDefault();
                window.alert('Добавьте текст (минимум 10 символов) или хотя бы одно изображение [img].');
                return;
            }
            contentInput.value = bbText;
            return;
        }

        const html = quill.root.innerHTML.trim();
        const plain = quill.getText().trim();

        if (plain.length < 10 && !html.includes('<img')) {
            event.preventDefault();
            window.alert('Добавьте текст (минимум 10 символов) или хотя бы одно изображение.');
            return;
        }

        contentInput.value = html;
    });

    function setEditorMode(mode) {
        currentMode = mode;
        if (editorModeInput) {
            editorModeInput.value = mode;
        }

        document.querySelectorAll('.editor-mode-tab').forEach((tab) => {
            const active = tab.dataset.editorMode === mode;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        if (visualPanel) {
            visualPanel.classList.toggle('is-active', mode === 'visual');
            visualPanel.hidden = mode !== 'visual';
        }
        if (bbcodePanel) {
            bbcodePanel.classList.toggle('is-active', mode === 'bbcode');
            bbcodePanel.hidden = mode !== 'bbcode';
        }
    }
});

function createEmojiPicker(quill) {
    const wrap = document.querySelector('.post-editor-wrap');
    if (!wrap) {
        return null;
    }

    const picker = document.createElement('div');
    picker.className = 'emoji-picker';
    picker.hidden = true;
    picker.setAttribute('role', 'dialog');
    picker.setAttribute('aria-label', 'Выбор эмодзи');

    EMOJI_GROUPS.forEach((group) => {
        const section = document.createElement('div');
        section.className = 'emoji-picker-group';

        const title = document.createElement('div');
        title.className = 'emoji-picker-title';
        title.textContent = group.title;
        section.appendChild(title);

        const grid = document.createElement('div');
        grid.className = 'emoji-picker-grid';

        group.items.forEach((emoji) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'emoji-picker-item';
            button.textContent = emoji;
            button.title = emoji;
            button.addEventListener('click', () => {
                insertEmoji(quill, emoji);
                closeEmojiPicker();
            });
            grid.appendChild(button);
        });

        section.appendChild(grid);
        picker.appendChild(section);
    });

    wrap.appendChild(picker);
    return picker;
}

function toggleEmojiPicker(quill) {
    const picker = document.querySelector('.emoji-picker');
    const button = document.querySelector('.ql-toolbar .ql-emoji');

    if (!picker || !button) {
        return;
    }

    if (!picker.hidden) {
        closeEmojiPicker();
        return;
    }

    const rect = button.getBoundingClientRect();
    const wrapRect = picker.offsetParent?.getBoundingClientRect() ?? { top: 0, left: 0 };

    picker.style.top = `${rect.bottom - wrapRect.top + 6}px`;
    picker.style.left = `${Math.max(0, rect.left - wrapRect.left)}px`;
    picker.hidden = false;
    quill.focus();
}

function closeEmojiPicker() {
    const picker = document.querySelector('.emoji-picker');
    if (picker) {
        picker.hidden = true;
    }
}

function insertEmoji(quill, emoji) {
    const range = quill.getSelection(true);
    quill.insertText(range.index, emoji, 'user');
    quill.setSelection(range.index + emoji.length, 0, 'user');
}

function insertBbcodeTag(textarea, tag) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.slice(start, end);
    let insertion = '';

    switch (tag) {
        case 'url': {
            const href = window.prompt('Адрес ссылки (https://...)', 'https://');
            if (!href) {
                return;
            }
            const label = selected || window.prompt('Текст ссылки', href) || href;
            insertion = `[url=${href}]${label}[/url]`;
            break;
        }
        case 'img': {
            const src = window.prompt('Путь к изображению (после загрузки в визуальном режиме)', 'uploads/posts/user_1/');
            if (!src) {
                return;
            }
            insertion = `[img]${src}[/img]`;
            break;
        }
        case 'code':
            insertion = selected
                ? `[code]${selected}[/code]`
                : '[code]\n\n[/code]';
            break;
        case 'quote':
            insertion = selected
                ? `[quote]${selected}[/quote]`
                : '[quote][/quote]';
            break;
        case 'h2':
            insertion = selected ? `[h2]${selected}[/h2]` : '[h2][/h2]';
            break;
        case 'list':
            insertion = selected
                ? `[list][*]${selected}[/list]`
                : '[list][*]пункт 1[*]пункт 2[/list]';
            break;
        default:
            insertion = selected
                ? `[${tag}]${selected}[/${tag}]`
                : `[${tag}][/${tag}]`;
    }

    textarea.setRangeText(insertion, start, end, 'end');
    textarea.focus();
}

function htmlToBbcode(html) {
    let text = html.trim();
    if (!text) {
        return '';
    }

    text = text.replace(/<pre[^>]*>\s*(?:<code[^>]*>)?([\s\S]*?)(?:<\/code>)?\s*<\/pre>/gi, (_, code) => `[code]${decodeHtml(stripTags(code))}[/code]`);
    text = text.replace(/<blockquote[^>]*>([\s\S]*?)<\/blockquote>/gi, (_, inner) => `[quote]${decodeHtml(stripTags(inner))}[/quote]`);
    text = text.replace(/<h2[^>]*>([\s\S]*?)<\/h2>/gi, (_, inner) => `[h2]${decodeHtml(stripTags(inner))}[/h2]`);
    text = text.replace(/<h3[^>]*>([\s\S]*?)<\/h3>/gi, (_, inner) => `[h3]${decodeHtml(stripTags(inner))}[/h3]`);
    text = text.replace(/<(strong|b)[^>]*>([\s\S]*?)<\/\1>/gi, (_, _t, inner) => `[b]${decodeHtml(stripTags(inner))}[/b]`);
    text = text.replace(/<(em|i)[^>]*>([\s\S]*?)<\/\1>/gi, (_, _t, inner) => `[i]${decodeHtml(stripTags(inner))}[/i]`);
    text = text.replace(/<u[^>]*>([\s\S]*?)<\/u>/gi, (_, inner) => `[u]${decodeHtml(stripTags(inner))}[/u]`);
    text = text.replace(/<s[^>]*>([\s\S]*?)<\/s>/gi, (_, inner) => `[s]${decodeHtml(stripTags(inner))}[/s]`);
    text = text.replace(/<a[^>]*href=["']([^"']+)["'][^>]*>([\s\S]*?)<\/a>/gi, (_, href, label) => `[url=${href}]${decodeHtml(stripTags(label))}[/url]`);
    text = text.replace(/<img[^>]*src=["']([^"']+)["'][^>]*>/gi, (_, src) => `[img]${src}[/img]`);
    text = text.replace(/<br\s*\/?>/gi, '\n');
    text = text.replace(/<\/p>\s*<p[^>]*>/gi, '\n\n');
    text = text.replace(/<\/?p[^>]*>/gi, '\n');
    text = text.replace(/<li[^>]*>([\s\S]*?)<\/li>/gi, (_, inner) => `[*]${decodeHtml(stripTags(inner))}`);
    text = text.replace(/<ul[^>]*>([\s\S]*?)<\/ul>/gi, (_, inner) => `[list]${inner}[/list]`);

    return decodeHtml(stripTags(text)).replace(/\n{3,}/g, '\n\n').trim();
}

function bbcodeToHtml(bbcode) {
    const text = bbcode.trim();
    if (!text) {
        return '';
    }

    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\[code\]([\s\S]*?)\[\/code\]/gi, (_, code) => `<pre class="post-code-block"><code>${code}</code></pre>`)
        .replace(/\[quote\]([\s\S]*?)\[\/quote\]/gi, (_, inner) => `<blockquote><p>${inner.replace(/\n/g, '<br>')}</p></blockquote>`)
        .replace(/\[h2\]([\s\S]*?)\[\/h2\]/gi, '<h2>$1</h2>')
        .replace(/\[h3\]([\s\S]*?)\[\/h3\]/gi, '<h3>$1</h3>')
        .replace(/\[b\]([\s\S]*?)\[\/b\]/gi, '<strong>$1</strong>')
        .replace(/\[i\]([\s\S]*?)\[\/i\]/gi, '<em>$1</em>')
        .replace(/\[u\]([\s\S]*?)\[\/u\]/gi, '<u>$1</u>')
        .replace(/\[s\]([\s\S]*?)\[\/s\]/gi, '<s>$1</s>')
        .replace(/\[url=(["']?)([^"\]\s]+)\1\]([\s\S]*?)\[\/url\]/gi, '<a href="$2" rel="noopener noreferrer" target="_blank">$3</a>')
        .replace(/\[url\]([\s\S]*?)\[\/url\]/gi, '<a href="$1" rel="noopener noreferrer" target="_blank">$1</a>')
        .replace(/\[img\]([\s\S]*?)\[\/img\]/gi, '<img class="post-gallery-image" src="$1" alt="">')
        .split(/\n{2,}/)
        .map((block) => {
            if (/^<(pre|blockquote|h2|h3|ul)/i.test(block.trim())) {
                return block;
            }
            return `<p>${block.replace(/\n/g, '<br>')}</p>`;
        })
        .join('');
}

function stripTags(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || '';
}

function decodeHtml(text) {
    const div = document.createElement('div');
    div.innerHTML = text;
    return div.textContent || '';
}

function selectAndUploadImages(quill) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/webp,image/gif';
    input.multiple = true;

    input.addEventListener('change', async () => {
        if (!input.files || input.files.length === 0) {
            return;
        }

        for (const file of input.files) {
            await uploadImageToEditor(quill, file);
        }
    });

    input.click();
}

async function uploadImageToEditor(quill, file) {
    const formData = new FormData();
    formData.append('image', file);

    try {
        const response = await fetch('upload_post_image.php', {
            method: 'POST',
            body: formData,
        });

        const data = await response.json();

        if (!response.ok || !data.ok || !data.url) {
            window.alert(data.error || 'Не удалось загрузить изображение.');
            return;
        }

        const range = quill.getSelection(true);
        quill.insertEmbed(range.index, 'image', data.url);
        quill.setSelection(range.index + 1);
    } catch (error) {
        window.alert('Ошибка загрузки изображения.');
    }
}
