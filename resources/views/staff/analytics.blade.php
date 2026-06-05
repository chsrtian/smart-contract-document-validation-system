@extends('layouts.staff')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/staff-analytics.css') }}">
@endpush

@section('content')
{{-- API endpoint URL exposed via meta (consumed by staff-analytics.js) --}}
<meta name="analytics-api-url" content="{{ route('staff.analytics.chart-data') }}">

<div class="analytics-page">

    {{-- ── Page Header ── --}}
    <div class="analytics-header">
        <h1 class="staff-page-title">Analytics Dashboard</h1>
        <p class="staff-page-subtitle">Visual overview of all processed documents — filter by time period to drill down.</p>
    </div>

    {{-- ── Summary Cards ── --}}
    <div class="analytics-summary">
        <div class="summary-card">
            <div class="summary-icon summary-icon--birth"><i class="fas fa-baby"></i></div>
            <div class="summary-text">
                <span class="summary-label">Birth Certificates</span>
                <span class="summary-value">{{ number_format($summary['birth']) }}</span>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon summary-icon--death"><i class="fas fa-cross"></i></div>
            <div class="summary-text">
                <span class="summary-label">Death Certificates</span>
                <span class="summary-value">{{ number_format($summary['death']) }}</span>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon summary-icon--marriage"><i class="fas fa-ring"></i></div>
            <div class="summary-text">
                <span class="summary-label">Marriage Certificates</span>
                <span class="summary-value">{{ number_format($summary['marriage']) }}</span>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon summary-icon--others"><i class="fas fa-folder-open"></i></div>
            <div class="summary-text">
                <span class="summary-label">Other Documents</span>
                <span class="summary-value">{{ number_format($summary['others']) }}</span>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon summary-icon--total"><i class="fas fa-layer-group"></i></div>
            <div class="summary-text">
                <span class="summary-label">Total Documents</span>
                <span class="summary-value">{{ number_format($summary['total']) }}</span>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         SECTION 1 — Overall Documents
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="analytics-section" data-section="overall">
        <div class="section-head">
            <h2 class="section-title"><i class="fas fa-globe"></i> Overall Documents</h2>
            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                <span class="section-stat section-stat--count"><i class="fas fa-file-alt"></i> <span>0</span></span>
                <span class="section-stat section-stat--confidence"><i class="fas fa-bullseye"></i> <span>0%</span></span>
                <div class="period-filters">
                    <button class="period-btn" data-section="overall" data-period="day">Day</button>
                    <button class="period-btn" data-section="overall" data-period="week">Week</button>
                    <button class="period-btn active" data-section="overall" data-period="month">Month</button>
                    <button class="period-btn" data-section="overall" data-period="year">Year</button>
                </div>
            </div>
        </div>
        <div class="charts-grid charts-grid--two">
            {{-- Bar --}}
            <div class="chart-card">
                <div class="chart-card__title">Documents by Period (Bar)</div>
                <div class="chart-card__body">
                    <canvas class="chart-bar"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
            {{-- Donut --}}
            <div class="chart-card chart-card--donut">
                <div class="chart-card__title">Distribution (Donut)</div>
                <div class="chart-card__body">
                    <canvas class="chart-donut"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
            {{-- Line / Area --}}
            <div class="chart-card" style="grid-column: 1 / -1;">
                <div class="chart-card__title">Trend Over Time (Area)</div>
                <div class="chart-card__body">
                    <canvas class="chart-line"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         SECTION 2 — Birth Certificates
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="analytics-section" data-section="birth">
        <div class="section-head">
            <h2 class="section-title"><i class="fas fa-baby" style="color:#3b82f6;"></i> Birth Certificates</h2>
            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                <span class="section-stat section-stat--count"><i class="fas fa-file-alt"></i> <span>0</span></span>
                <span class="section-stat section-stat--confidence"><i class="fas fa-bullseye"></i> <span>0%</span></span>
                <div class="period-filters">
                    <button class="period-btn" data-section="birth" data-period="day">Day</button>
                    <button class="period-btn" data-section="birth" data-period="week">Week</button>
                    <button class="period-btn active" data-section="birth" data-period="month">Month</button>
                    <button class="period-btn" data-section="birth" data-period="year">Year</button>
                </div>
            </div>
        </div>
        <div class="charts-grid charts-grid--two">
            <div class="chart-card">
                <div class="chart-card__title">Birth Certificates by Period (Bar)</div>
                <div class="chart-card__body">
                    <canvas class="chart-bar"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
            <div class="chart-card chart-card--donut">
                <div class="chart-card__title">Distribution (Donut)</div>
                <div class="chart-card__body">
                    <canvas class="chart-donut"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
            <div class="chart-card" style="grid-column: 1 / -1;">
                <div class="chart-card__title">Trend Over Time (Area)</div>
                <div class="chart-card__body">
                    <canvas class="chart-line"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         SECTION 3 — Death Certificates
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="analytics-section" data-section="death">
        <div class="section-head">
            <h2 class="section-title"><i class="fas fa-cross" style="color:#6b7280;"></i> Death Certificates</h2>
            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                <span class="section-stat section-stat--count"><i class="fas fa-file-alt"></i> <span>0</span></span>
                <span class="section-stat section-stat--confidence"><i class="fas fa-bullseye"></i> <span>0%</span></span>
                <div class="period-filters">
                    <button class="period-btn" data-section="death" data-period="day">Day</button>
                    <button class="period-btn" data-section="death" data-period="week">Week</button>
                    <button class="period-btn active" data-section="death" data-period="month">Month</button>
                    <button class="period-btn" data-section="death" data-period="year">Year</button>
                </div>
            </div>
        </div>
        <div class="charts-grid charts-grid--two">
            <div class="chart-card">
                <div class="chart-card__title">Death Certificates by Period (Bar)</div>
                <div class="chart-card__body">
                    <canvas class="chart-bar"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
            <div class="chart-card chart-card--donut">
                <div class="chart-card__title">Distribution (Donut)</div>
                <div class="chart-card__body">
                    <canvas class="chart-donut"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
            <div class="chart-card" style="grid-column: 1 / -1;">
                <div class="chart-card__title">Trend Over Time (Area)</div>
                <div class="chart-card__body">
                    <canvas class="chart-line"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         SECTION 4 — Marriage & Other Documents
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="analytics-section" data-section="marriage">
        <div class="section-head">
            <h2 class="section-title"><i class="fas fa-ring" style="color:#ec4899;"></i> Marriage Certificates</h2>
            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                <span class="section-stat section-stat--count"><i class="fas fa-file-alt"></i> <span>0</span></span>
                <span class="section-stat section-stat--confidence"><i class="fas fa-bullseye"></i> <span>0%</span></span>
                <div class="period-filters">
                    <button class="period-btn" data-section="marriage" data-period="day">Day</button>
                    <button class="period-btn" data-section="marriage" data-period="week">Week</button>
                    <button class="period-btn active" data-section="marriage" data-period="month">Month</button>
                    <button class="period-btn" data-section="marriage" data-period="year">Year</button>
                </div>
            </div>
        </div>
        <div class="charts-grid charts-grid--two">
            <div class="chart-card">
                <div class="chart-card__title">Marriage Certificates by Period (Bar)</div>
                <div class="chart-card__body">
                    <canvas class="chart-bar"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
            <div class="chart-card chart-card--donut">
                <div class="chart-card__title">Distribution (Donut)</div>
                <div class="chart-card__body">
                    <canvas class="chart-donut"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
            <div class="chart-card" style="grid-column: 1 / -1;">
                <div class="chart-card__title">Trend Over Time (Area)</div>
                <div class="chart-card__body">
                    <canvas class="chart-line"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="analytics-section" data-section="others">
        <div class="section-head">
            <h2 class="section-title"><i class="fas fa-folder-open" style="color:#f97316;"></i> Other Documents</h2>
            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                <span class="section-stat section-stat--count"><i class="fas fa-file-alt"></i> <span>0</span></span>
                <span class="section-stat section-stat--confidence"><i class="fas fa-bullseye"></i> <span>0%</span></span>
                <div class="period-filters">
                    <button class="period-btn" data-section="others" data-period="day">Day</button>
                    <button class="period-btn" data-section="others" data-period="week">Week</button>
                    <button class="period-btn active" data-section="others" data-period="month">Month</button>
                    <button class="period-btn" data-section="others" data-period="year">Year</button>
                </div>
            </div>
        </div>
        <div class="charts-grid charts-grid--two">
            <div class="chart-card">
                <div class="chart-card__title">Other Documents by Period (Bar)</div>
                <div class="chart-card__body">
                    <canvas class="chart-bar"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
            <div class="chart-card chart-card--donut">
                <div class="chart-card__title">Distribution (Donut)</div>
                <div class="chart-card__body">
                    <canvas class="chart-donut"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
            <div class="chart-card" style="grid-column: 1 / -1;">
                <div class="chart-card__title">Trend Over Time (Area)</div>
                <div class="chart-card__body">
                    <canvas class="chart-line"></canvas>
                    <div class="chart-loading"><div class="spinner"></div></div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
{{-- Analytics renderer --}}
<script src="{{ asset('js/staff-analytics.js') }}"></script>
@endpush
