<?php
declare(strict_types=1);

/**
 * POLYX PRO++ Karar Motoru API
 * 
 * Kurumsal seviye kural değerlendirme API endpoint'i
 * RateLimiter, QueryHistory ve ErrorHandler entegrasyonu
 * 
 * @author POLYX Development Team
 * @version 2.0.0
 */

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use Polyx\Engine\RuleEngine;
use Polyx\Engine\Service\RateLimiter;
use Polyx\Engine\Service\ErrorHandler;
use Polyx\Engine\Service\QueryHistory;

// Error Handler başlat
$errorHandler = ErrorHandler::getInstance();
$errorHandler->register()->setDebug(true)->setJsonOutput(true);

// CORS Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-Powered-By: POLYX PRO++ Engine v2.0');

// OPTIONS request için erken dönüş
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Session başlat
session_start();

// Rate Limiter
$rateLimiter = new RateLimiter(60, 60); // 60 istek/dakika
$clientIP = RateLimiter::getClientIP();
$rateLimiter->applyHeaders($clientIP);

$rateCheck = $rateLimiter->check($clientIP);
if (!$rateCheck['allowed']) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'RATE_LIMIT_EXCEEDED',
            'message' => 'Çok fazla istek gönderdiniz. Lütfen bekleyin.',
            'retry_after' => $rateCheck['retry_after']
        ],
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Rate limit hit kaydet
$rateLimiter->hit($clientIP);

// Query History
$history = new QueryHistory();

/**
 * JSON yanıt gönder
 */
function sendResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Hata yanıtı gönder
 */
function sendError(string $message, string $code = 'ERROR', int $statusCode = 400, array $details = []): void
{
    sendResponse([
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message,
            'details' => $details
        ],
        'timestamp' => date('c')
    ], $statusCode);
}

// Request metodu kontrolü
$method = $_SERVER['REQUEST_METHOD'];

