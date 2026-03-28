<?php
// ✅ Headers de seguridad HTTP
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

// ✅ Endurecer cookie de sesión (debe ir antes de session_start)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Lógica de Timeout (Fase 2C.1) ---
$timeout_segundos = 1800; // 30 minutos

if (isset($_SESSION['user'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_segundos)) {
        // Sesión expirada
        session_unset();
        session_destroy();
        header("Location: /PULPERIA-CHEBS/vistas/login.php?err=sesion_expirada");
        exit;
    }
    $_SESSION['last_activity'] = time(); // Actualiza la marca de tiempo
}

function require_login() {
    if (empty($_SESSION['user'])) {
        header("Location: /PULPERIA-CHEBS/vistas/login.php");
        exit;
    }
}

function require_role(array $roles = []) {
    require_login();
    $rol = $_SESSION['user']['rol'] ?? null;

    if (!$rol || (!empty($roles) && !in_array($rol, $roles, true))) {
        http_response_code(403);
        echo "403 - No autorizado";
        exit;
    }
}

function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}
