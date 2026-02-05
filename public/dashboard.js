/**
 * POLYX PRO++ Dashboard JavaScript
 * Kural Motoru Kontrol Paneli
 */

// Global State
let ruleEditor = null;
let contextEditor = null;
let currentTab = 'editor';

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    initEditors();
    loadInitialData();
    setActiveNav('editor');
});

// Initialize CodeMirror Editors
function initEditors() {
    // Rule Editor with custom mode
    CodeMirror.defineSimpleMode = CodeMirror.defineSimpleMode || function(){};
    
    ruleEditor = CodeMirror(document.getElementById('ruleEditorContainer'), {
        value: 'user.age >= 18 AND user.active == true',
        mode: 'javascript',
        theme: 'material-darker',
        lineNumbers: true,
        lineWrapping: true,
        placeholder: 'Kuralınızı buraya yazın...'
    });
    
    // Context Editor
    contextEditor = CodeMirror(document.getElementById('contextEditorContainer'), {
        value: JSON.stringify({
            user: {
                age: 25,
                active: true,
                role: "admin",
                email: "john@company.com"
            }
        }, null, 2),
        mode: { name: 'javascript', json: true },
        theme: 'material-darker',
        lineNumbers: true,
        lineWrapping: true
    });
    
    // Live JSON validation
    contextEditor.on('change', () => {
        validateContext();
    });
}

// Validate JSON Context
function validateContext() {
    const errorEl = document.getElementById('contextError');
    try {
        JSON.parse(contextEditor.getValue());
        errorEl.classList.add('hidden');
        errorEl.textContent = '';
        return true;
    } catch (e) {
        errorEl.classList.remove('hidden');
        errorEl.textContent = '⚠️ JSON Hatası: ' + e.message;
        return false;
    }
}

// Evaluate Rule
async function evaluateRule() {
    const btn = document.getElementById('evaluateBtn');
    const btnText = document.getElementById('evaluateBtnText');
    const spinner = document.getElementById('evaluateSpinner');
    
    if (!validateContext()) {
        showToast('Hata', 'JSON formatını düzeltin', 'error');
        return;
    }
    
    const rule = ruleEditor.getValue().trim();
    if (!rule) {
        showToast('Hata', 'Kural boş olamaz', 'error');
        return;
    }
    
    let context;
    try {
        context = JSON.parse(contextEditor.getValue());
    } catch (e) {
        showToast('Hata', 'Geçersiz JSON', 'error');
        return;
    }
    
    // Loading state
    btn.disabled = true;
    btnText.textContent = 'Değerlendiriliyor...';
    spinner.classList.remove('hidden');
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ rule, context })
        });
        
        const data = await response.json();
        displayResult(data);
        
        // Update memory usage
        if (data.meta) {
            document.getElementById('memoryUsage').innerHTML = 
                '<span class="text-zinc-500">RAM:</span> <span class="text-emerald-400">' + data.meta.memory + '</span>';
            document.getElementById('apiLatency').innerHTML = 
                '<span class="text-zinc-500">API:</span> <span class="text-indigo-400">' + data.meta.time + '</span>';
        }
        
    } catch (error) {
        showToast('Hata', 'API bağlantı hatası', 'error');
        console.error(error);
    } finally {
        btn.disabled = false;
        btnText.textContent = 'Değerlendir';
        spinner.classList.add('hidden');
    }
}

