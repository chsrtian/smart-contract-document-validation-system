/* ==========================================================================
   Staff Analytics Module – Chart Renderer
   public/js/staff-analytics.js
   Uses Chart.js (loaded via CDN in the Blade view).
   ========================================================================== */

(function () {
    'use strict';

    /* ------------------------------------------------------------------ */
    /*  State                                                              */
    /* ------------------------------------------------------------------ */
    const charts   = {};          // sectionKey → { bar, line, donut }
    const periods  = {};          // sectionKey → current period string
    const API_URL  = document.querySelector('meta[name="analytics-api-url"]')?.content || '/staff/analytics/chart-data';

    /* ------------------------------------------------------------------ */
    /*  Bootstrap – run after DOM ready                                    */
    /* ------------------------------------------------------------------ */
    document.addEventListener('DOMContentLoaded', function () {
        const sections = document.querySelectorAll('.analytics-section[data-section]');
        sections.forEach(function (el) {
            const section = el.dataset.section;
            periods[section] = 'month';           // default period
            initCharts(section, el);
            loadData(section);
        });

        // Bind period filter buttons
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.period-btn');
            if (!btn) return;
            var section = btn.dataset.section;
            var period  = btn.dataset.period;
            if (!section || !period) return;

            // Toggle active pill
            btn.closest('.period-filters').querySelectorAll('.period-btn').forEach(function (b) {
                b.classList.remove('active');
            });
            btn.classList.add('active');

            periods[section] = period;
            loadData(section);
        });
    });

    /* ------------------------------------------------------------------ */
    /*  Create empty Chart.js instances for a section                      */
    /* ------------------------------------------------------------------ */
    function initCharts(section, container) {
        var barCtx   = container.querySelector('.chart-bar')?.getContext('2d');
        var lineCtx  = container.querySelector('.chart-line')?.getContext('2d');
        var donutCtx = container.querySelector('.chart-donut')?.getContext('2d');

        charts[section] = {};

        if (barCtx) {
            charts[section].bar = new Chart(barCtx, {
                type: 'bar',
                data: { labels: [], datasets: [] },
                options: barOptions(section)
            });
        }
        if (lineCtx) {
            charts[section].line = new Chart(lineCtx, {
                type: 'line',
                data: { labels: [], datasets: [] },
                options: lineOptions(section)
            });
        }
        if (donutCtx) {
            charts[section].donut = new Chart(donutCtx, {
                type: 'doughnut',
                data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
                options: donutOptions()
            });
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Fetch data from API and update charts                              */
    /* ------------------------------------------------------------------ */
    function loadData(section) {
        var period = periods[section];
        showLoading(section, true);

        fetch(API_URL + '?section=' + encodeURIComponent(section) + '&period=' + encodeURIComponent(period), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.csrfToken || ''
            }
        })
        .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(function (payload) {
            updateCharts(section, payload);
            updateSectionStats(section, payload);
            showLoading(section, false);
        })
        .catch(function (err) {
            console.error('Analytics fetch error (' + section + '):', err);
            showLoading(section, false);
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Push API data into Chart.js instances                              */
    /* ------------------------------------------------------------------ */
    function updateCharts(section, payload) {
        var ts   = payload.timeSeries   || { labels: [], datasets: [] };
        var dist = payload.distribution || { labels: [], data: [], backgroundColor: [] };

        // -- Bar chart
        if (charts[section]?.bar) {
            var bar = charts[section].bar;
            bar.data.labels   = ts.labels;
            bar.data.datasets = ts.datasets.map(function (ds) {
                return Object.assign({}, ds, {
                    fill: false,
                    backgroundColor: ds.borderColor,
                    borderRadius: 4,
                    maxBarThickness: 40
                });
            });
            bar.update('none');
        }

        // -- Line chart (same time-series data, different presentation)
        if (charts[section]?.line) {
            var line = charts[section].line;
            line.data.labels   = ts.labels;
            line.data.datasets = ts.datasets.map(function (ds) {
                return Object.assign({}, ds, {
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    backgroundColor: ds.borderColor + '22'
                });
            });
            line.update('none');
        }

        // -- Donut chart
        if (charts[section]?.donut) {
            var donut = charts[section].donut;
            donut.data.labels = dist.labels;
            donut.data.datasets = [{
                data: dist.data,
                backgroundColor: dist.backgroundColor,
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 6
            }];
            donut.update('none');
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Update stat badges (total count, avg confidence)                   */
    /* ------------------------------------------------------------------ */
    function updateSectionStats(section, payload) {
        var wrapper = document.querySelector('.analytics-section[data-section="' + section + '"]');
        if (!wrapper) return;
        var countEl = wrapper.querySelector('.section-stat--count span');
        var confEl  = wrapper.querySelector('.section-stat--confidence span');
        if (countEl) countEl.textContent = formatNumber(payload.totalCount || 0);
        if (confEl)  confEl.textContent  = (payload.avgConfidence || 0) + '%';
    }

    /* ------------------------------------------------------------------ */
    /*  Show / hide loading overlay per section                            */
    /* ------------------------------------------------------------------ */
    function showLoading(section, show) {
        var wrapper = document.querySelector('.analytics-section[data-section="' + section + '"]');
        if (!wrapper) return;
        wrapper.querySelectorAll('.chart-loading').forEach(function (el) {
            if (show) el.classList.remove('hidden');
            else      el.classList.add('hidden');
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Chart.js option presets                                            */
    /* ------------------------------------------------------------------ */
    function barOptions(section) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 14, font: { size: 12 } } },
                tooltip: { backgroundColor: '#1f2937', titleFont: { size: 13 }, bodyFont: { size: 12 }, padding: 10, cornerRadius: 8 }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 }, maxRotation: 45 } },
                y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 }, precision: 0 } }
            }
        };
    }

    function lineOptions(section) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 14, font: { size: 12 } } },
                tooltip: { backgroundColor: '#1f2937', titleFont: { size: 13 }, bodyFont: { size: 12 }, padding: 10, cornerRadius: 8 }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 }, maxRotation: 45 } },
                y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 }, precision: 0 } }
            }
        };
    }

    function donutOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 16, boxWidth: 12, font: { size: 12 } } },
                tooltip: { backgroundColor: '#1f2937', titleFont: { size: 13 }, bodyFont: { size: 12 }, padding: 10, cornerRadius: 8 }
            }
        };
    }

    /* ------------------------------------------------------------------ */
    /*  Utilities                                                          */
    /* ------------------------------------------------------------------ */
    function formatNumber(n) {
        return Number(n).toLocaleString();
    }
})();
