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
