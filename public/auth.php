<?php
declare(strict_types=1);

/**
 * POLYX PRO++ Kimlik Doğrulama
 * 
 * @author POLYX Development Team
 * @version 1.0.0
 */

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Demo kullanıcılar (gerçek projede veritabanından gelir)
$users = [
    'admin' => [
        'password' => 'admin123',
        'name' => 'Admin Kullanıcı',
        'role' => 'admin',
        'email' => 'admin@polyx.io',
        'avatar' => 'A'
    ],
    'demo' => [
        'password' => 'demo123',
        'name' => 'Demo Kullanıcı',
        'role' => 'user',
        'email' => 'demo@polyx.io',
        'avatar' => 'D'
    ]
];

function sendResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function sendError(string $message, string $code = 'ERROR', int $statusCode = 400): void
{
    sendResponse([
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message
        ]
    ], $statusCode);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Sadece POST metodu kabul edilir', 'METHOD_NOT_ALLOWED', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendError('Geçersiz JSON formatı', 'INVALID_JSON', 400);
}

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$remember = $input['remember'] ?? false;

if (empty($username) || empty($password)) {
    sendError('Kullanıcı adı ve şifre gereklidir', 'MISSING_CREDENTIALS', 400);
}

// Kullanıcı kontrolü
if (!isset($users[$username])) {
    // Brute force koruması için gecikme
    usleep(random_int(100000, 500000));
    sendError('Kullanıcı adı veya şifre hatalı', 'INVALID_CREDENTIALS', 401);
}

$user = $users[$username];

if ($password !== $user['password']) {
    usleep(random_int(100000, 500000));
    sendError('Kullanıcı adı veya şifre hatalı', 'INVALID_CREDENTIALS', 401);
}

// Session oluştur
$_SESSION['polyx_user'] = [
    'username' => $username,
    'name' => $user['name'],
    'role' => $user['role'],
    'email' => $user['email'],
    'avatar' => $user['avatar'],
    'login_time' => time(),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
];

// Remember me cookie
if ($remember) {
    $token = bin2hex(random_bytes(32));
    setcookie('polyx_remember', $token, [
        'expires' => time() + (30 * 24 * 60 * 60), // 30 gün
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

sendResponse([
    'success' => true,
    'message' => 'Giriş başarılı',
    'user' => [
        'username' => $username,
        'name' => $user['name'],
        'role' => $user['role'],
        'email' => $user['email'],
        'avatar' => $user['avatar']
    ],
    'redirect' => 'index.php'
]);
