<?php
/* =========================
   LOTE MODELO (COMPLETO) + FIX CONCURRENCIA
   - ✅ Auto desactivar lotes sin stock
   - ✅ FIFO con bloqueo (FOR UPDATE) dentro de transacción
   - ✅ Activo consistente (si stock > 0 => activo=1)
   - ✅ Notificaciones: lotes vencidos activos
   - ✅ FIX FK: registrarMovimiento valida lote_id y existencia
   - ✅ FIX ID: guardarLote asegura insert_id > 0
   ========================= */

/* =========================
   ✅ AUTO: desactivar lotes sin stock
   ========================= */
function autoDesactivarLotesSinStock($conexion) {
    $sql = "UPDATE lotes
            SET activo = 0
            WHERE activo = 1
              AND cantidad_unidades <= 0";
    $conexion->query($sql);
}

/* =========================
   ✅ Guardar lote (activo=1 por defecto)
   ========================= */
function guardarLote($conexion, $producto_id, $fecha_vencimiento, $cantidad) {
    $producto_id = (int)$producto_id;
    $cantidad    = (int)$cantidad;
    $fecha_vencimiento = trim((string)$fecha_vencimiento);

    if ($producto_id <= 0) return false;
    if ($fecha_vencimiento === '') return false;
    if ($cantidad <= 0) return false;

    $sql = "INSERT INTO lotes (producto_id, fecha_vencimiento, cantidad_unidades, activo, fecha_ingreso)
            VALUES (?, ?, ?, 1, NOW())";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) return false;

    $stmt->bind_param("isi", $producto_id, $fecha_vencimiento, $cantidad);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) return false;

    $lote_id = (int)$conexion->insert_id;
    if ($lote_id <= 0) return false;

    return $lote_id;
}

/* =========================
   ✅ Select para combos
   ========================= */
function obtenerProductosSelect($conexion) {
    $sql = "SELECT id, nombre FROM productos WHERE activo=1 ORDER BY nombre ASC";
    return $conexion->query($sql);
}

/* =========================
   ✅ Listar lotes
   ========================= */
function obtenerLotes($conexion) {
    $sql = "
        SELECT l.*, p.nombre
        FROM lotes l
        JOIN productos p ON p.id = l.producto_id
        ORDER BY
          (l.fecha_vencimiento IS NOT NULL
           AND l.fecha_vencimiento <> '0000-00-00'
           AND l.fecha_vencimiento < CURDATE()) DESC,
          l.cantidad_unidades ASC,
          l.fecha_vencimiento ASC,
          l.id ASC
    ";
    return $conexion->query($sql);
}

/* =========================
   ✅ Stock total disponible
   (solo lotes activos + NO vencidos + con unidades)
   ========================= */
function obtenerStockDisponible($conexion, $producto_id) {
    $producto_id = (int)$producto_id;

    autoDesactivarLotesSinStock($conexion);

    $sql = "SELECT COALESCE(SUM(cantidad_unidades),0) AS total
            FROM lotes
            WHERE producto_id = ?
              AND activo = 1
              AND cantidad_unidades > 0
              AND (fecha_vencimiento IS NULL OR fecha_vencimiento = '0000-00-00' OR fecha_vencimiento >= CURDATE())";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($res['total'] ?? 0);
}

/* =========================
   ✅ NOTIF: lotes vencidos activos
   ========================= */
function obtenerLotesVencidosActivos($conexion) {
    $sql = "
        SELECT 
          l.id AS lote_id,
          l.producto_id,
          p.nombre AS producto_nombre,
          l.fecha_vencimiento,
          l.cantidad_unidades
        FROM lotes l
        INNER JOIN productos p ON p.id = l.producto_id
        WHERE l.activo = 1
          AND l.fecha_vencimiento < CURDATE()
        ORDER BY l.fecha_vencimiento ASC, l.id ASC
    ";
    $res = $conexion->query($sql);
    $out = [];
    if ($res) {
        while($row = $res->fetch_assoc()) $out[] = $row;
    }
    return $out;
}

