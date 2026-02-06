<?php

/* =========================
   PRODUCTOS (base)
   ========================= */

function guardarProducto($conexion, $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete) {

    // Como ahora usaremos presentaciones, estos 2 campos quedan "legacy".
    // Para no romper tu INSERT/UPDATE y evitar NULL en bind_param, los normalizamos:
    $precio_paquete = (is_numeric($precio_paquete) ? (float)$precio_paquete : 0.00);
    $unidades_paquete = (is_numeric($unidades_paquete) && (int)$unidades_paquete > 0) ? (int)$unidades_paquete : 1;

    $sql = "INSERT INTO productos (nombre, descripcion, precio_unidad, precio_paquete, unidades_por_paquete)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssddi", $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete);

    if(!$stmt->execute()){
        return 0; // error
    }

    return $conexion->insert_id; // ⭐ CLAVE
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

// (tu función, la dejo)
function obtenerProductoPorIds($conexion, $id) {
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function actualizarProducto($conexion, $id, $nombre, $precio_unidad, $precio_paquete, $activo) {
    // legacy: normalizar
    $precio_paquete = (is_numeric($precio_paquete) ? (float)$precio_paquete : 0.00);

    $sql = "UPDATE productos
            SET nombre=?, precio_unidad=?, precio_paquete=?, activo=?
            WHERE id=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sddii", $nombre, $precio_unidad, $precio_paquete, $activo, $id);
    return $stmt->execute();
}

/* =========================
   STOCK (unidades)
   ========================= */

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

/* =========================
   ✅ PRESENTACIONES (packs)
   ========================= */

function obtenerPresentacionesPorProducto($conexion, $producto_id) {
    $producto_id = (int)$producto_id;
    $stmt = $conexion->prepare("
        SELECT id, producto_id, nombre, unidades, precio_venta, costo, activa
        FROM producto_presentaciones
        WHERE producto_id=? AND activa=1
        ORDER BY unidades ASC
    ");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $out = [];
    while($row = $res->fetch_assoc()) $out[] = $row;
    return $out;
}

function obtenerPresentacionPorId($conexion, $presentacion_id) {
    $presentacion_id = (int)$presentacion_id;
    $stmt = $conexion->prepare("
        SELECT id, producto_id, nombre, unidades, precio_venta, costo, activa
        FROM producto_presentaciones
        WHERE id=? AND activa=1
        LIMIT 1
    ");
    $stmt->bind_param("i", $presentacion_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function eliminarPresentacionesProducto($conexion, $producto_id) {
    $producto_id = (int)$producto_id;
    $stmt = $conexion->prepare("DELETE FROM producto_presentaciones WHERE producto_id=?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
}

function guardarPresentacionesProducto($conexion, $producto_id, $nombres, $unidades, $precios, $costos) {
    $producto_id = (int)$producto_id;

    if (!is_array($nombres) || !is_array($unidades) || !is_array($precios)) return;

    // ✅ Truco profesional: NULLIF para costo opcional sin pelear con bind_param null
    $stmt = $conexion->prepare("
        INSERT INTO producto_presentaciones (producto_id, nombre, unidades, precio_venta, costo, activa)
        VALUES (?, ?, ?, ?, NULLIF(?, ''), 1)
    ");

    $count = count($nombres);
    for ($i = 0; $i < $count; $i++) {
        $nom = trim((string)($nombres[$i] ?? ''));
        $uni = (int)($unidades[$i] ?? 0);
        $pre = (float)($precios[$i] ?? 0);
        $cos = trim((string)($costos[$i] ?? '')); // puede ser '' para NULL

        if ($nom === '' || $uni <= 0 || $pre <= 0) continue;

        $stmt->bind_param("isids", $producto_id, $nom, $uni, $pre, $cos);
        $stmt->execute();
    }
}
