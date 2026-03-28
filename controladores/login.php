<?php
// ✅ Endurecer cookie de sesión (antes de session_start en conexion.php y de session_regenerate_id)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');

require_once __DIR__ . "/../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) session_start();

// ✅ Rate limiting: bloqueo por sesión tras 5 intentos fallidos
const LOGIN_MAX_INTENTOS = 5;
const LOGIN_BLOQUEO_SEG  = 300; // 5 minutos

if (!empty($_SESSION['login_bloqueado_hasta']) && time() < (int)$_SESSION['login_bloqueado_hasta']) {
    $restantes = (int)$_SESSION['login_bloqueado_hasta'] - time();
    header("Location: /PULPERIA-CHEBS/vistas/login.php?err=bloqueado&seg=" . $restantes);
    exit;
}

$usuario  = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

if ($usuario === '' || $password === '') {
    header("Location: /PULPERIA-CHEBS/vistas/login.php?err=1");
    exit;
}

$stmt = $conexion->prepare("
    SELECT id, nombre, usuario, password_hash, rol, activo
    FROM usuarios
    WHERE usuario = ?
    LIMIT 1
");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$res = $stmt->get_result();
$u = $res->fetch_assoc();

if (!$u || (int)$u['activo'] !== 1 || !password_verify($password, $u['password_hash'])) {
    $_SESSION['login_intentos'] = (int)($_SESSION['login_intentos'] ?? 0) + 1;

    if ((int)$_SESSION['login_intentos'] >= LOGIN_MAX_INTENTOS) {
        $_SESSION['login_bloqueado_hasta'] = time() + LOGIN_BLOQUEO_SEG;
        unset($_SESSION['login_intentos']);
        header("Location: /PULPERIA-CHEBS/vistas/login.php?err=bloqueado&seg=" . LOGIN_BLOQUEO_SEG);
        exit;
    }

    header("Location: /PULPERIA-CHEBS/vistas/login.php?err=1");
    exit;
}

unset($_SESSION['login_intentos'], $_SESSION['login_bloqueado_hasta']);
session_regenerate_id(true);

$_SESSION['user'] = [
    'id'      => $u['id'],
    'nombre'  => $u['nombre'],
    'usuario' => $u['usuario'],
    'rol'     => $u['rol'],
];

$_SESSION['last_activity'] = time(); // Inicializar marca de tiempo (Fase 2C.1)

if ($u['rol'] === 'admin') {
    header("Location: /PULPERIA-CHEBS/index.php");
    exit;
}

header("Location: /PULPERIA-CHEBS/vistas/ventas/venta.php");
exit;
