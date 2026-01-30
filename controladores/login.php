<?php
require_once __DIR__ . "/../config/conexion.php";

if (session_status() === PHP_SESSION_NONE) session_start();

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
    header("Location: /PULPERIA-CHEBS/vistas/login.php?err=1");
    exit;
}

session_regenerate_id(true);

$_SESSION['user'] = [
    'id'      => (int)$u['id'],
    'nombre'  => $u['nombre'],
    'usuario' => $u['usuario'],
    'rol'     => $u['rol'],
];

if ($u['rol'] === 'admin') {
    header("Location: /PULPERIA-CHEBS/index.php");
    exit;
}

header("Location: /PULPERIA-CHEBS/vistas/ventas/venta.php");
exit;
