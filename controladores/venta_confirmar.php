<?php
require_once "../config/conexion.php";
require_once "../modelos/producto_modelo.php";
require_once "../modelos/lote_modelo.php";
require_once "../modelos/venta_modelo.php";

header("Content-Type: application/json; charset=utf-8");

// Recibe JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["carrito"]) || !is_array($data["carrito"]) || count($data["carrito"]) === 0) {
    echo json_encode(["ok" => false, "msg" => "Carrito vacío"]);
    exit;
}

$carrito = $data["carrito"];

// ✅ Transacción (todo o nada)
$conexion->begin_transaction();

try {

    // ✅ Crear venta (depende de turno abierto)
    $venta_id = crearVenta($conexion);

    if (!$venta_id) {
        throw new Exception("No hay turno abierto. Abra turno antes de vender.");
    }

    foreach ($carrito as $item) {
        $producto_id = (int)($item["producto_id"] ?? 0);
        $tipo = (($item["tipo"] ?? "unidad") === "paquete") ? "paquete" : "unidad";
        $cantidad = (int)($item["cantidad"] ?? 0);

        if ($producto_id <= 0 || $cantidad <= 0) {
            throw new Exception("Datos inválidos en carrito");
        }

        $p = obtenerProductoPorId($conexion, $producto_id);
        if (!$p) throw new Exception("Producto no existe");

        // Precio real
        $precio = ($tipo === "paquete") ? (float)$p["precio_paquete"] : (float)$p["precio_unidad"];

        // Unidades reales a descontar
        $unidades = ($tipo === "paquete")
            ? $cantidad * (int)$p["unidades_por_paquete"]
            : $cantidad;

        // Validar stock
        $stock = obtenerStockDisponible($conexion, $producto_id);
        if ($stock < $unidades) {
            throw new Exception("Stock insuficiente para {$p['nombre']} (disp: $stock, req: $unidades)");
        }

        // Descontar FIFO
        $ok = descontarStockFIFO($conexion, $producto_id, $unidades);
        if (!$ok) throw new Exception("No se pudo descontar stock FIFO");

        // Guardar detalle
        $subtotal = round($precio * $cantidad, 2);
        agregarDetalleVenta($conexion, $venta_id, $producto_id, $tipo, $cantidad, $precio, $subtotal);
    }

    // Actualizar total de venta
    actualizarTotalVenta($conexion, $venta_id);

    $conexion->commit();
    echo json_encode(["ok" => true, "venta_id" => $venta_id]);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(["ok" => false, "msg" => $e->getMessage()]);
}
