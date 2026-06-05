@extends('layouts.staff')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/staff-theme.css') }}">
<link rel="stylesheet" href="{{ asset('css/staff-dashboard.css') }}">
@endpush

@section('content')
@php
    $documentService = app(\App\Services\DocumentService::class);
    $stats = $documentService->getDashboardStatistics();
    $recentDocuments = $documentService->getRecentDocumentsForTable(10);

    $documentTypeLabels = [
        'birth_certificate' => 'Birth',
        'death_certificate' => 'Death',
        'marriage_certificate' => 'Marriage',
        'cenomar' => 'CENOMAR',
        'affidavit' => 'Affidavit',
        'court_document' => 'Court Document',
        'contract' => 'Contract',
        'other' => 'Other',
    ];

    $formatStatusTypeBreakdown = static function (array $typeCounts) use ($documentTypeLabels): string {
        if (empty($typeCounts)) {
            return '';
        }

        $parts = [];
        foreach ($typeCounts as $type => $count) {
            if ((int) $count <= 0) {
                continue;
            }

            $label = $documentTypeLabels[$type] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $type));
            $parts[] = $label . ': ' . number_format((int) $count);
        }

        return implode(' · ', $parts);
    };

    $pendingTypeBreakdown = $formatStatusTypeBreakdown($stats['status_type_breakdown']['pending'] ?? []);
    $validatedTypeBreakdown = $formatStatusTypeBreakdown($stats['status_type_breakdown']['completed'] ?? []);
    $rejectedTypeBreakdown = $formatStatusTypeBreakdown($stats['status_type_breakdown']['rejected'] ?? []);
@endphp

