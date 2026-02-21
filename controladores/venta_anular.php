<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";

require_once __DIR__ . "/../modelos/venta_modelo.php";          // obtenerVentaPorId()
require_once __DIR__ . "/../modelos/venta_corregir_modelo.php"; // marcarVentaAnulada()
require_once __DIR__ . "/../modelos/lote_modelo.php";           // devolverStockCompletoVenta(), autoDesactivarLotesSinStock()

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ✅ Solo POST
if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
  header("Location: /PULPERIA-CHEBS/vistas/ventas/historial.php?err=" . urlencode("Método no permitido"));
  exit;
}

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

$conexion->begin_transaction();

try {
  // ✅ Limpieza previa
  autoDesactivarLotesSinStock($conexion);

  // ✅ Devolver TODO el stock a los mismos lotes usados
  $ok = devolverStockCompletoVenta($conexion, $venta_id);
  if (!$ok) {
    throw new Exception("No se pudo devolver stock en anulación (historial de lotes insuficiente).");
  }

  // ✅ Marcar venta anulada y total=0
  $ok2 = marcarVentaAnulada($conexion, $venta_id);
  if (!$ok2) {
    throw new Exception("No se pudo marcar la venta como anulada.");
  }

  // ✅ Limpieza final
  autoDesactivarLotesSinStock($conexion);

  $conexion->commit();
  header("Location: /PULPERIA-CHEBS/vistas/ventas/corregir_venta.php?id=$venta_id&ok_anular=1");
  exit;

} catch (Throwable $e) {
  $conexion->rollback();
  header("Location: /PULPERIA-CHEBS/vistas/ventas/corregir_venta.php?id=$venta_id&err=" . urlencode($e->getMessage()));
  exit;
}