// Display Result
function displayResult(data) {
    const resultCard = document.getElementById('resultCard');
    const resultContent = document.getElementById('resultContent');
    const resultBadge = document.getElementById('resultBadge');
    const metricsCard = document.getElementById('metricsCard');
    const astCard = document.getElementById('astCard');
    
    if (data.success) {
        const isApproved = data.decision;
        
        // Badge
        resultBadge.textContent = isApproved ? 'ONAYLANDI' : 'REDDEDİLDİ';
        resultBadge.className = 'px-3 py-1 rounded-full text-xs font-medium ' + 
            (isApproved ? 'status-success' : 'status-error');
        
        // Result content
        resultContent.innerHTML = `
            <div class="slide-in">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full ${isApproved ? 'bg-emerald-500/20' : 'bg-rose-500/20'} flex items-center justify-center">
                    ${isApproved 
                        ? '<svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
                        : '<svg class="w-10 h-10 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'
                    }
                </div>
                <h3 class="text-2xl font-bold ${isApproved ? 'text-emerald-400' : 'text-rose-400'} mb-2">${data.message}</h3>
                <p class="text-zinc-500 text-sm">Kural başarıyla değerlendirildi</p>
            </div>
        `;
        
        // Metrics
        if (data.meta) {
            metricsCard.classList.remove('hidden');
            document.getElementById('metricsGrid').innerHTML = `
                <div class="metric-card glass rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-indigo-400">${data.meta.time}</div>
                    <div class="text-xs text-zinc-500 mt-1">Süre</div>
                </div>
                <div class="metric-card glass rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-purple-400">${data.meta.memory}</div>
                    <div class="text-xs text-zinc-500 mt-1">Bellek</div>
                </div>
                <div class="metric-card glass rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-emerald-400">${data.meta.tokens}</div>
                    <div class="text-xs text-zinc-500 mt-1">Token</div>
                </div>
                <div class="metric-card glass rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-amber-400">${data.meta.evaluation_steps || '-'}</div>
                    <div class="text-xs text-zinc-500 mt-1">Adım</div>
                </div>
            `;
        }
        
        // AST
        if (data.debug_ast) {
            astCard.classList.remove('hidden');
            document.getElementById('astContent').innerHTML = renderAST(data.debug_ast.body || data.debug_ast);
        }
        
    } else {
        resultBadge.textContent = 'HATA';
        resultBadge.className = 'px-3 py-1 rounded-full text-xs font-medium status-error';
        
        resultContent.innerHTML = `
            <div class="slide-in text-left">
                <div class="flex items-start gap-4 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20">
                    <svg class="w-6 h-6 text-rose-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-rose-400">${data.error?.code || 'HATA'}</h4>
                        <p class="text-zinc-300 mt-1">${data.error?.message || 'Bilinmeyen hata'}</p>
                        ${data.error?.details ? '<pre class="mt-2 text-xs text-zinc-500 overflow-auto">' + JSON.stringify(data.error.details, null, 2) + '</pre>' : ''}
                    </div>
                </div>
            </div>
        `;
        
        metricsCard.classList.add('hidden');
        astCard.classList.add('hidden');
    }
}

// Render AST Tree
function renderAST(node, depth = 0) {
    if (!node || typeof node !== 'object') return '';
    
    let html = '';
    const type = node.type || 'unknown';
    
    switch (type) {
        case 'BinaryExpression':
            html = `
                <div class="ast-node binary">
                    <span class="text-purple-400 font-semibold">${node.operator}</span>
                    <span class="text-zinc-500 text-xs ml-2">Binary</span>
                </div>
                <div class="ast-children">
                    ${renderAST(node.left, depth + 1)}
                    ${renderAST(node.right, depth + 1)}
                </div>
            `;
            break;
            
        case 'UnaryExpression':
            html = `
                <div class="ast-node unary">
                    <span class="text-amber-400 font-semibold">${node.operator}</span>
                    <span class="text-zinc-500 text-xs ml-2">Unary</span>
                </div>
                <div class="ast-children">
                    ${renderAST(node.operand, depth + 1)}
                </div>
            `;
            break;
            
        case 'Variable':
            html = `
                <div class="ast-node variable">
                    <span class="text-blue-400">📊</span>
                    <span class="text-blue-300 ml-2">${node.name}</span>
                    <span class="text-zinc-500 text-xs ml-2">Variable</span>
                </div>
            `;
            break;
            
        case 'Literal':
            const valueColor = node.valueType === 'string' ? 'text-emerald-400' : 
                              node.valueType === 'number' ? 'text-amber-400' : 
                              node.valueType === 'boolean' ? 'text-pink-400' : 'text-zinc-400';
            html = `
                <div class="ast-node literal">
                    <span class="text-emerald-400">📌</span>
                    <span class="${valueColor} ml-2">${JSON.stringify(node.value)}</span>
                    <span class="text-zinc-500 text-xs ml-2">${node.valueType}</span>
                </div>
            `;
            break;
            
        case 'ContainsExpression':
            html = `
                <div class="ast-node binary">
                    <span class="text-cyan-400 font-semibold">CONTAINS</span>
                </div>
                <div class="ast-children">
                    ${renderAST(node.target, depth + 1)}
                    ${renderAST(node.search, depth + 1)}
                </div>
            `;
            break;
            
        case 'InExpression':
            html = `
                <div class="ast-node binary">
                    <span class="text-cyan-400 font-semibold">IN</span>
                </div>
                <div class="ast-children">
                    ${renderAST(node.value, depth + 1)}
                    ${renderAST(node.array, depth + 1)}
                </div>
            `;
            break;
            
        default:
            html = `<div class="ast-node">${JSON.stringify(node)}</div>`;
    }
    
    return html;
}

