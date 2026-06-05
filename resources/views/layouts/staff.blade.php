<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Laravel') }} - Staff Panel</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Staff Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/staff-theme.css') }}">
    
    <!-- Scripts (keep Vite for app functionality, but Tailwind won't override our CSS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Mobile-specific meta tags -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    @stack('styles')
</head>
<body class="staff-body">
    <!-- Staff Navigation -->
    @include('layouts.staff-navbar')
    
    <!-- Flash Messages -->
    @if(session('success'))
        <div id="flash-success" class="staff-flash staff-flash--success">
            <div class="staff-flash-content">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="staff-flash-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif
    
    @if(session('error'))
        <div id="flash-error" class="staff-flash staff-flash--error">
            <div class="staff-flash-content">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="staff-flash-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif
    
    <!-- Page Content -->
    <main class="staff-main">
        @yield('content')
    </main>
    
    <!-- Global JavaScript -->
    <script>
        // Auto-hide flash messages
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
        
        // CSRF token for AJAX
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    </script>
    
    @stack('scripts')
</body>
</html>