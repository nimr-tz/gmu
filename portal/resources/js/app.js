import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Charts load only on pages that have one, so other pages stay light.
if (document.querySelector('[data-chart]')) {
    import('./charts').then(({ mountCharts }) => mountCharts());
}