/* =========================
   ✅ Descontar FIFO (CON FIX DE CONCURRENCIA)
   - Solo lotes activos + NO vencidos
   - ✅ BLOQUEA filas (FOR UPDATE) si estás dentro de begin_transaction()
   - ✅ Si stock final > 0 => activo=1
   - ✅ Si stock final = 0 => activo=0
   ========================= */
function descontarStockFIFO($conexion, $producto_id, $unidades_a_descontar, $venta_id) {
    $producto_id = (int)$producto_id;
    $unidades_a_descontar = (int)$unidades_a_descontar;

    if ($producto_id <= 0) return false;
    if ($unidades_a_descontar <= 0) return true;

    autoDesactivarLotesSinStock($conexion);

    // ✅ BLOQUEO DE FILAS (FOR UPDATE): Otros procesos esperarán aquí
    $sql = "SELECT id, cantidad_unidades
            FROM lotes
            WHERE producto_id = ?
              AND activo = 1
              AND cantidad_unidades > 0
              AND (fecha_vencimiento IS NULL OR fecha_vencimiento = '0000-00-00' OR fecha_vencimiento >= CURDATE())
            ORDER BY fecha_vencimiento ASC, fecha_ingreso ASC
            FOR UPDATE";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    // 1. VALIDACIÓN DE SUFICIENCIA (Sin tocar la base de datos aún)
    $stock_acumulado = 0;
    $filas_bloqueadas = [];
    while ($row = $result->fetch_assoc()) {
        $stock_acumulado += (int)$row['cantidad_unidades'];
        $filas_bloqueadas[] = $row;
    }

    // Si el stock bloqueado no alcanza, fallamos inmediatamente sin cambios parciales
    if ($stock_acumulado < $unidades_a_descontar) {
        return false;
    }

    // 2. PROCESAMIENTO (Solo llegamos aquí si hay stock suficiente)
    $descontado_total = 0;
    $restante = $unidades_a_descontar;

    foreach ($filas_bloqueadas as $row) {
        if ($restante <= 0) break;

        $lote_id = (int)$row['id'];
        $disp    = (int)$row['cantidad_unidades'];
        $restar  = min($disp, $restante);

        // ✅ UPDATE atómico por lote
        // IMPORTANTE: MySQL evalúa SET en orden, por lo que en el IF
        // `cantidad_unidades` ya tiene el valor nuevo (después de restar).
        // Correcto: desactivar si el nuevo valor es <= 0, no comparar con $restar.
        $up = $conexion->prepare("
            UPDATE lotes
            SET cantidad_unidades = cantidad_unidades - ?,
                activo = IF(cantidad_unidades <= 0, 0, 1)
            WHERE id = ?
        ");
        $up->bind_param("ii", $restar, $lote_id);
        $up->execute();
        $up->close();

        // ✅ Movimiento (Integración V2: Registro relacional)
        registrarMovimiento(
            $conexion,
            $producto_id,
            $lote_id,
            'salida',
            -$restar,
            'Venta ID ' . $venta_id,
            $venta_id, // referencia_id
            'venta'    // referencia_tipo
        );

        $restante         -= $restar;
        $descontado_total     += $restar;
    }

    // 3. ACTUALIZACIÓN SÍNCRONA DE PRODUCTO (Dentro de la misma transacción)
    if ($descontado_total > 0) {
        $sqlp = "UPDATE productos SET stock_actual = GREATEST(stock_actual - ?, 0) WHERE id = ?";
        $stmtp = $conexion->prepare($sqlp);
        $stmtp->bind_param("ii", $descontado_total, $producto_id);
        $stmtp->execute();
        $stmtp->close();
    }

    autoDesactivarLotesSinStock($conexion);
    return true;
}

/* =========================
   ✅ Desactivar un lote (manual)
   ========================= */
function desactivarLote($conexion, $lote_id) {
    $lote_id = (int)$lote_id;
    if ($lote_id <= 0) return false;

    $lote = obtenerLotePorId($conexion, $lote_id);
    if ($lote && (int)$lote['cantidad_unidades'] > 0) {
        $cant_lote  = (int)$lote['cantidad_unidades'];
        $prod_id    = (int)$lote['producto_id'];

        registrarMovimiento(
            $conexion,
            $prod_id,
            $lote_id,
            'ajuste',
            -$cant_lote,
            'Desactivación de lote'
        );

        // ✅ Sincronizar productos.stock_actual (GREATEST evita negativos)
        $stmtp = $conexion->prepare(
            "UPDATE productos SET stock_actual = GREATEST(stock_actual - ?, 0) WHERE id = ?"
        );
        $stmtp->bind_param("ii", $cant_lote, $prod_id);
        $stmtp->execute();
        $stmtp->close();
    }

    $sql = "UPDATE lotes 
            SET cantidad_unidades = 0, activo = 0 
            WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $lote_id);
    $ok = $stmt->execute();
    $stmt->close();

    autoDesactivarLotesSinStock($conexion);
    return $ok;
}

