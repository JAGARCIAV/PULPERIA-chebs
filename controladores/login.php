<?php
// ✅ Endurecer cookie de sesión (antes de session_start en conexion.php y de session_regenerate_id)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');

require_once __DIR__ . "/../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . "/../config/auth.php"; // necesario para validate_csrf_token()

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    header("Location: /PULPERIA-CHEBS/vistas/login.php?err=1");
    exit;
}

define('LOGIN_MAX_INTENTOS', 5);
define('LOGIN_BLOQUEO_SEG',  300); // 5 minutos

$usuario  = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

if ($usuario === '' || $password === '') {
    header("Location: /PULPERIA-CHEBS/vistas/login.php?err=1");
    exit;
}

// ── Limpiar bloqueos expirados ────────────────────────────────────────────────
$conexion->query("DELETE FROM login_intentos WHERE bloqueado_hasta IS NOT NULL AND bloqueado_hasta < NOW()");

// ── Clave de rastreo: usuario + IP (estable sin importar cookies) ─────────────
$ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$clave = substr($usuario, 0, 50) . ':' . substr($ip, 0, 45);

// ── Verificar si la clave está bloqueada ──────────────────────────────────────
$stmt_chk = $conexion->prepare(
    "SELECT bloqueado_hasta FROM login_intentos WHERE clave = ? LIMIT 1"
);
$stmt_chk->bind_param("s", $clave);
$stmt_chk->execute();
$fila_bloqueo = $stmt_chk->get_result()->fetch_assoc();
$stmt_chk->close();

if ($fila_bloqueo && $fila_bloqueo['bloqueado_hasta'] !== null) {
    $seg = max(0, (int)(strtotime($fila_bloqueo['bloqueado_hasta']) - time()));
    header("Location: /PULPERIA-CHEBS/vistas/login.php?err=bloqueado&seg=" . $seg);
    exit;
}

// ── Verificar credenciales ────────────────────────────────────────────────────
$stmt = $conexion->prepare("
    SELECT id, nombre, usuario, password_hash, rol, activo
    FROM usuarios
    WHERE usuario = ?
    LIMIT 1
");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$u || (int)$u['activo'] !== 1 || !password_verify($password, $u['password_hash'])) {

    // Registrar intento fallido (INSERT o incrementar)
    $max     = LOGIN_MAX_INTENTOS;
    $seg_blq = LOGIN_BLOQUEO_SEG;

    $ups = $conexion->prepare("
        INSERT INTO login_intentos (clave, intentos, bloqueado_hasta)
        VALUES (?, 1, NULL)
        ON DUPLICATE KEY UPDATE
            intentos        = intentos + 1,
            bloqueado_hasta = IF(intentos >= ?, DATE_ADD(NOW(), INTERVAL ? SECOND), bloqueado_hasta)
    ");
    $ups->bind_param("sii", $clave, $max, $seg_blq);
    $ups->execute();
    $ups->close();

    // Verificar si este intento activó el bloqueo
    $stmt_b = $conexion->prepare(
        "SELECT bloqueado_hasta FROM login_intentos WHERE clave = ? LIMIT 1"
    );
    $stmt_b->bind_param("s", $clave);
    $stmt_b->execute();
    $fila_b = $stmt_b->get_result()->fetch_assoc();
    $stmt_b->close();

    if ($fila_b && $fila_b['bloqueado_hasta'] !== null) {
        header("Location: /PULPERIA-CHEBS/vistas/login.php?err=bloqueado&seg=" . LOGIN_BLOQUEO_SEG);
        exit;
    }

    header("Location: /PULPERIA-CHEBS/vistas/login.php?err=1");
    exit;
}

// ── Login exitoso: limpiar intentos acumulados ───────────────────────────────
$del = $conexion->prepare("DELETE FROM login_intentos WHERE clave = ?");
$del->bind_param("s", $clave);
$del->execute();
$del->close();

session_regenerate_id(true);

$_SESSION['user'] = [
    'id'      => $u['id'],
    'nombre'  => $u['nombre'],
    'usuario' => $u['usuario'],
    'rol'     => $u['rol'],
];

$_SESSION['last_activity'] = time();

if ($u['rol'] === 'admin') {
    header("Location: /PULPERIA-CHEBS/index.php");
    exit;
}

header("Location: /PULPERIA-CHEBS/vistas/ventas/venta.php");
exit;
