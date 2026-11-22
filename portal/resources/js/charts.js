/**
 * Charts, drawn with Chart.js from data the server writes into the page:
 *
 *   <canvas data-chart='{"type":"bar","stacked":true,"labels":[…],
 *           "series":[{"label":"Concept note","color":"chart-1","data":[…]}]}'></canvas>
 *
 * Colours are token names (chart-1 … chart-5, primary), read from the CSS
 * variables, so charts follow the design system and never invent a colour.
 */
import {
    Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend,
    DoughnutController, ArcElement,
} from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend, DoughnutController, ArcElement);

const css = getComputedStyle(document.documentElement);
const color = (name, alpha = 1) => `rgb(${css.getPropertyValue(`--${name}`).trim()} / ${alpha})`;

Chart.defaults.font.family = 'Geist, ui-sans-serif, system-ui, sans-serif';
Chart.defaults.font.size = 12;
Chart.defaults.color = color('muted-foreground');

const tooltip = {
    backgroundColor: color('foreground'),
    titleColor: '#fff',
    bodyColor: color('border'),
    padding: 10,
    cornerRadius: 8,
    boxPadding: 4,
    usePointStyle: true,
};

function bar(el, spec) {
    const stacked = !!spec.stacked;
    return new Chart(el, {
        type: 'bar',
        data: {
            labels: spec.labels,
            datasets: spec.series.map((s, i) => ({
                label: s.label,
                data: s.data,
                backgroundColor: color(s.color),
                hoverBackgroundColor: color(s.color, 0.85),
                borderRadius: 4,
                borderSkipped: false,
                borderColor: color('card'),
                borderWidth: stacked ? { top: 2 } : 0,
                maxBarThickness: 56,
                categoryPercentage: 0.55,
                barPercentage: 1,
                order: i,
            })),
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...tooltip,
                    filter: (item) => item.raw > 0,
                    callbacks: spec.money ? { label: (c) => ` ${c.dataset.label}: TZS ${Number(c.raw).toLocaleString('en-GB')}` } : {},
                },
            },
            scales: {
                x: {
                    stacked,
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { family: 'Geist Mono, ui-monospace, monospace', size: 12 }, color: color('foreground') },
                },
                y: {
                    stacked,
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: color('border'), drawTicks: false },
                    ticks: { precision: 0, padding: 8, maxTicksLimit: 5,
                        callback: spec.money ? (v) => (v >= 1e6 ? `${v / 1e6}M` : v) : undefined },
                },
            },
        },
    });
}

export function mountCharts() {
    document.querySelectorAll('[data-chart]').forEach((el) => {
        try {
            const spec = JSON.parse(el.dataset.chart);
            if (spec.type === 'bar') bar(el, spec);
        } catch (e) {
            // A chart that cannot draw leaves its table fallback in place.
            console.error('chart', e);
        }
    });
}
