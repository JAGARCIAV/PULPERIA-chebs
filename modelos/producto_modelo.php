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

    $precio_paquete     = (is_numeric($precio_paquete) ? (float)$precio_paquete : 0.00);
    $unidades_paquete   = (is_numeric($unidades_paquete) && (int)$unidades_paquete > 0) ? (int)$unidades_paquete : 1;
    $costo_unidad       = normalizarCosto($costo_unidad);

    // ✅ 1 solo INSERT, costo puede ser NULL
    $sql = "INSERT INTO productos (nombre, descripcion, precio_unidad, precio_paquete, unidades_por_paquete, costo_unidad)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);

    // OJO: para NULL, mysqli lo acepta si la variable es null
    $stmt->bind_param("ssddid", $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete, $costo_unidad);

    if (!$stmt->execute()) return 0;
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
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function actualizarProducto($conexion, $id, $nombre, $precio_unidad, $precio_paquete, $activo, $costo_unidad = null) {
    $id             = (int)$id;
    $activo         = (int)$activo;
    $precio_paquete = (is_numeric($precio_paquete) ? (float)$precio_paquete : 0.00);
    $costo_unidad   = normalizarCosto($costo_unidad);

    $sql = "UPDATE productos
            SET nombre=?, precio_unidad=?, precio_paquete=?, activo=?, costo_unidad=?
            WHERE id=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sddidi", $nombre, $precio_unidad, $precio_paquete, $activo, $costo_unidad, $id);
    return $stmt->execute();
}

/* =========================
   ✅ CAMBIO: SOLO VENDIBLES (con stock real por lotes)
   =========================
   - Requiere que exista obtenerStockDisponible() en lote_modelo.php
   - Devuelve un mysqli_result “falso” (array) NO, devuelve array simple
   - Para no romper tu venta.php que hace ->data_seek() y ->fetch_assoc(),
     aquí lo devolvemos como RESULTSET real usando una query por IDs.
*/

function obtenerProductosVendibles($conexion) {
    // ✅ Cargamos activos
    $res = $conexion->query("SELECT * FROM productos WHERE activo=1 ORDER BY nombre ASC");

    if (!$res) return $conexion->query("SELECT * FROM productos WHERE 1=0"); // vacío

    // ✅ Filtrar por stock disponible real (lotes activos, no vencidos)
    $ids = [];
    $rows = [];

    while ($p = $res->fetch_assoc()) {
        $pid = (int)$p['id'];

        // ⚠️ necesita lote_modelo.php incluido donde se llama
        if (function_exists('obtenerStockDisponible')) {
            $stock = (int) obtenerStockDisponible($conexion, $pid);
            if ($stock > 0) {
                $ids[] = $pid;
            }
        } else {
            // si no existe la función, por seguridad NO filtramos
            $ids[] = $pid;
        }
    }

    if (count($ids) === 0) {
        return $conexion->query("SELECT * FROM productos WHERE 1=0");
    }

    // ✅ devolvemos resultset real (para que tu venta.php funcione igual)
    $in = implode(',', array_map('intval', $ids));
    return $conexion->query("SELECT * FROM productos WHERE id IN ($in) ORDER BY nombre ASC");
}

/* =========================
   STOCK (unidades)
   ========================= */

function obtenerStockTotal($conexion, $producto_id) {
    $producto_id = (int)$producto_id;
    $sql = "SELECT COALESCE(SUM(cantidad_unidades),0) AS total_stock
            FROM lotes
            WHERE producto_id=? AND activo=1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
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
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();

    $out = [];
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $out[] = $row;
    return $out;
}

function eliminarPresentacionesProducto($conexion, $producto_id) {
    $producto_id = (int)$producto_id;

    // ✅ opción PRO (recomendado): desactivar en vez de borrar
    $stmt = $conexion->prepare("UPDATE producto_presentaciones SET activa=0 WHERE producto_id=?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
}

function guardarPresentacionesProducto($conexion, $producto_id, $nombres, $unidades, $precios, $costos) {
    $producto_id = (int)$producto_id;

    if (!is_array($nombres) || !is_array($unidades) || !is_array($precios)) return;

    $stmt = $conexion->prepare("
        INSERT INTO producto_presentaciones (producto_id, nombre, unidades, precio_venta, costo, activa)
        VALUES (?, ?, ?, ?, ?, 1)
    ");

    $count = count($nombres);
    for ($i = 0; $i < $count; $i++) {
        $nom = trim((string)($nombres[$i] ?? ''));
        $uni = (int)($unidades[$i] ?? 0);
        $pre = (float)($precios[$i] ?? 0);

        // ✅ costo opcional consistente
        $cos = normalizarCosto($costos[$i] ?? null);

        if ($nom === '' || $uni <= 0 || $pre <= 0) continue;

        $stmt->bind_param("isidd", $producto_id, $nom, $uni, $pre, $cos);
        $stmt->execute();
    }
}
