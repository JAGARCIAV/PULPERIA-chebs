<?php

/**
 * Cuenta el total de movimientos (para la paginación).
 */
function contarMovimientos($conexion, $producto_id = null) {

    if ($producto_id) {
        $sql  = "SELECT COUNT(*) AS total
                 FROM movimientos_inventario m
                 WHERE m.producto_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }

    $res = $conexion->query("SELECT COUNT(*) AS total FROM movimientos_inventario");
    return (int)($res->fetch_assoc()['total'] ?? 0);
}

/**
 * Devuelve una página de movimientos (igual que el historial de ventas).
 */
function obtenerMovimientos($conexion, $producto_id = null, $limite = 50, $offset = 0) {

    $limite = max(1, (int)$limite);
    $offset = max(0, (int)$offset);

    if ($producto_id) {
        $sql = "SELECT m.*, p.nombre AS producto, l.fecha_vencimiento
                FROM movimientos_inventario m
                JOIN productos p ON m.producto_id = p.id
                LEFT JOIN lotes l ON m.lote_id = l.id
                WHERE m.producto_id = ?
                ORDER BY m.fecha DESC, m.id DESC
                LIMIT ? OFFSET ?";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iii", $producto_id, $limite, $offset);
        $stmt->execute();
        return $stmt->get_result();
    }

    $sql = "SELECT m.*, p.nombre AS producto, l.fecha_vencimiento
            FROM movimientos_inventario m
            JOIN productos p ON m.producto_id = p.id
            LEFT JOIN lotes l ON m.lote_id = l.id
            ORDER BY m.fecha DESC, m.id DESC
            LIMIT ? OFFSET ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $limite, $offset);
    $stmt->execute();
    return $stmt->get_result();
}


?>
