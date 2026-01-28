<?php

function guardarLote($conexion, $producto_id, $fecha_vencimiento, $cantidad) {

    // Guardar lote
    $sql = "INSERT INTO lotes (producto_id, fecha_vencimiento, cantidad_unidades)
            VALUES (?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("isi", $producto_id, $fecha_vencimiento, $cantidad);
    $stmt->execute();

    // Aumentar stock del producto
    $sql2 = "UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?";
    $stmt2 = $conexion->prepare($sql2);
    $stmt2->bind_param("ii", $cantidad, $producto_id);
    $stmt2->execute();
}

function obtenerProductos($conexion) {
    $sql = "SELECT id, nombre FROM productos";
    return $conexion->query($sql);
}

function obtenerLotes($conexion) {
    $sql = "SELECT l.*, p.nombre 
            FROM lotes l
            JOIN productos p ON l.producto_id = p.id
            ORDER BY fecha_vencimiento ASC";
    return $conexion->query($sql);
}

?>
