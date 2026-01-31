<?php

function obtenerMovimientos($conexion, $producto_id = null) {

    if ($producto_id) {
        $sql = "SELECT m.*, p.nombre AS producto, l.fecha_vencimiento
                FROM movimientos_inventario m
                JOIN productos p ON m.producto_id = p.id
                LEFT JOIN lotes l ON m.lote_id = l.id
                WHERE m.producto_id = ?
                ORDER BY m.fecha DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        return $stmt->get_result();
    } else {
        $sql = "SELECT m.*, p.nombre AS producto, l.fecha_vencimiento
                FROM movimientos_inventario m
                JOIN productos p ON m.producto_id = p.id
                LEFT JOIN lotes l ON m.lote_id = l.id
                ORDER BY m.fecha DESC";

        return $conexion->query($sql);
    }
}


?>
