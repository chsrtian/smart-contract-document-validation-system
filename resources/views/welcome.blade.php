<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Contract Document Validation System | LGU Magallanes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes kenburns {
            0% {
                transform: scale(1);
            }
            100% {
                transform: scale(1.03);
            }
        }

        .hero-bg-layer {
            background-image: url('{{ asset('images/municipal-hall-magallanes-bg.png') }}');
            background-size: cover;
            background-position: 50% 22%;
            background-repeat: no-repeat;
        }
        
        .bg-kenburns {
            animation: kenburns 25s ease-in-out infinite alternate;
            transform-origin: 50% 22%;
        }

        .hero-section {
            min-height: calc(100vh - 84px);
        }

        @media (max-width: 1024px) {
            .hero-bg-layer {
                background-position: 50% 20%;
            }

            .bg-kenburns {
                transform-origin: 50% 20%;
            }

            .hero-section {
                min-height: calc(100vh - 78px);
            }
        }

        @media (max-width: 640px) {
            .hero-bg-layer {
                background-position: 50% 18%;
            }

            .bg-kenburns {
                transform-origin: 50% 18%;
            }

            .hero-section {
                min-height: calc(100vh - 72px);
            }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Hero Background with Municipal Hall Image -->
    <div class="fixed inset-0 w-full h-full overflow-hidden -z-10">
        <!-- Background Image with Ken Burns Effect -->
        <div class="absolute inset-0 w-full h-full bg-kenburns hero-bg-layer"></div>
        <!-- Dark Overlay for Text Readability -->
        <div class="absolute inset-0 bg-gray-900/45"></div>
    </div>

    <!-- Header -->
    <header class="bg-white/95 backdrop-blur-sm shadow-sm border-b relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('images/magallanes-seal-transparent.png') }}" alt="LGU Magallanes Logo" class="w-12 h-12 object-contain shrink-0">
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 leading-tight">Smart Contract Document Validation System</h1>
                        <p class="text-xs text-blue-600 font-medium">Local Civil Registry Office — LGU Magallanes, Agusan del Norte</p>
                    </div>
                </div>
                <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Log In
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section flex-1 flex items-center py-8 lg:py-12 px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-7xl mx-auto w-full grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
            <!-- Left Content -->
            <div class="space-y-5 order-2 lg:order-1">
                <div class="inline-flex items-center px-3 py-1 bg-white/90 backdrop-blur-sm text-blue-700 text-sm font-medium rounded-full shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Official Government Platform
                </div>
                <h2 class="text-3xl lg:text-4xl xl:text-5xl font-bold text-white leading-tight drop-shadow-lg">
                    Secure Document Validation with <span class="text-blue-300">Smart Contracts</span>
                </h2>
                <p class="text-base lg:text-lg text-gray-100 leading-relaxed drop-shadow">
                    Our smart contract-powered platform ensures the authenticity and integrity
                    of your official civil registry documents through cryptographic verification
                    and tamper-proof validation.
                </p>
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3 text-base font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-lg shadow-blue-600/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Get Started
                    </a>
                    <a href="#features" class="inline-flex items-center justify-center px-6 py-3 text-base font-medium bg-white/90 backdrop-blur-sm border-2 border-white text-gray-800 rounded-lg hover:bg-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Learn More
                    </a>
                </div>
            </div>

            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-12 px-4 sm:px-6 lg:px-8 bg-white border-t relative z-10">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-10">
                <h3 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-3">Why Choose Our System?</h3>
                <p class="text-gray-600 max-w-2xl mx-auto">Trusted by the Local Civil Registry Office for secure and efficient document management.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-br from-blue-50 to-slate-50 p-6 rounded-xl border border-blue-100">
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Tamper-Proof Security</h4>
                    <p class="text-gray-600 text-sm">Documents are cryptographically secured using smart contracts, ensuring data integrity.</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-slate-50 p-6 rounded-xl border border-green-100">
                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Instant Verification</h4>
                    <p class="text-gray-600 text-sm">Verify document authenticity in seconds with our automated validation system.</p>
                </div>
                <div class="bg-gradient-to-br from-cyan-50 to-slate-50 p-6 rounded-xl border border-cyan-100">
                    <div class="w-12 h-12 bg-cyan-600 rounded-lg flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Government Trusted</h4>
                    <p class="text-gray-600 text-sm">Official platform of the Local Civil Registry Office, LGU Magallanes.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-10 px-4 sm:px-6 lg:px-8 bg-blue-600 text-center text-white relative z-10">
        <h3 class="text-2xl lg:text-3xl font-bold mb-3">Ready to Secure Your Documents?</h3>
        <p class="text-lg text-blue-100 mb-6">Experience trusted smart contract-powered validation for your civil registry documents.</p>
        <a href="{{ route('login') }}" class="inline-flex items-center px-6 py-3 text-base font-medium bg-white text-blue-600 rounded-lg hover:bg-gray-100 transition-colors shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
            </svg>
            Access Platform
        </a>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-4 gap-6 mb-6">
                <div class="space-y-3 md:col-span-2">
                    <div class="flex items-center space-x-3">
                        <img src="{{ asset('images/magallanes-seal-transparent.png') }}" alt="LGU Magallanes" class="w-10 h-10 object-contain shrink-0">
                        <div>
                            <span class="font-semibold text-lg block">SCDVS</span>
                            <span class="text-xs text-gray-400">Smart Contract Document Validation System</span>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm">Official document validation platform for the Local Civil Registry Office, Municipality of Magallanes, Agusan del Norte.</p>
                </div>
                <div>
                    <h5 class="font-semibold mb-3 text-sm">Quick Links</h5>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="#features" class="hover:text-white transition-colors">Features</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-white transition-colors">Login</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-semibold mb-3 text-sm">Support</h5>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Documentation</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-6 text-center text-gray-400 text-sm">
                <p>&copy; 2025 Smart Contract Document Validation System. Developed by Caraga State University — Cabadbaran Campus. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>