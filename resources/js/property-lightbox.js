import { bindSwipe, normalizeIndex } from './gallery-swipe';

export const createLightbox = (images) => {
    const lightbox = document.createElement('div');
    lightbox.className = 'gallery-lightbox';
    lightbox.hidden = true;
    lightbox.setAttribute('role', 'dialog');
    lightbox.setAttribute('aria-modal', 'true');
    lightbox.setAttribute('aria-label', 'Visor de imágenes');
    lightbox.innerHTML = `
        <div class="gallery-lightbox-toolbar">
            <span data-lightbox-counter></span>
            <button type="button" data-lightbox-close aria-label="Cerrar visor">
                <span class="material-symbols-rounded" aria-hidden="true">close</span>
            </button>
        </div>
        <button type="button" class="gallery-lightbox-arrow gallery-lightbox-prev" data-lightbox-prev aria-label="Imagen anterior">
            <span class="material-symbols-rounded" aria-hidden="true">chevron_left</span>
        </button>
        <figure data-lightbox-stage>
            <img data-lightbox-image src="" alt="" draggable="false">
            <figcaption data-lightbox-caption></figcaption>
        </figure>
        <button type="button" class="gallery-lightbox-arrow gallery-lightbox-next" data-lightbox-next aria-label="Imagen siguiente">
            <span class="material-symbols-rounded" aria-hidden="true">chevron_right</span>
        </button>`;
    document.body.append(lightbox);

    const stage = lightbox.querySelector('[data-lightbox-stage]');
    const image = lightbox.querySelector('[data-lightbox-image]');
    const caption = lightbox.querySelector('[data-lightbox-caption]');
    const counter = lightbox.querySelector('[data-lightbox-counter]');
    const previousButton = lightbox.querySelector('[data-lightbox-prev]');
    const nextButton = lightbox.querySelector('[data-lightbox-next]');
    let current = 0;
    let returnFocus = null;

    const render = (index) => {
        current = normalizeIndex(index, images.length);
        const source = images[current];
        image.src = source.src;
        image.alt = source.alt;
        caption.textContent = source.alt;
        counter.textContent = `${current + 1} / ${images.length}`;
        previousButton.hidden = images.length < 2;
        nextButton.hidden = images.length < 2;
        [images[normalizeIndex(current - 1, images.length)], images[normalizeIndex(current + 1, images.length)]]
            .forEach((item) => { const preload = new Image(); preload.src = item.src; });
    };

    const previous = () => render(current - 1);
    const next = () => render(current + 1);
    const close = () => {
        lightbox.hidden = true;
        document.body.classList.remove('gallery-lightbox-open');
        image.removeAttribute('src');
        returnFocus?.focus?.();
    };
    const open = (index, trigger) => {
        returnFocus = trigger;
        render(index);
        lightbox.hidden = false;
        document.body.classList.add('gallery-lightbox-open');
        lightbox.querySelector('[data-lightbox-close]').focus();
    };

    previousButton.addEventListener('click', previous);
    nextButton.addEventListener('click', next);
    lightbox.querySelector('[data-lightbox-close]').addEventListener('click', close);
    lightbox.addEventListener('click', (event) => {
        if (event.target === lightbox) close();
    });
    lightbox.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close();
        if (event.key === 'ArrowLeft') previous();
        if (event.key === 'ArrowRight') next();
    });
    bindSwipe(stage, previous, next,
        (delta) => stage.style.setProperty('--lightbox-drag-x', `${delta}px`),
        () => stage.style.removeProperty('--lightbox-drag-x'));

    return { open };
};
