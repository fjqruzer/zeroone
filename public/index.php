<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zero One Labs - Professional Web Development</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --vscode-bg: #ffffff;
            --vscode-sidebar: #f3f3f3;
            --vscode-editor: #ffffff;
            --vscode-text: #24292f;
            --vscode-text-muted: #656d76;
            --vscode-border: #d0d7de;
            --vscode-accent: #0969da;
            --vscode-accent-hover: #0860ca;
            --vscode-card-bg: #ffffff;
            --vscode-shadow: rgba(31, 35, 40, 0.04);
        }

        [data-theme="dark"] {
            --vscode-bg: #1e1e1e;
            --vscode-sidebar: #252526;
            --vscode-editor: #1e1e1e;
            --vscode-text: #cccccc;
            --vscode-text-muted: #969696;
            --vscode-border: #3e3e42;
            --vscode-accent: #007acc;
            --vscode-accent-hover: #1177bb;
            --vscode-card-bg: #252526;
            --vscode-shadow: rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--vscode-bg);
            color: var(--vscode-text);
            transition: all 0.3s ease;
            overflow-x: hidden;
        }

        .font-mono {
            font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
        }

        .vscode-card {
            background: var(--vscode-card-bg);
            border: 1px solid var(--vscode-border);
            box-shadow: 0 2px 8px var(--vscode-shadow);
        }

        .vscode-button {
            background: var(--vscode-accent);
            color: white;
            border: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .vscode-button:hover {
            background: var(--vscode-accent-hover);
            transform: translateY(-1px);
        }

        .typing-animation {
            border-right: 2px solid var(--vscode-accent);
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 50% { border-color: var(--vscode-accent); }
            51%, 100% { border-color: transparent; }
        }

        .gradient-bg {
            background: linear-gradient(135deg, var(--vscode-bg), var(--vscode-sidebar));
        }

        .service-card {
            transition: all 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px var(--vscode-shadow);
        }

        .nav-link {
            position: relative;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            color: var(--vscode-accent);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: var(--vscode-accent);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .hero-code {
            background: var(--vscode-editor);
            border: 1px solid var(--vscode-border);
            border-radius: 8px;
            padding: 20px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
            line-height: 1.6;
            overflow-x: auto;
        }

        .code-line {
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        .line-number {
            color: var(--vscode-text-muted);
            margin-right: 20px;
            user-select: none;
            width: 20px;
            text-align: right;
            flex-shrink: 0;
        }

        .keyword { color: #d73a49; }
        .string { color: #032f62; }
        .function { color: #6f42c1; }
        .comment { color: var(--vscode-text-muted); font-style: italic; }

        [data-theme="dark"] .keyword { color: #ff7b72; }
        [data-theme="dark"] .string { color: #a5d6ff; }
        [data-theme="dark"] .function { color: #d2a8ff; }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .hero-code {
                font-size: 12px;
                padding: 15px;
                margin-top: 20px;
            }

            .code-line {
                font-size: 12px;
            }

            .line-number {
                margin-right: 15px;
                width: 15px;
            }

            /* Mobile navigation improvements */
            #mobileMenu {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--vscode-bg);
                border-top: 1px solid var(--vscode-border);
                box-shadow: 0 4px 6px var(--vscode-shadow);
            }

            #mobileMenu a {
                padding: 12px 20px;
                border-bottom: 1px solid var(--vscode-border);
                transition: background-color 0.2s ease;
            }

            #mobileMenu a:hover {
                background-color: var(--vscode-sidebar);
            }

            /* Mobile button improvements */
            .vscode-button {
                padding: 10px 16px;
                font-size: 14px;
            }

            /* Mobile form improvements */
            input, textarea, select {
                font-size: 16px !important; /* Prevents zoom on iOS */
            }
        }

        @media (max-width: 640px) {
            .hero-code {
                font-size: 11px;
                padding: 12px;
            }

            .code-line {
                font-size: 11px;
            }

            .line-number {
                margin-right: 12px;
                width: 12px;
            }

            /* Stack buttons vertically on very small screens */
            .flex-col-sm {
                flex-direction: column;
            }

            .flex-col-sm > * {
                margin-bottom: 10px;
            }

            .flex-col-sm > *:last-child {
                margin-bottom: 0;
            }
        }

        /* Tablet improvements */
        @media (min-width: 769px) and (max-width: 1024px) {
            .hero-code {
                font-size: 13px;
                padding: 18px;
            }
        }

        /* Ensure proper touch targets on mobile */
        @media (max-width: 768px) {
            button, a, input, textarea, select {
                min-height: 44px;
            }

            .nav-link {
                padding: 12px 0;
            }
        }

        /* Improve readability on small screens */
        @media (max-width: 480px) {
            h1 {
                font-size: 2rem !important;
                line-height: 1.2 !important;
            }

            h2 {
                font-size: 1.75rem !important;
                line-height: 1.3 !important;
            }

            h3 {
                font-size: 1.5rem !important;
                line-height: 1.4 !important;
            }

            p {
                font-size: 1rem !important;
                line-height: 1.6 !important;
            }
        }

        /* Smooth transitions for mobile menu */
        #mobileMenu {
            transition: all 0.3s ease;
            transform-origin: top;
        }

        #mobileMenu.hidden {
            transform: scaleY(0);
            opacity: 0;
        }

        #mobileMenu:not(.hidden) {
            transform: scaleY(1);
            opacity: 1;
        }
    </style>
</head>
<body data-theme="light">
    <!-- Navigation -->
    <nav class="sticky top-0 z-50" style="background: var(--vscode-bg); border-bottom: 1px solid var(--vscode-border);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-code text-xl sm:text-2xl text-blue-500 mr-2 sm:mr-3"></i>
                    <span class="text-lg sm:text-xl font-bold">ZERO ONE LABS</span>
                </div>
                <div class="hidden md:flex space-x-8">
                    <a href="#home" class="nav-link">Home</a>
                    <a href="#services" class="nav-link">Services</a>
                    <a href="#portfolio" class="nav-link">Portfolio</a>
                    <a href="#about" class="nav-link">About</a>
                    <a href="#contact" class="nav-link">Contact</a>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <!-- Theme Toggle -->
                    <button onclick="toggleTheme()" class="vscode-button p-2 sm:px-3 sm:py-2 rounded-md text-sm">
                        <i id="theme-icon" class="fas fa-moon"></i>
                    </button>
                    <!-- Login/Signup buttons -->
                    <a href="../login.php" class="px-2 sm:px-3 lg:px-4 py-2 rounded border text-xs sm:text-sm lg:text-base" style="border-color: var(--vscode-border);">
                        <i class="fas fa-sign-in-alt mr-1 sm:mr-2"></i><span class="hidden sm:inline">Login</span>
                    </a>
                    <a href="../register.php" class="vscode-button px-2 sm:px-3 lg:px-4 py-2 rounded text-xs sm:text-sm lg:text-base">
                        <i class="fas fa-user-plus mr-1 sm:mr-2"></i><span class="hidden sm:inline">Sign Up</span>
                    </a>
                </div>
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button onclick="toggleMobileMenu()" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- Mobile menu -->
        <div id="mobileMenu" class="hidden md:hidden" style="background: var(--vscode-bg); border-top: 1px solid var(--vscode-border);">
            <div class="px-4 py-2 space-y-1">
                <a href="#home" class="block px-3 py-3 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Home</a>
                <a href="#services" class="block px-3 py-3 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Services</a>
                <a href="#portfolio" class="block px-3 py-3 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Portfolio</a>
                <a href="#about" class="block px-3 py-3 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">About</a>
                <a href="#contact" class="block px-3 py-3 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Contact</a>
                <div class="border-t pt-2 mt-2" style="border-color: var(--vscode-border);">
                    <!-- Theme Toggle for Mobile -->
                    <button onclick="toggleTheme()" class="w-full text-left px-3 py-3 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i id="theme-icon-mobile" class="fas fa-moon mr-2"></i>Toggle Theme
                    </button>
                    <a href="../login.php" class="block px-3 py-3 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="../register.php" class="block px-3 py-3 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-user-plus mr-2"></i>Sign Up
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="gradient-bg py-12 sm:py-16 lg:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
                <div class="text-center lg:text-left">
                    <h1 class="text-3xl sm:text-4xl lg:text-6xl font-bold mb-4 sm:mb-6 leading-tight">
                        We Build
                        <span class="typing-animation" id="typingText">Amazing</span>
                        <br>Web Solutions
                    </h1>
                    <p class="text-lg sm:text-xl mb-6 sm:mb-8" style="color: var(--vscode-text-muted);">
                        Professional web development services using cutting-edge technologies. 
                        From concept to deployment, we bring your digital vision to life.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="#contact" class="vscode-button px-6 sm:px-8 py-3 rounded-lg text-base sm:text-lg font-medium">
                            <i class="fas fa-rocket mr-2"></i>Start Your Project
                        </a>
                        <a href="#portfolio" class="px-6 sm:px-8 py-3 rounded-lg text-base sm:text-lg font-medium border" style="border-color: var(--vscode-border);">
                            <i class="fas fa-eye mr-2"></i>View Our Work
                        </a>
                    </div>
                </div>
                <div class="hero-code order-first lg:order-last">
                    <div class="code-line mb-2">
                        <span class="line-number">1</span>
                        <span class="comment">// Welcome to Zero One Labs</span>
                    </div>
                    <div class="code-line mb-2">
                        <span class="line-number">2</span>
                        <span class="keyword">const</span> <span class="function">buildAmazingWebsite</span> = () => {
                    </div>
                    <div class="code-line mb-2">
                        <span class="line-number">3</span>
                        <span>&nbsp;&nbsp;</span><span class="keyword">return</span> {
                    </div>
                    <div class="code-line mb-2">
                        <span class="line-number">4</span>
                        <span>&nbsp;&nbsp;&nbsp;&nbsp;</span>design: <span class="string">'modern'</span>,
                    </div>
                    <div class="code-line mb-2">
                        <span class="line-number">5</span>
                        <span>&nbsp;&nbsp;&nbsp;&nbsp;</span>performance: <span class="string">'optimized'</span>,
                    </div>
                    <div class="code-line mb-2">
                        <span class="line-number">6</span>
                        <span>&nbsp;&nbsp;&nbsp;&nbsp;</span>security: <span class="string">'enterprise-grade'</span>,
                    </div>
                    <div class="code-line mb-2">
                        <span class="line-number">7</span>
                        <span>&nbsp;&nbsp;&nbsp;&nbsp;</span>support: <span class="string">'24/7'</span>
                    </div>
                    <div class="code-line mb-2">
                        <span class="line-number">8</span>
                        <span>&nbsp;&nbsp;</span>};
                    </div>
                    <div class="code-line">
                        <span class="line-number">9</span>
                        <span>};</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-12 sm:py-16 lg:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 sm:mb-16">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-4">Our Services</h2>
                <p class="text-lg sm:text-xl" style="color: var(--vscode-text-muted);">
                    Comprehensive web development solutions tailored to your needs
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <div class="service-card vscode-card rounded-lg p-4 sm:p-6">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-3 sm:mb-4">
                        <i class="fas fa-code text-blue-600 text-lg sm:text-xl"></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">Web Development</h3>
                    <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">
                        Custom web applications built with modern frameworks like React, Next.js, and Node.js.
                    </p>
                </div>
                <div class="service-card vscode-card rounded-lg p-4 sm:p-6">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-lg flex items-center justify-center mb-3 sm:mb-4">
                        <i class="fas fa-mobile-alt text-green-600 text-lg sm:text-xl"></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">Mobile Development</h3>
                    <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">
                        Responsive web apps and native mobile applications for iOS and Android platforms.
                    </p>
                </div>
                <div class="service-card vscode-card rounded-lg p-4 sm:p-6">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-3 sm:mb-4">
                        <i class="fas fa-database text-purple-600 text-lg sm:text-xl"></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">Backend Development</h3>
                    <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">
                        Scalable server-side solutions with secure APIs and database management.
                    </p>
                </div>
                <div class="service-card vscode-card rounded-lg p-4 sm:p-6">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-3 sm:mb-4">
                        <i class="fas fa-cloud text-yellow-600 text-lg sm:text-xl"></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">Cloud Solutions</h3>
                    <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">
                        Cloud deployment, hosting, and DevOps solutions for maximum reliability.
                    </p>
                </div>
                <div class="service-card vscode-card rounded-lg p-4 sm:p-6">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-red-100 rounded-lg flex items-center justify-center mb-3 sm:mb-4">
                        <i class="fas fa-shield-alt text-red-600 text-lg sm:text-xl"></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">Security</h3>
                    <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">
                        Enterprise-grade security implementation and vulnerability assessments.
                    </p>
                </div>
                <div class="service-card vscode-card rounded-lg p-4 sm:p-6">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-3 sm:mb-4">
                        <i class="fas fa-headset text-indigo-600 text-lg sm:text-xl"></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">Support & Maintenance</h3>
                    <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">
                        24/7 support, regular updates, and ongoing maintenance for your applications.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Portfolio Section -->
    <section id="portfolio" class="py-12 sm:py-16 lg:py-20" style="background: var(--vscode-sidebar);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 sm:mb-16">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-4">Our Portfolio</h2>
                <p class="text-lg sm:text-xl" style="color: var(--vscode-text-muted);">
                    Showcasing our latest projects and success stories
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <div class="vscode-card rounded-lg overflow-hidden">
                    <div class="h-40 sm:h-48 bg-gradient-to-br from-blue-500 to-purple-600"></div>
                    <div class="p-4 sm:p-6">
                        <h3 class="text-lg sm:text-xl font-semibold mb-2">E-Commerce Platform</h3>
                        <p class="mb-4 text-sm sm:text-base" style="color: var(--vscode-text-muted);">
                            Modern e-commerce solution with advanced features
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">React</span>
                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Node.js</span>
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded">MongoDB</span>
                        </div>
                    </div>
                </div>
                <div class="vscode-card rounded-lg overflow-hidden">
                    <div class="h-40 sm:h-48 bg-gradient-to-br from-green-500 to-teal-600"></div>
                    <div class="p-4 sm:p-6">
                        <h3 class="text-lg sm:text-xl font-semibold mb-2">Healthcare Dashboard</h3>
                        <p class="mb-4 text-sm sm:text-base" style="color: var(--vscode-text-muted);">
                            Patient management system for healthcare providers
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">Next.js</span>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">PostgreSQL</span>
                            <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded">Redis</span>
                        </div>
                    </div>
                </div>
                <div class="vscode-card rounded-lg overflow-hidden">
                    <div class="h-40 sm:h-48 bg-gradient-to-br from-orange-500 to-red-600"></div>
                    <div class="p-4 sm:p-6">
                        <h3 class="text-lg sm:text-xl font-semibold mb-2">Financial Analytics</h3>
                        <p class="mb-4 text-sm sm:text-base" style="color: var(--vscode-text-muted);">
                            Real-time financial data visualization platform
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">Vue.js</span>
                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Python</span>
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded">Docker</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-12 sm:py-16 lg:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
                <div class="text-center lg:text-left">
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-4 sm:mb-6">About Zero One Labs</h2>
                    <p class="text-base sm:text-lg mb-6" style="color: var(--vscode-text-muted);">
                        We are a team of passionate developers and designers dedicated to creating 
                        exceptional digital experiences. With years of experience in web development, 
                        we combine creativity with technical expertise to deliver solutions that drive results.
                    </p>
                    <div class="grid grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <h3 class="text-xl sm:text-2xl font-bold text-blue-600">50+</h3>
                            <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">Projects Completed</p>
                        </div>
                        <div>
                            <h3 class="text-xl sm:text-2xl font-bold text-green-600">100%</h3>
                            <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">Client Satisfaction</p>
                        </div>
                        <div>
                            <h3 class="text-xl sm:text-2xl font-bold text-purple-600">24/7</h3>
                            <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">Support Available</p>
                        </div>
                        <div>
                            <h3 class="text-xl sm:text-2xl font-bold text-orange-600">5+</h3>
                            <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">Years Experience</p>
                        </div>
                    </div>
                </div>
                <div class="vscode-card rounded-lg p-6 sm:p-8">
                    <h3 class="text-lg sm:text-xl font-semibold mb-4">Our Tech Stack</h3>
                    <div class="grid grid-cols-3 gap-3 sm:gap-4">
                        <div class="text-center">
                            <i class="fab fa-react text-2xl sm:text-3xl text-blue-500 mb-2"></i>
                            <p class="text-xs sm:text-sm">React</p>
                        </div>
                        <div class="text-center">
                            <i class="fab fa-node-js text-2xl sm:text-3xl text-green-500 mb-2"></i>
                            <p class="text-xs sm:text-sm">Node.js</p>
                        </div>
                        <div class="text-center">
                            <i class="fab fa-python text-2xl sm:text-3xl text-yellow-500 mb-2"></i>
                            <p class="text-xs sm:text-sm">Python</p>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-database text-2xl sm:text-3xl text-purple-500 mb-2"></i>
                            <p class="text-xs sm:text-sm">Database</p>
                        </div>
                        <div class="text-center">
                            <i class="fab fa-aws text-2xl sm:text-3xl text-orange-500 mb-2"></i>
                            <p class="text-xs sm:text-sm">AWS</p>
                        </div>
                        <div class="text-center">
                            <i class="fab fa-docker text-2xl sm:text-3xl text-blue-400 mb-2"></i>
                            <p class="text-xs sm:text-sm">Docker</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-12 sm:py-16 lg:py-20" style="background: var(--vscode-sidebar);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 sm:mb-16">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-4">Get In Touch</h2>
                <p class="text-lg sm:text-xl" style="color: var(--vscode-text-muted);">
                    Ready to start your project? Let's discuss your requirements
                </p>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                <div>
                    <div class="vscode-card rounded-lg p-6 sm:p-8">
                        <h3 class="text-lg sm:text-xl font-semibold mb-4 sm:mb-6">Send us a message</h3>
                        <form id="contactForm">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Name</label>
                                    <input type="text" name="name" required class="w-full px-3 py-3 border rounded text-base" style="border-color: var(--vscode-border); background: var(--vscode-bg);">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Email</label>
                                    <input type="email" name="email" required class="w-full px-3 py-3 border rounded text-base" style="border-color: var(--vscode-border); background: var(--vscode-bg);">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">Subject</label>
                                <input type="text" name="subject" required class="w-full px-3 py-3 border rounded text-base" style="border-color: var(--vscode-border); background: var(--vscode-bg);">
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-medium mb-2">Message</label>
                                <textarea name="message" rows="4" required class="w-full px-3 py-3 border rounded text-base" style="border-color: var(--vscode-border); background: var(--vscode-bg);"></textarea>
                            </div>
                            <button type="submit" class="vscode-button w-full py-3 rounded text-base">
                                <i class="fas fa-paper-plane mr-2"></i>Send Message
                            </button>
                        </form>
                    </div>
                </div>
                <div class="space-y-6 sm:space-y-8">
                    <div class="vscode-card rounded-lg p-4 sm:p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-3 sm:mr-4">
                                <i class="fas fa-envelope text-blue-600 text-lg sm:text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-base sm:text-lg">Email Us</h3>
                                <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">hello@zeroonelabs.com</p>
                            </div>
                        </div>
                    </div>
                    <div class="vscode-card rounded-lg p-4 sm:p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-lg flex items-center justify-center mr-3 sm:mr-4">
                                <i class="fas fa-phone text-green-600 text-lg sm:text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-base sm:text-lg">Call Us</h3>
                                <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">+1 (555) 123-4567</p>
                            </div>
                        </div>
                    </div>
                    <div class="vscode-card rounded-lg p-4 sm:p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-3 sm:mr-4">
                                <i class="fas fa-map-marker-alt text-purple-600 text-lg sm:text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-base sm:text-lg">Visit Us</h3>
                                <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">123 Tech Street, Digital City</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <a href="../register.php" class="vscode-button px-6 sm:px-8 py-3 rounded-lg text-base sm:text-lg font-medium inline-block">
                            <i class="fas fa-user-plus mr-2"></i>Join Our Platform
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-8 sm:py-12" style="background: var(--vscode-bg); border-top: 1px solid var(--vscode-border);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                <div class="text-center sm:text-left">
                    <div class="flex items-center justify-center sm:justify-start mb-4">
                        <i class="fas fa-code text-xl sm:text-2xl text-blue-500 mr-2 sm:mr-3"></i>
                        <span class="text-lg sm:text-xl font-bold">ZERO ONE LABS</span>
                    </div>
                    <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">
                        Professional web development services for modern businesses.
                    </p>
                </div>
                <div class="text-center sm:text-left">
                    <h3 class="font-semibold mb-3 sm:mb-4 text-base sm:text-lg">Services</h3>
                    <ul class="space-y-2" style="color: var(--vscode-text-muted);">
                        <li><a href="#" class="hover:text-blue-500 text-sm sm:text-base">Web Development</a></li>
                        <li><a href="#" class="hover:text-blue-500 text-sm sm:text-base">Mobile Apps</a></li>
                        <li><a href="#" class="hover:text-blue-500 text-sm sm:text-base">Backend Development</a></li>
                        <li><a href="#" class="hover:text-blue-500 text-sm sm:text-base">Cloud Solutions</a></li>
                    </ul>
                </div>
                <div class="text-center sm:text-left">
                    <h3 class="font-semibold mb-3 sm:mb-4 text-base sm:text-lg">Company</h3>
                    <ul class="space-y-2" style="color: var(--vscode-text-muted);">
                        <li><a href="#about" class="hover:text-blue-500 text-sm sm:text-base">About Us</a></li>
                        <li><a href="#portfolio" class="hover:text-blue-500 text-sm sm:text-base">Portfolio</a></li>
                        <li><a href="#contact" class="hover:text-blue-500 text-sm sm:text-base">Contact</a></li>
                        <li><a href="../login.php" class="hover:text-blue-500 text-sm sm:text-base">Client Portal</a></li>
                    </ul>
                </div>
                <div class="text-center sm:text-left">
                    <h3 class="font-semibold mb-3 sm:mb-4 text-base sm:text-lg">Connect</h3>
                    <div class="flex justify-center sm:justify-start space-x-3 sm:space-x-4">
                        <a href="#" class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-100 rounded-lg flex items-center justify-center hover:bg-blue-200 transition-colors">
                            <i class="fab fa-twitter text-blue-600 text-sm sm:text-base"></i>
                        </a>
                        <a href="#" class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-100 rounded-lg flex items-center justify-center hover:bg-blue-200 transition-colors">
                            <i class="fab fa-linkedin text-blue-600 text-sm sm:text-base"></i>
                        </a>
                        <a href="#" class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-100 rounded-lg flex items-center justify-center hover:bg-blue-200 transition-colors">
                            <i class="fab fa-github text-blue-600 text-sm sm:text-base"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t mt-6 sm:mt-8 pt-6 sm:pt-8 text-center" style="border-color: var(--vscode-border);">
                <p class="text-sm sm:text-base" style="color: var(--vscode-text-muted);">
                    © 2024 Zero One Labs. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Theme toggle functionality
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            const themeIconMobile = document.getElementById('theme-icon-mobile');
            const currentTheme = body.getAttribute('data-theme');
            
            if (currentTheme === 'light') {
                body.setAttribute('data-theme', 'dark');
                themeIcon.className = 'fas fa-sun';
                themeIconMobile.className = 'fas fa-sun mr-2';
                localStorage.setItem('theme', 'dark');
            } else {
                body.setAttribute('data-theme', 'light');
                themeIcon.className = 'fas fa-moon';
                themeIconMobile.className = 'fas fa-moon mr-2';
                localStorage.setItem('theme', 'light');
            }
        }

        // Mobile menu toggle with improved functionality
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            const isHidden = mobileMenu.classList.contains('hidden');
            
            if (isHidden) {
                mobileMenu.classList.remove('hidden');
                // Prevent body scroll when menu is open
                document.body.style.overflow = 'hidden';
            } else {
                mobileMenu.classList.add('hidden');
                // Restore body scroll
                document.body.style.overflow = '';
            }
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const mobileMenu = document.getElementById('mobileMenu');
            const menuButton = document.querySelector('[onclick="toggleMobileMenu()"]');
            
            if (!mobileMenu.contains(e.target) && !menuButton.contains(e.target) && !mobileMenu.classList.contains('hidden')) {
                toggleMobileMenu();
            }
        });

        // Close mobile menu when window is resized to desktop
        window.addEventListener('resize', function() {
            const mobileMenu = document.getElementById('mobileMenu');
            if (window.innerWidth >= 768 && !mobileMenu.classList.contains('hidden')) {
                toggleMobileMenu();
            }
        });

        // Typing animation
        const words = ['Amazing', 'Modern', 'Scalable', 'Secure', 'Fast'];
        let currentWordIndex = 0;
        let currentCharIndex = 0;
        let isDeleting = false;
        const typingElement = document.getElementById('typingText');

        function typeWriter() {
            const currentWord = words[currentWordIndex];
            
            if (isDeleting) {
                typingElement.textContent = currentWord.substring(0, currentCharIndex - 1);
                currentCharIndex--;
            } else {
                typingElement.textContent = currentWord.substring(0, currentCharIndex + 1);
                currentCharIndex++;
            }

            if (!isDeleting && currentCharIndex === currentWord.length) {
                setTimeout(() => isDeleting = true, 2000);
            } else if (isDeleting && currentCharIndex === 0) {
                isDeleting = false;
                currentWordIndex = (currentWordIndex + 1) % words.length;
            }

            const typingSpeed = isDeleting ? 100 : 200;
            setTimeout(typeWriter, typingSpeed);
        }

        // Smooth scrolling for navigation links with mobile menu close
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    // Close mobile menu if open
                    const mobileMenu = document.getElementById('mobileMenu');
                    if (!mobileMenu.classList.contains('hidden')) {
                        toggleMobileMenu();
                    }
                    
                    // Smooth scroll to target
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Contact form submission with better UX
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            const name = formData.get('name');
            const email = formData.get('email');
            const subject = formData.get('subject');
            const message = formData.get('message');
            
            // Simple validation
            if (!name || !email || !subject || !message) {
                alert('Please fill in all fields.');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address.');
                return;
            }
            
            // Show success message
            alert('Thank you for your message! We will get back to you soon.');
            this.reset();
        });

        // Add loading state to buttons
        document.querySelectorAll('.vscode-button').forEach(button => {
            button.addEventListener('click', function() {
                if (this.type !== 'submit') {
                    this.style.opacity = '0.7';
                    setTimeout(() => {
                        this.style.opacity = '1';
                    }, 200);
                }
            });
        });

        // Intersection Observer for animations (optional enhancement)
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe service cards for animation
        document.querySelectorAll('.service-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });

        // Load saved theme and initialize
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            const themeIconMobile = document.getElementById('theme-icon-mobile');
            
            body.setAttribute('data-theme', savedTheme);
            themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            themeIconMobile.className = savedTheme === 'dark' ? 'fas fa-sun mr-2' : 'fas fa-moon mr-2';
            
            // Start typing animation
            setTimeout(typeWriter, 1000);
            
            // Add touch support for mobile
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
            }
        });
    </script>
</body>
</html>
