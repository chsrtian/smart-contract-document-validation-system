<nav class="staff-navbar">
    <div class="staff-navbar-inner">
        <div class="staff-navbar-content">
            
            <!-- Brand -->
            <div class="staff-brand">
                <div class="staff-brand-icon">
                    <i class="fas fa-user-tie" aria-hidden="true"></i>
                </div>
                <div class="staff-brand-text">
                    <span class="staff-brand-title">Staff Panel</span>
                    <span class="staff-brand-subtitle">Document Validation System</span>
                </div>
            </div>

            <!-- Navigation Links (Desktop) -->
            <div class="staff-nav-links">
                <div class="staff-nav-group staff-nav-group--primary">
                    <a href="{{ route('staff.dashboard') }}" 
                       class="staff-nav-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <!-- Primary Action: Scan -->
                <a href="{{ route('staff.scan') }}" 
                   class="staff-nav-link staff-nav-link--primary {{ request()->routeIs('staff.scan') ? 'active' : '' }}">
                    <i class="fas fa-camera"></i>
                    <span>Scan</span>
                </a>

                <div class="staff-nav-group staff-nav-group--secondary">
                    <a href="{{ route('staff.upload') }}" 
                       class="staff-nav-link staff-nav-link--secondary {{ request()->routeIs('staff.upload') ? 'active' : '' }}">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Upload</span>
                    </a>

                    <a href="{{ route('staff.search') }}" 
                       class="staff-nav-link staff-nav-link--secondary {{ request()->routeIs('staff.search') ? 'active' : '' }}">
                        <i class="fas fa-search"></i>
                        <span>Search</span>
                    </a>

                    <a href="{{ route('staff.analytics.index') }}" 
                       class="staff-nav-link staff-nav-link--utility {{ request()->routeIs('staff.analytics.*') ? 'active' : '' }}"
                       title="Analytics">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analytics</span>
                    </a>

                    <div class="staff-more-dropdown" id="staff-more-dropdown">
                        <button type="button"
                                class="staff-nav-link staff-nav-link--utility {{ request()->routeIs('corrections.requests.*') || request()->routeIs('corrections.petitions.*') || request()->routeIs('staff.reports.*') ? 'active' : '' }}"
                                onclick="toggleMoreDropdown()"
                                aria-haspopup="true"
                                aria-expanded="false">
                            <i class="fas fa-ellipsis-h"></i>
                            <span>More</span>
                        </button>

                        <div class="staff-more-menu">
                            <a href="{{ route('corrections.requests.index') }}" class="staff-more-item {{ request()->routeIs('corrections.requests.*') ? 'active' : '' }}">
                                <i class="fas fa-edit"></i>
                                <span>Corrections</span>
                            </a>
                            <a href="{{ route('corrections.petitions.index') }}" class="staff-more-item {{ request()->routeIs('corrections.petitions.*') ? 'active' : '' }}">
                                <i class="fas fa-gavel"></i>
                                <span>Petitions</span>
                            </a>
                            <a href="{{ route('staff.reports.index') }}" class="staff-more-item {{ request()->routeIs('staff.reports.*') ? 'active' : '' }}">
                                <i class="fas fa-file-lines"></i>
                                <span>Reports</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side Actions -->
            <div class="staff-nav-actions">
                
                <!-- User Dropdown -->
                <div class="staff-user-dropdown" id="user-dropdown">
                    <button type="button" class="staff-user-btn" onclick="toggleUserDropdown()">
                        <div class="staff-user-avatar">
                            {{ substr(Auth::user()->name ?? 'S', 0, 1) }}
                        </div>
                        <div class="staff-user-info">
                            <span class="staff-user-name">{{ Auth::user()->name ?? 'Staff' }}</span>
                            <span class="staff-user-role">Staff Member</span>
                        </div>
                        <i class="fas fa-chevron-down staff-user-chevron"></i>
                    </button>
                    
                    <div class="staff-dropdown-menu">
                        <div class="staff-dropdown-header">
                            <div class="staff-dropdown-header-name">{{ Auth::user()->name ?? 'Staff' }}</div>
                            <div class="staff-dropdown-header-email">{{ Auth::user()->email ?? '' }}</div>
                        </div>
                        <div class="staff-dropdown-divider"></div>
                        <a href="{{ route('profile.edit') }}" class="staff-dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="{{ route('profile.edit') }}" class="staff-dropdown-item">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <div class="staff-dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}" id="logout-form">
                            @csrf
                            <button type="submit" class="staff-dropdown-item staff-dropdown-item--danger">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Sign Out</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <button type="button" class="staff-mobile-menu-btn" id="mobile-menu-button">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="staff-mobile-menu" id="mobile-menu">
        <a href="{{ route('staff.dashboard') }}" 
           class="staff-mobile-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('corrections.requests.index') }}" 
           class="staff-mobile-link {{ request()->routeIs('corrections.requests.*') ? 'active' : '' }}">
            <i class="fas fa-edit"></i>
            <span>Correction Requests</span>
        </a>

        <a href="{{ route('corrections.petitions.index') }}" 
           class="staff-mobile-link {{ request()->routeIs('corrections.petitions.*') ? 'active' : '' }}">
            <i class="fas fa-gavel"></i>
            <span>Legal Petitions</span>
        </a>

        <a href="{{ route('staff.reports.index') }}"
           class="staff-mobile-link {{ request()->routeIs('staff.reports.*') ? 'active' : '' }}">
            <i class="fas fa-file-lines"></i>
            <span>Reports</span>
        </a>
        
        <a href="{{ route('staff.scan') }}" 
           class="staff-mobile-link staff-mobile-link--primary {{ request()->routeIs('staff.scan') ? 'active' : '' }}">
            <i class="fas fa-camera"></i>
            <span>Scan Documents</span>
        </a>
        
        <a href="{{ route('staff.upload') }}" 
           class="staff-mobile-link staff-mobile-link--secondary {{ request()->routeIs('staff.upload') ? 'active' : '' }}">
            <i class="fas fa-cloud-upload-alt"></i>
            <span>Upload Documents</span>
        </a>
        
        <a href="{{ route('staff.search') }}" 
           class="staff-mobile-link staff-mobile-link--secondary {{ request()->routeIs('staff.search') ? 'active' : '' }}">
            <i class="fas fa-search"></i>
            <span>Search Documents</span>
        </a>

        <a href="{{ route('staff.analytics.index') }}" 
           class="staff-mobile-link staff-mobile-link--secondary {{ request()->routeIs('staff.analytics.*') ? 'active' : '' }}">
            <i class="fas fa-chart-bar"></i>
            <span>Analytics</span>
        </a>
    </div>
</nav>

<script>
// User Dropdown Toggle
function toggleUserDropdown() {
    const dropdown = document.getElementById('user-dropdown');
    dropdown.classList.toggle('open');
}

function toggleMoreDropdown() {
    const dropdown = document.getElementById('staff-more-dropdown');
    if (!dropdown) return;
    dropdown.classList.toggle('open');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('user-dropdown');
    if (dropdown && !dropdown.contains(event.target)) {
        dropdown.classList.remove('open');
    }

    const moreDropdown = document.getElementById('staff-more-dropdown');
    if (moreDropdown && !moreDropdown.contains(event.target)) {
        moreDropdown.classList.remove('open');
    }
});

// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('open');
        });
        
        document.addEventListener('click', function(event) {
            if (!mobileMenuButton.contains(event.target) && !mobileMenu.contains(event.target)) {
                mobileMenu.classList.remove('open');
            }
        });
    }
});
</script>