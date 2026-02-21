<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/venta_modelo.php";
require_once __DIR__ . "/../modelos/venta_corregir_modelo.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";
require_once __DIR__ . "/../modelos/producto_modelo.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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
  header("Location: /PULPERIA-CHEBS/vistas/ventas/corregir_venta.php?id=$venta_id&err=" . urlencode("La venta está anulada."));
  exit;
}

$detalle_ids = $_POST["detalle_id"] ?? [];
$nuevos_ids  = $_POST["nuevo_producto_id"] ?? [];
$cantidades  = $_POST["cantidad"] ?? [];
$eliminar    = $_POST["eliminar"] ?? [];

if (!is_array($detalle_ids) || count($detalle_ids) === 0) {
  header("Location: /PULPERIA-CHEBS/vistas/ventas/corregir_venta.php?id=$venta_id&err=" . urlencode("No hay líneas para corregir."));
  exit;
}

$conexion->begin_transaction();

try {
  autoDesactivarLotesSinStock($conexion);

  for ($i=0; $i<count($detalle_ids); $i++) {

    $det_id = (int)$detalle_ids[$i];
    if ($det_id <= 0) continue;

    $orig = obtenerDetalleEspecifico($conexion, $det_id);
    if (!$orig) continue;

    if ((int)$orig["venta_id"] !== $venta_id) {
      throw new Exception("Detalle no pertenece a la venta.");
    }

    $origProd   = (int)$orig["producto_id"];
    $origTipo   = (string)$orig["tipo_venta"];
    $origPresId = isset($orig["presentacion_id"]) ? ($orig["presentacion_id"] !== null ? (int)$orig["presentacion_id"] : null) : null;
    $origCant   = (int)$orig["cantidad"];
    $origUnRe   = isset($orig["unidades_reales"]) ? (int)$orig["unidades_reales"] : $origCant;

    $newProd = (isset($nuevos_ids[$i]) && $nuevos_ids[$i] !== "") ? (int)$nuevos_ids[$i] : 0;
    $newCant = isset($cantidades[$i]) ? (int)$cantidades[$i] : $origCant;
    $toDel   = isset($eliminar[$i]) && (int)$eliminar[$i] === 1;

    if ($newCant < 0) $newCant = 0;

    // ✅ eliminar o cantidad 0 => devolver TODO y borrar línea
    if ($toDel || $newCant === 0) {

      if ($origUnRe > 0) {
        $okDev = devolverStockProductoDesdeVenta($conexion, $venta_id, $origProd, $origUnRe);
        if (!$okDev) throw new Exception("No se pudo devolver stock al eliminar.");
      }

      eliminarLineaDetalle($conexion, $det_id);
      continue;
    }

    // ✅ Caso: cambiar producto
    // Regla: cuando cambias producto, lo tratamos como "unidad" (sin presentaciones).
    if ($newProd > 0 && $newProd !== $origProd) {

      // 1) devolver TODO del producto original
      if ($origUnRe > 0) {
        $okDev = devolverStockProductoDesdeVenta($conexion, $venta_id, $origProd, $origUnRe);
        if (!$okDev) throw new Exception("No se pudo devolver stock del producto original.");
      }

      // 2) descontar stock del nuevo producto como unidad
      $unReNew = $newCant;

      $stock = obtenerStockDisponible($conexion, $newProd);
      if ((int)$stock < (int)$unReNew) {
        throw new Exception("Stock insuficiente para el nuevo producto (disp: $stock, req: $unReNew).");
      }

      $okDesc = descontarStockFIFO($conexion, $newProd, $unReNew, $venta_id);
      if (!$okDesc) throw new Exception("No se pudo descontar stock del nuevo producto.");

      // precio unidad del nuevo producto
      $precio = (float)obtenerPrecioProducto($conexion, $newProd);
      if ($precio <= 0) throw new Exception("El nuevo producto no tiene precio por unidad.");

      $subtotal = round($precio * $newCant, 2);

      actualizarLineaDetalleCorregida(
        $conexion,
        $det_id,
        $newProd,
        $newCant,
        $precio,
        $subtotal,
        null,      // presentacion_id NULL
        "unidad",  // tipo_venta unidad
        $unReNew
      );

      continue;
    }

    // ✅ Caso: mismo producto => recalcular unidades_reales según tipo/presentación
    $unPorPack = 1;
    if ($origTipo === "paquete" && $origPresId !== null) {
      $u = obtenerUnidadesPresentacion($conexion, $origPresId);
      if ($u <= 0) throw new Exception("Presentación inválida en detalle.");
      $unPorPack = (int)$u;
    }

    $newUnRe = ($origTipo === "paquete") ? ($newCant * $unPorPack) : $newCant;

    if ($newUnRe < $origUnRe) {
      $diff = $origUnRe - $newUnRe;

      $okDev = devolverStockProductoDesdeVenta($conexion, $venta_id, $origProd, $diff);
      if (!$okDev) throw new Exception("No se pudo devolver stock al bajar cantidad.");

    } else if ($newUnRe > $origUnRe) {
      $diff = $newUnRe - $origUnRe;

      $stock = obtenerStockDisponible($conexion, $origProd);
      if ((int)$stock < (int)$diff) {
        throw new Exception("Stock insuficiente para aumentar cantidad (disp: $stock, req: $diff).");
      }

      $okDesc = descontarStockFIFO($conexion, $origProd, $diff, $venta_id);
      if (!$okDesc) throw new Exception("No se pudo descontar stock al aumentar cantidad.");
    }

    $precio = (float)$orig["precio_unitario"];
    $subtotal = round($precio * $newCant, 2);

    actualizarLineaDetalleCorregida(
      $conexion,
      $det_id,
      $origProd,
      $newCant,
      $precio,
      $subtotal,
      $origPresId,
      $origTipo,
      $newUnRe
    );
  }

  actualizarTotalVenta($conexion, $venta_id);
  autoDesactivarLotesSinStock($conexion);

  $conexion->commit();

  header("Location: /PULPERIA-CHEBS/vistas/ventas/corregir_venta.php?id=$venta_id&ok=1");
  exit;

} catch (Throwable $e) {
  $conexion->rollback();
  header("Location: /PULPERIA-CHEBS/vistas/ventas/corregir_venta.php?id=$venta_id&err=" . urlencode($e->getMessage()));
  exit;
}