<nav class="admin-navbar">
    <div class="admin-navbar-inner">
        <div class="admin-navbar-content">
            
            <!-- Brand -->
            <div class="admin-brand">
                <div class="admin-brand-icon">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div class="admin-brand-text">
                    <span class="admin-brand-title">Admin Panel</span>
                    <span class="admin-brand-subtitle">Document Validation System</span>
                </div>
            </div>

            <!-- Primary Navigation Links (Desktop) - Reduced to 5 core items -->
            <div class="admin-nav-links">
                <a href="{{ route('admin.dashboard') }}" 
                   class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('admin.users.index') }}" 
                   class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>

                <a href="{{ route('admin.documents.index') }}" 
                   class="admin-nav-link {{ request()->routeIs('admin.documents.*') ? 'active' : '' }}">
                    <i class="fas fa-file-alt"></i>
                    <span>Documents</span>
                </a>

                <a href="{{ route('admin.corrections.index') }}" 
                   class="admin-nav-link {{ (request()->routeIs('admin.corrections.*') && !request()->routeIs('admin.corrections.legal.*')) || request()->routeIs('admin.escalations.*') ? 'active' : '' }}">
                    <i class="fas fa-edit"></i>
                    <span>Corrections</span>
                </a>

                <a href="{{ route('admin.corrections.legal.index') }}" 
                   class="admin-nav-link {{ request()->routeIs('admin.corrections.legal.*') ? 'active' : '' }}">
                    <i class="fas fa-gavel"></i>
                    <span>Legal</span>
                </a>

                <!-- More Dropdown -->
                <div class="admin-nav-dropdown" id="admin-nav-more-dropdown">
                    <button type="button" class="admin-nav-link" onclick="toggleMoreDropdown()">
                        <i class="fas fa-ellipsis-h"></i>
                        <span>More</span>
                        <i class="fas fa-chevron-down" style="font-size: 0.625rem; margin-left: 0.25rem;"></i>
                    </button>
                    
                    <div class="admin-nav-dropdown-menu">
                        <a href="{{ route('admin.blockchain.index') }}" 
                           class="admin-nav-dropdown-item {{ request()->routeIs('admin.blockchain.*') ? 'active' : '' }}">
                            <i class="fas fa-cube"></i>
                            <span>Blockchain</span>
                        </a>
                        <a href="{{ route('admin.system-health.index') }}" 
                           class="admin-nav-dropdown-item {{ request()->routeIs('admin.system-health.*') ? 'active' : '' }}">
                            <i class="fas fa-heartbeat"></i>
                            <span>System Health</span>
                        </a>
                        <a href="{{ route('admin.analytics.index') }}" 
                           class="admin-nav-dropdown-item {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}">
                            <i class="fas fa-chart-line"></i>
                            <span>Analytics</span>
                        </a>
                        <a href="{{ route('admin.audit-logs.index') }}" 
                           class="admin-nav-dropdown-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Audit Logs</span>
                        </a>
                        <a href="{{ route('admin.backups.index') }}" 
                           class="admin-nav-dropdown-item {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">
                            <i class="fas fa-database"></i>
                            <span>Backups</span>
                        </a>
                        <a href="{{ route('admin.reports.index') }}" 
                           class="admin-nav-dropdown-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                            <i class="fas fa-file-pdf"></i>
                            <span>Reports</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Side Actions -->
            <div class="admin-nav-actions">
                
                <!-- Notifications -->
                @php
                    $unreadCount = \App\Models\AdminNotification::where('read_at', null)->count();
                @endphp
                <a href="{{ route('admin.notifications.index') }}" class="admin-notification-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                    @if($unreadCount > 0)
                        <span class="admin-notification-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </a>

                <!-- User Dropdown -->
                <div class="admin-user-dropdown" id="admin-user-dropdown">
                    <button type="button" class="admin-user-btn" onclick="toggleAdminUserDropdown()">
                        <div class="admin-user-avatar">
                            {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                        </div>
                        <div class="admin-user-info">
                            <span class="admin-user-name">{{ Auth::user()->name ?? 'Admin' }}</span>
                            <span class="admin-user-role">Administrator</span>
                        </div>
                        <i class="fas fa-chevron-down admin-user-chevron"></i>
                    </button>
                    
                    <div class="admin-dropdown-menu">
                        <div class="admin-dropdown-header">
                            <div class="admin-dropdown-header-name">{{ Auth::user()->name ?? 'Admin' }}</div>
                            <div class="admin-dropdown-header-email">{{ Auth::user()->email ?? '' }}</div>
                        </div>
                        <div class="admin-dropdown-divider"></div>
                        <a href="{{ route('profile.edit') }}" class="admin-dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="{{ route('profile.edit') }}" class="admin-dropdown-item">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <div class="admin-dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}" id="admin-logout-form">
                            @csrf
                            <button type="submit" class="admin-dropdown-item admin-dropdown-item--danger">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Sign Out</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <button type="button" class="admin-mobile-menu-btn" id="admin-mobile-menu-button">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="admin-mobile-menu" id="admin-mobile-menu">
        <a href="{{ route('admin.dashboard') }}" 
           class="admin-mobile-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="{{ route('admin.users.index') }}" 
           class="admin-mobile-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <i class="fas fa-users"></i>
            <span>User Management</span>
        </a>
        
        <a href="{{ route('admin.documents.index') }}" 
           class="admin-mobile-link {{ request()->routeIs('admin.documents.*') ? 'active' : '' }}">
            <i class="fas fa-file-alt"></i>
            <span>Documents</span>
        </a>
        
        <a href="{{ route('admin.corrections.index') }}" 
           class="admin-mobile-link {{ request()->routeIs('admin.corrections.*') ? 'active' : '' }}">
            <i class="fas fa-edit"></i>
            <span>Corrections</span>
        </a>

        <a href="{{ route('admin.corrections.legal.index') }}" 
           class="admin-mobile-link {{ request()->routeIs('admin.corrections.legal.*') ? 'active' : '' }}">
            <i class="fas fa-gavel"></i>
            <span>Legal Corrections</span>
        </a>

        <a href="{{ route('admin.blockchain.index') }}" 
           class="admin-mobile-link {{ request()->routeIs('admin.blockchain.*') ? 'active' : '' }}">
            <i class="fas fa-cube"></i>
            <span>Blockchain</span>
        </a>

        <a href="{{ route('admin.system-health.index') }}" 
           class="admin-mobile-link {{ request()->routeIs('admin.system-health.*') ? 'active' : '' }}">
            <i class="fas fa-heartbeat"></i>
            <span>System Health</span>
        </a>

        <a href="{{ route('admin.analytics.index') }}" 
           class="admin-mobile-link {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}">
            <i class="fas fa-chart-line"></i>
            <span>Analytics</span>
        </a>

        <a href="{{ route('admin.audit-logs.index') }}" 
           class="admin-mobile-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
            <i class="fas fa-clipboard-list"></i>
            <span>Audit Logs</span>
        </a>

        <a href="{{ route('admin.backups.index') }}" 
           class="admin-mobile-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">
            <i class="fas fa-database"></i>
            <span>Backups</span>
        </a>

        <a href="{{ route('admin.reports.index') }}" 
           class="admin-mobile-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
            <i class="fas fa-file-pdf"></i>
            <span>Reports</span>
        </a>
    </div>
