<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Laravel') }} - Supervisor Panel</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Supervisor Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/supervisor-theme.css') }}">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Mobile-specific meta tags -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- Dark mode initialization (prevent flash) -->
    <script>
        (function() {
            const theme = localStorage.getItem('sv-theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    
    @stack('styles')
</head>
<body class="sv-body">
    <!-- Supervisor Navigation -->
    @include('layouts.supervisor-navbar')
    
    <!-- Flash Messages -->
    @if(session('success'))
        <div id="flash-success" class="sv-flash sv-flash-success">
            <div class="sv-flash-content">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="sv-flash-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif
    
    @if(session('error'))
        <div id="flash-error" class="sv-flash sv-flash-error">
            <div class="sv-flash-content">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="sv-flash-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif
    
    @if(session('info'))
        <div id="flash-info" class="sv-flash sv-flash-info">
            <div class="sv-flash-content">
                <i class="fas fa-info-circle"></i>
                <span>{{ session('info') }}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="sv-flash-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif
    
    <!-- Page Content -->
    <main class="sv-main">
        @yield('content')
    </main>
    
    <!-- Global JavaScript -->
    <script>
        // Auto-hide flash messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessages = document.querySelectorAll('[id^="flash-"]');
            flashMessages.forEach(function(flash) {
                setTimeout(function() {
                    if (flash && flash.parentNode) {
                        flash.style.opacity = '0';
                        flash.style.transform = 'translateX(100%)';
                        flash.style.transition = 'all 0.3s ease';
                        setTimeout(() => flash.remove(), 300);
                    }
                }, 5000);
            });
        });
        
        // CSRF token for AJAX requests
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Dark Mode Toggle
        function toggleDarkMode() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('sv-theme', newTheme);
            
            // Update toggle icon
            const icon = document.getElementById('theme-toggle-icon');
            if (icon) {
                icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
        
        // Set initial icon state
        document.addEventListener('DOMContentLoaded', function() {
            const theme = document.documentElement.getAttribute('data-theme');
            const icon = document.getElementById('theme-toggle-icon');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>