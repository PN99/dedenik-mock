(() => {
    'use strict';
    const dialog = document.getElementById('dedivadlo-dialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    const close = dialog.querySelector('.theatre-dialog-close');
    const triggers = document.querySelectorAll('[data-theatre-dialog]');
    if (!close || !triggers.length) return;
    let opener;
    let scrollLocked = false;
    triggers.forEach(link => {
        link.setAttribute('aria-haspopup', 'dialog');
        link.setAttribute('aria-controls', dialog.id);
        link.addEventListener('click', event => {
            if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            if (dialog.open) return;
            // Preserve the real page link if opening the dialog fails.
            try { dialog.showModal(); } catch { return; }
            event.preventDefault();
            opener = link;
            scrollLocked = !document.documentElement.classList.contains('has-theatre-dialog');
            document.documentElement.classList.add('has-theatre-dialog');
        });
    });
    close.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => {
        if (scrollLocked) document.documentElement.classList.remove('has-theatre-dialog');
        opener?.focus({ preventScroll: true });
    });
    // Escape and focus trapping are provided by the native modal dialog.
})();
