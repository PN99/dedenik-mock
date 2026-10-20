(() => {
    'use strict';
    const toggle = document.querySelector('.menu-toggle');
    const menu = document.getElementById('main-menu');
    const secondary = document.getElementById('secondary-menu');
    if (!toggle || !menu) return;
    toggle.hidden = false;
    document.documentElement.classList.add('has-navigation-js');
    const close = (focus = false) => {
        toggle.setAttribute('aria-expanded', 'false');
        menu.classList.remove('is-open');
        secondary?.classList.remove('is-open');
        if (focus) toggle.focus();
    };
    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(open));
        menu.classList.toggle('is-open', open);
        secondary?.classList.toggle('is-open', open);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') close(true);
    });
    document.addEventListener('click', event => {
        if (!menu.contains(event.target) && !secondary?.contains(event.target) && !toggle.contains(event.target)) close();
    });
    window.matchMedia('(min-width: 1051px)').addEventListener('change', () => close());
})();
