export const normalizeIndex = (index, length) => (index + length) % length;

export const bindSwipe = (element, onPrevious, onNext, onMove = null, onEnd = null) => {
    let pointerId = null;
    let startX = 0;
    let startY = 0;
    let deltaX = 0;
    let deltaY = 0;

    element.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        if (event.target.closest('button, a, iframe, video')) return;
        pointerId = event.pointerId;
        startX = event.clientX;
        startY = event.clientY;
        deltaX = 0;
        deltaY = 0;
        element.setPointerCapture?.(pointerId);
        element.classList.add('is-dragging');
    });

    element.addEventListener('pointermove', (event) => {
        if (event.pointerId !== pointerId) return;
        deltaX = event.clientX - startX;
        deltaY = event.clientY - startY;
        if (Math.abs(deltaX) > Math.abs(deltaY)) onMove?.(deltaX);
    });

    const finish = (event) => {
        if (event.pointerId !== pointerId) return;
        const threshold = Math.max(46, element.clientWidth * .08);
        const horizontal = Math.abs(deltaX) > Math.abs(deltaY) * 1.15;
        element.releasePointerCapture?.(pointerId);
        element.classList.remove('is-dragging');
        pointerId = null;
        onEnd?.();
        if (! horizontal || Math.abs(deltaX) < threshold) return;
        if (deltaX < 0) onNext();
        else onPrevious();
    };

    element.addEventListener('pointerup', finish);
    element.addEventListener('pointercancel', finish);
};
