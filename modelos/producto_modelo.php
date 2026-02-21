<?php

/* =========================
   Helpers
   ========================= */
function normalizarCosto($valor) {
    if ($valor === '' || $valor === null) return null;
    if (!is_numeric($valor)) return null;
    $v = (float)$valor;
    return ($v > 0) ? $v : null;
}

/* =========================
   PRODUCTOS (base)
   ========================= */

function guardarProducto($conexion, $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete, $costo_unidad = null) {

    $precio_unidad      = (is_numeric($precio_unidad) ? (float)$precio_unidad : 0.00);
    $precio_paquete     = (is_numeric($precio_paquete) ? (float)$precio_paquete : 0.00);
    $unidades_paquete   = (is_numeric($unidades_paquete) && (int)$unidades_paquete > 0) ? (int)$unidades_paquete : 1;
    $costo_unidad       = normalizarCosto($costo_unidad);

    $sql = "INSERT INTO productos (nombre, descripcion, precio_unidad, precio_paquete, unidades_por_paquete, costo_unidad)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) return 0;

    // ✅ FIX: costo_unidad NULL seguro
    // Truco estable: si es null, mandamos NULL con 's' (string) y variable null.
    // Si no es null, lo mandamos como double.
    if ($costo_unidad === null) {
        $null = null;
        $stmt->bind_param("ssddis", $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete, $null);
    } else {
        // costo como string para evitar problemas raros de NULL/float en bind (estable en MariaDB)
        $cos = (string)$costo_unidad;
        $stmt->bind_param("ssddis", $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete, $cos);
    }

    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) return 0;
    return (int)$conexion->insert_id;
}

function obtenerProductos($conexion, $soloActivos = false) {
    if ($soloActivos) {
        return $conexion->query("SELECT * FROM productos WHERE activo=1 ORDER BY nombre ASC");
    }
    return $conexion->query("SELECT * FROM productos ORDER BY nombre ASC");
}

function obtenerProductoPorId($conexion, $id) {
    $id = (int)$id;
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE id=? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function actualizarProducto($conexion, $id, $nombre, $precio_unidad, $precio_paquete, $activo, $costo_unidad = null) {
    $id             = (int)$id;
    $activo         = (int)$activo;
    $precio_unidad  = (is_numeric($precio_unidad) ? (float)$precio_unidad : 0.00);
    $precio_paquete = (is_numeric($precio_paquete) ? (float)$precio_paquete : 0.00);
    $costo_unidad   = normalizarCosto($costo_unidad);

    $sql = "UPDATE productos
            SET nombre=?, precio_unidad=?, precio_paquete=?, activo=?, costo_unidad=?
            WHERE id=?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) return false;

    // ✅ FIX NULL costo
    if ($costo_unidad === null) {
        $null = null;
        $stmt->bind_param("sddiss", $nombre, $precio_unidad, $precio_paquete, $activo, $null, $id);
    } else {
        $cos = (string)$costo_unidad;
        $stmt->bind_param("sddiss", $nombre, $precio_unidad, $precio_paquete, $activo, $cos, $id);
    }

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/* =========================
   ✅ CAMBIO: SOLO VENDIBLES (con stock real por lotes)
   + ✅ Reparación: reactivar lotes con stock>0 que quedaron inactivos (no vencidos)
   ========================= */
function obtenerProductosVendibles($conexion) {

    $res = $conexion->query("SELECT id FROM productos WHERE activo=1 ORDER BY nombre ASC");
    if (!$res) return $conexion->query("SELECT * FROM productos WHERE 1=0");

    $ids = [];

    while ($r = $res->fetch_assoc()) {
        $pid = (int)$r["id"];

        // ✅ Reparar lotes “apagados con stock” (esto corrige tu caso del cigarro)
        // Solo NO vencidos y con stock > 0.
        $fix = $conexion->prepare("
            UPDATE lotes
            SET activo = 1
            WHERE producto_id = ?
              AND activo = 0
              AND cantidad_unidades > 0
              AND fecha_vencimiento >= CURDATE()
        ");
        if ($fix) {
            $fix->bind_param("i", $pid);
            $fix->execute();
            $fix->close();
        }

        // ✅ Filtrar por stock disponible real (requiere lote_modelo incluido donde se llame)
        if (function_exists('obtenerStockDisponible')) {
            $stock = (int) obtenerStockDisponible($conexion, $pid);
            if ($stock > 0) $ids[] = $pid;
        } else {
            // si no existe la función, por seguridad NO filtramos
            $ids[] = $pid;
        }
    }

    if (count($ids) === 0) {
        return $conexion->query("SELECT * FROM productos WHERE 1=0");
    }

    $in = implode(',', array_map('intval', $ids));
    return $conexion->query("SELECT * FROM productos WHERE id IN ($in) ORDER BY nombre ASC");
}

/* =========================
   STOCK (unidades) - legado
   ========================= */
function obtenerStockTotal($conexion, $producto_id) {
    $producto_id = (int)$producto_id;
    $sql = "SELECT COALESCE(SUM(cantidad_unidades),0) AS total_stock
            FROM lotes
            WHERE producto_id=? AND activo=1";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) return 0;

    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row['total_stock'] ?? 0);
}

/* =========================
   PRESENTACIONES (packs)
   ========================= */

function obtenerPresentacionesPorProducto($conexion, $producto_id) {
    $producto_id = (int)$producto_id;
    $stmt = $conexion->prepare("
        SELECT id, producto_id, nombre, unidades, precio_venta, costo, activa
        FROM producto_presentaciones
        WHERE producto_id=? AND activa=1
        ORDER BY unidades ASC
    ");
    if (!$stmt) return [];

    $stmt->bind_param("i", $producto_id);
    $stmt->execute();

    $out = [];
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $out[] = $row;

    $stmt->close();
    return $out;
}

function eliminarPresentacionesProducto($conexion, $producto_id) {
    $producto_id = (int)$producto_id;

    $stmt = $conexion->prepare("UPDATE producto_presentaciones SET activa=0 WHERE producto_id=?");
    if (!$stmt) return;
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $stmt->close();
}

function guardarPresentacionesProducto($conexion, $producto_id, $nombres, $unidades, $precios, $costos) {
    $producto_id = (int)$producto_id;

    if (!is_array($nombres) || !is_array($unidades) || !is_array($precios)) return;

    $stmt = $conexion->prepare("
        INSERT INTO producto_presentaciones (producto_id, nombre, unidades, precio_venta, costo, activa)
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    if (!$stmt) return;

    $count = count($nombres);
    for ($i = 0; $i < $count; $i++) {
        $nom = trim((string)($nombres[$i] ?? ''));
        $uni = (int)($unidades[$i] ?? 0);
        $pre = (float)($precios[$i] ?? 0);
        $cos = normalizarCosto($costos[$i] ?? null);

        if ($nom === '' || $uni <= 0 || $pre <= 0) continue;

        // costo como string (o null) estable
        $cosSend = ($cos === null) ? null : (string)$cos;

        $stmt->bind_param("isids", $producto_id, $nom, $uni, $pre, $cosSend);
        $stmt->execute();
    }

    $stmt->close();
}