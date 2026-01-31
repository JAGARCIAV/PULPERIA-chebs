<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php"; // ✅ tu conexión oficial ($conexion)
require_once __DIR__ . "/../modelos/producto_modelo.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";
require_once __DIR__ . "/../modelos/venta_modelo.php";

header("Content-Type: application/json; charset=utf-8");

// ✅ Para que mysqli lance excepciones y no falle “silencioso”
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["carrito"]) || !is_array($data["carrito"]) || count($data["carrito"]) === 0) {
    echo json_encode(["ok" => false, "msg" => "Carrito vacío"]);
    exit;
}

$carrito = $data["carrito"];

// ✅ Transacción
$conexion->begin_transaction();

try {
    // ✅ crear venta ligada a turno abierto
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
        if (!$p) {
            throw new Exception("Producto no existe");
        }

        // ✅ precio real
        if ($tipo === "paquete") {
            if (empty($p["precio_paquete"])) {
                throw new Exception("El producto {$p['nombre']} no tiene precio por paquete.");
            }
            $precio = (float)$p["precio_paquete"];
        } else {
            $precio = (float)$p["precio_unidad"];
        }

        // ✅ unidades reales a descontar
        $unidades_por_paquete = (int)($p["unidades_por_paquete"] ?? 1);
        if ($unidades_por_paquete <= 0) $unidades_por_paquete = 1;

        $unidades = ($tipo === "paquete")
            ? $cantidad * $unidades_por_paquete
            : $cantidad;

        // ✅ stock SOLO lotes activos (tu modelo ya debe filtrar activo=1)
        $stock = obtenerStockDisponible($conexion, $producto_id);
        if ($stock < $unidades) {
            throw new Exception("Stock insuficiente para {$p['nombre']} (disp: $stock, req: $unidades)");
        }

        // ✅ descontar FIFO SOLO lotes activos (tu modelo ya debe filtrar activo=1)
        $ok = descontarStockFIFO($conexion, $producto_id, $unidades, $venta_id);
        if (!$ok) {
            throw new Exception("No se pudo descontar stock FIFO");
        }

        // ✅ guardar detalle
        $subtotal = round($precio * $cantidad, 2);
        agregarDetalleVenta($conexion, $venta_id, $producto_id, $tipo, $cantidad, $precio, $subtotal);
    }

    // ✅ actualizar total final
    actualizarTotalVenta($conexion, $venta_id);

    $conexion->commit();
    echo json_encode(["ok" => true, "venta_id" => $venta_id]);
    exit;

} catch (Throwable $e) {
    $conexion->rollback();
    echo json_encode(["ok" => false, "msg" => $e->getMessage()]);
    exit;
}
