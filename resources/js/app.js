import Alpine from 'alpinejs';

// Alpine powers the small interactive bits of the UI: the mobile sidebar
// drawer, dropdown menus, and dismissible flash messages.
window.Alpine = Alpine;
Alpine.start();

// Load the amCharts dashboard bundle only on pages that actually have a chart,
// so its weight is code-split away from every other page.
if (document.querySelector('[data-chart]')) {
    import('./dashboard-charts.js');
}

// Flag the body while a text field is focused so the mobile bottom tab bar can
// slide out of the keyboard's way (see .kb-open in app.css). Checkboxes,
// radios, and buttons don't raise the keyboard, so they're excluded.
const raisesKeyboard = (el) =>
    el instanceof HTMLElement &&
    (el.matches('input:not([type=checkbox]):not([type=radio]):not([type=button]):not([type=submit]):not([type=reset])') ||
        el.matches('textarea') ||
        el.matches('select') ||
        el.isContentEditable);

document.addEventListener('focusin', (e) => {
    if (raisesKeyboard(e.target)) document.body.classList.add('kb-open');
});
document.addEventListener('focusout', (e) => {
    if (raisesKeyboard(e.target)) document.body.classList.remove('kb-open');
});
