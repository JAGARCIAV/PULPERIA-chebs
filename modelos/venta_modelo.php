<?php
require_once __DIR__ . "/turno_modelo.php";

/* =========================================================
   ✅ VENTA MODELO (LIMPIO)
   - NO incluye helpers de corregir/anular (eso va en venta_corregir_modelo.php)
   ========================================================= */

function crearVenta($conexion) {

    $turno = obtenerTurnoAbiertoHoy($conexion);
    if (!$turno) return 0;

    $turno_id = (int)$turno["id"];

    $sql = "INSERT INTO ventas (fecha, total, turno_id)
            VALUES (NOW(), 0, ?)";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) return 0;

    $stmt->bind_param("i", $turno_id);

    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }

    $id = (int)$conexion->insert_id;
    $stmt->close();
    return $id;
}

/* =========================
   ✅ Agregar detalle venta
   (con presentacion_id y unidades_reales)
   ========================= */
function agregarDetalleVenta($conexion, $venta_id, $producto_id, $tipo_venta, $cantidad, $precio_unitario, $subtotal, $presentacion_id = null, $unidades_reales = null) {

    $venta_id    = (int)$venta_id;
    $producto_id = (int)$producto_id;
    $cantidad    = (int)$cantidad;
    $precio_unitario = (float)$precio_unitario;
    $subtotal    = (float)$subtotal;
    $tipo_venta  = (string)$tipo_venta;

    if ($unidades_reales === null) $unidades_reales = $cantidad;
    $unidades_reales = (int)$unidades_reales;

    if ($presentacion_id === null) {
        $sql = "INSERT INTO detalle_venta
                (venta_id, producto_id, presentacion_id, tipo_venta, cantidad, precio_unitario, subtotal, unidades_reales)
                VALUES (?, ?, NULL, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iisiddi", $venta_id, $producto_id, $tipo_venta, $cantidad, $precio_unitario, $subtotal, $unidades_reales);
        $stmt->execute();
        $stmt->close();
        return;
    }

    $presentacion_id = (int)$presentacion_id;

    $sql = "INSERT INTO detalle_venta
            (venta_id, producto_id, presentacion_id, tipo_venta, cantidad, precio_unitario, subtotal, unidades_reales)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiisiddi", $venta_id, $producto_id, $presentacion_id, $tipo_venta, $cantidad, $precio_unitario, $subtotal, $unidades_reales);
    $stmt->execute();
    $stmt->close();
}

/* =========================
   Total venta desde detalle
   ========================= */
function actualizarTotalVenta($conexion, $venta_id) {
    $venta_id = (int)$venta_id;
    $sql = "UPDATE ventas
            SET total = (SELECT COALESCE(SUM(subtotal),0) FROM detalle_venta WHERE venta_id = ?)
            WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $venta_id, $venta_id);
    $stmt->execute();
    $stmt->close();
}

function obtenerUltimasVentasDesde($conexion, $desde_id = 0, $limite = 10) {
    $desde_id = (int)$desde_id;
    $limite   = (int)$limite;

    $sql = "SELECT id, fecha, total
            FROM ventas
            WHERE DATE(fecha)=CURDATE()
              AND anulada = 0
              AND id > ?
            ORDER BY id DESC
            LIMIT ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $desde_id, $limite);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    return $res;
}

function obtenerDetalleVenta($conexion, $venta_id) {
    $venta_id = (int)$venta_id;

    $sql = "SELECT d.tipo_venta, d.cantidad, d.precio_unitario, d.subtotal, d.unidades_reales, p.nombre
            FROM detalle_venta d
            JOIN productos p ON p.id = d.producto_id
            WHERE d.venta_id = ?
            ORDER BY d.id ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    return $res;
}

function obtenerTotalVentasHoy($conexion) {
    $sql = "SELECT COALESCE(SUM(total),0) AS total_hoy
            FROM ventas
            WHERE DATE(fecha) = CURDATE()
              AND anulada = 0";
    $res = $conexion->query($sql);
    $row = $res->fetch_assoc();
    return (float)($row["total_hoy"] ?? 0);
}

/* =========================
   ✅ Ventas filtradas
   ========================= */
function obtenerVentasFiltradas($conexion, $fecha = null, $turno = null, $tipo = null, $busqueda = null) {

    $sql = "SELECT v.id, v.fecha, v.total,
                   v.turno_id,
                   u.nombre AS responsable,
                   CASE
                     WHEN TIME(v.fecha) < '12:00:00' THEN 'mañana'
                     ELSE 'tarde'
                   END AS turno
            FROM ventas v
            JOIN turnos t ON t.id = v.turno_id
            JOIN usuarios u ON u.id = t.usuario_id
            WHERE v.anulada = 0";

    $params = [];
    $types  = "";

    if ($fecha) {
        $sql .= " AND DATE(v.fecha) = ?";
        $params[] = $fecha;
        $types .= "s";
    }

    if ($turno) {
        if ($turno === "mañana") {
            $sql .= " AND TIME(v.fecha) < '12:00:00'";
        } elseif ($turno === "tarde") {
            $sql .= " AND TIME(v.fecha) >= '12:00:00'";
        }
    }

    if ($busqueda && $tipo) {
        if ($tipo === "id") {
            $sql .= " AND v.id = ?";
            $params[] = (int)$busqueda;
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
    $res = $stmt->get_result();
    $stmt->close();
    return $res;
}

/* =========================
   Venta por ID (incluye anulada)
   ========================= */
function obtenerVentaPorId($conexion, $id) {
    $id = (int)$id;
    $sql = "SELECT id, fecha, total, turno_id, anulada
            FROM ventas
            WHERE id=? LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res;
}

/* =========================
   Detalle para corregir (campos completos)
   ========================= */
function corregirVenta($conexion, $venta_id) {
    $venta_id = (int)$venta_id;

    $sql = "SELECT d.id, d.producto_id, d.presentacion_id, d.tipo_venta,
                   d.cantidad, d.precio_unitario, d.subtotal, d.unidades_reales,
                   p.nombre
            FROM detalle_venta d
            JOIN productos p ON p.id = d.producto_id
            WHERE d.venta_id = ?
            ORDER BY d.id ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    return $res;
}