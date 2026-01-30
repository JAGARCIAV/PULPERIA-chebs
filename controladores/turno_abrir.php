<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/turno_modelo.php";

if (session_status() === PHP_SESSION_NONE) session_start();

$efectivoInicial = (float)($_POST['monto_inicial'] ?? 0);
if ($efectivoInicial < 0) $efectivoInicial = 0;

// Responsable desde sesión
$userId = (int)($_SESSION['user']['id'] ?? 0);
$nombre = $_SESSION['user']['nombre'] ?? 'SIN_USUARIO';

if ($userId <= 0) {
    header("Location: /PULPERIA-CHEBS/vistas/login.php");
    exit;
}

// desde qué venta inicia historial (déjalo en 0 por ahora)
$desde = 0;

$res = abrirTurno($conexion, $nombre, $userId, $efectivoInicial, $desde);

if (!$res["ok"]) {
    header("Location: /PULPERIA-CHEBS/vistas/ventas/venta.php?turno_err=" . urlencode($res["msg"]));
    exit;
}

$_SESSION['turno_id'] = (int)$res["turno_id"];

header("Location: /PULPERIA-CHEBS/vistas/ventas/venta.php?turno_ok=1");
exit;