/* =========================
   ✅ Activar lote
   ========================= */
function activarLote($conexion, $lote_id) {
    $lote_id = (int)$lote_id;
    if ($lote_id <= 0) return false;

    $lote = obtenerLotePorId($conexion, $lote_id);
    if (!$lote) return false;

    $cant    = (int)$lote['cantidad_unidades'];
    $prod_id = (int)$lote['producto_id'];

    $sql  = "UPDATE lotes SET activo = 1 WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $lote_id);
    $ok = $stmt->execute();
    $stmt->close();

    // Solo sumamos a stock_actual si hay un movimiento de desactivación previo
    // que haya restado esa cantidad. Si el lote se desactivó por el bug de MySQL
    // SET (activo=0 sin tocar stock_actual), sumar aquí inflaría el stock.
    if ($ok && $cant > 0 && $prod_id > 0) {
        $chk = $conexion->prepare(
            "SELECT COUNT(*) AS n FROM movimientos_inventario
             WHERE lote_id = ? AND tipo = 'ajuste' AND motivo = 'Desactivación de lote'"
        );
        $chk->bind_param("i", $lote_id);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();

        // Solo si existe el movimiento de desactivación, el stock_actual fue restado → sumamos de vuelta
        if ((int)($row['n'] ?? 0) > 0) {
            $stmtp = $conexion->prepare(
                "UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?"
            );
            $stmtp->bind_param("ii", $cant, $prod_id);
            $stmtp->execute();
            $stmtp->close();
        }
    }

    return $ok;
}

/* =========================
   Obtener / actualizar lote
   ========================= */
function obtenerLotePorId($conexion, $id) {
    $id = (int)$id;
    $sql = "SELECT * FROM lotes WHERE id = ? LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function actualizarFechaLote($conexion, $id, $fecha_vencimiento) {
    $id = (int)$id;
    $sql = "UPDATE lotes SET fecha_vencimiento = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $fecha_vencimiento, $id);
    $stmt->execute();
    $stmt->close();
}

