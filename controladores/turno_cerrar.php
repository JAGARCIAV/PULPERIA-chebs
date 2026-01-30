

<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/turno_modelo.php";

if (session_status() === PHP_SESSION_NONE) session_start();

$turnoId = (int)($_SESSION['turno_id'] ?? ($_POST['turno_id'] ?? 0));
if ($turnoId <= 0) {
    header("Location: /PULPERIA-CHEBS/vistas/ventas/venta.php?err=no_turno");
    exit;
}

// lo que contó al cerrar (modal)
$efectivoCierre = null;
if (isset($_POST['efectivo_cierre'])) {
    $efectivoCierre = (float)$_POST['efectivo_cierre'];
    if ($efectivoCierre < 0) $efectivoCierre = 0;
}

$res = cerrarTurno($conexion, $turnoId, $efectivoCierre);

if (!$res["ok"]) {
    header("Location: /PULPERIA-CHEBS/vistas/ventas/venta.php?turno_err=" . urlencode($res["msg"]));
    exit;
}

$_SESSION['cierre_resumen'] = $res['resumen'];

// limpiar turno actual
unset($_SESSION['turno_id']);

header("Location: /PULPERIA-CHEBS/vistas/ventas/venta.php?turno_cerrado=1");
exit;