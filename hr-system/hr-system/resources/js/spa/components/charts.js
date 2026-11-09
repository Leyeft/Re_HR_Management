import { BarElement, CategoryScale, Chart as ChartJS, LinearScale, Tooltip } from 'chart.js';

// Chart.js pieces the app uses (registered once).
ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip);

// Read a brand colour from the CSS variables in app.css, so charts follow the theme.
export const cssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

/**
 * Shared look for bar charts: thin bars with rounded ends, light grid,
 * muted axis text, whole-number ticks, and a hover tooltip on every bar.
 */
export function barOptions({ horizontal = false, tooltipLabel } = {}) {
    const muted = cssVar('--color-text-muted');
    const grid = cssVar('--color-border');
    const valueAxis = horizontal ? 'x' : 'y';
    const categoryAxis = horizontal ? 'y' : 'x';

    return {
        indexAxis: horizontal ? 'y' : 'x',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }, // one series — the card title names it
            tooltip: {
                backgroundColor: cssVar('--color-primary-hover'),
                padding: 10,
                cornerRadius: 6,
                displayColors: false,
                callbacks: tooltipLabel ? { label: tooltipLabel } : {},
            },
        },
        scales: {
            [valueAxis]: {
                beginAtZero: true,
                ticks: { precision: 0, color: muted },
                grid: { color: grid },
                border: { display: false },
            },
            [categoryAxis]: {
                ticks: { color: muted },
                grid: { display: false },
                border: { color: grid },
            },
        },
    };
}

// Bar styling: rounded data-end, thin bars, a little gap between them.
export const barStyle = {
    borderRadius: 4,
    borderSkipped: 'start',
    maxBarThickness: 28,
    categoryPercentage: 0.7,
};