// GET - API bilgisi ve geçmiş
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'info';
    
    switch ($action) {
        case 'info':
            sendResponse([
                'success' => true,
                'engine' => 'POLYX PRO++ Karar Motoru',
                'version' => '2.0.0',
                'features' => [
                    'dot_notation' => 'Destekleniyor (örn: user.finance.salary)',
                    'operators' => ['==', '!=', '>', '<', '>=', '<='],
                    'logic' => ['AND', 'OR', 'NOT', 'VE', 'VEYA'],
                    'functions' => ['CONTAINS', 'IN', 'İÇERİR', 'İÇİNDE'],
                    'types' => ['string', 'number', 'boolean', 'null', 'array'],
                    'security' => ['rate_limiting', 'error_handling', 'no_eval'],
                    'extras' => ['query_history', 'favorites', 'statistics']
                ],
                'endpoints' => [
                    'POST /api.php' => 'Kural değerlendirme',
                    'GET /api.php?action=info' => 'API bilgisi',
                    'GET /api.php?action=examples' => 'Örnek kurallar',
                    'GET /api.php?action=templates' => 'Kural şablonları',
                    'GET /api.php?action=history' => 'Sorgu geçmişi',
                    'GET /api.php?action=favorites' => 'Favoriler',
                    'GET /api.php?action=stats' => 'Sistem istatistikleri'
                ],
                'rate_limit' => $rateCheck,
                'timestamp' => date('c')
            ]);
            break;
            
        case 'examples':
            sendResponse([
                'success' => true,
                'examples' => [
                    [
                        'id' => 1,
                        'name' => 'Basit Karşılaştırma',
                        'description' => 'Yaş kontrolü',
                        'rule' => 'user.age >= 18',
                        'context' => ['user' => ['age' => 25]],
                        'expected' => true
                    ],
                    [
                        'id' => 2,
                        'name' => 'Mantıksal VE',
                        'description' => 'Admin yetkisi kontrolü',
                        'rule' => 'user.role == "admin" AND user.active == true',
                        'context' => ['user' => ['role' => 'admin', 'active' => true]],
                        'expected' => true
                    ],
                    [
                        'id' => 3,
                        'name' => 'Karmaşık Maaş Kontrolü',
                        'description' => 'Maaş veya bonus ile departman kontrolü',
                        'rule' => '(user.salary > 50000 OR user.bonus > 10000) AND user.department != "intern"',
                        'context' => ['user' => ['salary' => 75000, 'bonus' => 5000, 'department' => 'engineering']],
                        'expected' => true
                    ],
                    [
                        'id' => 4,
                        'name' => 'İçerik Kontrolü',
                        'description' => 'Email domain kontrolü',
                        'rule' => 'user.email CONTAINS "@company.com"',
                        'context' => ['user' => ['email' => 'john@company.com']],
                        'expected' => true
                    ],
                    [
                        'id' => 5,
                        'name' => 'Dizi İçinde Arama',
                        'description' => 'Rol yetki kontrolü',
                        'rule' => 'user.role IN ["admin", "manager", "supervisor"]',
                        'context' => ['user' => ['role' => 'manager']],
                        'expected' => true
                    ],
                    [
                        'id' => 6,
                        'name' => 'Türkçe Operatörler',
                        'description' => 'VE/VEYA ile kredi skoru kontrolü',
                        'rule' => '(kredi.skor >= 700 VE kredi.gecikme == 0) VEYA (kredi.kefil == true)',
                        'context' => ['kredi' => ['skor' => 750, 'gecikme' => 0, 'kefil' => false]],
                        'expected' => true
                    ],
                    [
                        'id' => 7,
                        'name' => 'Çoklu Koşul',
                        'description' => 'Seviye ve doğrulama kontrolü',
                        'rule' => '(user.level >= 5 AND user.verified == true) OR (user.admin == true AND NOT user.suspended == true)',
                        'context' => ['user' => ['level' => 3, 'verified' => true, 'admin' => true, 'suspended' => false]],
                        'expected' => true
                    ],
                    [
                        'id' => 8,
                        'name' => 'Derin Nesne Erişimi',
                        'description' => 'Detaylı finansal kontrol',
                        'rule' => 'user.finance.balance > 1000 AND user.finance.credit.limit >= 5000',
                        'context' => ['user' => ['finance' => ['balance' => 2500, 'credit' => ['limit' => 10000]]]],
                        'expected' => true
                    ]
                ],
                'timestamp' => date('c')
            ]);
            break;

        case 'templates':
            sendResponse([
                'success' => true,
                'templates' => [
                    [
                        'id' => 'age_check',
                        'name' => '🎂 Yaş Kontrolü',
                        'category' => 'Temel',
                        'rule' => 'user.age >= {min_age}',
                        'defaultContext' => ['user' => ['age' => 0]],
                        'variables' => ['min_age' => 18],
                        'description' => 'Kullanıcının belirli yaşta olup olmadığını kontrol eder'
                    ],
                    [
                        'id' => 'role_check',
                        'name' => '👤 Rol Kontrolü',
                        'category' => 'Yetkilendirme',
                        'rule' => 'user.role IN ["admin", "manager", "editor"]',
                        'defaultContext' => ['user' => ['role' => '']],
                        'variables' => [],
                        'description' => 'Kullanıcının yetkili rollere sahip olup olmadığını kontrol eder'
                    ],
                    [
                        'id' => 'credit_score',
                        'name' => '💳 Kredi Skoru',
                        'category' => 'Finans',
                        'rule' => 'kredi.skor >= 650 AND kredi.borc < kredi.limit * 0.8',
                        'defaultContext' => ['kredi' => ['skor' => 0, 'borc' => 0, 'limit' => 10000]],
                        'variables' => [],
                        'description' => 'Kredi skoru ve borç oranı kontrolü'
                    ],
                    [
                        'id' => 'subscription_active',
                        'name' => '⭐ Abonelik Kontrolü',
                        'category' => 'Üyelik',
                        'rule' => 'subscription.active == true AND subscription.plan IN ["pro", "enterprise"]',
                        'defaultContext' => ['subscription' => ['active' => true, 'plan' => 'free']],
                        'variables' => [],
                        'description' => 'Aktif premium abonelik kontrolü'
                    ],
                    [
                        'id' => 'geo_restriction',
                        'name' => '🌍 Coğrafi Kısıtlama',
                        'category' => 'Güvenlik',
                        'rule' => 'request.country IN ["TR", "DE", "FR", "UK"] AND NOT request.vpn == true',
                        'defaultContext' => ['request' => ['country' => 'TR', 'vpn' => false]],
                        'variables' => [],
                        'description' => 'İzin verilen ülkeler ve VPN kontrolü'
                    ],
                    [
                        'id' => 'working_hours',
                        'name' => '🕐 Çalışma Saatleri',
                        'category' => 'Zaman',
                        'rule' => 'time.hour >= 9 AND time.hour < 18 AND time.weekday IN [1, 2, 3, 4, 5]',
                        'defaultContext' => ['time' => ['hour' => 12, 'weekday' => 3]],
                        'variables' => [],
                        'description' => 'Hafta içi mesai saatleri kontrolü'
                    ],
                    [
                        'id' => 'ecommerce_discount',
                        'name' => '🛒 E-Ticaret İndirim',
                        'category' => 'E-Ticaret',
                        'rule' => '(cart.total >= 500 OR customer.vip == true) AND cart.items > 0',
                        'defaultContext' => ['cart' => ['total' => 0, 'items' => 0], 'customer' => ['vip' => false]],
                        'variables' => [],
                        'description' => 'Sepet tutarı veya VIP müşteri indirimi'
                    ],
                    [
                        'id' => 'api_access',
                        'name' => '🔐 API Erişimi',
                        'category' => 'Güvenlik',
                        'rule' => 'api.key_valid == true AND api.rate_limit > 0 AND NOT api.blocked == true',
                        'defaultContext' => ['api' => ['key_valid' => true, 'rate_limit' => 100, 'blocked' => false]],
                        'variables' => [],
                        'description' => 'API anahtarı ve limit kontrolü'
                    ]
                ],
                'categories' => ['Temel', 'Yetkilendirme', 'Finans', 'Üyelik', 'Güvenlik', 'Zaman', 'E-Ticaret'],
                'timestamp' => date('c')
            ]);
            break;
            
        case 'history':
            $limit = min((int)($_GET['limit'] ?? 20), 100);
            $offset = max((int)($_GET['offset'] ?? 0), 0);
            
            sendResponse([
                'success' => true,
                'history' => $history->getRecent($limit, $offset),
                'stats' => $history->getStats(),
                'timestamp' => date('c')
            ]);
            break;
            
        case 'favorites':
            sendResponse([
                'success' => true,
                'favorites' => $history->getFavorites(),
                'timestamp' => date('c')
            ]);
            break;
            
        case 'stats':
            sendResponse([
                'success' => true,
                'stats' => [
                    'system' => [
                        'php_version' => PHP_VERSION,
                        'engine_version' => '2.0.0',
                        'memory_limit' => ini_get('memory_limit'),
                        'memory_usage' => [
                            'current' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                            'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB'
                        ],
                        'server_time' => date('c'),
                        'timezone' => date_default_timezone_get()
                    ],
                    'queries' => $history->getStats(),
                    'rate_limit' => $rateLimiter->getStats()
                ],
                'timestamp' => date('c')
            ]);
            break;
            
        default:
            sendError('Geçersiz action parametresi', 'INVALID_ACTION', 400);
    }
}

