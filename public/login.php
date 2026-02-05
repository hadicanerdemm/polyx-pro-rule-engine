<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="POLYX PRO++ Kurumsal Karar Motoru - Yüksek Performanslı Kural İşleme Sistemi">
    <title>POLYX PRO++ | Giriş</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                        'mono': ['JetBrains Mono', 'monospace']
                    },
                    colors: {
                        'polyx': {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81'
                        }
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                        'shimmer': 'shimmer 2s linear infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'gradient': 'gradient 8s ease infinite',
                    }
                }
            }
        }
    </script>
    
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #09090b;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .bg-animated {
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(16, 185, 129, 0.05) 0%, transparent 70%),
                #09090b;
            background-size: 200% 200%;
            animation: gradient 15s ease infinite;
        }
        
        @keyframes gradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }
        
        @keyframes glow {
            from { box-shadow: 0 0 20px rgba(99, 102, 241, 0.3); }
            to { box-shadow: 0 0 40px rgba(99, 102, 241, 0.6); }
        }
        
        @keyframes shimmer {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        
        /* Glass Effect */
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .glass-strong {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Glowing Border */
        .glow-border {
            position: relative;
        }
        
        .glow-border::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: inherit;
            background: linear-gradient(135deg, #6366f1, #8b5cf6, #10b981, #6366f1);
            background-size: 400% 400%;
            animation: gradient 4s ease infinite;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .glow-border:hover::before,
        .glow-border:focus-within::before {
            opacity: 1;
        }
        
        /* Input Styling */
        .input-modern {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .input-modern:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.2);
            outline: none;
        }
        
        /* Button Effects */
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.5s ease;
        }
        
        .btn-primary:hover::before {
            transform: translateX(100%);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(99, 102, 241, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        /* Logo Animation */
        .logo-container {
            animation: float 6s ease-in-out infinite;
        }
        
        .logo-glow {
            filter: drop-shadow(0 0 30px rgba(99, 102, 241, 0.5));
        }
        
        /* Particles */
        .particles {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(99, 102, 241, 0.3);
            border-radius: 50%;
            animation: float 10s ease-in-out infinite;
        }
        
        /* Grid Pattern */
        .grid-pattern {
            background-image: 
                linear-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
        }
        
        /* Feature Cards */
        .feature-card {
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-4px);
            background: rgba(255, 255, 255, 0.06);
        }
        
        /* Checkbox Custom */
        .checkbox-custom {
            appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(99, 102, 241, 0.5);
            border-radius: 4px;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .checkbox-custom:checked {
            background: #6366f1;
            border-color: #6366f1;
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
        }
        
        /* Loading Spinner */
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top-color: #6366f1;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Notification */
        .notification {
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        /* Stats Counter */
        .stats-value {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-animated grid-pattern">
    <!-- Particles -->
    <div class="particles" id="particles"></div>
    
    <!-- Notification -->
    <div id="notification" class="notification fixed top-6 right-6 z-50 glass-strong rounded-xl px-6 py-4 max-w-sm">
        <div class="flex items-center gap-3">
            <span id="notificationIcon" class="text-2xl">✓</span>
            <div>
                <p id="notificationTitle" class="font-semibold text-white">Başlık</p>
                <p id="notificationMessage" class="text-sm text-zinc-400">Mesaj</p>
            </div>
        </div>
    </div>
    
    <div class="min-h-screen flex items-center justify-center p-4 relative z-10">
        <div class="w-full max-w-6xl grid lg:grid-cols-2 gap-8 items-center">
            
            <!-- Sol Taraf - Branding -->
            <div class="hidden lg:block space-y-8">
                <!-- Logo -->
                <div class="logo-container">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-emerald-500 flex items-center justify-center logo-glow">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">POLYX<span class="text-indigo-400"> PRO++</span></h1>
                            <p class="text-zinc-500 text-sm">Kurumsal Karar Motoru</p>
                        </div>
                    </div>
                </div>
                
                <!-- Headline -->
                <div class="space-y-4">
                    <h2 class="text-4xl lg:text-5xl font-bold text-white leading-tight">
                        Kurallarınızı<br>
                        <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-emerald-400 bg-clip-text text-transparent">
                            Akıllıca Yönetin
                        </span>
                    </h2>
                    <p class="text-zinc-400 text-lg max-w-md">
                        Yüksek performanslı kural motoru ile iş mantığınızı kolayca tanımlayın, test edin ve optimize edin.
                    </p>
                </div>
                
                <!-- Features -->
                <div class="grid grid-cols-2 gap-4 mt-8">
                    <div class="feature-card glass rounded-xl p-4 space-y-2">
                        <div class="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-white">Ultra Hızlı</h3>
                        <p class="text-zinc-500 text-sm">Mikrosaniye seviyesinde değerlendirme</p>
                    </div>
                    
                    <div class="feature-card glass rounded-xl p-4 space-y-2">
                        <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-white">Güvenli</h3>
                        <p class="text-zinc-500 text-sm">eval() kullanmayan güvenli motor</p>
                    </div>
                    
                    <div class="feature-card glass rounded-xl p-4 space-y-2">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-white">Esnek</h3>
                        <p class="text-zinc-500 text-sm">Türkçe operatör desteği</p>
                    </div>
                    
                    <div class="feature-card glass rounded-xl p-4 space-y-2">
                        <div class="w-10 h-10 rounded-lg bg-rose-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-white">Analitik</h3>
                        <p class="text-zinc-500 text-sm">Gerçek zamanlı performans metrikleri</p>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="flex gap-8 mt-8">
                    <div>
                        <div class="stats-value text-3xl font-bold">< 1ms</div>
                        <div class="text-zinc-500 text-sm">Ortalama Yanıt</div>
                    </div>
                    <div>
                        <div class="stats-value text-3xl font-bold">100%</div>
                        <div class="text-zinc-500 text-sm">Tip Güvenliği</div>
                    </div>
                    <div>
                        <div class="stats-value text-3xl font-bold">∞</div>
                        <div class="text-zinc-500 text-sm">Kural Kapasitesi</div>
                    </div>
                </div>
            </div>
            
            <!-- Sağ Taraf - Login Form -->
            <div class="w-full max-w-md mx-auto lg:mx-0">
                <div class="glass-strong rounded-3xl p-8 space-y-6 glow-border">
                    <!-- Mobile Logo -->
                    <div class="lg:hidden flex items-center justify-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 via-purple-500 to-emerald-500 flex items-center justify-center">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-white">POLYX <span class="text-indigo-400">PRO++</span></span>
                    </div>
                    
                    <!-- Form Header -->
                    <div class="text-center lg:text-left">
                        <h2 class="text-2xl font-bold text-white">Hoş Geldiniz</h2>
                        <p class="text-zinc-400 mt-1">Karar motoruna erişmek için giriş yapın</p>
                    </div>
                    
                    <!-- Login Form -->
                    <form id="loginForm" class="space-y-5">
                        <!-- Username -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-300 flex items-center gap-2">
                                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Kullanıcı Adı
                            </label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username"
                                class="input-modern w-full px-4 py-3 rounded-xl text-white placeholder-zinc-500"
                                placeholder="admin"
                                required
                                autocomplete="username"
                            >
                        </div>
                        
                        <!-- Password -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-300 flex items-center gap-2">
                                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                Şifre
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password"
                                    class="input-modern w-full px-4 py-3 rounded-xl text-white placeholder-zinc-500 pr-12"
                                    placeholder="••••••••"
                                    required
                                    autocomplete="current-password"
                                >
                                <button 
                                    type="button" 
                                    id="togglePassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-300 transition-colors"
                                >
                                    <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Remember & Forgot -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="remember" class="checkbox-custom">
                                <span class="text-sm text-zinc-400">Beni hatırla</span>
                            </label>
                            <a href="#" class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors">Şifremi unuttum</a>
                        </div>
                        
                        <!-- Submit Button -->
                        <button 
                            type="submit" 
                            id="submitBtn"
                            class="btn-primary w-full py-3.5 rounded-xl text-white font-semibold flex items-center justify-center gap-2"
                        >
                            <span id="submitText">Giriş Yap</span>
                            <div id="submitSpinner" class="spinner hidden"></div>
                            <svg id="submitArrow" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </form>
                    
                    <!-- Divider -->
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-zinc-800"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-zinc-900/50 text-zinc-500">veya</span>
                        </div>
                    </div>
                    
                    <!-- Demo Access -->
                    <div class="space-y-3">
                        <button 
                            type="button" 
                            id="demoAdminBtn"
                            class="w-full py-3 rounded-xl border border-indigo-500/30 text-indigo-400 font-medium hover:bg-indigo-500/10 transition-all flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Demo Admin Girişi
                        </button>
                        
                        <button 
                            type="button" 
                            id="demoUserBtn"
                            class="w-full py-3 rounded-xl border border-emerald-500/30 text-emerald-400 font-medium hover:bg-emerald-500/10 transition-all flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Demo Kullanıcı Girişi
                        </button>
                    </div>
                    
                    <!-- Footer -->
                    <p class="text-center text-zinc-600 text-sm">
                        POLYX PRO++ Karar Motoru v2.0<br>
                        <span class="text-zinc-700">© 2024 Tüm Hakları Saklıdır</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Particles Animation
        function createParticles() {
            const container = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 10 + 's';
                particle.style.animationDuration = (10 + Math.random() * 10) + 's';
                particle.style.opacity = Math.random() * 0.5 + 0.1;
                container.appendChild(particle);
            }
        }
        
        createParticles();
        
        // Password Toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            if (type === 'text') {
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                `;
            } else {
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                `;
            }
        });
        
        // Notification Function
        function showNotification(title, message, type = 'success') {
            const notification = document.getElementById('notification');
            const icon = document.getElementById('notificationIcon');
            const titleEl = document.getElementById('notificationTitle');
            const messageEl = document.getElementById('notificationMessage');
            
            titleEl.textContent = title;
            messageEl.textContent = message;
            
            if (type === 'success') {
                icon.textContent = '✓';
                icon.className = 'text-2xl text-emerald-400';
            } else if (type === 'error') {
                icon.textContent = '✕';
                icon.className = 'text-2xl text-rose-400';
            } else {
                icon.textContent = 'ℹ';
                icon.className = 'text-2xl text-indigo-400';
            }
            
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 4000);
        }
        
        // Login Form Handler
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const submitSpinner = document.getElementById('submitSpinner');
        const submitArrow = document.getElementById('submitArrow');
        
        function setLoading(loading) {
            submitBtn.disabled = loading;
            submitText.textContent = loading ? 'Giriş yapılıyor...' : 'Giriş Yap';
            submitSpinner.classList.toggle('hidden', !loading);
            submitArrow.classList.toggle('hidden', loading);
        }
        
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;
            
            if (!username || !password) {
                showNotification('Hata', 'Kullanıcı adı ve şifre gereklidir', 'error');
                return;
            }
            
            setLoading(true);
            
            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password, remember })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Başarılı', 'Giriş yapıldı, yönlendiriliyorsunuz...', 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    showNotification('Hata', data.error?.message || 'Giriş başarısız', 'error');
                }
            } catch (error) {
                showNotification('Hata', 'Bağlantı hatası oluştu', 'error');
            } finally {
                setLoading(false);
            }
        });
        
        // Demo Login Handlers
        document.getElementById('demoAdminBtn').addEventListener('click', () => {
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'admin123';
            loginForm.dispatchEvent(new Event('submit'));
        });
        
        document.getElementById('demoUserBtn').addEventListener('click', () => {
            document.getElementById('username').value = 'demo';
            document.getElementById('password').value = 'demo123';
            loginForm.dispatchEvent(new Event('submit'));
        });
        
        // Auto-fill from localStorage
        const savedUsername = localStorage.getItem('polyx_username');
        if (savedUsername) {
            document.getElementById('username').value = savedUsername;
            document.getElementById('remember').checked = true;
        }
    </script>
</body>
</html>
