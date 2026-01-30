<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin']); // SOLO admin

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/retiro_modelo.php";

if (session_status() === PHP_SESSION_NONE) session_start();

$turnoId = (int)($_POST['turno_id'] ?? 0);
$monto   = (float)($_POST['monto'] ?? 0);
$motivo  = $_POST['motivo'] ?? null;

$adminId = (int)($_SESSION['user']['id'] ?? 0);
if ($adminId <= 0) {
    header("Location: /PULPERIA-CHEBS/vistas/login.php");
    exit;
}

$res = registrarRetiroCaja($conexion, $turnoId, $adminId, $monto, $motivo);

if (!$res["ok"]) {
    header("Location: /PULPERIA-CHEBS/vistas/ventas/venta.php?ret_err=" . urlencode($res["msg"]));
    exit;
}

header("Location: /PULPERIA-CHEBS/vistas/ventas/venta.php?ret_ok=1");
exit;
