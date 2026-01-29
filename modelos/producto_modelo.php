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

//  NUEVO: obtener producto por ID (para precios, unidades_paquete, etc.)
function obtenerProductoPorId($conexion, $id) {
    $sql = "SELECT * FROM productos WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>
