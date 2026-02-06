<?php

/* =========================
   PRODUCTOS (base)
   ========================= */

function guardarProducto($conexion, $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete, $costo_unidad = null) {

    // legacy: normalizar
    $precio_paquete = (is_numeric($precio_paquete) ? (float)$precio_paquete : 0.00);
    $unidades_paquete = (is_numeric($unidades_paquete) && (int)$unidades_paquete > 0) ? (int)$unidades_paquete : 1;

    // costo_unidad opcional
    if ($costo_unidad === '' || $costo_unidad === null) {
        $costo_unidad = null;
    } else {
        $costo_unidad = (float)$costo_unidad;
        if ($costo_unidad <= 0) $costo_unidad = null;
    }

    // ✅ incluimos costo_unidad
    $sql = "INSERT INTO productos (nombre, descripcion, precio_unidad, precio_paquete, unidades_por_paquete, costo_unidad)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);

    // bind: s s d d i d  -> pero costo puede ser NULL: usamos "d" igual, y enviamos null como 0 + set_null?
    // Truco: si es null, lo mandamos como NULL usando bind_param con variable y luego ->send_long_data? (mysqli no)
    // Solución simple: usar NULLIF en SQL para que '' sea NULL. Pero aquí es float.
    // Mejor: si es null, insertamos NULL directamente con query preparada dinámica:
    if ($costo_unidad === null) {
        $sql2 = "INSERT INTO productos (nombre, descripcion, precio_unidad, precio_paquete, unidades_por_paquete, costo_unidad)
                 VALUES (?, ?, ?, ?, ?, NULL)";
        $stmt2 = $conexion->prepare($sql2);
        $stmt2->bind_param("ssddi", $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete);

        if(!$stmt2->execute()){
            return 0;
        }
        return $conexion->insert_id;
    }

    $stmt->bind_param("ssddid", $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete, $costo_unidad);

    if(!$stmt->execute()){
        return 0; // error
    }

    return $conexion->insert_id;
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

function obtenerProductoPorIds($conexion, $id) {
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function actualizarProducto($conexion, $id, $nombre, $precio_unidad, $precio_paquete, $activo, $costo_unidad = null) {
    $precio_paquete = (is_numeric($precio_paquete) ? (float)$precio_paquete : 0.00);

    if ($costo_unidad === '' || $costo_unidad === null) {
        $costo_unidad = null;
    } else {
        $costo_unidad = (float)$costo_unidad;
        if ($costo_unidad <= 0) $costo_unidad = null;
    }

    if ($costo_unidad === null) {
        $sql2 = "UPDATE productos
                 SET nombre=?, precio_unidad=?, precio_paquete=?, activo=?, costo_unidad=NULL
                 WHERE id=?";
        $stmt2 = $conexion->prepare($sql2);
        $stmt2->bind_param("sddii", $nombre, $precio_unidad, $precio_paquete, $activo, $id);
        return $stmt2->execute();
    }

    $sql = "UPDATE productos
            SET nombre=?, precio_unidad=?, precio_paquete=?, activo=?, costo_unidad=?
            WHERE id=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sddidi", $nombre, $precio_unidad, $precio_paquete, $activo, $costo_unidad, $id);
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

    $stmt = $conexion->prepare("
        INSERT INTO producto_presentaciones (producto_id, nombre, unidades, precio_venta, costo, activa)
        VALUES (?, ?, ?, ?, NULLIF(?, ''), 1)
    ");

    $count = count($nombres);
    for ($i = 0; $i < $count; $i++) {
        $nom = trim((string)($nombres[$i] ?? ''));
        $uni = (int)($unidades[$i] ?? 0);
        $pre = (float)($precios[$i] ?? 0);
        $cos = trim((string)($costos[$i] ?? ''));

        if ($nom === '' || $uni <= 0 || $pre <= 0) continue;

        $stmt->bind_param("isids", $producto_id, $nom, $uni, $pre, $cos);
        $stmt->execute();
    }
}
