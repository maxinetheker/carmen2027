import './property-content';
import './media-manager';
import './dirty-form';
import './hero-carousel';
import './site-settings';
import './youtube-videos';
import './searchable-select';
import './emoji-render';

if (document.querySelector('[data-property-gallery]')) {
    import('./property-gallery');
}
if (document.querySelector('[data-location-picker]')) {
    import('./property-location');
}
if (document.querySelector('[data-catalog-filters]')) {
    import('./catalog-filters');
}

const menuButton = document.querySelector('[data-menu-toggle]');
const menu = document.querySelector('[data-menu]');
const sidebarButton = document.querySelector('[data-sidebar-toggle]');
const sidebar = document.querySelector('[data-sidebar]');

menuButton?.addEventListener('click', () => {
    const open = menu?.classList.toggle('open');
    menuButton.setAttribute('aria-expanded', String(Boolean(open)));
});

sidebarButton?.addEventListener('click', () => {
    sidebar?.classList.toggle('open');
});

document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (! window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});

document.querySelectorAll('[data-reminder-card]').forEach((card) => {
    const frequency = card.querySelector('[data-frequency]');
    const weekday = card.querySelector('[data-weekday]');
    const sync = () => weekday.hidden = frequency.value !== 'weekly';
    frequency.addEventListener('change', sync);
    sync();
});

document.querySelectorAll('[data-auto-submit]').forEach((field) => {
    field.addEventListener('change', () => field.form?.requestSubmit());
});

document.querySelectorAll('.favorite-button').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.preventDefault();
        button.textContent = button.textContent === '♡' ? '♥' : '♡';
        button.setAttribute('aria-pressed', button.textContent === '♥');
    });
});

document.querySelectorAll('.flash').forEach((flash) => {
    window.setTimeout(() => flash.remove(), 4500);
});

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const revealTargets = document.querySelectorAll(
    '.section, .property-card, .metric-card, .panel, .data-card, .service-list article'
);

revealTargets.forEach((item) => item.classList.add('reveal'));
if (reducedMotion.matches) {
    revealTargets.forEach((item) => item.classList.add('reveal-visible'));
} else {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) return;
            entry.target.classList.add('reveal-visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.12 });
    revealTargets.forEach((item) => observer.observe(item));
}

const progress = document.createElement('span');
progress.className = 'scroll-progress';
document.body.append(progress);
const heroImages = document.querySelectorAll('.hero-slide img');
const siteHeader = document.querySelector('[data-header]');

const syncScrollMotion = () => {
    const scrollable = document.documentElement.scrollHeight - innerHeight;
    const ratio = scrollable > 0 ? scrollY / scrollable : 0;
    progress.style.transform = `scaleX(${ratio})`;
    siteHeader?.classList.toggle('scrolled', scrollY > 45);
    if (! reducedMotion.matches) heroImages.forEach((image) => {
        image.style.setProperty('--parallax-y', `${scrollY * 0.06}px`);
    });
};

window.addEventListener('scroll', syncScrollMotion, { passive: true });
syncScrollMotion();

if (window.matchMedia('(pointer: fine)').matches && ! reducedMotion.matches) {
    document.querySelectorAll('.property-card').forEach((card) => {
        card.addEventListener('pointermove', (event) => {
            const bounds = card.getBoundingClientRect();
            const x = (event.clientX - bounds.left) / bounds.width - .5;
            const y = (event.clientY - bounds.top) / bounds.height - .5;
            card.style.setProperty('--tilt-x', `${-y * 4}deg`);
            card.style.setProperty('--tilt-y', `${x * 4}deg`);
        });
        card.addEventListener('pointerleave', () => {
            card.style.removeProperty('--tilt-x');
            card.style.removeProperty('--tilt-y');
        });
    });
}