// Tab Switching
function switchTab(tab) {
    currentTab = tab;
    
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    
    // Show selected tab
    const tabEl = document.getElementById('tab-' + tab);
    if (tabEl) {
        tabEl.classList.remove('hidden');
    }
    
    // Update nav
    setActiveNav(tab);
    
    // Update title
    const titles = {
        editor: 'Kural Editörü',
        templates: 'Kural Şablonları',
        history: 'Sorgu Geçmişi',
        favorites: 'Favoriler',
        stats: 'İstatistikler',
        docs: 'Dokümantasyon'
    };
    document.getElementById('pageTitle').textContent = titles[tab] || 'Dashboard';
    
    // Load tab content
    loadTabContent(tab);
}

function setActiveNav(tab) {
    document.querySelectorAll('.nav-link').forEach(link => {
        const linkTab = link.dataset.tab;
        if (linkTab === tab) {
            link.classList.add('bg-indigo-500/20', 'text-indigo-400');
            link.classList.remove('text-zinc-300');
        } else {
            link.classList.remove('bg-indigo-500/20', 'text-indigo-400');
            link.classList.add('text-zinc-300');
        }
    });
}

// Load Tab Content
async function loadTabContent(tab) {
    switch (tab) {
        case 'templates':
            await loadTemplates();
            break;
        case 'history':
            await loadHistory();
            break;
        case 'favorites':
            await loadFavorites();
            break;
        case 'stats':
            await loadStats();
            break;
        case 'docs':
            loadDocs();
            break;
    }
}

