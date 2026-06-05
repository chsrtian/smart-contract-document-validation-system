@extends('layouts.staff')

@section('title', $categoryName . ' Dashboard')


@section('content')

<link href="{{ asset('css/index.css') }}" rel="stylesheet">

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="mb-8">
            <div class="flex items-center justify-between">
                
                <div class="flex items-center space-x-4">
                    <a href="{{ route('staff.dashboard') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white rounded-xl shadow-sm border border-gray-200 hover:bg-gray-50 hover:border-gray-300 transition-all">
                        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            @if(in_array(strtolower($categoryName ?? ''), ['other', 'cenomar']))
                                Other Documents
                            @elseif(strtolower($categoryName ?? '') == 'birth_certificate')
                                Birth Certificates
                            @elseif(strtolower($categoryName ?? '') == 'marriage_certificate')
                                Marriage Certificates
                            @elseif(strtolower($categoryName ?? '') == 'death_certificate')
                                Death Certificates
                            @else
                                {{ ucwords(str_replace('_', ' ', $categoryName)) }} Documents
                            @endif
                        </h1>
                        
                        <p class="text-sm text-gray-500 mt-1">
                            @if(in_array(strtolower($categoryName ?? ''), ['other']))
                                Manage legal documents, affidavits, and CENOMAR
                            @else
                                Manage and track {{ strtolower(str_replace('_', ' ', $categoryName)) }} records
                            @endif
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-3">
                    <a href="{{ route('staff.scan') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add New
                    </a>
                </div>

            </div> </div>
        

        <div class="dashboard-grid">

            <div class="stat-card border-pending">
                <div style="display: flex; align-items: center;">
                    <div class="icon-box bg-orange">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="label-text">Pending</p>
                        <p class="value-text">{{ $stats['pending'] ?? 0 }}</p>
                    </div>
                </div>
                <span class="status-badge badge-orange">Review</span>
            </div>

            <div class="stat-card border-validated">
                <div style="display: flex; align-items: center;">
                    <div class="icon-box bg-green">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="label-text">Validated</p>
                        <p class="value-text">{{ $stats['validated'] ?? 0 }}</p>
                    </div>
                </div>
                <span class="status-badge badge-green">Verified</span>
            </div>

            <div class="stat-card border-released">
                <div style="display: flex; align-items: center;">
                    <div class="icon-box bg-blue">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <p class="label-text">Released</p>
                        <p class="value-text">{{ $stats['released'] ?? 0 }}</p>
                    </div>
                </div>
                <span class="status-badge badge-blue">Done</span>
            </div>

        </div>

        <!-- ═══════════════════════════════════════════════════════════════════════════ -->
        <!-- ROW 2: CHART / DATA VISUALIZATION - PROPER SPACING -->
        <!-- ═══════════════════════════════════════════════════════════════════════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-10 mb-8">
            
            <!-- Time-Based Chart (Line Chart) -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Document Activity</h3>
                        <p class="text-sm text-gray-500">Submissions over time</p>
                    </div>
                    
                    <!-- Period Filter Dropdown -->
                    <div class="relative">
                        <select id="period-filter" class="appearance-none bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 pr-8 text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">
                            <option value="today" {{ $currentPeriod === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="this_week" {{ $currentPeriod === 'this_week' ? 'selected' : '' }}>This Week</option>
                            <option value="this_month" {{ $currentPeriod === 'this_month' ? 'selected' : '' }}>This Month</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Chart Container -->
                <div class="relative h-64">
                    <canvas id="activity-chart"></canvas>
                </div>
                
                <!-- Chart Legend -->
                <div class="flex items-center justify-center space-x-6 mt-4 pt-4 border-t border-gray-100">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <span class="text-xs text-gray-600">Documents Submitted</span>
                    </div>
                </div>
            </div>

            <!-- Status Breakdown Doughnut Chart -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Status Breakdown</h3>
                    <p class="text-sm text-gray-500">Current distribution</p>
                </div>
                
                <!-- Doughnut Chart Container -->
                <div class="relative h-48 flex items-center justify-center">
                    <canvas id="status-chart"></canvas>
                </div>
                
                <!-- Status Legend -->
                <div class="space-y-3 mt-4 pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                            <span class="text-sm text-gray-700 font-medium">Pending</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">{{ $chartData['status_breakdown']['pending'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            <span class="text-sm text-gray-700 font-medium">Validated</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">{{ $chartData['status_breakdown']['validated'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                            <span class="text-sm text-gray-700 font-medium">Released</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">{{ $chartData['status_breakdown']['released'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════════════ -->
        <!-- ROW 3: MASTER LIST TABLE -->
        <!-- ═══════════════════════════════════════════════════════════════════════════ -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            
            <!-- Table Header with Search & Filters -->
            <div class="px-6 py-5 border-b border-gray-200">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ $categoryName }} Records</h3>
                        <p class="text-sm text-gray-500">{{ $stats['total'] }} total documents</p>
                    </div>
                    
                    <!-- Search & Filter Controls -->
                    <div class="flex flex-col sm:flex-row items-center gap-4 mb-6">
                        <!-- Search Input -->
                        <div class="relative w-full sm:w-72">
                            <input type="text" 
                                   id="search-input" 
                                   placeholder="Search documents..." 
                                   value="{{ $filters['search'] ?? '' }}"
                                   class="w-full h-11 pl-12 pr-4 bg-white border border-gray-300 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                        
                        <!-- Status Filter Dropdown -->
                        <div class="relative w-full sm:w-44">
                            <select id="status-filter" 
                                    class="w-full h-11 pl-4 pr-10 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 appearance-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                <option value="">All Status</option>
                                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Validated</option>
                                <option value="released" {{ ($filters['status'] ?? '') === 'released' ? 'selected' : '' }}>Released</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-auto">
                <table class="w-full" id="documents-table">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Document ID</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Owner Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Date Submitted</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="documents-tbody">
                        @forelse($documents as $document)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <!-- Document ID -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center mr-3">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $categoryConfig['icon'] }}" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $document->document_id }}</p>
                                            <p class="text-xs text-gray-500">{{ $document->type_name }}</p>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Owner Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($document->owner_name && $document->owner_name !== 'Name not available')
                                        <p class="text-sm font-medium text-gray-900">{{ $document->owner_name }}</p>
                                    @else
                                        <p class="text-sm text-gray-400">—</p>
                                    @endif
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                
                                @if($document->verification_status === 'pending')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-gray-900 border border-orange-200">
                                        <svg class="w-3 h-3 mr-1.5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                        </svg>
                                        Pending
                                    </span>

                                @elseif($document->verification_status === 'completed')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-gray-900 border border-green-200">
                                        <svg class="w-3 h-3 mr-1.5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Validated
                                    </span>

                                @elseif($document->verification_status === 'released')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-gray-900 border border-blue-200">
                                        <svg class="w-3 h-3 mr-1.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Released
                                    </span>

                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-900 border border-gray-200">
                                        {{ $document->status_label }}
                                    </span>
                                @endif

                            </td>
                                
                                <!-- Date Submitted -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm font-medium text-gray-900">{{ $document->date_formatted }}</p>
                                    <p class="text-xs text-gray-500">{{ $document->time_formatted }}</p>
                                </td>
                                
                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('staff.scans.show', $document->id) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all shadow-sm">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $categoryConfig['icon'] }}" />
                                            </svg>
                                        </div>
                                        <p class="text-gray-600 font-medium">No {{ strtolower($categoryName) }} documents found</p>
                                        <p class="text-gray-400 text-sm mt-1">Start by scanning or uploading a document</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($documents->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart colors
    const colors = {
        main: 'rgb(59, 130, 246)',
        light: 'rgba(59, 130, 246, 0.1)'
    };

    // Time Series Data from PHP
    const timeSeriesData = @json($chartData['time_series']);
    const statusBreakdown = @json($chartData['status_breakdown']);

    // ═══════════════════════════════════════════════════════════════════════════
    // ACTIVITY CHART (Line Chart)
    // ═══════════════════════════════════════════════════════════════════════════
    const activityCtx = document.getElementById('activity-chart').getContext('2d');
    const activityChart = new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: timeSeriesData.map(item => item.label),
            datasets: [{
                label: 'Documents',
                data: timeSeriesData.map(item => item.value),
                borderColor: colors.main,
                backgroundColor: colors.light,
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: colors.main,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: colors.main,
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 },
                    displayColors: false
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: { size: 11, weight: '500' },
                        color: '#6b7280'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(107, 114, 128, 0.08)',
                        drawBorder: false
                    },
                    ticks: {
                        font: { size: 11, weight: '500' },
                        color: '#6b7280',
                        stepSize: 1,
                        padding: 8
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    // ═══════════════════════════════════════════════════════════════════════════
    // STATUS CHART (Doughnut Chart)
    // ═══════════════════════════════════════════════════════════════════════════
    const statusCtx = document.getElementById('status-chart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Validated', 'Released'],
            datasets: [{
                data: [
                    statusBreakdown.pending,
                    statusBreakdown.validated,
                    statusBreakdown.released
                ],
                backgroundColor: [
                    'rgb(249, 115, 22)',  // Orange
                    'rgb(34, 197, 94)',   // Green
                    'rgb(59, 130, 246)'   // Blue
                ],
                borderWidth: 0,
                cutout: '70%',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    boxWidth: 12,
                    boxHeight: 12,
                    boxPadding: 4
                }
            }
        }
    });

    // ═══════════════════════════════════════════════════════════════════════════
    // PERIOD FILTER - Update charts when changed
    // ═══════════════════════════════════════════════════════════════════════════
    const periodFilter = document.getElementById('period-filter');
    periodFilter.addEventListener('change', function() {
        const period = this.value;
        const category = '{{ $category }}';
        
        // Show loading state
        activityChart.data.labels = ['Loading...'];
        activityChart.data.datasets[0].data = [0];
        activityChart.update();

        // Fetch new chart data
        fetch(`/staff/categories/${category.replace('_certificate', '')}/chart-data?period=${period}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update activity chart
                activityChart.data.labels = data.data.time_series.map(item => item.label);
                activityChart.data.datasets[0].data = data.data.time_series.map(item => item.value);
                activityChart.update();

                // Update status chart
                statusChart.data.datasets[0].data = [
                    data.data.status_breakdown.pending,
                    data.data.status_breakdown.validated,
                    data.data.status_breakdown.released
                ];
                statusChart.update();
            }
        })
        .catch(error => {
            console.error('Failed to load chart data:', error);
        });
    });

    // ═══════════════════════════════════════════════════════════════════════════
    // SEARCH & FILTER - Reload page with filters
    // ═══════════════════════════════════════════════════════════════════════════
    let searchTimeout;
    const searchInput = document.getElementById('search-input');
    const statusFilter = document.getElementById('status-filter');

    function applyFilters() {
        const params = new URLSearchParams(window.location.search);
        
        if (searchInput.value) {
            params.set('search', searchInput.value);
        } else {
            params.delete('search');
        }
        
        if (statusFilter.value) {
            params.set('status', statusFilter.value);
        } else {
            params.delete('status');
        }

        window.location.search = params.toString();
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    statusFilter.addEventListener('change', applyFilters);
});
</script>

@endsection