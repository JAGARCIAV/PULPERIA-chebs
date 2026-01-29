<?php

function guardarLote($conexion, $producto_id, $fecha_vencimiento, $cantidad) {
    $sql = "INSERT INTO lotes (producto_id, fecha_vencimiento, cantidad_unidades, fecha_ingreso)
            VALUES (?, ?, ?, NOW())";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("isi", $producto_id, $fecha_vencimiento, $cantidad);
    $stmt->execute();

    
}

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
