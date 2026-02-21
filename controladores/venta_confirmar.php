<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/producto_modelo.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";
require_once __DIR__ . "/../modelos/venta_modelo.php";

header("Content-Type: application/json; charset=utf-8");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data || !isset($data["carrito"]) || !is_array($data["carrito"]) || count($data["carrito"]) === 0) {
  echo json_encode(["ok" => false, "msg" => "Carrito vacío"]);
  exit;
}

$carrito = $data["carrito"];

// Limpieza global ANTES de vender
autoDesactivarLotesSinStock($conexion);

$conexion->begin_transaction();

try {
  $venta_id = crearVenta($conexion);
  if (!$venta_id) {
    throw new Exception("No hay turno abierto. Abra turno antes de vender.");
  }

  // ✅ Pre-validación acumulada por producto
  $reqPorProducto = [];
  foreach ($carrito as $item) {
    $pid = (int)($item["producto_id"] ?? 0);
    if ($pid <= 0) throw new Exception("Datos inválidos en carrito (producto_id)");

    $unidadesReales = (int)($item["unidades_reales"] ?? 0);
    if ($unidadesReales <= 0) {
      $cant = (int)($item["cantidad"] ?? 0);
      if ($cant <= 0) throw new Exception("Cantidad inválida en carrito");
      $unidadesReales = $cant;
    }

    if (!isset($reqPorProducto[$pid])) $reqPorProducto[$pid] = 0;
    $reqPorProducto[$pid] += $unidadesReales;
  }

  foreach ($reqPorProducto as $pid => $req) {
    $p = obtenerProductoPorId($conexion, (int)$pid);
    if (!$p) throw new Exception("Producto no existe (ID: $pid)");

    $stock = obtenerStockDisponible($conexion, (int)$pid);
    if ((int)$stock < (int)$req) {
      throw new Exception("Stock insuficiente para {$p['nombre']} (disp: $stock, req: $req)");
    }
  }

  // Procesar items
  foreach ($carrito as $item) {
    $producto_id = (int)($item["producto_id"] ?? 0);
    $tipo = (($item["tipo"] ?? "unidad") === "paquete") ? "paquete" : "unidad";
    $cantidad = (int)($item["cantidad"] ?? 0);

    $presentacion_id = isset($item["presentacion_id"]) && $item["presentacion_id"] !== null
      ? (int)$item["presentacion_id"]
      : null;

    if ($producto_id <= 0 || $cantidad <= 0) {
      throw new Exception("Datos inválidos en carrito");
    }

    $p = obtenerProductoPorId($conexion, $producto_id);
    if (!$p) throw new Exception("Producto no existe");

    $precio = 0.0;
    $unidades_reales = 0;

    if ($tipo === "paquete") {
      if (!$presentacion_id) {
        throw new Exception("Falta presentacion_id para paquete");
      }

      $stmt = $conexion->prepare("
        SELECT id, producto_id, nombre, unidades, precio_venta
        FROM producto_presentaciones
        WHERE id=? AND activa=1
        LIMIT 1
      ");
      $stmt->bind_param("i", $presentacion_id);
      $stmt->execute();
      $pres = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      if (!$pres) throw new Exception("Presentación no existe o está inactiva");
      if ((int)$pres["producto_id"] !== $producto_id) throw new Exception("Presentación no pertenece al producto");

      $u = (int)$pres["unidades"];
      if ($u <= 0) throw new Exception("Presentación con unidades inválidas");

      $precio = (float)$pres["precio_venta"];
      if ($precio <= 0) throw new Exception("Presentación con precio inválido");

      $unidades_reales = $cantidad * $u;

    } else {
      $precio = (float)($p["precio_unidad"] ?? 0);
      if ($precio <= 0) throw new Exception("El producto {$p['nombre']} no tiene precio por unidad.");
      $unidades_reales = $cantidad;
      $presentacion_id = null;
    }

    // Check extra
    $stockNow = obtenerStockDisponible($conexion, $producto_id);
    if ((int)$stockNow < (int)$unidades_reales) {
      throw new Exception("Stock insuficiente para {$p['nombre']} (disp: $stockNow, req: $unidades_reales)");
    }

    // ✅ Descontar FIFO (ya viene bloqueado por FOR UPDATE en el modelo)
    $ok = descontarStockFIFO($conexion, $producto_id, $unidades_reales, $venta_id);
    if (!$ok) throw new Exception("No se pudo descontar stock FIFO");

    $subtotal = round($precio * $cantidad, 2);

    // Guardar detalle
    if ($presentacion_id === null) {
      $stmtD = $conexion->prepare("
        INSERT INTO detalle_venta
          (venta_id, producto_id, presentacion_id, tipo_venta, cantidad, precio_unitario, subtotal, unidades_reales)
        VALUES
          (?, ?, NULL, ?, ?, ?, ?, ?)
      ");
      $stmtD->bind_param(
        "iisiddi",
        $venta_id,
        $producto_id,
        $tipo,
        $cantidad,
        $precio,
        $subtotal,
        $unidades_reales
      );
      $stmtD->execute();
      $stmtD->close();
    } else {
      $stmtD = $conexion->prepare("
        INSERT INTO detalle_venta
          (venta_id, producto_id, presentacion_id, tipo_venta, cantidad, precio_unitario, subtotal, unidades_reales)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmtD->bind_param(
        "iiisiddi",
        $venta_id,
        $producto_id,
        $presentacion_id,
        $tipo,
        $cantidad,
        $precio,
        $subtotal,
        $unidades_reales
      );
      $stmtD->execute();
      $stmtD->close();
    }
  }

  actualizarTotalVenta($conexion, $venta_id);

  // Limpieza final
  autoDesactivarLotesSinStock($conexion);

  $conexion->commit();
  echo json_encode(["ok" => true, "venta_id" => $venta_id]);
  exit;

} catch (Throwable $e) {
  $conexion->rollback();
  echo json_encode(["ok" => false, "msg" => $e->getMessage()]);
  exit;
}