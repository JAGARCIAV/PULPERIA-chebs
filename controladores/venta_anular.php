<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/venta_modelo.php";
require_once __DIR__ . "/../modelos/venta_corregir_modelo.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    header("Location: /PULPERIA-CHEBS/vistas/ventas/historial.php");
    exit;
}

// ✅ CSRF
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    header("Location: /PULPERIA-CHEBS/vistas/ventas/historial.php?err=" . urlencode("Error de seguridad. Recarga la página."));
    exit;
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
$rol    = (string)($_SESSION['user']['rol'] ?? '');

$venta_id = isset($_POST["venta_id"]) ? (int)$_POST["venta_id"] : 0;
if ($venta_id <= 0) {
    header("Location: /PULPERIA-CHEBS/vistas/ventas/historial.php?err=" . urlencode("ID inválido"));
    exit;
}

$venta = obtenerVentaPorId($conexion, $venta_id);
if (!$venta) {
    header("Location: /PULPERIA-CHEBS/vistas/ventas/historial.php?err=" . urlencode("Venta no existe"));
    exit;
}

if ((int)($venta["anulada"] ?? 0) === 1) {
    header("Location: /PULPERIA-CHEBS/vistas/ventas/corregir_venta.php?id=$venta_id&err=" . urlencode("La venta ya está anulada."));
    exit;
}

// ✅ Control de permisos (turno, propiedad, ventana de tiempo)
$permiso = validarPermisoEdicionVenta($conexion, $venta, $userId, $rol);
if (!$permiso['ok']) {
    header("Location: /PULPERIA-CHEBS/vistas/ventas/corregir_venta.php?id=$venta_id&err=" . urlencode($permiso['msg']));
    exit;
}

$conexion->begin_transaction();

try {
    // Primero: reclamar la anulación de forma atómica (AND anulada=0 en el UPDATE).
    // Si otro proceso llegó primero, affected_rows=0 y el rollback ocurre
    // antes de tocar el stock — imposible duplicar la devolución.
    $claimed = marcarVentaAnulada($conexion, $venta_id);
    if (!$claimed) {
        throw new Exception("La venta ya fue anulada por otro proceso.");
    }

    autoDesactivarLotesSinStock($conexion);

    $ok = devolverStockCompletoVenta($conexion, $venta_id);
    if (!$ok) {
        throw new Exception("No se pudo devolver stock (historial de lotes insuficiente).");
    }

    autoDesactivarLotesSinStock($conexion);

    $conexion->commit();

    // ✅ Auditoría fuera de la transacción (no bloquea si falla)
    registrarAuditoriaAnulacion($conexion, $venta_id, $userId);

    header("Location: /PULPERIA-CHEBS/vistas/ventas/corregir_venta.php?id=$venta_id&ok_anular=1");
    exit;

} catch (Throwable $e) {
    $conexion->rollback();
    error_log("[venta_anular] venta_id=$venta_id — " . $e->getMessage());
    header("Location: /PULPERIA-CHEBS/vistas/ventas/corregir_venta.php?id=$venta_id&err=" . urlencode($e->getMessage()));
    exit;
}
