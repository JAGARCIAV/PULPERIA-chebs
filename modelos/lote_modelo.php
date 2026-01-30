<?php

// ✅ Guardar lote (activo=1 por defecto)
function guardarLote($conexion, $producto_id, $fecha_vencimiento, $cantidad) {
    $producto_id = (int)$producto_id;
    $cantidad = (int)$cantidad;

    if ($producto_id <= 0) return false;
    if (!$fecha_vencimiento) return false;
    if ($cantidad <= 0) return false;

    $sql = "INSERT INTO lotes (producto_id, fecha_vencimiento, cantidad_unidades, activo, fecha_ingreso)
            VALUES (?, ?, ?, 1, NOW())";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("isi", $producto_id, $fecha_vencimiento, $cantidad);
    $ok = $stmt->execute();

    if ($ok) {
        // ✅ mantener productos.stock_actual coherente (solo un contador rápido)
        $sql2 = "UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?";
        $stmt2 = $conexion->prepare($sql2);
        $stmt2->bind_param("ii", $cantidad, $producto_id);
        $stmt2->execute();
    }

    return $ok;
}

// ✅ Select para combos
function obtenerProductosSelect($conexion) {
    $sql = "SELECT id, nombre FROM productos WHERE activo=1 ORDER BY nombre ASC";
    return $conexion->query($sql);
}

// ✅ Listar lotes
function obtenerLotes($conexion) {
    $sql = "SELECT l.*, p.nombre 
            FROM lotes l
            JOIN productos p ON l.producto_id = p.id
            ORDER BY l.activo DESC, l.fecha_vencimiento ASC, l.fecha_ingreso ASC";
    return $conexion->query($sql);
}

// ✅ Stock total disponible (solo lotes activos + NO vencidos + con unidades)
function obtenerStockDisponible($conexion, $producto_id) {
    $producto_id = (int)$producto_id;

    $sql = "SELECT COALESCE(SUM(cantidad_unidades),0) AS total
            FROM lotes
            WHERE producto_id = ?
              AND activo = 1
              AND cantidad_unidades > 0
              AND fecha_vencimiento >= CURDATE()";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    return (int)($res['total'] ?? 0);
}

// ✅ Descontar FIFO (solo lotes activos + NO vencidos)
function descontarStockFIFO($conexion, $producto_id, $unidades_a_descontar) {
    $producto_id = (int)$producto_id;
    $unidades_a_descontar = (int)$unidades_a_descontar;

    if ($producto_id <= 0) return false;
    if ($unidades_a_descontar <= 0) return true;

    // Tomar lotes FIFO por vencimiento primero (mejor), luego por ingreso
    $sql = "SELECT id, cantidad_unidades
            FROM lotes
            WHERE producto_id = ?
              AND activo = 1
              AND cantidad_unidades > 0
              AND fecha_vencimiento >= CURDATE()
            ORDER BY fecha_vencimiento ASC, fecha_ingreso ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $lotes = $stmt->get_result();

    $descontado_total = 0;

    while ($row = $lotes->fetch_assoc()) {
        if ($unidades_a_descontar <= 0) break;

        $lote_id = (int)$row['id'];
        $disp = (int)$row['cantidad_unidades'];

        $restar = min($disp, $unidades_a_descontar);

        $up = $conexion->prepare("UPDATE lotes SET cantidad_unidades = cantidad_unidades - ? WHERE id = ?");
        $up->bind_param("ii", $restar, $lote_id);
        $up->execute();

        $unidades_a_descontar -= $restar;
        $descontado_total += $restar;
    }

    // ✅ actualizar contador rápido en productos.stock_actual
    if ($descontado_total > 0) {
        $sqlp = "UPDATE productos SET stock_actual = GREATEST(stock_actual - ?, 0) WHERE id = ?";
        $stmtp = $conexion->prepare($sqlp);
        $stmtp->bind_param("ii", $descontado_total, $producto_id);
        $stmtp->execute();
    }

    return $unidades_a_descontar <= 0;
}

// ✅ Desactivar un lote (ej: vencido, dañado, etc.)
function desactivarLote($conexion, $lote_id) {
    $lote_id = (int)$lote_id;
    if ($lote_id <= 0) return false;

    $sql = "UPDATE lotes SET activo = 0 WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $lote_id);
    return $stmt->execute();
}

// ✅ Activar lote (por si se activó mal)
function activarLote($conexion, $lote_id) {
    $lote_id = (int)$lote_id;
    if ($lote_id <= 0) return false;

    $sql = "UPDATE lotes SET activo = 1 WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $lote_id);
    return $stmt->execute();
}
