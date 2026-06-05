<nav class="sv-navbar">
    <div class="sv-navbar-inner">
        <div class="sv-navbar-content">
            <!-- Brand -->
            <div class="sv-brand">
                <div class="sv-brand-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <span class="sv-brand-title">Supervisor Panel</span>
                    <span class="sv-brand-subtitle">LCRO | Correction Approval System</span>
                </div>
            </div>

            <!-- Navigation Links (Desktop) -->
            <div class="sv-nav-links">
                <a href="{{ route('corrections.approval.dashboard') }}"
                   class="sv-nav-link {{ request()->routeIs('corrections.approval.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('corrections.approval.pending') }}"
                   class="sv-nav-link {{ request()->routeIs('corrections.approval.pending') || request()->routeIs('corrections.approval.review') ? 'active' : '' }}">
                    <i class="fas fa-clock"></i>
                    <span>Pending</span>
                    @php
                        $pendingCount = \App\Models\CorrectionRequest::pending()->count();
                    @endphp
                    @if($pendingCount > 0)
                        <span class="sv-nav-badge">{{ $pendingCount }}</span>
                    @endif
                </a>

                <a href="{{ route('corrections.approval.history') }}"
                   class="sv-nav-link {{ request()->routeIs('corrections.approval.history') ? 'active' : '' }}">
                    <i class="fas fa-history"></i>
                    <span>History</span>
                </a>

                <a href="{{ route('corrections.approval.audit-log') }}"
                   class="sv-nav-link {{ request()->routeIs('corrections.approval.audit-log') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Audit Log</span>
                </a>
            </div>

            <!-- Right Side Actions -->
            <div class="sv-nav-actions">
                <!-- Dark Mode Toggle Button -->
                <button type="button" class="sv-theme-toggle" onclick="toggleDarkMode()" title="Toggle dark mode">
                    <i id="theme-toggle-icon" class="fas fa-moon"></i>
                </button>

                <!-- User Info (Desktop) -->
                <div class="sv-user-info">
                    <div class="sv-user-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <span class="sv-user-name">{{ Auth::user()->name ?? 'Supervisor' }}</span>
                        <span class="sv-user-role">Supervisor</span>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <button type="button" class="sv-mobile-menu-btn" id="supervisor-mobile-menu-button">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Logout Button -->
                <form method="POST" action="{{ route('logout') }}" class="sv-logout-form">
                    @csrf
                    <button type="submit" class="sv-logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="sv-mobile-menu" id="supervisor-mobile-menu">
        <a href="{{ route('corrections.approval.dashboard') }}"
           class="sv-mobile-link {{ request()->routeIs('corrections.approval.dashboard') ? 'active' : '' }}">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('corrections.approval.pending') }}"
           class="sv-mobile-link {{ request()->routeIs('corrections.approval.pending') ? 'active' : '' }}">
            <i class="fas fa-clock"></i>
            <span>Pending</span>
            @if($pendingCount > 0)
                <span class="sv-nav-badge">{{ $pendingCount }}</span>
            @endif
        </a>

        <a href="{{ route('corrections.approval.history') }}"
           class="sv-mobile-link {{ request()->routeIs('corrections.approval.history') ? 'active' : '' }}">
            <i class="fas fa-history"></i>
            <span>History</span>
        </a>

        <a href="{{ route('corrections.approval.audit-log') }}"
           class="sv-mobile-link {{ request()->routeIs('corrections.approval.audit-log') ? 'active' : '' }}">
            <i class="fas fa-clipboard-list"></i>
            <span>Audit Log</span>
        </a>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn  = document.getElementById('supervisor-mobile-menu-button');
    const menu = document.getElementById('supervisor-mobile-menu');
    if (!btn || !menu) return;

    btn.addEventListener('click', () => menu.classList.toggle('open'));

    document.addEventListener('click', function (e) {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove('open');
        }
    });
});
</script>