// DELETE - Favori silme
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        sendError('Geçerli bir ID belirtmelisiniz', 'INVALID_ID', 400);
    }
    
    $history->deleteFavorite($id);
    
    sendResponse([
        'success' => true,
        'message' => 'Favori silindi',
        'timestamp' => date('c')
    ]);
}

// POST - Kural değerlendirme veya favori ekleme
if ($method === 'POST') {
    // JSON body oku
    $rawInput = file_get_contents('php://input');
    
    if (empty($rawInput)) {
        sendError('İstek gövdesi boş', 'EMPTY_BODY', 400);
    }
    
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError(
            'Geçersiz JSON formatı: ' . json_last_error_msg(),
            'INVALID_JSON',
            400
        );
    }

    $action = $input['action'] ?? 'evaluate';

    // Favori ekleme
    if ($action === 'add_favorite') {
        if (empty($input['name']) || empty($input['rule'])) {
            sendError('İsim ve kural zorunludur', 'MISSING_FIELDS', 400);
        }

        $id = $history->addFavorite(
            $input['name'],
            $input['rule'],
            $input['context'] ?? [],
            $input['description'] ?? ''
        );

        sendResponse([
            'success' => true,
            'message' => 'Favori eklendi',
            'favorite_id' => $id,
            'timestamp' => date('c')
        ]);
    }
    
    // Kural değerlendirme
    if (!isset($input['rule']) || !is_string($input['rule']) || trim($input['rule']) === '') {
        sendError('Kural (rule) alanı gerekli ve boş olamaz', 'MISSING_RULE', 400);
    }
    
    $rule = trim($input['rule']);
    $context = $input['context'] ?? [];
    
    // Context kontrolü
    if (!is_array($context)) {
        sendError('Context bir nesne olmalıdır', 'INVALID_CONTEXT', 400);
    }
    
    // Motor oluştur ve çalıştır
    $engine = new RuleEngine();
    $result = $engine->execute($rule, $context);
    
    // Geçmişe kaydet
    $historyData = [
        'rule' => $rule,
        'context' => $context,
        'result' => $result['success'] ? $result['decision'] : null,
        'execution_time' => $result['meta']['time_raw'] ?? null,
        'memory_used' => $result['meta']['memory_raw'] ?? null,
        'token_count' => $result['meta']['tokens'] ?? null,
        'error_message' => $result['success'] ? null : ($result['error']['message'] ?? 'Bilinmeyen hata')
    ];
    $historyId = $history->save($historyData);
    
    // Sonucu döndür
    $result['timestamp'] = date('c');
    $result['history_id'] = $historyId;
    $result['request'] = [
        'rule' => $rule,
        'context_keys' => array_keys($context)
    ];
    
    sendResponse($result, $result['success'] ? 200 : 400);
}

// Desteklenmeyen metod
sendError(
    'Bu HTTP metodu desteklenmiyor: ' . $method,
    'METHOD_NOT_ALLOWED',
    405
);
