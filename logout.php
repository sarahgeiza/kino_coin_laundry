<?php
declare(strict_types=1);
require_once __DIR__ . '/backend/middleware/session.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php');
    exit;
}
$token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf']) || $token === '' || !hash_equals($_SESSION['csrf'], $token)) {
    http_response_code(403);
    exit('Permintaan logout tidak valid.');
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: index.php');
exit;
