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

/* =========================================================
   REGLA ÚNICA DE PRODUCTO VENDIBLE (PURA LECTURA)
   - Producto activo
   - Lote activo y con unidades
   - Fecha de vencimiento válida (>= hoy)
   ========================================================= */
function obtenerProductosVendibles($conexion, $termino = '', $limite = null) {
    $params = [];
    $types = "";
    $where = " WHERE p.activo = 1 AND l.activo = 1 AND l.cantidad_unidades > 0 AND l.fecha_vencimiento >= CURDATE() ";

    if ($termino !== '') {
        $where .= " AND p.nombre LIKE ? ";
        $params[] = "%$termino%";
        $types .= "s";
    }

    $limitSql = "";
    if ($limite !== null) {
        $limitSql = " LIMIT ? ";
        $params[] = (int)$limite;
        $types .= "i";
    }

    $sql = "
        SELECT 
            p.id, p.nombre, p.imagen, p.precio_unidad,
            SUM(l.cantidad_unidades) AS stock_total
        FROM productos p
        INNER JOIN lotes l ON l.producto_id = p.id
        $where
        GROUP BY p.id
        HAVING stock_total > 0
        ORDER BY p.nombre ASC
        $limitSql
    ";

    $stmt = $conexion->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    return $conexion->query($sql);
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

/* =========================
   ✅ ELIMINAR PRODUCTO + LOTES (seguro)
   Tablas confirmadas:
   - productos(id, imagen, activo, ...)
   - lotes(id, producto_id, activo, ...)
   - producto_presentaciones (opcional, si existe)
   ========================= */
function eliminarProductoConLotesSeguro(mysqli $conexion, int $producto_id): array {

    // 1) Obtener datos del producto (imagen + si tiene ventas asociadas)
    $stmt = $conexion->prepare("SELECT imagen FROM productos WHERE id=? LIMIT 1");
    if (!$stmt) return ['ok' => false, 'msg' => 'No se pudo preparar consulta'];
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['ok' => false, 'msg' => 'Producto no existe'];
    }

    $imgRel = trim((string)($row['imagen'] ?? ''));

    // 2) Verificar si el producto tiene ventas asociadas ANTES de intentar borrar
    // Si tiene ventas no podemos borrarlo, solo ocultarlo (activo=0)
    // pero NO tocamos los lotes para no perder stock
    $chkVenta = $conexion->prepare("
        SELECT COUNT(*) AS cnt
        FROM detalle_venta dv
        WHERE dv.producto_id = ?
        LIMIT 1
    ");
    $tieneVentas = false;
    if ($chkVenta) {
        $chkVenta->bind_param("i", $producto_id);
        $chkVenta->execute();
        $resChk = $chkVenta->get_result()->fetch_assoc();
        $chkVenta->close();
        $tieneVentas = ((int)($resChk['cnt'] ?? 0)) > 0;
    }

    // 3) Si tiene ventas: solo desactivar el PRODUCTO, NO tocar los lotes
    // Esto evita el bug donde el fallback apagaba lotes con stock real
    if ($tieneVentas) {
        $up = $conexion->prepare("UPDATE productos SET activo=0 WHERE id=? LIMIT 1");
        if ($up) {
            $up->bind_param("i", $producto_id);
            $up->execute();
            $up->close();
        }
        // NO desactivamos lotes — si tiene ventas, probablemente tiene historial
        // y los lotes son datos reales de stock que NO deben perderse
        return [
            'ok'  => true,
            'msg' => 'El producto tiene ventas registradas y no puede borrarse. Fue desactivado (oculto) para no romper el historial. Sus lotes NO fueron tocados.'
        ];
    }

    // 4) Sin ventas: intentar borrado REAL en transacción
    $conexion->begin_transaction();

    try {
        // Borrar presentaciones si existen
        $delPres = $conexion->prepare("DELETE FROM producto_presentaciones WHERE producto_id=?");
        if ($delPres) {
            $delPres->bind_param("i", $producto_id);
            $delPres->execute();
            $delPres->close();
        }

        // Borrar lotes
        $delL = $conexion->prepare("DELETE FROM lotes WHERE producto_id=?");
        if (!$delL) {
            $conexion->rollback();
            return ['ok' => false, 'msg' => 'No se pudo preparar delete lotes'];
        }
        $delL->bind_param("i", $producto_id);
        $delL->execute();
        $delL->close();

        // Borrar producto
        $delP = $conexion->prepare("DELETE FROM productos WHERE id=? LIMIT 1");
        if (!$delP) {
            $conexion->rollback();
            return ['ok' => false, 'msg' => 'No se pudo preparar delete producto'];
        }
        $delP->bind_param("i", $producto_id);
        $delP->execute();
        $aff = (int)$delP->affected_rows;
        $delP->close();

        if ($aff <= 0) {
            $conexion->rollback();
            return ['ok' => false, 'msg' => 'No se eliminó el producto'];
        }

        $conexion->commit();

        // Borrar imagen física si está en uploads/productos/
        if ($imgRel !== '') {
            $imgRelClean = ltrim($imgRel, '/');
            if (strpos($imgRelClean, 'uploads/productos/') === 0) {
                $rutaFs = __DIR__ . "/../" . $imgRelClean;
                if (is_file($rutaFs)) {
                    @unlink($rutaFs);
                }
            }
        }

        return ['ok' => true, 'msg' => 'Producto eliminado correctamente (y sus lotes también).'];

    } catch (Throwable $e) {
        $conexion->rollback();

        // Fallback de último recurso: si por alguna FK inesperada falló,
        // solo desactivar el producto, NUNCA los lotes
        $up = $conexion->prepare("UPDATE productos SET activo=0 WHERE id=? LIMIT 1");
        if ($up) {
            $up->bind_param("i", $producto_id);
            $up->execute();
            $up->close();
        }

        return [
            'ok'  => true,
            'msg' => 'No se pudo eliminar por restricciones de la base de datos. Producto desactivado. Los lotes conservan su stock.'
        ];
    }
}

function obtenerProductosConStock($conexion, $soloActivos = true) {
    // ✅ FIX: default cambiado a true.
    // Antes era false, entonces listar.php (que llama sin parámetro) mostraba
    // también productos con activo=0 — incluyendo el producto 499 que quedó
    // desactivado por el fallback de eliminar. Ahora el default es true y
    // listar.php solo ve productos activos, igual que caja.
    $where = $soloActivos ? "WHERE p.activo=1" : "";

    $sql = "
      SELECT
        p.*,
        COALESCE(SUM(CASE WHEN l.activo=1 THEN l.cantidad_unidades ELSE 0 END), 0) AS stock_total
      FROM productos p
      LEFT JOIN lotes l ON l.producto_id = p.id
      $where
      GROUP BY p.id
      ORDER BY p.nombre ASC
    ";

    return $conexion->query($sql);
}