document.addEventListener('DOMContentLoaded', () => {
    initPostImageGallery();
});

function initPostImageGallery() {
    const containers = document.querySelectorAll('.post-content-html');

    if (containers.length === 0) {
        return;
    }

    const lightbox = createImageLightbox();
    document.body.appendChild(lightbox.element);

    containers.forEach((container) => {
        const images = Array.from(container.querySelectorAll('img.post-gallery-image, img'));

        images.forEach((img, index) => {
            img.classList.add('post-gallery-image');
            img.addEventListener('click', (event) => {
                event.preventDefault();
                lightbox.open(images, index);
            });
        });
    });
}

function createImageLightbox() {
    const root = document.createElement('div');
    root.className = 'image-lightbox';
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    root.innerHTML = `
        <div class="image-lightbox__backdrop" data-lightbox-close></div>
        <button type="button" class="image-lightbox__close" aria-label="Закрыть">&times;</button>
        <button type="button" class="image-lightbox__nav image-lightbox__nav--prev" aria-label="Предыдущее изображение">&#8249;</button>
        <button type="button" class="image-lightbox__nav image-lightbox__nav--next" aria-label="Следующее изображение">&#8250;</button>
        <div class="image-lightbox__content">
            <img class="image-lightbox__img" src="" alt="">
            <div class="image-lightbox__counter"></div>
        </div>
    `;

    const imgEl = root.querySelector('.image-lightbox__img');
    const counterEl = root.querySelector('.image-lightbox__counter');
    const prevBtn = root.querySelector('.image-lightbox__nav--prev');
    const nextBtn = root.querySelector('.image-lightbox__nav--next');
    const closeBtn = root.querySelector('.image-lightbox__close');
    const backdrop = root.querySelector('[data-lightbox-close]');

    let gallery = [];
    let currentIndex = 0;

    const updateView = () => {
        if (gallery.length === 0) {
            return;
        }

        const current = gallery[currentIndex];
        imgEl.src = current.src;
        imgEl.alt = current.alt || '';
        counterEl.textContent = `${currentIndex + 1} / ${gallery.length}`;
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex === gallery.length - 1;
    };

    const onKeyDown = (event) => {
        if (!root.classList.contains('is-open')) {
            return;
        }

        if (event.key === 'Escape') {
            close();
        } else if (event.key === 'ArrowLeft') {
            showPrev();
        } else if (event.key === 'ArrowRight') {
            showNext();
        }
    };

    const showPrev = () => {
        if (currentIndex > 0) {
            currentIndex -= 1;
            updateView();
        }
    };

    const showNext = () => {
        if (currentIndex < gallery.length - 1) {
            currentIndex += 1;
            updateView();
        }
    };

    const close = () => {
        root.classList.remove('is-open');
        imgEl.src = '';
        document.body.style.overflow = '';
        document.removeEventListener('keydown', onKeyDown);
        gallery = [];
        currentIndex = 0;
    };

    const open = (images, startIndex) => {
        gallery = images;
        currentIndex = Math.max(0, Math.min(startIndex, images.length - 1));
        updateView();
        root.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', onKeyDown);
        closeBtn.focus();
    };

    prevBtn.addEventListener('click', showPrev);
    nextBtn.addEventListener('click', showNext);
    closeBtn.addEventListener('click', close);
    backdrop.addEventListener('click', close);

    return { element: root, open, close };
}
