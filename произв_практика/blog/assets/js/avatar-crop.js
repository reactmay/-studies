document.addEventListener('DOMContentLoaded', () => {
    initAvatarCropper();
});

function initAvatarCropper() {
    const form = document.getElementById('avatar-form');
    const fileInput = document.getElementById('avatar-file');
    const uploadInput = document.getElementById('avatar-upload');
    const panel = document.getElementById('avatar-crop-panel');
    const image = document.getElementById('avatar-crop-image');
    const submitButton = document.getElementById('avatar-submit');
    const preview = document.querySelector('.avatar-crop-preview');

    if (!form || !fileInput || !uploadInput || !panel || !image || !submitButton || typeof Cropper === 'undefined') {
        return;
    }

    const OUTPUT_SIZE = 400;
    const MAX_SOURCE_SIZE = 5 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    let cropper = null;
    let objectUrl = null;

    const destroyCropper = () => {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        image.removeAttribute('src');
        panel.classList.add('is-hidden');
        submitButton.disabled = true;
        uploadInput.value = '';
    };

    fileInput.addEventListener('change', () => {
        const file = fileInput.files?.[0];

        destroyCropper();

        if (!file) {
            return;
        }

        if (!allowedTypes.includes(file.type)) {
            window.alert('Допустимы только JPG, PNG, WEBP и GIF.');
            fileInput.value = '';
            return;
        }

        if (file.size > MAX_SOURCE_SIZE) {
            window.alert('Исходное изображение не должно превышать 5 МБ.');
            fileInput.value = '';
            return;
        }

        objectUrl = URL.createObjectURL(file);
        image.src = objectUrl;
        panel.classList.remove('is-hidden');

        image.onload = () => {
            if (cropper) {
                cropper.destroy();
            }

            cropper = new Cropper(image, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.85,
                responsive: true,
                guides: true,
                background: false,
                preview: preview || undefined,
            });

            submitButton.disabled = false;
        };
    });

    panel.querySelector('[data-crop-zoom="-"]')?.addEventListener('click', () => {
        cropper?.zoom(-0.1);
    });

    panel.querySelector('[data-crop-zoom="+"]')?.addEventListener('click', () => {
        cropper?.zoom(0.1);
    });

    panel.querySelector('[data-crop-rotate="left"]')?.addEventListener('click', () => {
        cropper?.rotate(-90);
    });

    panel.querySelector('[data-crop-rotate="right"]')?.addEventListener('click', () => {
        cropper?.rotate(90);
    });

    panel.querySelector('[data-crop-reset]')?.addEventListener('click', () => {
        cropper?.reset();
    });

    form.addEventListener('submit', (event) => {
        if (!cropper) {
            event.preventDefault();
            window.alert('Сначала выберите изображение и настройте область обрезки.');
            return;
        }

        event.preventDefault();

        const canvas = cropper.getCroppedCanvas({
            width: OUTPUT_SIZE,
            height: OUTPUT_SIZE,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!canvas) {
            window.alert('Не удалось обрезать изображение.');
            return;
        }

        canvas.toBlob((blob) => {
            if (!blob) {
                window.alert('Не удалось подготовить аватар.');
                return;
            }

            const croppedFile = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
            const transfer = new DataTransfer();
            transfer.items.add(croppedFile);
            uploadInput.files = transfer.files;

            form.submit();
        }, 'image/jpeg', 0.92);
    });
}
