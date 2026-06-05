@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-legal-corrections.css') }}">
@endpush

@section('content')
<div class="admin-container">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Legal Corrections</h1>
            <p class="admin-page-subtitle">RA 9048 / RA 10172 petitions for civil registry corrections</p>
        </div>
        <a href="{{ route('admin.corrections.index') }}" class="alc-btn alc-btn-secondary alc-btn-sm">
            <i class="fas fa-arrow-left"></i> Internal Corrections
        </a>
    </div>

    <div class="admin-content">
        <!-- Stats Row -->
        <div class="alc-stats-row">
            <div class="alc-stat-card">
                <div class="alc-stat-count" style="color: #f59e0b;">{{ $stats['pending_approval'] ?? 0 }}</div>
                <div class="alc-stat-label">Pending Approval</div>
            </div>
            <div class="alc-stat-card">
                <div class="alc-stat-count" style="color: #10b981;">{{ $stats['approved'] ?? 0 }}</div>
                <div class="alc-stat-label">Approved</div>
            </div>
            <div class="alc-stat-card">
                <div class="alc-stat-count" style="color: #ef4444;">{{ $stats['rejected'] ?? 0 }}</div>
                <div class="alc-stat-label">Rejected</div>
            </div>
            <div class="alc-stat-card">
                <div class="alc-stat-count" style="color: #3b82f6;">{{ $stats['forwarded_to_psa'] ?? 0 }}</div>
                <div class="alc-stat-label">Forwarded to PSA</div>
            </div>
            <div class="alc-stat-card">
                <div class="alc-stat-count" style="color: #6b7280;">{{ $stats['total'] ?? 0 }}</div>
                <div class="alc-stat-label">Total Petitions</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="admin-filter-panel" style="margin-bottom: 1.5rem;">
                <form method="GET" action="{{ route('admin.corrections.legal.index') }}" style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="admin-form-label">Status</label>
                            <select name="status" id="status" class="admin-form-select">
                                <option value="pending_approval" {{ request('status', 'pending_approval') == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                                <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="forwarded_to_psa" {{ request('status') == 'forwarded_to_psa' ? 'selected' : '' }}>Forwarded to PSA</option>
                            </select>
                        </div>

                        <!-- Petition Type -->
                        <div>
                            <label for="petition_type" class="admin-form-label">Petition Type</label>
                            <select name="petition_type" id="petition_type" class="admin-form-select">
                                <option value="">All Types</option>
                                @foreach($petitionTypes as $value => $label)
                                    <option value="{{ $value }}" {{ request('petition_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Staff Filter -->
                        <div>
                            <label for="staff_id" class="admin-form-label">Filed By (Staff)</label>
                            <select name="staff_id" id="staff_id" class="admin-form-select">
                                <option value="">All Staff</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->id }}" {{ request('staff_id') == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Search -->
                        <div>
                            <label for="search" class="admin-form-label">Search</label>
                            <input type="text" name="search" id="search" class="admin-form-input" value="{{ request('search') }}"
                                placeholder="Petition # or Petitioner...">
                        </div>

                        <!-- Date From -->
                        <div>
                            <label for="date_from" class="admin-form-label">Date From</label>
                            <input type="date" name="date_from" id="date_from" class="admin-form-input" value="{{ request('date_from') }}">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label for="date_to" class="admin-form-label">Date To</label>
                            <input type="date" name="date_to" id="date_to" class="admin-form-input" value="{{ request('date_to') }}">
                        </div>

                        <!-- Buttons -->
                        <div style="display: flex; align-items: flex-end; grid-column: span 2;">
                            <div class="admin-btn-group">
                                <button type="submit" class="alc-btn alc-btn-primary">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <a href="{{ route('admin.corrections.legal.index') }}" class="alc-btn alc-btn-secondary">
                                    <i class="fas fa-undo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
        </div>

        <!-- Petitions Table -->
        <div class="admin-card">
            <div class="alc-table-wrap">
                @if($petitions->count() > 0)
                <table class="alc-table">
                    <thead>
                        <tr>
                            <th>Petition #</th>
                            <th>Type</th>
                            <th>Petitioner</th>
                            <th>Document</th>
                            <th>Filed By</th>
                            <th>Changes</th>
                            <th>Status</th>
                            <th>Date Filed</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($petitions as $petition)
                        <tr>
                            <td style="font-weight: 600; color: #111827;">{{ $petition->petition_number }}</td>
                            <td>
                                <span class="alc-type-pill">{{ $petition->petition_type_label }}</span>
                            </td>
                            <td>{{ $petition->petitioner_name }}</td>
                            <td>
                                @if($petition->scan)
                                    <span style="font-size: 0.75rem; color: #6b7280;">
                                        {{ ucwords(str_replace('_', ' ', $petition->scan->document_type)) }}
                                    </span>
                                @else
                                    <span style="color: #d1d5db;">—</span>
                                @endif
                            </td>
                            <td>{{ $petition->creator->name ?? 'N/A' }}</td>
                            <td style="text-align: center;">
                                <span style="background: #ede9fe; color: #6d28d9; padding: 0.125rem 0.5rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                    {{ $petition->fieldChanges->count() }}
                                </span>
                            </td>
                            <td>
                                <span class="alc-badge {{ $petition->status }}">{{ $petition->status_label }}</span>
                            </td>
                            <td style="font-size: 0.8125rem; color: #6b7280;">{{ $petition->created_at->format('M d, Y') }}</td>
                            <td style="text-align: center;">
                                <a href="{{ route('admin.corrections.legal.show', $petition) }}" class="alc-btn alc-btn-primary alc-btn-sm">
                                    <i class="fas fa-eye"></i> Review
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="alc-empty-state">
                    <i class="fas fa-gavel"></i>
                    <p>No legal correction petitions found.</p>
                    <span>Adjust your filters or wait for staff to submit petitions.</span>
                </div>
                @endif
            </div>

            @if($petitions->hasPages())
            <div style="padding: 1rem 1.25rem; border-top: 1px solid #e5e7eb;">
                {{ $petitions->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
