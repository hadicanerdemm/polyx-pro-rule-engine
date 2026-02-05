<?php
declare(strict_types=1);
session_start();

// Oturum kontrolü
if (!isset($_SESSION['polyx_user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['polyx_user'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="POLYX PRO++ Kurumsal Karar Motoru Dashboard">
    <title>POLYX PRO++ | Dashboard</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- CodeMirror 6 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/material-darker.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                        'mono': ['JetBrains Mono', 'monospace']
                    }
                }
            }
        }
    </script>
    
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #09090b;
            min-height: 100vh;
        }
        
        /* Glass Effects */
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-strong {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Animations */
        @keyframes pulse-glow { 0%, 100% { opacity: 0.5; } 50% { opacity: 1; } }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .animate-pulse-glow { animation: pulse-glow 2s ease-in-out infinite; }
        .spinner { border: 3px solid rgba(255,255,255,0.1); border-top-color: #6366f1; border-radius: 50%; width: 24px; height: 24px; animation: spin 0.8s linear infinite; }
        .slide-in { animation: slideIn 0.3s ease-out; }
        
        /* CodeMirror Custom Theme */
        .CodeMirror {
            background: rgba(0, 0, 0, 0.3) !important;
            color: #e4e4e7 !important;
            font-family: 'JetBrains Mono', monospace !important;
            font-size: 14px !important;
            border-radius: 12px;
            height: auto !important;
            min-height: 120px;
            padding: 8px;
        }
        .CodeMirror-gutters {
            background: transparent !important;
            border: none !important;
        }
        .CodeMirror-linenumber { color: #52525b !important; }
        .CodeMirror-cursor { border-left-color: #6366f1 !important; }
        .CodeMirror-selected { background: rgba(99, 102, 241, 0.2) !important; }
        
        /* Syntax Highlighting */
        .cm-keyword { color: #c084fc !important; font-weight: 600; }
        .cm-operator { color: #f472b6 !important; }
        .cm-variable { color: #38bdf8 !important; }
        .cm-string { color: #4ade80 !important; }
        .cm-number { color: #fb923c !important; }
        .cm-boolean { color: #f472b6 !important; }
        
        /* AST Tree */
        .ast-tree { font-family: 'JetBrains Mono', monospace; font-size: 13px; }
        .ast-node { 
            padding: 8px 12px; 
            margin: 4px 0; 
            border-radius: 8px; 
            background: rgba(255,255,255,0.03);
            border-left: 3px solid;
            transition: all 0.2s;
        }
        .ast-node:hover { background: rgba(255,255,255,0.06); transform: translateX(4px); }
        .ast-node.binary { border-color: #8b5cf6; }
        .ast-node.literal { border-color: #10b981; }
        .ast-node.variable { border-color: #3b82f6; }
        .ast-node.unary { border-color: #f59e0b; }
        .ast-children { margin-left: 24px; border-left: 1px dashed rgba(255,255,255,0.1); padding-left: 16px; }
        
        /* Status Badge */
        .status-success { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-error { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
        .status-pending { background: rgba(99, 102, 241, 0.15); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.3); }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
        
        /* Tooltip */
        .tooltip { position: relative; }
        .tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 6px 12px;
            background: #27272a;
            color: #e4e4e7;
            font-size: 12px;
            border-radius: 6px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
        }
        .tooltip:hover::after { opacity: 1; }

        /* Tab Active */
        .tab-active { background: rgba(99, 102, 241, 0.2); border-color: rgba(99, 102, 241, 0.5); color: #818cf8; }
        
        /* Metric Card Hover */
        .metric-card { transition: all 0.3s; }
        .metric-card:hover { transform: translateY(-2px); background: rgba(255,255,255,0.06); }
        
        /* Template Card */
        .template-card { transition: all 0.2s; cursor: pointer; }
        .template-card:hover { background: rgba(99, 102, 241, 0.1); border-color: rgba(99, 102, 241, 0.3); }
        
        /* History Item */
        .history-item { transition: all 0.2s; }
        .history-item:hover { background: rgba(255,255,255,0.04); }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-100">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 glass-strong z-40 transform transition-transform duration-300 lg:translate-x-0 -translate-x-full">
        <div class="flex flex-col h-full p-4">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-8 px-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 via-purple-500 to-emerald-500 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="font-bold text-white">POLYX <span class="text-indigo-400">PRO++</span></h1>
                    <p class="text-xs text-zinc-500">Karar Motoru v2.0</p>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 space-y-1">
                <a href="#" onclick="switchTab('editor')" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-zinc-300 hover:text-white hover:bg-white/5 transition-all" data-tab="editor">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    Kural Editörü
                </a>
                <a href="#" onclick="switchTab('templates')" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-zinc-300 hover:text-white hover:bg-white/5 transition-all" data-tab="templates">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    Şablonlar
                </a>
                <a href="#" onclick="switchTab('history')" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-zinc-300 hover:text-white hover:bg-white/5 transition-all" data-tab="history">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Geçmiş
                </a>
                <a href="#" onclick="switchTab('favorites')" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-zinc-300 hover:text-white hover:bg-white/5 transition-all" data-tab="favorites">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    Favoriler
                </a>
                <a href="#" onclick="switchTab('stats')" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-zinc-300 hover:text-white hover:bg-white/5 transition-all" data-tab="stats">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    İstatistikler
                </a>
                <a href="#" onclick="switchTab('docs')" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-zinc-300 hover:text-white hover:bg-white/5 transition-all" data-tab="docs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    Dokümantasyon
                </a>
            </nav>
            
            <!-- User -->
            <div class="border-t border-white/5 pt-4 mt-4">
                <div class="flex items-center gap-3 px-2">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center font-bold text-white">
                        <?= htmlspecialchars($user['avatar']) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-white truncate"><?= htmlspecialchars($user['name']) ?></p>
                        <p class="text-xs text-zinc-500 truncate"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    <a href="logout.php" class="p-2 text-zinc-500 hover:text-rose-400 transition-colors" title="Çıkış">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <header class="sticky top-0 z-30 glass border-b border-white/5">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 text-zinc-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h2 id="pageTitle" class="text-xl font-bold text-white">Kural Editörü</h2>
                </div>
                
                <!-- Live Metrics -->
                <div class="flex items-center gap-4">
                    <div class="hidden md:flex items-center gap-6 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse-glow"></div>
                            <span class="text-zinc-400">Motor Aktif</span>
                        </div>
                        <div id="memoryUsage" class="text-zinc-400">
                            <span class="text-zinc-500">RAM:</span> <span class="text-emerald-400">-</span>
                        </div>
                        <div id="apiLatency" class="text-zinc-400">
                            <span class="text-zinc-500">API:</span> <span class="text-indigo-400">-</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <button onclick="refreshData()" class="p-2 text-zinc-400 hover:text-white transition-colors rounded-lg hover:bg-white/5" title="Yenile">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Content Area -->
        <div class="p-6">
            <!-- Editor Tab -->
            <div id="tab-editor" class="tab-content">
                <div class="grid xl:grid-cols-2 gap-6">
                    <!-- Left Column - Editors -->
                    <div class="space-y-6">
                        <!-- Rule Editor Card -->
                        <div class="glass-strong rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                    Kural İfadesi
                                </h3>
                                <div class="flex items-center gap-2">
                                    <button onclick="formatRule()" class="px-3 py-1.5 text-xs text-zinc-400 hover:text-white bg-white/5 hover:bg-white/10 rounded-lg transition-all">Format</button>
                                    <button onclick="clearRule()" class="px-3 py-1.5 text-xs text-zinc-400 hover:text-rose-400 bg-white/5 hover:bg-rose-500/10 rounded-lg transition-all">Temizle</button>
                                </div>
                            </div>
                            <div id="ruleEditorContainer" class="rounded-xl overflow-hidden border border-white/10"></div>
                            <p class="mt-3 text-xs text-zinc-500">💡 Tip: <code class="px-1.5 py-0.5 bg-white/5 rounded">AND</code>, <code class="px-1.5 py-0.5 bg-white/5 rounded">OR</code>, <code class="px-1.5 py-0.5 bg-white/5 rounded">VE</code>, <code class="px-1.5 py-0.5 bg-white/5 rounded">VEYA</code> operatörlerini kullanabilirsiniz</p>
                        </div>
                        
                        <!-- Context Editor Card -->
                        <div class="glass-strong rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                                    Veri Konteksti (JSON)
                                </h3>
                                <button onclick="formatContext()" class="px-3 py-1.5 text-xs text-zinc-400 hover:text-white bg-white/5 hover:bg-white/10 rounded-lg transition-all">Format JSON</button>
                            </div>
                            <div id="contextEditorContainer" class="rounded-xl overflow-hidden border border-white/10"></div>
                            <div id="contextError" class="hidden mt-2 text-xs text-rose-400"></div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <button onclick="evaluateRule()" id="evaluateBtn" class="flex-1 py-3.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 text-white font-semibold hover:shadow-lg hover:shadow-indigo-500/25 transition-all flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span id="evaluateBtnText">Değerlendir</span>
                                <div id="evaluateSpinner" class="spinner hidden"></div>
                            </button>
                            <button onclick="addToFavorites()" class="px-4 py-3.5 rounded-xl border border-amber-500/30 text-amber-400 hover:bg-amber-500/10 transition-all" title="Favorilere Ekle">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Right Column - Results -->
                    <div class="space-y-6">
                        <!-- Result Card -->
                        <div id="resultCard" class="glass-strong rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Sonuç
                                </h3>
                                <span id="resultBadge" class="px-3 py-1 rounded-full text-xs font-medium status-pending">Bekliyor</span>
                            </div>
                            
                            <div id="resultContent" class="text-center py-8 text-zinc-500">
                                <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                <p>Bir kural girin ve <strong>Değerlendir</strong> butonuna tıklayın</p>
                            </div>
                        </div>
                        
                        <!-- Metrics Card -->
                        <div id="metricsCard" class="glass-strong rounded-2xl p-6 hidden">
                            <h3 class="text-lg font-semibold text-white flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                Performans Metrikleri
                            </h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4" id="metricsGrid"></div>
                        </div>
                        
                        <!-- AST Visualizer -->
                        <div id="astCard" class="glass-strong rounded-2xl p-6 hidden">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                                    AST Görselleştirici
                                </h3>
                                <button onclick="toggleASTView()" class="text-xs text-zinc-400 hover:text-white">Genişlet/Daralt</button>
                            </div>
                            <div id="astContent" class="ast-tree max-h-80 overflow-auto"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Templates Tab -->
            <div id="tab-templates" class="tab-content hidden"></div>
            
            <!-- History Tab -->
            <div id="tab-history" class="tab-content hidden"></div>
            
            <!-- Favorites Tab -->
            <div id="tab-favorites" class="tab-content hidden"></div>
            
            <!-- Stats Tab -->
            <div id="tab-stats" class="tab-content hidden"></div>
            
            <!-- Docs Tab -->
            <div id="tab-docs" class="tab-content hidden"></div>
        </div>
    </main>
    
    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300">
        <div class="glass-strong rounded-xl px-5 py-4 flex items-center gap-3 max-w-sm">
            <span id="toastIcon" class="text-xl">✓</span>
            <div>
                <p id="toastTitle" class="font-medium text-white">Başlık</p>
                <p id="toastMessage" class="text-sm text-zinc-400">Mesaj</p>
            </div>
        </div>
    </div>
    
    <!-- Favorite Modal -->
    <div id="favoriteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="glass-strong rounded-2xl p-6 w-full max-w-md mx-4">
            <h3 class="text-xl font-bold text-white mb-4">Favorilere Ekle</h3>
            <form id="favoriteForm">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">İsim</label>
                        <input type="text" id="favoriteName" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white focus:border-indigo-500 focus:outline-none" placeholder="Örn: Yaş Kontrolü" required>
                    </div>
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">Açıklama (Opsiyonel)</label>
                        <textarea id="favoriteDescription" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white focus:border-indigo-500 focus:outline-none resize-none" rows="2" placeholder="Bu kuralın ne işe yaradığını açıklayın..."></textarea>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeFavoriteModal()" class="flex-1 py-3 rounded-xl border border-white/10 text-zinc-400 hover:text-white hover:bg-white/5 transition-all">İptal</button>
                    <button type="submit" class="flex-1 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 text-white font-semibold hover:shadow-lg transition-all">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <script src="dashboard.js"></script>
</body>
</html>
