<?php
require_once __DIR__ . "/turno_modelo.php";

function crearVenta($conexion) {

    // ✅ Turno abierto (desde modelo)
    $turno = obtenerTurnoAbiertoHoy($conexion);

    if (!$turno) {
        return 0; // controlador mostrará mensaje
    }

    $turno_id = (int)$turno["id"];

    // ✅ Crear venta ligada al turno abierto
    $sql = "INSERT INTO ventas (fecha, total, turno_id)
            VALUES (NOW(), 0, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $turno_id);

    if (!$stmt->execute()) {
        return 0;
    }

    return (int)$conexion->insert_id;
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

function obtenerUltimasVentasDesde($conexion, $desde_id = 0, $limite = 10) {
    $sql = "SELECT id, fecha, total
            FROM ventas
            WHERE DATE(fecha)=CURDATE() AND id > ?
            ORDER BY id DESC
            LIMIT ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $desde_id, $limite);
    $stmt->execute();
    return $stmt->get_result();
}

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

function obtenerVentasFiltradas($conexion, $fecha = null, $turno = null, $tipo = null, $busqueda = null) {

    $sql = "SELECT v.id, v.fecha, v.total, v.turno,
                   u.nombre AS responsable
            FROM ventas v
            JOIN turnos t ON t.id = v.turno_id
            JOIN usuarios u ON u.id = t.usuario_id
            WHERE 1=1";

    $params = [];
    $types  = "";

    // FILTRO POR FECHA
    if ($fecha) {
        $sql .= " AND DATE(v.fecha) = ?";
        $params[] = $fecha;
        $types .= "s";
    }

    // FILTRO POR TURNO
    if ($turno) {
        $sql .= " AND v.turno = ?";
        $params[] = $turno;
        $types .= "s";
    }

    // FILTRO BUSCADOR (ID / Responsable)
    if ($busqueda && $tipo) {
        if ($tipo === "id") {
            $sql .= " AND v.id = ?";
            $params[] = $busqueda;
            $types .= "i";
        }
        if ($tipo === "responsable") {
            $sql .= " AND u.nombre LIKE ?";
            $params[] = "%$busqueda%";
            $types .= "s";
        }
    }

    $sql .= " ORDER BY v.id DESC LIMIT 200";

    $stmt = $conexion->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt->get_result();
}