</nav>

<script>
// More Dropdown Toggle
function toggleMoreDropdown() {
    const dropdown = document.getElementById('admin-nav-more-dropdown');
    dropdown.classList.toggle('open');
    
    // Close user dropdown if open
    const userDropdown = document.getElementById('admin-user-dropdown');
    if (userDropdown) {
        userDropdown.classList.remove('open');
    }
}

// User Dropdown Toggle
function toggleAdminUserDropdown() {
    const dropdown = document.getElementById('admin-user-dropdown');
    dropdown.classList.toggle('open');
    
    // Close more dropdown if open
    const moreDropdown = document.getElementById('admin-nav-more-dropdown');
    if (moreDropdown) {
        moreDropdown.classList.remove('open');
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const userDropdown = document.getElementById('admin-user-dropdown');
    const moreDropdown = document.getElementById('admin-nav-more-dropdown');
    
    if (userDropdown && !userDropdown.contains(event.target)) {
        userDropdown.classList.remove('open');
    }
    
    if (moreDropdown && !moreDropdown.contains(event.target)) {
        moreDropdown.classList.remove('open');
    }
});

// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('admin-mobile-menu-button');
    const mobileMenu = document.getElementById('admin-mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileMenu.classList.toggle('open');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenu.contains(event.target) && !mobileMenuButton.contains(event.target)) {
                mobileMenu.classList.remove('open');
            }
        });
    }
});
</script>