function actualizarCantidadLote($conexion, $id, $nueva_cantidad) {
    $id             = (int)$id;
    $nueva_cantidad = (int)$nueva_cantidad;

    // ✅ Leer estado actual para calcular diferencia
    $lote_actual = obtenerLotePorId($conexion, $id);
    $cant_anterior = $lote_actual ? (int)$lote_actual['cantidad_unidades'] : 0;
    $prod_id       = $lote_actual ? (int)$lote_actual['producto_id']       : 0;

    $sql = "UPDATE lotes
            SET cantidad_unidades = ?,
                activo = CASE WHEN ? <= 0 THEN 0 ELSE 1 END
            WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iii", $nueva_cantidad, $nueva_cantidad, $id);
    $stmt->execute();
    $stmt->close();

    // ✅ Registrar movimiento y sincronizar stock_actual solo si hubo cambio real
    $diff = $nueva_cantidad - $cant_anterior;
    if ($diff !== 0 && $prod_id > 0) {
        $tipo   = $diff > 0 ? 'entrada' : 'ajuste';
        registrarMovimiento($conexion, $prod_id, $id, $tipo, $diff, 'Corrección de cantidad lote');

        // GREATEST evita negativos si stock_actual ya estaba desincrónizado
        $stmtp = $conexion->prepare(
            "UPDATE productos SET stock_actual = GREATEST(stock_actual + ?, 0) WHERE id = ?"
        );
        $stmtp->bind_param("ii", $diff, $prod_id);
        $stmtp->execute();
        $stmtp->close();
    }

    // Si el lote quedó con stock positivo, reactivar el producto si estaba inactivo
    // (puede quedar inactivo por el fallback de eliminación fallida)
    if ($nueva_cantidad > 0 && $prod_id > 0) {
        $stmtp = $conexion->prepare(
            "UPDATE productos SET activo = 1 WHERE id = ? AND activo = 0"
        );
        $stmtp->bind_param("i", $prod_id);
        $stmtp->execute();
        $stmtp->close();
    }

    autoDesactivarLotesSinStock($conexion);
}

/* =========================
   Movimientos inventario
   ========================= */
function registrarMovimiento($conexion, $producto_id, $lote_id, $tipo, $cantidad, $motivo, $ref_id = null, $ref_tipo = null) {
    $producto_id = (int)$producto_id;
    $lote_id     = (int)$lote_id;
    $tipo        = (string)$tipo;
    $cantidad    = (int)$cantidad;
    $motivo      = (string)$motivo;

    if ($producto_id <= 0 || $lote_id <= 0) {
        return false;
    }

    $chk = $conexion->prepare("SELECT id FROM lotes WHERE id = ? LIMIT 1");
    if (!$chk) return false;
    $chk->bind_param("i", $lote_id);
    $chk->execute();
    $existe = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$existe) {
        return false;
    }

    $sql = "INSERT INTO movimientos_inventario 
            (producto_id, lote_id, tipo, cantidad, motivo, referencia_id, referencia_tipo)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) return false;

    $stmt->bind_param("iisisis", $producto_id, $lote_id, $tipo, $cantidad, $motivo, $ref_id, $ref_tipo);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/* =========================
   ✅ DEVOLUCIONES
   ========================= */
