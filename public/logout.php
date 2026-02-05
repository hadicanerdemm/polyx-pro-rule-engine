<?php
declare(strict_types=1);

session_start();

// Session temizle
$_SESSION = [];

// Session cookie sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Remember cookie sil
setcookie('polyx_remember', '', time() - 42000, '/');

// Session yok et
session_destroy();

// Login sayfasına yönlendir
header('Location: login.php');
exit;
