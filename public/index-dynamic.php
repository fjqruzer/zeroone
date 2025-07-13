<?php
// Load content from database via API calls
function fetchContent($endpoint) {
    $url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/../api/" . $endpoint;
    $response = file_get_contents($url);
    return json_decode($response, true);
}

// Fetch content
$company_info = fetchContent('company-info.php');
$services = fetchContent('services.php');
$portfolio = fetchContent('portfolio.php?featured=true&limit=6');
?>

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

            .vscode-button {
                padding: 10px 16px;
                font-size: 14px;
            }

            input, textarea, select {
                font-size: 16px !important;
            }
        }
    </style>
</head>
<body data-theme="light">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <i class="fas fa-code text-blue-600 text-2xl mr-3"></i>
                    <span class="text-xl font-bold">Zero One Labs</span>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#home" class="nav-link text-gray-700 hover:text-blue-600">Home</a>
                    <a href="#about" class="nav-link text-gray-700 hover:text-blue-600">About</a>
                    <a href="#services" class="nav-link text-gray-700 hover:text-blue-600">Services</a>
                    <a href="#portfolio" class="nav-link text-gray-700 hover:text-blue-600">Portfolio</a>
                    <a href="#contact" class="nav-link text-gray-700 hover:text-blue-600">Contact</a>
                    <a href="../login.php" class="vscode-button px-4 py-2 rounded-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>Client Portal
                    </a>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button onclick="toggleMobileMenu()" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div id="mobileMenu" class="md:hidden hidden">
                <a href="#home" class="block py-2 text-gray-700 hover:text-blue-600">Home</a>
                <a href="#about" class="block py-2 text-gray-700 hover:text-blue-600">About</a>
                <a href="#services" class="block py-2 text-gray-700 hover:text-blue-600">Services</a>
                <a href="#portfolio" class="block py-2 text-gray-700 hover:text-blue-600">Portfolio</a>
                <a href="#contact" class="block py-2 text-gray-700 hover:text-blue-600">Contact</a>
                <a href="../login.php" class="block py-2 text-blue-600 font-semibold">Client Portal</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="py-12 sm:py-16 lg:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
                <div>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold mb-6">
                        Building Digital
                        <span class="text-blue-600 typing-animation">Solutions</span>
                    </h1>
                    <p class="text-lg sm:text-xl mb-8" style="color: var(--vscode-text-muted);">
                        We create innovative web applications and digital experiences that drive business growth and user engagement.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="#contact" class="vscode-button px-6 py-3 rounded-lg text-lg">
                            <i class="fas fa-rocket mr-2"></i>Start Your Project
                        </a>
                        <a href="#portfolio" class="vscode-button-secondary px-6 py-3 rounded-lg text-lg border border-gray-300 hover:border-blue-600">
                            <i class="fas fa-eye mr-2"></i>View Our Work
                        </a>
                    </div>
                </div>
                
                <div class="hero-code">
                    <div class="code-line">
                        <span class="line-number">1</span>
                        <span class="keyword">const</span> <span class="function">project</span> = {<br>
                    </div>
                    <div class="code-line">
                        <span class="line-number">2</span>
                        &nbsp;&nbsp;<span class="string">name</span>: <span class="string">'Your Vision'</span>,<br>
                    </div>
                    <div class="code-line">
                        <span class="line-number">3</span>
                        &nbsp;&nbsp;<span class="string">status</span>: <span class="string">'In Development'</span>,<br>
                    </div>
                    <div class="code-line">
                        <span class="line-number">4</span>
                        &nbsp;&nbsp;<span class="string">technologies</span>: [<span class="string">'React'</span>, <span class="string">'Node.js'</span>],<br>
                    </div>
                    <div class="code-line">
                        <span class="line-number">5</span>
                        &nbsp;&nbsp;<span class="function">launch</span>: () => <span class="string">'Success!'</span><br>
                    </div>
                    <div class="code-line">
                        <span class="line-number">6</span>
                    };<br>
                    </div>
                    <div class="code-line">
                        <span class="line-number">7</span>
                        <span class="comment">// Ready to build your next project?</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-12 sm:py-16 lg:py-20 gradient-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 sm:mb-16">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-6">About Zero One Labs</h2>
                <p class="text-lg sm:text-xl max-w-3xl mx-auto" style="color: var(--vscode-text-muted);">
                    We are passionate about creating exceptional digital experiences that help businesses thrive in the digital age.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php if (isset($company_info['data']['about'])): ?>
                    <?php foreach ($company_info['data']['about'] as $about): ?>
                    <div class="vscode-card rounded-lg p-6 text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-info-circle text-blue-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-3"><?php echo htmlspecialchars($about['title']); ?></h3>
                        <p style="color: var(--vscode-text-muted);"><?php echo htmlspecialchars($about['content']); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (isset($company_info['data']['mission'])): ?>
                    <?php foreach ($company_info['data']['mission'] as $mission): ?>
                    <div class="vscode-card rounded-lg p-6 text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-bullseye text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-3"><?php echo htmlspecialchars($mission['title']); ?></h3>
                        <p style="color: var(--vscode-text-muted);"><?php echo htmlspecialchars($mission['content']); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (isset($company_info['data']['vision'])): ?>
                    <?php foreach ($company_info['data']['vision'] as $vision): ?>
                    <div class="vscode-card rounded-lg p-6 text-center">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-eye text-purple-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-3"><?php echo htmlspecialchars($vision['title']); ?></h3>
                        <p style="color: var(--vscode-text-muted);"><?php echo htmlspecialchars($vision['content']); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-12 sm:py-16 lg:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 sm:mb-16">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-6">Our Services</h2>
                <p class="text-lg sm:text-xl max-w-3xl mx-auto" style="color: var(--vscode-text-muted);">
                    Comprehensive web development solutions tailored to your business needs
                </p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <?php if (isset($services['data'])): ?>
                    <?php foreach ($services['data'] as $service): ?>
                    <div class="service-card vscode-card rounded-lg p-4 sm:p-6">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-3 sm:mb-4">
                            <i class="<?php echo htmlspecialchars($service['icon']); ?> text-blue-600 text-lg sm:text-xl"></i>
                        </div>
                        <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                        <p class="text-sm sm:text-base mb-3" style="color: var(--vscode-text-muted);">
                            <?php echo htmlspecialchars($service['description']); ?>
                        </p>
                        <p class="text-sm font-semibold text-blue-600 mb-3"><?php echo htmlspecialchars($service['price_range']); ?></p>
                        <?php if (!empty($service['features'])): ?>
                        <div class="space-y-1">
                            <?php foreach (array_slice($service['features'], 0, 3) as $feature): ?>
                            <div class="flex items-center text-sm" style="color: var(--vscode-text-muted);">
                                <i class="fas fa-check text-green-500 mr-2 text-xs"></i>
                                <?php echo htmlspecialchars($feature); ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Portfolio Section -->
    <section id="portfolio" class="py-12 sm:py-16 lg:py-20" style="background: var(--vscode-sidebar);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 sm:mb-16">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-6">Our Portfolio</h2>
                <p class="text-lg sm:text-xl max-w-3xl mx-auto" style="color: var(--vscode-text-muted);">
                    Showcasing our latest projects and success stories
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <?php if (isset($portfolio['data'])): ?>
                    <?php foreach ($portfolio['data'] as $project): ?>
                    <div class="vscode-card rounded-lg overflow-hidden">
                        <?php if ($project['image_url']): ?>
                        <div class="h-40 sm:h-48 bg-gray-200">
                            <img src="../<?php echo htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-full object-cover">
                        </div>
                        <?php else: ?>
                        <div class="h-40 sm:h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                            <i class="fas fa-image text-white text-4xl"></i>
                        </div>
                        <?php endif; ?>
                        <div class="p-4 sm:p-6">
                            <h3 class="text-lg sm:text-xl font-semibold mb-2"><?php echo htmlspecialchars($project['title']); ?></h3>
                            <p class="mb-4 text-sm sm:text-base" style="color: var(--vscode-text-muted);">
                                <?php echo htmlspecialchars(substr($project['description'], 0, 100)) . (strlen($project['description']) > 100 ? '...' : ''); ?>
                            </p>
                            <?php if (!empty($project['technologies'])): ?>
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php foreach (array_slice($project['technologies'], 0, 3) as $tech): ?>
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded"><?php echo htmlspecialchars($tech); ?></span>
                                <?php endforeach; ?>
                                <?php if (count($project['technologies']) > 3): ?>
                                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">+<?php echo count($project['technologies']) - 3; ?> more</span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <div class="flex space-x-2">
                                <?php if ($project['demo_url']): ?>
                                <a href="<?php echo htmlspecialchars($project['demo_url']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-external-link-alt mr-1"></i>Live Demo
                                </a>
                                <?php endif; ?>
                                <?php if ($project['github_url']): ?>
                                <a href="<?php echo htmlspecialchars($project['github_url']); ?>" target="_blank" class="text-gray-600 hover:text-gray-800 text-sm">
                                    <i class="fab fa-github mr-1"></i>Code
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-12 sm:py-16 lg:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 sm:mb-16">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-6">Get In Touch</h2>
                <p class="text-lg sm:text-xl max-w-3xl mx-auto" style="color: var(--vscode-text-muted);">
                    Ready to start your next project? Let's discuss how we can help bring your vision to life.
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                <!-- Contact Form -->
                <div class="vscode-card rounded-lg p-6 sm:p-8">
                    <h3 class="text-2xl font-semibold mb-6">Send us a message</h3>
                    <form id="contactForm" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                                <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Company</label>
                                <input type="text" name="company" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Inquiry Type</label>
                            <select name="inquiry_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="general">General Inquiry</option>
                                <option value="project">Project Request</option>
                                <option value="support">Support</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                            <input type="text" name="subject" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                            <textarea name="message" rows="5" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                        <button type="submit" class="vscode-button w-full px-6 py-3 rounded-lg text-lg">
                            <i class="fas fa-paper-plane mr-2"></i>Send Message
                        </button>
                    </form>
                </div>
                
                <!-- Contact Information -->
                <div class="space-y-6">
                    <div class="vscode-card rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Contact Information</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center">
                                <i class="fas fa-envelope mr-3 text-blue-600"></i>
                                <span>support@zeroonelabs.com</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-phone mr-3 text-green-600"></i>
                                <span>+1 (555) 123-4567</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock mr-3 text-purple-600"></i>
                                <span>Mon-Fri, 9AM-6PM EST</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="vscode-card rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Why Choose Us?</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                                <span>Experienced development team</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                                <span>Modern technology stack</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                                <span>Responsive and scalable solutions</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                                <span>Ongoing support and maintenance</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center sm:text-left">
                    <div class="flex items-center justify-center sm:justify-start mb-4">
                        <i class="fas fa-code text-blue-400 text-2xl mr-3"></i>
                        <span class="text-xl font-bold">Zero One Labs</span>
                    </div>
                    <p class="text-gray-400 text-sm sm:text-base">
                        Building innovative digital solutions for modern businesses.
                    </p>
                </div>
                <div class="text-center sm:text-left">
                    <h3 class="font-semibold mb-3 sm:mb-4 text-base sm:text-lg">Services</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#services" class="hover:text-blue-400 text-sm sm:text-base">Web Development</a></li>
                        <li><a href="#services" class="hover:text-blue-400 text-sm sm:text-base">E-commerce</a></li>
                        <li><a href="#services" class="hover:text-blue-400 text-sm sm:text-base">Mobile Apps</a></li>
                        <li><a href="#services" class="hover:text-blue-400 text-sm sm:text-base">API Development</a></li>
                    </ul>
                </div>
                <div class="text-center sm:text-left">
                    <h3 class="font-semibold mb-3 sm:mb-4 text-base sm:text-lg">Company</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#about" class="hover:text-blue-400 text-sm sm:text-base">About Us</a></li>
                        <li><a href="#portfolio" class="hover:text-blue-400 text-sm sm:text-base">Portfolio</a></li>
                        <li><a href="#contact" class="hover:text-blue-400 text-sm sm:text-base">Contact</a></li>
                        <li><a href="../login.php" class="hover:text-blue-400 text-sm sm:text-base">Client Portal</a></li>
                    </ul>
                </div>
                <div class="text-center sm:text-left">
                    <h3 class="font-semibold mb-3 sm:mb-4 text-base sm:text-lg">Connect</h3>
                    <div class="flex justify-center sm:justify-start space-x-3 sm:space-x-4">
                        <a href="#" class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-700 transition-colors">
                            <i class="fab fa-twitter text-white text-sm sm:text-base"></i>
                        </a>
                        <a href="#" class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-700 transition-colors">
                            <i class="fab fa-linkedin text-white text-sm sm:text-base"></i>
                        </a>
                        <a href="#" class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-700 transition-colors">
                            <i class="fab fa-github text-white text-sm sm:text-base"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400 text-sm">
                    © 2024 Zero One Labs. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Theme toggle
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            
            if (body.getAttribute('data-theme') === 'dark') {
                body.setAttribute('data-theme', 'light');
                themeIcon.className = 'fas fa-moon';
            } else {
                body.setAttribute('data-theme', 'dark');
                themeIcon.className = 'fas fa-sun';
            }
        }

        // Mobile menu toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Contact form submission
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('../api/inquiries.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Thank you! Your message has been sent successfully.');
                    this.reset();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error sending message. Please try again.');
            }
        });

        // Typing animation
        const typingElement = document.querySelector('.typing-animation');
        if (typingElement) {
            const words = ['Solutions', 'Experiences', 'Platforms', 'Applications'];
            let wordIndex = 0;
            let charIndex = 0;
            let isDeleting = false;
            
            function type() {
                const currentWord = words[wordIndex];
                
                if (isDeleting) {
                    typingElement.textContent = currentWord.substring(0, charIndex - 1);
                    charIndex--;
                } else {
                    typingElement.textContent = currentWord.substring(0, charIndex + 1);
                    charIndex++;
                }
                
                if (!isDeleting && charIndex === currentWord.length) {
                    setTimeout(() => isDeleting = true, 2000);
                } else if (isDeleting && charIndex === 0) {
                    isDeleting = false;
                    wordIndex = (wordIndex + 1) % words.length;
                }
                
                setTimeout(type, isDeleting ? 100 : 200);
            }
            
            setTimeout(type, 1000);
        }
    </script>
</body>
</html> 