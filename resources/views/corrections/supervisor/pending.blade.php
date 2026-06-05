@extends('layouts.supervisor')

@section('content')
<link rel="stylesheet" href="{{ asset('css/supervisor-review.css') }}">

<div class="sv-container">
    
    <!-- Page Header -->
    <div class="sv-page-header">
        <nav class="sv-breadcrumb">
            <a href="{{ route('corrections.approval.dashboard') }}">Dashboard</a>
            <span class="sep"><i class="fas fa-chevron-right"></i></span>
            <span>Pending Requests</span>
        </nav>
        <div class="sv-header-row">
            <div>
                <h1>Pending Correction Requests</h1>
                <p>Review and approve or reject staff correction requests</p>
            </div>
            <div class="sv-pending-badge">
                <i class="fas fa-clock"></i>
                <span>{{ $requests->total() }} Pending</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form action="{{ route('corrections.approval.pending') }}" method="GET" class="sv-filter-bar">
        <div class="sv-filter-group">
            <label for="document_type">Document Type</label>
            <select name="document_type" id="document_type" onchange="this.form.submit()">
                <option value="">All Document Types</option>
                <option value="birth_certificate" {{ request('document_type') == 'birth_certificate' ? 'selected' : '' }}>Birth Certificate</option>
                <option value="marriage_certificate" {{ request('document_type') == 'marriage_certificate' ? 'selected' : '' }}>Marriage Certificate</option>
                <option value="death_certificate" {{ request('document_type') == 'death_certificate' ? 'selected' : '' }}>Death Certificate</option>
            </select>
        </div>
        <div class="sv-filter-actions">
            <button type="submit" class="sv-btn-filter primary">
                <i class="fas fa-filter"></i> Apply
            </button>
            <a href="{{ route('corrections.approval.pending') }}" class="sv-btn-filter secondary">
                <i class="fas fa-times"></i> Clear
            </a>
        </div>
    </form>

    <!-- Table -->
    @if($requests->count())
        <div class="sv-table-wrapper">
            <table class="sv-table">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>Document ID</th>
                        <th>Field to Correct</th>
                        <th>Requestor</th>
                        <th>Submitted</th>
                        <th>Urgency</th>
                        <th style="text-align: center; width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $req)
                        @php
                            $days = (int) $req->requested_at->copy()->startOfDay()->diffInDays(now()->startOfDay());
                            $dayLabel = $days === 1 ? 'day' : 'days';
                            if ($days >= 4) {
                                $urgencyClass = 'overdue';
                                $urgencyLabel = 'Overdue';
                                $urgencyIcon = 'exclamation-circle';
                            } elseif ($days >= 2) {
                                $urgencyClass = 'soon';
                                $urgencyLabel = 'Soon';
                                $urgencyIcon = 'clock';
                            } else {
                                $urgencyClass = 'new';
                                $urgencyLabel = 'New';
                                $urgencyIcon = 'check-circle';
                            }
                        @endphp
                        <tr>
                            <td class="doc-type">{{ $req->scan->document_type_name ?? 'Document' }}</td>
                            <td><span class="doc-id">{{ $req->scan->document_id ?? 'N/A' }}</span></td>
                            <td>{{ $req->field_display_name ?? 'N/A' }}</td>
                            <td class="requestor">{{ $req->requester->name ?? 'Unknown' }}</td>
                            <td class="date">{{ $req->requested_at->format('M d, Y') }}</td>
                            <td>
                                <span class="sv-urgency {{ $urgencyClass }}">
                                    <i class="fas fa-{{ $urgencyIcon }}"></i>
                                    {{ $urgencyLabel }} ({{ $days }} {{ $dayLabel }})
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('corrections.approval.review', $req) }}" class="sv-btn sv-btn-review">
                                    <i class="fas fa-eye"></i> Review
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            @if($requests->hasPages())
                <div class="sv-pagination">
                    {{ $requests->withQueryString()->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="sv-empty-state">
            <div class="icon">
                <i class="fas fa-check-double"></i>
            </div>
            <h3>All Caught Up!</h3>
            <p>No pending correction requests at the moment.</p>
        </div>
    @endif
    
</div>
@endsection