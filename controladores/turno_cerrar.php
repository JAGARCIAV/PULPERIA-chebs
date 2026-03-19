<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/turno_modelo.php";

if (session_status() === PHP_SESSION_NONE) session_start();

// ✅ Validación CSRF Amigable (Ruta Relativa)
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    header("Location: ../vistas/ventas/venta.php?turno_err=" . urlencode("Sesión de seguridad inválida. Intente de nuevo."));
    exit;
}

// ✅ FIX: leer turno de BD, no del POST (evita que alguien cierre turno ajeno)
$turnoObj = obtenerTurnoAbiertoHoy($conexion);
$turnoId  = $turnoObj ? (int)$turnoObj['id'] : 0;

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

// limpiar turno de sesión
unset($_SESSION['turno_id']);

header("Location: /PULPERIA-CHEBS/vistas/ventas/venta.php?turno_cerrado=1");
exit;