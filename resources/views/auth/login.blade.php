<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Smart Contract Document Validation System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
    </style>
</head>
<body class="h-screen flex flex-col overflow-hidden">
    <!-- Full-screen Background with 50% Black Overlay -->
    <div class="fixed inset-0 w-full h-full overflow-hidden -z-10">
        <!-- Background Image -->
        <div 
            class="absolute inset-0 w-full h-full"
            style="background-image: url('{{ asset('images/municipal-hall-magallanes-bg.png') }}'); background-size: cover; background-position: center center; background-repeat: no-repeat;"
        ></div>
        <!-- Dark Overlay - Exactly 50% Black Opacity -->
        <div class="absolute inset-0 bg-black/50"></div>
    </div>

    <!-- Header (Exact copy from landing page, with "Back to Home" button) -->
    <header class="bg-white/95 backdrop-blur-sm shadow-sm border-b relative z-10 flex-shrink-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-2.5">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('images/magallanes-seal-transparent.png') }}" alt="LGU Magallanes Logo" class="w-12 h-12 object-contain shrink-0">
                    <div>
                        <h1 class="text-base font-bold text-gray-900 leading-tight">Smart Contract Document Validation System</h1>
                        <p class="text-xs text-blue-600 font-medium">Local Civil Registry Office — LGU Magallanes, Agusan del Norte</p>
                    </div>
                </div>
                <a href="{{ url('/') }}" class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Home
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content - Centered Login Card -->
    <main class="flex-1 flex items-center justify-center px-4 py-4 overflow-hidden">
        <!-- Login Card - Glassmorphism Design -->
        <div class="w-full max-w-sm">
            <div class="bg-white/95 backdrop-blur-md rounded-xl shadow-2xl border border-white/20 p-6">
                <!-- Logo -->
                <div class="flex justify-center mb-4">
                    <img 
                        src="{{ asset('images/magallanes-seal-transparent.png') }}" 
                        alt="LGU Magallanes" 
                        class="w-14 h-14 object-contain"
                    >
                </div>
                
                <!-- Login Header -->
                <div class="text-center mb-5">
                    <h2 class="text-xl font-bold text-gray-900 mb-0.5">Welcome Back</h2>
                    <p class="text-gray-500 text-xs">Sign in to your account to continue</p>
                </div>
                
                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-medium text-gray-700 mb-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                placeholder="you@example.com"
                                class="w-full h-10 pl-9 pr-3 text-sm text-gray-900 placeholder-gray-400 bg-gray-50 border border-gray-200 rounded-lg outline-none transition-all focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 @error('email') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
                                required
                                autofocus
                            />
                        </div>
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-xs font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                placeholder="••••••••"
                                class="w-full h-10 pl-9 pr-10 text-sm text-gray-900 placeholder-gray-400 bg-gray-50 border border-gray-200 rounded-lg outline-none transition-all focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 @error('password') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
                                required
                            />
                            <button
                                type="button"
                                onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center"
                            >
                                <svg id="eye-open" class="w-4 h-4 text-gray-400 hover:text-gray-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="eye-closed" class="w-4 h-4 text-gray-400 hover:text-gray-600 transition-colors hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Login Button - Royal Blue (matching landing page) -->
                    <button
                        type="submit"
                        class="w-full h-10 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-lg shadow-blue-600/30 hover:shadow-blue-700/40 flex items-center justify-center"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        Sign In
                    </button>
                </form>
                
                <!-- Footer Text -->
                <div class="mt-5 pt-4 border-t border-gray-100 text-center">
                    <p class="text-xs text-gray-400">
                        Protected by Smart Contract Technology
                    </p>
                </div>
            </div>
            
            <!-- Copyright below card -->
            <p class="text-center text-white/70 text-xs mt-4">
                &copy; 2025 Smart Contract Document Validation System. Caraga State University Cabadbaran Campus. All rights reserved.
            </p>
        </div>
    </main>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }
    </script>
</body>
</html>