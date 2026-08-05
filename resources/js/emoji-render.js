import twemoji from '@twemoji/api';

document.querySelectorAll('[data-emoji-render]').forEach((root) => {
    twemoji.parse(root, {
        base: 'https://cdn.jsdelivr.net/gh/jdecked/twemoji@17.0.3/assets/',
        className: 'emoji-glyph',
        folder: 'svg',
        ext: '.svg',
    });

    root.querySelectorAll('img.emoji-glyph').forEach((image) => {
        image.decoding = 'async';
        image.addEventListener('error', () => {
            image.replaceWith(document.createTextNode(image.alt));
        }, { once: true });
    });
});
