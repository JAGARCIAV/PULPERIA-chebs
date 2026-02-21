<?php
require_once __DIR__ . "/venta_modelo.php"; // usa obtenerVentaPorId sin redeclare

function ventaEstaAnulada($conexion, $venta_id) {
    $v = obtenerVentaPorId($conexion, (int)$venta_id);
    return $v && isset($v["anulada"]) && (int)$v["anulada"] === 1;
}

function marcarVentaAnulada($conexion, $venta_id) {
    $venta_id = (int)$venta_id;

    $stmt = $conexion->prepare("UPDATE ventas SET anulada=1, total=0 WHERE id=?");
    $stmt->bind_param("i", $venta_id);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function obtenerDetalleVentaPorVenta($conexion, $venta_id) {
    $venta_id = (int)$venta_id;
    $stmt = $conexion->prepare("
        SELECT dv.*, p.nombre
        FROM detalle_venta dv
        INNER JOIN productos p ON p.id = dv.producto_id
        WHERE dv.venta_id=?
        ORDER BY dv.id ASC
    ");
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    return $stmt->get_result();
}

function obtenerDetalleEspecifico($conexion, $detalle_id) {
    $detalle_id = (int)$detalle_id;
    $stmt = $conexion->prepare("SELECT * FROM detalle_venta WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $detalle_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r;
}

function obtenerPrecioProducto($conexion, $producto_id) {
    $producto_id = (int)$producto_id;
    $stmt = $conexion->prepare("SELECT precio_unidad FROM productos WHERE id=?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res ? (float)$res['precio_unidad'] : 0.0;
}

function obtenerUnidadesPresentacion($conexion, $presentacion_id) {
    $presentacion_id = (int)$presentacion_id;
    if ($presentacion_id <= 0) return 0;

    $stmt = $conexion->prepare("SELECT unidades FROM producto_presentaciones WHERE id=? AND activa=1 LIMIT 1");
    $stmt->bind_param("i", $presentacion_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $r ? (int)$r["unidades"] : 0;
}

function actualizarLineaDetalleCorregida($conexion, $id_detalle, $producto_id, $cantidad, $precio, $subtotal, $presentacion_id, $tipo_venta, $unidades_reales) {

    $id_detalle = (int)$id_detalle;
    $producto_id = (int)$producto_id;
    $cantidad = (int)$cantidad;
    $precio = (float)$precio;
    $subtotal = (float)$subtotal;
    $unidades_reales = (int)$unidades_reales;
    $tipo_venta = (string)$tipo_venta;

    if ($presentacion_id === null) {
        $stmt = $conexion->prepare("
            UPDATE detalle_venta
            SET producto_id=?,
                presentacion_id=NULL,
                tipo_venta=?,
                cantidad=?,
                precio_unitario=?,
                subtotal=?,
                unidades_reales=?
            WHERE id=?
        ");
        $stmt->bind_param("isiddii", $producto_id, $tipo_venta, $cantidad, $precio, $subtotal, $unidades_reales, $id_detalle);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    $presentacion_id = (int)$presentacion_id;
    $stmt = $conexion->prepare("
        UPDATE detalle_venta
        SET producto_id=?,
            presentacion_id=?,
            tipo_venta=?,
            cantidad=?,
            precio_unitario=?,
            subtotal=?,
            unidades_reales=?
        WHERE id=?
    ");
    $stmt->bind_param("iisiddii", $producto_id, $presentacion_id, $tipo_venta, $cantidad, $precio, $subtotal, $unidades_reales, $id_detalle);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function eliminarLineaDetalle($conexion, $id_detalle) {
    $id_detalle = (int)$id_detalle;
    $stmt = $conexion->prepare("DELETE FROM detalle_venta WHERE id = ?");
    $stmt->bind_param("i", $id_detalle);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function actualizarTotalVentaPorDetalle($conexion, $venta_id) {
    $venta_id = (int)$venta_id;

    $stmt = $conexion->prepare("SELECT COALESCE(SUM(subtotal),0) AS total FROM detalle_venta WHERE venta_id=?");
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total = (float)($row["total"] ?? 0);

    $up = $conexion->prepare("UPDATE ventas SET total=? WHERE id=?");
    $up->bind_param("di", $total, $venta_id);
    $up->execute();
    $up->close();

    return $total;
}