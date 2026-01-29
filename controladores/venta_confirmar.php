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

// Transacción para que sea seguro (todo o nada)
$conexion->begin_transaction();

try {
    $venta_id = crearVenta($conexion);

    foreach ($carrito as $item) {
        $producto_id = (int)$item["producto_id"];
        $tipo = $item["tipo"] === "paquete" ? "paquete" : "unidad";
        $cantidad = (int)$item["cantidad"];

        if ($producto_id <= 0 || $cantidad <= 0) {
            throw new Exception("Datos inválidos en carrito");
        }

        $p = obtenerProductoPorId($conexion, $producto_id);
        if (!$p) throw new Exception("Producto no existe");

        // precio real
        $precio = ($tipo === "paquete") ? (float)$p["precio_paquete"] : (float)$p["precio_unidad"];

        // unidades reales a descontar
        $unidades = ($tipo === "paquete")
            ? $cantidad * (int)$p["unidades_por_paquete"]
            : $cantidad;

        // validar stock
        $stock = obtenerStockDisponible($conexion, $producto_id);
        if ($stock < $unidades) {
            throw new Exception("Stock insuficiente para {$p['nombre']} (disp: $stock, req: $unidades)");
        }

        // descontar FIFO
        $ok = descontarStockFIFO($conexion, $producto_id, $unidades);
        if (!$ok) throw new Exception("No se pudo descontar stock FIFO");

        $subtotal = $precio * $cantidad;
        agregarDetalleVenta($conexion, $venta_id, $producto_id, $tipo, $cantidad, $precio, $subtotal);
    }

    actualizarTotalVenta($conexion, $venta_id);

    $conexion->commit();
    echo json_encode(["ok" => true, "venta_id" => $venta_id]);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(["ok" => false, "msg" => $e->getMessage()]);
}
