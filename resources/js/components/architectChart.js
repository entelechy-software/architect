/**
 * architectChart — Alpine data component wrapping ApexCharts.
 *
 * Registered in app.js as Alpine.data('architectChart', architectChartComponent).
 *
 * Usage in Blade:
 *   <div x-data="architectChart({{ json_encode($chartConfig) }})"></div>
 *
 * Config shapes (produced by DashboardEngine):
 *
 *   Time-series (line chart):
 *   { type: 'line', title, series: [{name, data: [[ts_ms, val], ...]}], granularity: 'H'|'D'|'M'|'A' }
 *
 *   Categorical bar chart:
 *   { type: 'bar', title, series: [{name, data: int[]}], categories: string[], horizontal?: bool, stacked?: bool }
 *
 *   Donut chart:
 *   { type: 'donut', title, series: int[], labels: string[] }
 *
 * The engine dispatches 'architect:chart-update' on granularity/date changes.
 * Only time-series charts respond — categorical charts re-render via Livewire.
 */

import ApexCharts from 'apexcharts';

/**
 * Map granularity code → ApexCharts xaxis tickAmount / datetime format hints.
 */
const GRANULARITY_FORMAT = {
    H: { unit: 'hour',  format: 'HH:mm',   tooltip: 'HH:mm dd MMM' },
    D: { unit: 'day',   format: 'dd MMM',   tooltip: 'dd MMM yyyy' },
    M: { unit: 'month', format: 'MMM yyyy', tooltip: 'MMM yyyy' },
    A: { unit: 'year',  format: 'yyyy',     tooltip: 'yyyy' },
};

/**
 * Build the full ApexCharts options object from the server-rendered config.
 * Branches into three modes: donut, categorical bar, or time-series.
 *
 * @param {object} config
 * @param {boolean} dark
 * @returns {object}
 */
function buildOptions(config, dark) {
    const textColor = dark ? '#9ca3af' : '#6b7280';
    const gridColor = dark ? '#374151' : '#e5e7eb';
    const colors    = ['#047db5', '#10b981', '#f59e0b', '#ef4444'];

    // ── Donut chart ────────────────────────────────────────────────────
    if (config.labels) {
        return {
            chart: {
                type: 'donut',
                height: 300,
                background: 'transparent',
                toolbar: { show: false },
                animations: { enabled: false },
                fontFamily: 'inherit',
            },
            theme: { mode: dark ? 'dark' : 'light' },
            series: config.series ?? [],
            labels: config.labels ?? [],
            legend: {
                position: 'bottom',
                labels: { colors: textColor },
            },
            dataLabels: {
                dropShadow: { enabled: false },
                style: { colors: ['#fff'] },
            },
            tooltip: { theme: dark ? 'dark' : 'light' },
            colors,
        };
    }

    // ── Categorical bar chart ──────────────────────────────────────────
    if (config.categories) {
        const horizontal = config.horizontal ?? false;
        return {
            chart: {
                type: 'bar',
                height: 300,
                background: 'transparent',
                toolbar: { show: false },
                animations: { enabled: false },
                fontFamily: 'inherit',
                stacked: config.stacked ?? false,
            },
            theme: { mode: dark ? 'dark' : 'light' },
            series: (config.series ?? []).map(s => ({ name: s.name ?? s.label, data: s.data ?? [] })),
            plotOptions: {
                bar: {
                    horizontal,
                    borderRadius: 3,
                    dataLabels: { position: horizontal ? 'center' : 'top' },
                },
            },
            xaxis: {
                categories: config.categories,
                labels: {
                    style: { colors: textColor },
                    ...(horizontal ? {} : { rotate: -35, trim: true, maxHeight: 80 }),
                },
            },
            yaxis: { labels: { style: { colors: textColor } } },
            dataLabels: { enabled: false },
            tooltip: { theme: dark ? 'dark' : 'light' },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                labels: { colors: textColor },
            },
            grid: { borderColor: gridColor },
            colors,
        };
    }

    // ── Time-series chart (line / area) ───────────────────────────────
    const fmt = GRANULARITY_FORMAT[config.granularity] ?? GRANULARITY_FORMAT.D;
    return {
        chart: {
            type: config.type ?? 'line',
            height: 300,
            background: 'transparent',
            toolbar: { show: false },
            animations: { enabled: false },
            fontFamily: 'inherit',
        },
        theme: { mode: dark ? 'dark' : 'light' },
        series: (config.series ?? []).map(s => ({ name: s.name ?? s.label, data: s.data ?? [] })),
        xaxis: {
            type: 'datetime',
            labels: {
                datetimeUTC: false,
                format: fmt.format,
                style: { colors: textColor },
            },
        },
        yaxis: { labels: { style: { colors: textColor } } },
        stroke: { curve: 'smooth', width: 2 },
        markers: { size: 3, hover: { size: 5 } },
        tooltip: {
            x: { format: fmt.tooltip },
            theme: dark ? 'dark' : 'light',
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left',
            labels: { colors: textColor },
        },
        grid: { borderColor: gridColor },
        colors,
    };
}

/**
 * Detect whether dark mode is currently active.
 *
 * @returns {boolean}
 */
function isDark() {
    return document.documentElement.classList.contains('dark');
}

export function registerArchitectChart(Alpine) {
    Alpine.data('architectChart', (config) => ({
        /** @type {ApexCharts|null} */
        _chart: null,

        /** @type {MutationObserver|null} */
        _darkObserver: null,

        /** @type {Function|null} */
        _updateHandler: null,

        /** @type {Function|null} */
        _resizeHandler: null,

        /** Section key resolved from nearest [data-section-key] ancestor, or null */
        _sectionKey: null,

        init() {
            // Wait one tick so the element is in the DOM
            this.$nextTick(() => {
                this._chart = new ApexCharts(this.$el, buildOptions(config, isDark()));
                this._chart.render();
                // Detect enclosing dashboard section key for height-change events
                this._sectionKey = this.$el.closest('[data-section-key]')?.dataset.sectionKey ?? null;
            });

            // Listen for Livewire browser event when date range / granularity changes.
            // Only time-series charts respond — categorical/donut charts re-render via Livewire.
            this._updateHandler = (event) => {
                if (!this._chart || config.categories || config.labels) return;
                const { series, granularity } = event.detail ?? {};
                const newConfig = { ...config, series: series ?? config.series, granularity: granularity ?? config.granularity };
                this._chart.updateOptions(buildOptions(newConfig, isDark()), false, false);
            };
            window.addEventListener('architect:chart-update', this._updateHandler);

            // Resize chart height when the enclosing dashboard section height changes.
            // ApexCharts ignores CSS min-height on its container; updateOptions is required.
            this._resizeHandler = (event) => {
                if (!this._chart || !this._sectionKey) return;
                const { key, height } = event.detail ?? {};
                if (key !== this._sectionKey) return;
                this._chart.updateOptions(
                    { chart: { height: height ?? 300 } },
                    false,
                    false
                );
            };
            window.addEventListener('architect:section-height-change', this._resizeHandler);

            // Re-theme when dark mode toggles
            this._darkObserver = new MutationObserver(() => {
                this._chart?.updateOptions({ theme: { mode: isDark() ? 'dark' : 'light' } }, false, false);
            });
            this._darkObserver.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class'],
            });
        },

        destroy() {
            this._chart?.destroy();
            this._chart = null;
            if (this._updateHandler) {
                window.removeEventListener('architect:chart-update', this._updateHandler);
            }
            if (this._resizeHandler) {
                window.removeEventListener('architect:section-height-change', this._resizeHandler);
            }
            this._darkObserver?.disconnect();
        },
    }));
}