// Load Templates
async function loadTemplates() {
    const container = document.getElementById('tab-templates');
    container.innerHTML = '<div class="flex justify-center py-12"><div class="spinner"></div></div>';
    
    try {
        const response = await fetch('api.php?action=templates');
        const data = await response.json();
        
        if (data.success) {
            let html = '<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">';
            data.templates.forEach(template => {
                html += `
                    <div class="template-card glass-strong rounded-xl p-5 border border-white/5" onclick="useTemplate(${JSON.stringify(template).replace(/"/g, '&quot;')})">
                        <div class="flex items-start justify-between mb-3">
                            <span class="text-2xl">${template.name.split(' ')[0]}</span>
                            <span class="px-2 py-1 text-xs rounded-full bg-indigo-500/20 text-indigo-400">${template.category}</span>
                        </div>
                        <h4 class="font-semibold text-white mb-2">${template.name.split(' ').slice(1).join(' ')}</h4>
                        <p class="text-sm text-zinc-500 mb-3">${template.description}</p>
                        <code class="block text-xs text-zinc-400 bg-black/20 p-2 rounded-lg overflow-x-auto">${template.rule}</code>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }
    } catch (error) {
        container.innerHTML = '<p class="text-center text-rose-400 py-12">Şablonlar yüklenemedi</p>';
    }
}

// Load History
async function loadHistory() {
    const container = document.getElementById('tab-history');
    container.innerHTML = '<div class="flex justify-center py-12"><div class="spinner"></div></div>';
    
    try {
        const response = await fetch('api.php?action=history&limit=50');
        const data = await response.json();
        
        if (data.success) {
            if (data.history.length === 0) {
                container.innerHTML = '<p class="text-center text-zinc-500 py-12">Henüz sorgu geçmişi yok</p>';
                return;
            }
            
            let html = '<div class="glass-strong rounded-2xl overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm">';
            html += '<thead class="bg-white/5"><tr><th class="px-4 py-3 text-left text-zinc-400">Kural</th><th class="px-4 py-3 text-center text-zinc-400">Sonuç</th><th class="px-4 py-3 text-center text-zinc-400">Süre</th><th class="px-4 py-3 text-right text-zinc-400">Tarih</th></tr></thead>';
            html += '<tbody>';
            
            data.history.forEach(item => {
                const isSuccess = item.result === 1 || item.result === true;
                html += `
                    <tr class="history-item border-t border-white/5 cursor-pointer" onclick="loadHistoryItem(${item.id})">
                        <td class="px-4 py-3 text-zinc-300 max-w-xs truncate">${escapeHtml(item.rule)}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded-full text-xs ${item.error_message ? 'status-error' : (isSuccess ? 'status-success' : 'status-error')}">
                                ${item.error_message ? 'HATA' : (isSuccess ? 'ONAY' : 'RET')}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-zinc-500">${item.execution_time ? item.execution_time.toFixed(2) + ' ms' : '-'}</td>
                        <td class="px-4 py-3 text-right text-zinc-500">${new Date(item.created_at).toLocaleString('tr-TR')}</td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div></div>';
            container.innerHTML = html;
        }
    } catch (error) {
        container.innerHTML = '<p class="text-center text-rose-400 py-12">Geçmiş yüklenemedi</p>';
    }
}

// Load Favorites
async function loadFavorites() {
    const container = document.getElementById('tab-favorites');
    container.innerHTML = '<div class="flex justify-center py-12"><div class="spinner"></div></div>';
    
    try {
        const response = await fetch('api.php?action=favorites');
        const data = await response.json();
        
        if (data.success) {
            if (data.favorites.length === 0) {
                container.innerHTML = '<p class="text-center text-zinc-500 py-12">Henüz favori eklenmemiş</p>';
                return;
            }
            
            let html = '<div class="grid md:grid-cols-2 gap-4">';
            data.favorites.forEach(fav => {
                html += `
                    <div class="glass-strong rounded-xl p-5">
                        <div class="flex items-start justify-between mb-3">
                            <h4 class="font-semibold text-white">${escapeHtml(fav.name)}</h4>
                            <button onclick="deleteFavorite(${fav.id})" class="text-zinc-500 hover:text-rose-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                        ${fav.description ? '<p class="text-sm text-zinc-500 mb-3">' + escapeHtml(fav.description) + '</p>' : ''}
                        <code class="block text-xs text-zinc-400 bg-black/20 p-2 rounded-lg overflow-x-auto mb-3">${escapeHtml(fav.rule)}</code>
                        <button onclick="useFavorite(${JSON.stringify(fav).replace(/"/g, '&quot;')})" class="w-full py-2 text-sm text-indigo-400 border border-indigo-500/30 rounded-lg hover:bg-indigo-500/10 transition-all">Kullan</button>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }
    } catch (error) {
        container.innerHTML = '<p class="text-center text-rose-400 py-12">Favoriler yüklenemedi</p>';
    }
}

// Load Stats
async function loadStats() {
    const container = document.getElementById('tab-stats');
    container.innerHTML = '<div class="flex justify-center py-12"><div class="spinner"></div></div>';
    
    try {
        const response = await fetch('api.php?action=stats');
        const data = await response.json();
        
        if (data.success) {
            const s = data.stats;
            container.innerHTML = `
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="glass-strong rounded-xl p-6 text-center">
                        <div class="text-3xl font-bold text-indigo-400">${s.queries?.total_queries || 0}</div>
                        <div class="text-sm text-zinc-500 mt-1">Toplam Sorgu</div>
                    </div>
                    <div class="glass-strong rounded-xl p-6 text-center">
                        <div class="text-3xl font-bold text-emerald-400">${s.queries?.successful || 0}</div>
                        <div class="text-sm text-zinc-500 mt-1">Başarılı</div>
                    </div>
                    <div class="glass-strong rounded-xl p-6 text-center">
                        <div class="text-3xl font-bold text-rose-400">${s.queries?.failed || 0}</div>
                        <div class="text-sm text-zinc-500 mt-1">Başarısız</div>
                    </div>
                    <div class="glass-strong rounded-xl p-6 text-center">
                        <div class="text-3xl font-bold text-amber-400">${s.queries?.avg_execution_time ? s.queries.avg_execution_time.toFixed(2) : 0} ms</div>
                        <div class="text-sm text-zinc-500 mt-1">Ort. Süre</div>
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="glass-strong rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Sistem Bilgileri</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between"><span class="text-zinc-500">PHP Versiyonu</span><span class="text-zinc-300">${s.system?.php_version}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Motor Versiyonu</span><span class="text-zinc-300">${s.system?.engine_version}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Bellek Limiti</span><span class="text-zinc-300">${s.system?.memory_limit}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Mevcut Bellek</span><span class="text-emerald-400">${s.system?.memory_usage?.current}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Zaman Dilimi</span><span class="text-zinc-300">${s.system?.timezone}</span></div>
                        </div>
                    </div>
                    <div class="glass-strong rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Rate Limit</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between"><span class="text-zinc-500">Aktif İstemci</span><span class="text-zinc-300">${s.rate_limit?.active_clients || 0}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Toplam İstek</span><span class="text-zinc-300">${s.rate_limit?.total_requests || 0}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Limit</span><span class="text-zinc-300">${s.rate_limit?.default_limit || 60}/dk</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Pencere</span><span class="text-zinc-300">${s.rate_limit?.default_window || 60}s</span></div>
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        container.innerHTML = '<p class="text-center text-rose-400 py-12">İstatistikler yüklenemedi</p>';
    }
}

// Load Documentation
function loadDocs() {
    const container = document.getElementById('tab-docs');
    container.innerHTML = `
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="glass-strong rounded-2xl p-6">
                <h3 class="text-xl font-bold text-white mb-4">Operatörler</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div><span class="text-indigo-400 font-mono">==</span> <span class="text-zinc-400">Eşitlik kontrolü</span></div>
                    <div><span class="text-indigo-400 font-mono">!=</span> <span class="text-zinc-400">Eşitsizlik kontrolü</span></div>
                    <div><span class="text-indigo-400 font-mono">></span> <span class="text-zinc-400">Büyüktür</span></div>
                    <div><span class="text-indigo-400 font-mono"><</span> <span class="text-zinc-400">Küçüktür</span></div>
                    <div><span class="text-indigo-400 font-mono">>=</span> <span class="text-zinc-400">Büyük eşit</span></div>
                    <div><span class="text-indigo-400 font-mono"><=</span> <span class="text-zinc-400">Küçük eşit</span></div>
                </div>
            </div>
            <div class="glass-strong rounded-2xl p-6">
                <h3 class="text-xl font-bold text-white mb-4">Mantıksal Operatörler</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div><span class="text-purple-400 font-mono">AND / VE</span> <span class="text-zinc-400">Her iki koşul da doğru olmalı</span></div>
                    <div><span class="text-purple-400 font-mono">OR / VEYA</span> <span class="text-zinc-400">En az bir koşul doğru olmalı</span></div>
                    <div><span class="text-purple-400 font-mono">NOT / DEĞİL</span> <span class="text-zinc-400">Koşulu tersine çevirir</span></div>
                </div>
            </div>
            <div class="glass-strong rounded-2xl p-6">
                <h3 class="text-xl font-bold text-white mb-4">Özel Fonksiyonlar</h3>
                <div class="space-y-4">
                    <div>
                        <span class="text-cyan-400 font-mono">CONTAINS / İÇERİR</span>
                        <p class="text-zinc-400 text-sm mt-1">String veya dizi içinde arama yapar</p>
                        <code class="block mt-2 text-xs bg-black/20 p-2 rounded">user.email CONTAINS "@company.com"</code>
                    </div>
                    <div>
                        <span class="text-cyan-400 font-mono">IN / İÇİNDE</span>
                        <p class="text-zinc-400 text-sm mt-1">Değerin dizi içinde olup olmadığını kontrol eder</p>
                        <code class="block mt-2 text-xs bg-black/20 p-2 rounded">user.role IN ["admin", "manager"]</code>
                    </div>
                </div>
            </div>
            <div class="glass-strong rounded-2xl p-6">
                <h3 class="text-xl font-bold text-white mb-4">Örnek Kurallar</h3>
                <div class="space-y-3">
                    <code class="block text-sm bg-black/20 p-3 rounded-lg text-emerald-400">user.age >= 18 AND user.active == true</code>
                    <code class="block text-sm bg-black/20 p-3 rounded-lg text-emerald-400">(user.role == "admin" OR user.role == "manager") AND user.verified == true</code>
                    <code class="block text-sm bg-black/20 p-3 rounded-lg text-emerald-400">user.salary > 50000 AND user.department IN ["IT", "HR", "Finance"]</code>
                </div>
            </div>
        </div>
    `;
}

// Use Template
function useTemplate(template) {
    ruleEditor.setValue(template.rule);
    contextEditor.setValue(JSON.stringify(template.defaultContext, null, 2));
    switchTab('editor');
    showToast('Şablon Yüklendi', template.name, 'success');
}

// Use Favorite
function useFavorite(fav) {
    ruleEditor.setValue(fav.rule);
    if (fav.context) {
        contextEditor.setValue(JSON.stringify(fav.context, null, 2));
    }
    switchTab('editor');
    showToast('Favori Yüklendi', fav.name, 'success');
}

// Add to Favorites
function addToFavorites() {
    document.getElementById('favoriteModal').classList.remove('hidden');
    document.getElementById('favoriteModal').classList.add('flex');
    document.getElementById('favoriteName').focus();
}

function closeFavoriteModal() {
    document.getElementById('favoriteModal').classList.add('hidden');
    document.getElementById('favoriteModal').classList.remove('flex');
}

document.getElementById('favoriteForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const name = document.getElementById('favoriteName').value.trim();
    const description = document.getElementById('favoriteDescription').value.trim();
    const rule = ruleEditor.getValue().trim();
    
    let context = {};
    try {
        context = JSON.parse(contextEditor.getValue());
    } catch (e) {}
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add_favorite', name, rule, context, description })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Başarılı', 'Favori eklendi', 'success');
            closeFavoriteModal();
            document.getElementById('favoriteName').value = '';
            document.getElementById('favoriteDescription').value = '';
        } else {
            showToast('Hata', data.error?.message || 'Favori eklenemedi', 'error');
        }
    } catch (error) {
        showToast('Hata', 'Bağlantı hatası', 'error');
    }
});

// Delete Favorite
async function deleteFavorite(id) {
    if (!confirm('Bu favoriyi silmek istediğinizden emin misiniz?')) return;
    
    try {
        const response = await fetch('api.php?id=' + id, { method: 'DELETE' });
        const data = await response.json();
        
        if (data.success) {
            showToast('Başarılı', 'Favori silindi', 'success');
            loadFavorites();
        }
    } catch (error) {
        showToast('Hata', 'Favori silinemedi', 'error');
    }
}

// Utility Functions
function formatRule() {
    let rule = ruleEditor.getValue();
    rule = rule.replace(/\s+/g, ' ').trim();
    rule = rule.replace(/\(\s+/g, '(').replace(/\s+\)/g, ')');
    ruleEditor.setValue(rule);
}

function clearRule() {
    ruleEditor.setValue('');
    ruleEditor.focus();
}

function formatContext() {
    try {
        const obj = JSON.parse(contextEditor.getValue());
        contextEditor.setValue(JSON.stringify(obj, null, 2));
    } catch (e) {
        showToast('Hata', 'Geçersiz JSON', 'error');
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('-translate-x-full');
}

function toggleASTView() {
    const content = document.getElementById('astContent');
    content.classList.toggle('max-h-80');
}

function refreshData() {
    loadTabContent(currentTab);
    showToast('Yenilendi', 'Veriler güncellendi', 'info');
}

function loadInitialData() {
    fetch('api.php?action=stats').then(r => r.json()).then(data => {
        if (data.stats?.system?.memory_usage) {
            document.getElementById('memoryUsage').innerHTML = 
                '<span class="text-zinc-500">RAM:</span> <span class="text-emerald-400">' + data.stats.system.memory_usage.current + '</span>';
        }
    }).catch(() => {});
}

function loadHistoryItem(id) {
    // Could load and populate editors with history item
    showToast('Bilgi', 'Geçmiş öğesi #' + id, 'info');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(title, message, type = 'success') {
    const toast = document.getElementById('toast');
    const icon = document.getElementById('toastIcon');
    const titleEl = document.getElementById('toastTitle');
    const messageEl = document.getElementById('toastMessage');
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    const icons = { success: '✓', error: '✕', info: 'ℹ' };
    const colors = { success: 'text-emerald-400', error: 'text-rose-400', info: 'text-indigo-400' };
    
    icon.textContent = icons[type] || '✓';
    icon.className = 'text-xl ' + (colors[type] || 'text-emerald-400');
    
    toast.classList.remove('translate-y-20', 'opacity-0');
    
    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
    }, 3000);
}

// Keyboard Shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + Enter to evaluate
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        evaluateRule();
    }
});
