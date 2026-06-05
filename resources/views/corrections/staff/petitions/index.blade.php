@extends('layouts.staff')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/legal-corrections.css') }}">
@endpush

@section('content')
<div class="staff-page-shell py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="staff-page-title">Legal Correction Petitions</h1>
                    <p class="staff-page-subtitle">RA 9048 / RA 10172 — Petition for correction of civil registry entries</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('corrections.requests.index') }}" class="lc-btn lc-btn-secondary lc-btn-sm">
                        <i class="fas fa-arrow-left"></i> Internal Corrections
                    </a>
                    <a href="{{ route('corrections.petitions.create') }}" class="lc-btn lc-btn-primary">
                        <i class="fas fa-plus"></i> New Petition
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="lc-stats-row">
            <div class="lc-stat-card">
                <div class="lc-stat-icon total"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="lc-stat-value">{{ $stats['total'] ?? 0 }}</div>
                    <div class="lc-stat-label">Total</div>
                </div>
            </div>
            <div class="lc-stat-card">
                <div class="lc-stat-icon draft"><i class="fas fa-file-alt"></i></div>
                <div>
                    <div class="lc-stat-value">{{ $stats['draft'] ?? 0 }}</div>
                    <div class="lc-stat-label">Draft</div>
                </div>
            </div>
            <div class="lc-stat-card">
                <div class="lc-stat-icon pending"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="lc-stat-value">{{ $stats['pending'] ?? 0 }}</div>
                    <div class="lc-stat-label">Pending</div>
                </div>
            </div>
            <div class="lc-stat-card">
                <div class="lc-stat-icon approved"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="lc-stat-value">{{ $stats['approved'] ?? 0 }}</div>
                    <div class="lc-stat-label">Approved</div>
                </div>
            </div>
            <div class="lc-stat-card">
                <div class="lc-stat-icon rejected"><i class="fas fa-times-circle"></i></div>
                <div>
                    <div class="lc-stat-value">{{ $stats['rejected'] ?? 0 }}</div>
                    <div class="lc-stat-label">Rejected</div>
                </div>
            </div>
            <div class="lc-stat-card">
                <div class="lc-stat-icon forwarded"><i class="fas fa-share"></i></div>
                <div>
                    <div class="lc-stat-value">{{ $stats['forwarded'] ?? 0 }}</div>
                    <div class="lc-stat-label">Forwarded</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="lc-filter-bar">
            <form action="{{ route('corrections.petitions.index') }}" method="GET">
                <div class="lc-form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="forwarded_to_psa" {{ request('status') == 'forwarded_to_psa' ? 'selected' : '' }}>Forwarded to PSA</option>
                    </select>
                </div>
                <div class="lc-form-group">
                    <label for="petition_type">Petition Type</label>
                    <select name="petition_type" id="petition_type">
                        <option value="">All Types</option>
                        <option value="ra_9048_clerical" {{ request('petition_type') == 'ra_9048_clerical' ? 'selected' : '' }}>Clerical Error (RA 9048)</option>
                        <option value="ra_9048_first_name" {{ request('petition_type') == 'ra_9048_first_name' ? 'selected' : '' }}>First Name (RA 9048)</option>
                        <option value="ra_10172_gender" {{ request('petition_type') == 'ra_10172_gender' ? 'selected' : '' }}>Gender (RA 10172)</option>
                        <option value="ra_10172_birthdate" {{ request('petition_type') == 'ra_10172_birthdate' ? 'selected' : '' }}>Birthdate (RA 10172)</option>
                    </select>
                </div>
                <div class="lc-form-group">
                    <label for="search">Search</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Petition no. or petitioner...">
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
                    <button type="submit" class="lc-btn lc-btn-primary lc-btn-sm">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="{{ route('corrections.petitions.index') }}" class="lc-btn lc-btn-secondary lc-btn-sm">Clear</a>
                </div>
            </form>
        </div>

        <!-- Petitions Table -->
        @if($petitions->count() > 0)
        <div class="lc-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Petition No.</th>
                        <th>Type</th>
                        <th>Document</th>
                        <th>Petitioner</th>
                        <th>Fields</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($petitions as $petition)
                    <tr>
                        <td>
                            <span style="font-weight: 600; color: #111827;">{{ $petition->petition_number }}</span>
                        </td>
                        <td>
                            <span class="lc-type-pill">{{ $petition->petition_type_label }}</span>
                        </td>
                        <td>
                            @if($petition->scan)
                                <span title="{{ $petition->scan->document_id }}">
                                    {{ Str::limit($petition->scan->title ?? $petition->scan->document_id, 25) }}
                                </span>
                            @else
                                <span style="color: #9ca3af;">N/A</span>
                            @endif
                        </td>
                        <td>{{ Str::limit($petition->petitioner_name, 20) }}</td>
                        <td>
                            <span style="font-weight: 600;">{{ $petition->fieldChanges->count() }}</span>
                        </td>
                        <td>
                            <span class="lc-badge {{ $petition->status }}">
                                @if($petition->status === 'draft')
                                    <i class="fas fa-file-alt"></i>
                                @elseif($petition->status === 'pending_approval')
                                    <i class="fas fa-clock"></i>
                                @elseif($petition->status === 'approved')
                                    <i class="fas fa-check-circle"></i>
                                @elseif($petition->status === 'rejected')
                                    <i class="fas fa-times-circle"></i>
                                @elseif($petition->status === 'forwarded_to_psa')
                                    <i class="fas fa-share"></i>
                                @endif
                                {{ $petition->status_label }}
                            </span>
                        </td>
                        <td>{{ $petition->created_at->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('corrections.petitions.show', $petition) }}" class="lc-btn lc-btn-outline lc-btn-sm">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $petitions->appends(request()->query())->links() }}
        </div>
        @else
        <div class="lc-table-wrap">
            <div class="lc-empty-state">
                <i class="fas fa-gavel"></i>
                <p>No legal correction petitions found.</p>
                <a href="{{ route('corrections.petitions.create') }}" class="lc-btn lc-btn-primary">
                    <i class="fas fa-plus"></i> Create Your First Petition
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
