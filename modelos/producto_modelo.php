<?php

function guardarProducto($conexion, $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete) {
    $sql = "INSERT INTO productos (nombre, descripcion, precio_unidad, precio_paquete, unidades_por_paquete)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssddi", $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete);
    $stmt->execute();
}

function obtenerProductos($conexion) {
    $sql = "SELECT * FROM productos";
    return $conexion->query($sql);
}

function obtenerProductoPorId($conexion, $id) {
    $sql = "SELECT * FROM productos WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function obtenerStockTotal($conexion, $producto_id) {
    // ✅ solo lotes activos
    $sql = "SELECT COALESCE(SUM(cantidad_unidades),0) AS total_stock
            FROM lotes
            WHERE producto_id = ? AND activo = 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['total_stock'] ?? 0);
}
