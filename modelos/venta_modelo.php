<?php

function crearVenta($conexion) {
    $turno = (date("H") < 14) ? "mañana" : "tarde";

    $sql = "INSERT INTO ventas (fecha, total, turno) VALUES (NOW(), 0, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $turno);
    $stmt->execute();
    return $conexion->insert_id;
}

function agregarDetalleVenta($conexion, $venta_id, $producto_id, $tipo_venta, $cantidad, $precio_unitario, $subtotal) {
    $sql = "INSERT INTO detalle_venta
            (venta_id, producto_id, tipo_venta, cantidad, precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iisidd", $venta_id, $producto_id, $tipo_venta, $cantidad, $precio_unitario, $subtotal);
    $stmt->execute();
}

function actualizarTotalVenta($conexion, $venta_id) {
    $sql = "UPDATE ventas
            SET total = (SELECT COALESCE(SUM(subtotal),0) FROM detalle_venta WHERE venta_id = ?)
            WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $venta_id, $venta_id);
    $stmt->execute();
}

function obtenerUltimasVentas($conexion, $limite = 10) {
    $sql = "SELECT id, fecha, total
            FROM ventas
            WHERE DATE(fecha) = CURDATE()
            ORDER BY id DESC
            LIMIT ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    return $stmt->get_result();
}


/**
 *  NUEVO: obtener detalle de una venta (para mostrar tipo factura/nota)
 */
function obtenerDetalleVenta($conexion, $venta_id) {
    $sql = "SELECT d.tipo_venta, d.cantidad, d.precio_unitario, d.subtotal, p.nombre
            FROM detalle_venta d
            JOIN productos p ON p.id = d.producto_id
            WHERE d.venta_id = ?
            ORDER BY d.id ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    return $stmt->get_result();
}

function obtenerTotalVentasHoy($conexion) {
    $sql = "SELECT COALESCE(SUM(total),0) AS total_hoy
            FROM ventas
            WHERE DATE(fecha) = CURDATE()";
    $res = $conexion->query($sql);
    $row = $res->fetch_assoc();
    return (float)$row["total_hoy"];
}


?>
