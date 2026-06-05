@extends('layouts.admin')

@section('content')
    <div class="admin-container">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Monthly Reports</h1>
                <p class="admin-page-subtitle">Generate and download system reports</p>
            </div>
        </div>

        <div class="admin-content">
            <!-- Generate New Report -->
            <div class="admin-card" style="margin-bottom: 1.5rem;">
                <div class="admin-card-body">
                    <h3 class="admin-section-title"><i class="fas fa-file-alt"></i> Generate New Report</h3>

                    <form action="{{ route('admin.reports.generate') }}" method="POST" style="display: flex; gap: 1rem; align-items: flex-end;">
                        @csrf

                        <div>
                            <label class="admin-form-label">Month</label>
                            <select name="month" class="admin-form-select" required>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $i == now()->subMonth()->month ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div>
                            <label class="admin-form-label">Year</label>
                            <select name="year" class="admin-form-select" required>
                                @for($i = now()->year; $i >= 2020; $i--)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-cog"></i> Generate Report
                </div>
            </div>

            <!-- Generated Reports List -->
            <div class="admin-card">
                <div class="admin-card-body">
                    <h3 class="admin-section-title"><i class="fas fa-folder-open"></i> Generated Reports ({{ count($reports) }})</h3>

                    @if($reports->isEmpty())
                        <div class="admin-empty-state">
                            <i class="fas fa-file-alt admin-empty-icon"></i>
                            <p class="admin-empty-title">No reports generated yet</p>
                            <p class="admin-empty-desc">Generate your first report above!</p>
                        </div>
                    @else
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Report Name</th>
                                        <th>Size</th>
                                        <th>Generated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reports as $report)
                                        <tr>
                                            <td class="admin-font-semibold">{{ $report['filename'] }}</td>
                                            <td>{{ round($report['size'] / 1024, 2) }} KB</td>
                                            <td>{{ date('M d, Y H:i', $report['modified']) }}</td>
                                            <td>
                                                <a href="{{ route('admin.reports.download', basename($report['path'])) }}"
                                                   class="admin-link">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection