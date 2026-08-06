import { bindSwipe, normalizeIndex } from './gallery-swipe';
import { createLightbox } from './property-lightbox';

document.querySelectorAll('[data-property-gallery]').forEach((gallery) => {
    const stage = gallery.querySelector('[data-gallery-stage]');
    const panels = [...gallery.querySelectorAll('[data-gallery-panel]')];
    const thumbnails = [...gallery.querySelectorAll('[data-gallery-target]')];
    const imageElements = panels.map((panel, panelIndex) => {
        const image = panel.querySelector('[data-gallery-open]');
        return image ? { element: image, panelIndex, src: image.currentSrc || image.src, alt: image.alt } : null;
    }).filter(Boolean);
    const lightbox = imageElements.length ? createLightbox(imageElements) : null;
    let current = 0;
    let suppressOpenUntil = 0;

    const show = (index) => {
        current = normalizeIndex(index, panels.length);
        panels.forEach((panel, panelIndex) => {
            panel.hidden = panelIndex !== current;
            panel.style.removeProperty('--gallery-drag-x');
            if (panelIndex !== current) {
                panel.querySelector('video')?.pause();
                const frame = panel.querySelector('iframe');
                if (frame) frame.src = frame.src;
            }
        });
        thumbnails.forEach((button, buttonIndex) => {
            const active = buttonIndex === current;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', String(active));
            if (active) button.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
        });
    };
    const previous = () => show(current - 1);
    const next = () => show(current + 1);

    thumbnails.forEach((button) => button.addEventListener('click', () => {
        show(Number(button.dataset.galleryTarget));
    }));
    gallery.querySelector('[data-gallery-prev]')?.addEventListener('click', previous);
    gallery.querySelector('[data-gallery-next]')?.addEventListener('click', next);
    stage.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') previous();
        if (event.key === 'ArrowRight') next();
    });
    bindSwipe(stage, previous, next,
        (delta) => panels[current].style.setProperty('--gallery-drag-x', `${delta}px`),
        () => {
            if (Math.abs(parseFloat(panels[current].style.getPropertyValue('--gallery-drag-x'))) > 8) {
                suppressOpenUntil = Date.now() + 300;
            }
            panels[current].style.removeProperty('--gallery-drag-x');
        });

    imageElements.forEach((item, imageIndex) => {
        const open = (event) => {
            if (Date.now() < suppressOpenUntil) return;
            lightbox.open(imageIndex, event.currentTarget);
        };
        item.element.addEventListener('click', open);
        panels[item.panelIndex].querySelector('[data-gallery-expand]')?.addEventListener('click', open);
    });
});
