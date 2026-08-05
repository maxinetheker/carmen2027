document.querySelectorAll('[data-hero-carousel]').forEach((carousel) => {
    const slides = [...carousel.querySelectorAll('[data-hero-slide]')];
    const dots = [...carousel.querySelectorAll('[data-hero-dot]')];
    if (slides.length < 2) return;

    let current = 0;
    let timer;
    let drag;
    let suppressClick = false;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const show = (index) => {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, position) => {
            slide.style.removeProperty('--hero-drag-x');
            const active = position === current;
            slide.classList.toggle('active', active);
            slide.setAttribute('aria-hidden', String(! active));
        });
        dots.forEach((dot, position) => dot.classList.toggle('active', position === current));
    };
    const stop = () => window.clearInterval(timer);
    const play = () => {
        stop();
        if (! reducedMotion.matches) timer = window.setInterval(() => show(current + 1), 6500);
    };
    const finishDrag = (event, cancelled = false) => {
        if (! drag || event.pointerId !== drag.pointerId) return;
        const distance = drag.distance;
        const threshold = Math.min(90, carousel.clientWidth * .12);
        carousel.classList.remove('dragging');
        slides[current].style.removeProperty('--hero-drag-x');
        if (carousel.hasPointerCapture(event.pointerId)) carousel.releasePointerCapture(event.pointerId);
        drag = null;
        if (! cancelled && Math.abs(distance) >= threshold) {
            suppressClick = true;
            show(current + (distance < 0 ? 1 : -1));
            window.setTimeout(() => { suppressClick = false; }, 0);
        }
        play();
    };

    carousel.querySelector('[data-hero-previous]')?.addEventListener('click', () => {
        show(current - 1);
        play();
    });
    carousel.querySelector('[data-hero-next]')?.addEventListener('click', () => {
        show(current + 1);
        play();
    });
    dots.forEach((dot) => dot.addEventListener('click', () => {
        show(Number(dot.dataset.heroDot));
        play();
    }));
    carousel.addEventListener('pointerdown', (event) => {
        if (! event.isPrimary || event.button !== 0
            || event.target.closest('.hero-carousel-controls, button, input, select')) return;
        drag = {
            pointerId: event.pointerId, startX: event.clientX,
            startY: event.clientY, distance: 0, horizontal: null,
        };
        stop();
    });
    carousel.addEventListener('pointermove', (event) => {
        if (! drag || event.pointerId !== drag.pointerId) return;
        const distanceX = event.clientX - drag.startX;
        const distanceY = event.clientY - drag.startY;
        if (drag.horizontal === null && Math.hypot(distanceX, distanceY) > 7) {
            drag.horizontal = Math.abs(distanceX) > Math.abs(distanceY);
            if (drag.horizontal) {
                carousel.setPointerCapture(event.pointerId);
                carousel.classList.add('dragging');
            }
        }
        if (! drag.horizontal) return;
        drag.distance = distanceX;
        const movement = Math.max(-150, Math.min(150, distanceX * .55));
        slides[current].style.setProperty('--hero-drag-x', `${movement}px`);
    });
    window.addEventListener('pointerup', (event) => finishDrag(event));
    window.addEventListener('pointercancel', (event) => finishDrag(event, true));
    carousel.addEventListener('click', (event) => {
        if (! suppressClick) return;
        event.preventDefault();
        event.stopPropagation();
        suppressClick = false;
    }, true);
    carousel.addEventListener('keydown', (event) => {
        if (! ['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        event.preventDefault();
        show(current + (event.key === 'ArrowRight' ? 1 : -1));
        play();
    });
    carousel.addEventListener('mouseenter', stop);
    carousel.addEventListener('mouseleave', play);
    carousel.addEventListener('focusin', stop);
    carousel.addEventListener('focusout', play);
    play();
});
