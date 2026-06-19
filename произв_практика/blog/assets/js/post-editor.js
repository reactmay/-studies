document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('post-form');
    const editorElement = document.getElementById('post-editor');
    const contentInput = document.getElementById('content');

    if (!form || !editorElement || !contentInput || typeof Quill === 'undefined') {
        return;
    }

    const quill = new Quill('#post-editor', {
        theme: 'snow',
        placeholder: 'Напишите текст поста и добавьте изображения...',
        modules: {
            toolbar: {
                container: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['blockquote', 'link', 'image'],
                    ['clean'],
                ],
                handlers: {
                    image: () => selectAndUploadImages(quill),
                },
            },
        },
    });

    if (contentInput.value.trim() !== '') {
        quill.root.innerHTML = contentInput.value;
    }

    form.addEventListener('submit', (event) => {
        const html = quill.root.innerHTML.trim();
        const plain = quill.getText().trim();

        if (plain.length < 10 && !html.includes('<img')) {
            event.preventDefault();
            window.alert('Добавьте текст (минимум 10 символов) или хотя бы одно изображение.');
            return;
        }

        contentInput.value = html;
    });
});

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
