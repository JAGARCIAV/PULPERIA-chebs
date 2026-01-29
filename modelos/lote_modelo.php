<?php

function guardarLote($conexion, $producto_id, $fecha_vencimiento, $cantidad) {
    $sql = "INSERT INTO lotes (producto_id, fecha_vencimiento, cantidad_unidades, fecha_ingreso)
            VALUES (?, ?, ?, NOW())";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("isi", $producto_id, $fecha_vencimiento, $cantidad);
    $stmt->execute();

    $sql2 = "UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?";
    $stmt2 = $conexion->prepare($sql2);
    $stmt2->bind_param("ii", $cantidad, $producto_id);
    $stmt2->execute();
}


/*
 OTRA OPCION 
function guardarLote($conexion, $producto_id, $fecha_vencimiento, $cantidad) {

    $producto_id = (int)$producto_id;
    $cantidad = (int)$cantidad;

    if ($producto_id <= 0) die("Producto inválido");
    if (!$fecha_vencimiento) die("Fecha requerida");
    if ($cantidad <= 0) die("Cantidad inválida");

    $sql = "INSERT INTO lotes (producto_id, fecha_vencimiento, cantidad_unidades, fecha_ingreso)
            VALUES (?, ?, ?, NOW())";

    $stmt = $conexion->prepare($sql);
    if(!$stmt) die("Error prepare: " . $conexion->error);

    $stmt->bind_param("isi", $producto_id, $fecha_vencimiento, $cantidad);

    if(!$stmt->execute()) die("Error execute: " . $stmt->error);

    $stmt->close();
}*/



function obtenerProductosSelect($conexion) {
    $sql = "SELECT id, nombre FROM productos";
    return $conexion->query($sql);
}

function obtenerLotes($conexion) {
    $sql = "SELECT l.*, p.nombre 
            FROM lotes l
            JOIN productos p ON l.producto_id = p.id
            ORDER BY l.fecha_ingreso ASC";
    return $conexion->query($sql);
}

// ✅ NUEVO: stock total disponible (suma lotes)
function obtenerStockDisponible($conexion, $producto_id) {
    $sql = "SELECT COALESCE(SUM(cantidad_unidades),0) AS total
            FROM lotes
            WHERE producto_id = ? AND cantidad_unidades > 0";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return (int)$res['total'];
}

// ✅ NUEVO: descontar FIFO desde lotes
function descontarStockFIFO($conexion, $producto_id, $unidades_a_descontar) {

    $sql = "SELECT id, cantidad_unidades
            FROM lotes
            WHERE producto_id = ? AND cantidad_unidades > 0
            ORDER BY fecha_ingreso ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $lotes = $stmt->get_result();

    while ($row = $lotes->fetch_assoc()) {
        if ($unidades_a_descontar <= 0) break;

        $lote_id = (int)$row['id'];
        $disp = (int)$row['cantidad_unidades'];

        $restar = min($disp, $unidades_a_descontar);

        $up = $conexion->prepare("UPDATE lotes SET cantidad_unidades = cantidad_unidades - ? WHERE id = ?");
        $up->bind_param("ii", $restar, $lote_id);
        $up->execute();

        $unidades_a_descontar -= $restar;
    }

    return $unidades_a_descontar <= 0;
}
?>