<div class="dashboard-container">
    
    <!-- Page Header -->
    <div class="dashboard-header">
        <div class="dashboard-header-content">
            <h1 class="staff-page-title">Dashboard Overview</h1>
            <p class="staff-page-subtitle">Office-wide document statistics and recent activity</p>
        </div>
        
        <div class="system-status">
            <span class="system-status-dot"></span>
            <span>System Online</span>
        </div>
    </div>

    <!-- Row 1: Summary Metrics (KPIs first) -->
    <!-- Tier Header: Performance Metrics -->
    <div class="tier-header">
        <span class="tier-header-label">Performance Metrics</span>
        <div class="tier-header-rule"></div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        
        <!-- Total Documents -->
        <div class="stat-card">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <span class="stat-card-label">Total Documents</span>
                    <span class="stat-card-value animated-counter" data-target="{{ $stats['total_documents'] }}">0</span>
                    <span class="stat-card-meta stat-card-meta--positive">
                        <i class="fas fa-arrow-up"></i>
                        +{{ $stats['total_documents_change'] ?? 0 }}% this month
                    </span>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
            </div>
        </div>

        <!-- Pending - Using solid/filled icon -->
        <div class="stat-card stat-card--warning">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <span class="stat-card-label">Pending Review</span>
                    <span class="stat-card-value animated-counter" data-target="{{ $stats['pending_documents'] }}">0</span>
                    <span class="stat-card-meta stat-card-meta--neutral">
                        <i class="fas fa-clock"></i>
                        Awaiting validation
                    </span>
                    @if($pendingTypeBreakdown !== '')
                        <span class="stat-card-breakdown">{{ $pendingTypeBreakdown }}</span>
                    @endif
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-hourglass"></i>
                </div>
            </div>
        </div>

        <!-- Validated -->
        <div class="stat-card stat-card--success">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <span class="stat-card-label">Validated</span>
                    <span class="stat-card-value animated-counter" data-target="{{ $stats['validated_documents'] }}">0</span>
                    <span class="stat-card-meta stat-card-meta--positive">
                        <i class="fas fa-check"></i>
                        {{ $stats['success_rate'] ?? 0 }}% success rate
                    </span>
                    @if($validatedTypeBreakdown !== '')
                        <span class="stat-card-breakdown">{{ $validatedTypeBreakdown }}</span>
                    @endif
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <!-- Rejected -->
        <div class="stat-card stat-card--danger">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <span class="stat-card-label">Rejected</span>
                    <span class="stat-card-value animated-counter" data-target="{{ $stats['rejected_documents'] ?? 0 }}">0</span>
                    <span class="stat-card-meta stat-card-meta--negative">
                        <i class="fas fa-times"></i>
                        Failed validation
                    </span>
                    @if($rejectedTypeBreakdown !== '')
                        <span class="stat-card-breakdown">{{ $rejectedTypeBreakdown }}</span>
                    @endif
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: All-Time Category Breakdown -->
    <!-- Tier Header: Document Breakdown -->
    <div class="tier-header">
        <span class="tier-header-label">Document Breakdown</span>
        <div class="tier-header-rule"></div>
    </div>

    <div class="overall-category-grid">

        <!-- Birth Certificates (Overall) -->
        <div class="overall-category-card overall-category-card--birth">
            <div class="overall-category-card-content">
                <div class="overall-category-card-info">
                    <span class="overall-category-card-label">Birth Certificates</span>
                    <span class="overall-category-card-value animated-counter" data-target="{{ $stats['birth_certificate'] ?? 0 }}">0</span>
                    <span class="overall-category-card-badge">All time</span>
                </div>
                <div class="overall-category-card-icon">
                    <i class="fas fa-baby"></i>
                </div>
            </div>
        </div>

        <!-- Marriage Certificates (Overall) -->
        <div class="overall-category-card overall-category-card--marriage">
            <div class="overall-category-card-content">
                <div class="overall-category-card-info">
                    <span class="overall-category-card-label">Marriage Certificates</span>
                    <span class="overall-category-card-value animated-counter" data-target="{{ $stats['marriage_certificate'] ?? 0 }}">0</span>
                    <span class="overall-category-card-badge">All time</span>
                </div>
                <div class="overall-category-card-icon">
                    <i class="fas fa-heart"></i>
                </div>
            </div>
        </div>

        <!-- Death Certificates (Overall) -->
        <div class="overall-category-card overall-category-card--death">
            <div class="overall-category-card-content">
                <div class="overall-category-card-info">
                    <span class="overall-category-card-label">Death Certificates</span>
                    <span class="overall-category-card-value animated-counter" data-target="{{ $stats['death_certificate'] ?? 0 }}">0</span>
                    <span class="overall-category-card-badge">All time</span>
                </div>
                <div class="overall-category-card-icon">
                    <i class="fas fa-scroll"></i>
                </div>
            </div>
        </div>

        <!-- Other Certificates (Overall) -->
        <div class="overall-category-card overall-category-card--other">
            <div class="overall-category-card-content">
                <div class="overall-category-card-info">
                    <span class="overall-category-card-label">Other Certificates</span>
                    <span class="overall-category-card-value animated-counter" data-target="{{ ($stats['cenomar'] ?? 0) + ($stats['affidavit'] ?? 0) + ($stats['court_document'] ?? 0) + ($stats['contract'] ?? 0) + ($stats['other'] ?? 0) }}">0</span>
                    <span class="overall-category-card-badge">All time</span>
                </div>
                <div class="overall-category-card-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- Row 3: Daily Category Cards (Read-Only, Today's Totals) -->
    <div class="section-header">
        <div>
            <h2 class="section-title">Today's Submissions</h2>
            <p class="section-subtitle">Documents submitted today per category</p>
        </div>
    </div>

    <div class="daily-category-grid">

        <!-- Birth Certificates (Today) -->
        <div class="daily-category-card daily-category-card--birth">
            <div class="daily-category-card-content">
                <div class="daily-category-card-info">
                    <span class="daily-category-card-label">Birth Certificates</span>
                    <span class="daily-category-card-value animated-counter" data-target="{{ $stats['daily_birth_certificate'] ?? 0 }}">0</span>
                    <span class="daily-category-card-tag"><i class="fas fa-calendar-day"></i> Today</span>
                </div>
                <div class="daily-category-card-icon">
                    <i class="fas fa-baby"></i>
                </div>
            </div>
        </div>

        <!-- Marriage Certificates (Today) -->
        <div class="daily-category-card daily-category-card--marriage">
            <div class="daily-category-card-content">
                <div class="daily-category-card-info">
                    <span class="daily-category-card-label">Marriage Certificates</span>
                    <span class="daily-category-card-value animated-counter" data-target="{{ $stats['daily_marriage_certificate'] ?? 0 }}">0</span>
                    <span class="daily-category-card-tag"><i class="fas fa-calendar-day"></i> Today</span>
                </div>
                <div class="daily-category-card-icon">
                    <i class="fas fa-heart"></i>
                </div>
            </div>
        </div>

        <!-- Death Certificates (Today) -->
        <div class="daily-category-card daily-category-card--death">
            <div class="daily-category-card-content">
                <div class="daily-category-card-info">
                    <span class="daily-category-card-label">Death Certificates</span>
                    <span class="daily-category-card-value animated-counter" data-target="{{ $stats['daily_death_certificate'] ?? 0 }}">0</span>
                    <span class="daily-category-card-tag"><i class="fas fa-calendar-day"></i> Today</span>
                </div>
                <div class="daily-category-card-icon">
                    <i class="fas fa-scroll"></i>
                </div>
            </div>
        </div>

        <!-- Other Certificates (Today) -->
        <div class="daily-category-card daily-category-card--other">
            <div class="daily-category-card-content">
                <div class="daily-category-card-info">
                    <span class="daily-category-card-label">Other Certificates</span>
                    <span class="daily-category-card-value animated-counter" data-target="{{ $stats['daily_other'] ?? 0 }}">0</span>
                    <span class="daily-category-card-tag"><i class="fas fa-calendar-day"></i> Today</span>
                </div>
                <div class="daily-category-card-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- Recent Documents Section -->
    <div class="documents-section">
        <div class="documents-header">
            <div class="documents-header-left">
                <div class="documents-header-icon">
                    <i class="fas fa-history"></i>
                </div>
                <div class="documents-header-text">
                    <h3>Recent Documents</h3>
                    <p>Latest 10 document submissions</p>
                </div>
            </div>
            <a href="{{ route('staff.search') }}" class="documents-view-all">
                View All
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="documents-table-wrapper">
            @if(count($recentDocuments) > 0)
                <table class="documents-table">
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentDocuments as $document)
                            <tr onclick="window.location='{{ route('staff.scans.show', $document['id']) }}'">
                                <td>
                                    <span class="doc-type">{{ $document['document_type'] }}</span>
                                </td>
                                <td>
                                    <div class="doc-owner">
                                        @if($document['owner_name'] && $document['owner_name'] !== 'Name not available')
                                            <span class="doc-owner-name">{{ $document['owner_name'] }}</span>
                                        @else
                                            <span class="doc-owner-name doc-owner-name--missing">No name on record</span>
                                        @endif
                                        <span class="doc-owner-id">{{ $document['document_id'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($document['status']) {
                                            'completed' => 'validated',
                                            'pending' => 'pending',
                                            'released' => 'released',
                                            'rejected' => 'rejected',
                                            default => 'pending'
                                        };
                                        $statusIcon = match($document['status']) {
                                            'completed' => 'check-circle',
                                            'pending' => 'clock',
                                            'released' => 'paper-plane',
                                            'rejected' => 'times-circle',
                                            default => 'clock'
                                        };
                                    @endphp
                                    <span class="doc-status doc-status--{{ $statusClass }}">
                                        <i class="fas fa-{{ $statusIcon }}"></i>
                                        {{ $document['status_label'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="doc-date">
                                        <span class="doc-date-primary">{{ $document['date_submitted'] }}</span>
                                        <span class="doc-date-secondary">{{ $document['time_submitted'] }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="documents-empty">
                    <div class="documents-empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h4>No documents submitted yet</h4>
                    <p>Start by scanning or uploading a document</p>
                    <a href="{{ route('staff.scan') }}" class="documents-empty-action">
                        <i class="fas fa-camera"></i>
                        Scan Document
                    </a>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animated counters
    const counters = document.querySelectorAll('.animated-counter');
    
    const animateCounter = (counter) => {
        const target = parseInt(counter.getAttribute('data-target'), 10);
        if (isNaN(target)) {
            counter.textContent = '0';
            return;
        }
        
        const duration = 1200;
        const steps = 30;
        const stepTime = duration / steps;
        const increment = target / steps;
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.ceil(current).toLocaleString();
                setTimeout(updateCounter, stepTime);
            } else {
                counter.textContent = target.toLocaleString();
            }
        };
        
        updateCounter();
    };
    
    // Stagger animation start
    counters.forEach((counter, index) => {
        setTimeout(() => animateCounter(counter), 100 + (index * 50));
    });
    
    // Session validation
    const validateSession = () => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) return;
        
        fetch('{{ route("staff.validate-session") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).then(response => {
            if (response.status === 401 || response.status === 419) {
                window.location.href = '{{ route("login") }}';
            }
        }).catch(() => {});
    };
    
    setInterval(validateSession, 180000);
});
</script>
@endpush