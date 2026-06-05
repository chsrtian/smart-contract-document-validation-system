<div class="ada-empty-state" role="status" aria-live="polite">
    <div class="ada-empty-icon" aria-hidden="true">
        <i class="fas fa-chart-line"></i>
    </div>
    <h3 class="ada-empty-title">{{ $title ?? 'No admin activity in this period' }}</h3>
    <p class="ada-empty-message">{{ $message ?? 'Try adjusting the date range or check back later.' }}</p>

    @if(!empty($primaryUrl) || !empty($secondaryUrl))
        <div class="ada-empty-actions">
            @if(!empty($primaryUrl))
                <a href="{{ $primaryUrl }}" class="admin-btn admin-btn-primary">
                    {{ $primaryLabel ?? 'Select Date Range' }}
                </a>
            @endif

            @if(!empty($secondaryUrl))
                <a href="{{ $secondaryUrl }}" class="admin-btn admin-btn-secondary">
                    {{ $secondaryLabel ?? 'Export Sample Data' }}
                </a>
            @endif
        </div>
    @endif
</div>