function devolverStockProductoDesdeVenta($conexion, $venta_id, $producto_id, $unidades_devolver) {
    $venta_id = (int)$venta_id;
    $producto_id = (int)$producto_id;
    $unidades_devolver = (int)$unidades_devolver;

    if ($venta_id <= 0 || $producto_id <= 0) return false;
    if ($unidades_devolver <= 0) return true;

    // ✅ INTEGRIDAD V2: Ya no dependemos de comparar texto en 'motivo'
    $sql = "
        SELECT id, lote_id, cantidad
        FROM movimientos_inventario
        WHERE producto_id = ?
          AND tipo = 'salida'
          AND referencia_id = ?
          AND referencia_tipo = 'venta'
        ORDER BY id DESC
    ";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $producto_id, $venta_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    // ✅ CONTROL DE DATOS: Si no hay movimientos relacionales, la anulación debe fallar
    // Esto asegura que no devolvemos stock si no hay trazabilidad clara.
    if ($res->num_rows === 0) return false;

    $devuelto_total = 0;
    $unidades_restantes = $unidades_devolver;

    while ($row = $res->fetch_assoc()) {
        if ($unidades_restantes <= 0) break;

        $lote_id = (int)$row["lote_id"];
        $salio   = abs((int)$row["cantidad"]);

        if ($lote_id <= 0 || $salio <= 0) continue;

        $a_devolver = min($salio, $unidades_restantes);

        // ✅ PROTECCIÓN: Solo reactivamos (activo=1) si el lote NO ha vencido
        // Lotes sin fecha (NULL o '0000-00-00') se consideran válidos (sin vencimiento)
        $up = $conexion->prepare("
            UPDATE lotes
            SET cantidad_unidades = cantidad_unidades + ?,
                activo = IF(fecha_vencimiento IS NULL OR fecha_vencimiento = '0000-00-00' OR fecha_vencimiento >= CURDATE(), 1, 0)
            WHERE id = ?
        ");
        $up->bind_param("ii", $a_devolver, $lote_id);
        $up->execute();
        $up->close();

        registrarMovimiento(
            $conexion,
            $producto_id,
            $lote_id,
            'entrada',
            $a_devolver,
            'Devolución Venta ID ' . $venta_id,
            $venta_id, // referencia_id
            'venta'    // referencia_tipo
        );

        $unidades_restantes -= $a_devolver;
        $devuelto_total     += $a_devolver;
    }

    if ($devuelto_total > 0) {
        $sqlp = "UPDATE productos SET stock_actual = COALESCE(stock_actual, 0) + ? WHERE id = ?";
        $stmtp = $conexion->prepare($sqlp);
        $stmtp->bind_param("ii", $devuelto_total, $producto_id);
        $stmtp->execute();
        $stmtp->close();
    }

    autoDesactivarLotesSinStock($conexion);
    return ($unidades_restantes <= 0);
}

function devolverStockCompletoVenta($conexion, $venta_id) {
    $venta_id = (int)$venta_id;
    if ($venta_id <= 0) return false;

    $stmt = $conexion->prepare("
        SELECT producto_id, SUM(unidades_reales) AS uds
        FROM detalle_venta
        WHERE venta_id = ?
        GROUP BY producto_id
    ");
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    while ($row = $res->fetch_assoc()) {
        $pid = (int)$row["producto_id"];
        $uds = (int)$row["uds"];
        if ($pid > 0 && $uds > 0) {
            $ok = devolverStockProductoDesdeVenta($conexion, $venta_id, $pid, $uds);
            if (!$ok) return false;
        }
    }
    return true;
}

/* =========================
   ✅ Corregir producto del lote (igual que tenías)
   ========================= */
function corregirProductoDeLote($conexion, $lote_id, $nuevo_producto_id) {
    $lote_id = (int)$lote_id;
    $nuevo_producto_id = (int)$nuevo_producto_id;

    if ($lote_id <= 0 || $nuevo_producto_id <= 0) return false;

    $lote = obtenerLotePorId($conexion, $lote_id);
    if (!$lote) return false;

    $producto_actual = (int)($lote['producto_id'] ?? 0);
    if ($producto_actual <= 0) return false;

    if ($producto_actual === $nuevo_producto_id) return true;

    $cant = (int)$lote['cantidad_unidades'];

    $stmt = $conexion->prepare("UPDATE lotes SET producto_id = ? WHERE id = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param("ii", $nuevo_producto_id, $lote_id);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) return false;

    // ✅ Sincronizar stock_actual de ambos productos si el lote tenía unidades
    if ($cant > 0) {
        // Producto anterior: restar (GREATEST evita negativos)
        $stmtA = $conexion->prepare(
            "UPDATE productos SET stock_actual = GREATEST(stock_actual - ?, 0) WHERE id = ?"
        );
        $stmtA->bind_param("ii", $cant, $producto_actual);
        $stmtA->execute();
        $stmtA->close();

        // Producto nuevo: sumar
        $stmtB = $conexion->prepare(
            "UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?"
        );
        $stmtB->bind_param("ii", $cant, $nuevo_producto_id);
        $stmtB->execute();
        $stmtB->close();
    }

    // ✅ Movimiento con cantidad real (antes era 0)
    registrarMovimiento(
        $conexion,
        $nuevo_producto_id,
        $lote_id,
        'ajuste',
        $cant,
        "Corrección: lote movido de producto $producto_actual a $nuevo_producto_id"
    );

    return true;
}