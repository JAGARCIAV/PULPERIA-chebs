<?php

function guardarProducto($conexion, $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete) {

    $sql = "INSERT INTO productos (nombre, descripcion, precio_unidad, precio_paquete, unidades_por_paquete, stock_actual)
            VALUES (?, ?, ?, ?, ?, 0)";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssddi", $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete);
    $stmt->execute();
}

function obtenerProductos($conexion) {
    $sql = "SELECT * FROM productos";
    return $conexion->query($sql);
}
?>
