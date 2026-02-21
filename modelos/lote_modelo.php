<?php
/* =========================
   LOTE MODELO (COMPLETO) + CAMBIOS
   - ✅ Auto desactivar lotes sin stock
   - ✅ Descontar FIFO: desactiva lote automáticamente al quedar en 0
   - ✅ Notificaciones: obtener lotes vencidos activos
   - ✅ Limpieza: eliminé duplicados (crearLote / desactivarLotes) para evitar bugs
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
    @mysqli_query($conexion, $sql);
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

    if (!$ok) return false;

    // ✅ asegurar ID real
    $lote_id = (int)$conexion->insert_id;
    if ($lote_id <= 0) return false;

    // ✅ Limpieza inmediata
    autoDesactivarLotesSinStock($conexion);

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
   ✅ Listar lotes (orden: vencidos arriba, menos stock primero)
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

    // ✅ Limpieza por si hay lotes en 0 aún activos
    autoDesactivarLotesSinStock($conexion);

    $sql = "SELECT COALESCE(SUM(cantidad_unidades),0) AS total
            FROM lotes
            WHERE producto_id = ?
              AND activo = 1
              AND cantidad_unidades > 0
              AND fecha_vencimiento >= CURDATE()";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    return (int)($res['total'] ?? 0);
}

/* =========================
   ✅ NOTIF: obtener lotes vencidos activos (para popup)
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
   ✅ Descontar FIFO
   - Solo lotes activos + NO vencidos
   - ✅ Si lote queda en 0 => activo=0 automáticamente
   ========================= */
function descontarStockFIFO($conexion, $producto_id, $unidades_a_descontar, $venta_id) {
    $producto_id = (int)$producto_id;
    $unidades_a_descontar = (int)$unidades_a_descontar;

    if ($producto_id <= 0) return false;
    if ($unidades_a_descontar <= 0) return true;

    // ✅ Limpieza previa
    autoDesactivarLotesSinStock($conexion);

    // Tomar lotes FIFO por vencimiento primero, luego por ingreso
    $sql = "SELECT id, cantidad_unidades
            FROM lotes
            WHERE producto_id = ?
              AND activo = 1
              AND cantidad_unidades > 0
              AND fecha_vencimiento >= CURDATE()
            ORDER BY fecha_vencimiento ASC, fecha_ingreso ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $lotes = $stmt->get_result();

    $descontado_total = 0;

    while ($row = $lotes->fetch_assoc()) {
        if ($unidades_a_descontar <= 0) break;

        $lote_id = (int)$row['id'];
        $disp    = (int)$row['cantidad_unidades'];

        $restar = min($disp, $unidades_a_descontar);

        // ✅ CAMBIO: descuenta y si queda <= 0 desactiva el lote
        $up = $conexion->prepare("
            UPDATE lotes
            SET cantidad_unidades = GREATEST(cantidad_unidades - ?, 0),
                activo = CASE WHEN (cantidad_unidades - ?) <= 0 THEN 0 ELSE activo END
            WHERE id = ?
        ");
        $up->bind_param("iii", $restar, $restar, $lote_id);
        $up->execute();

        registrarMovimiento(
            $conexion,
            $producto_id,
            $lote_id,
            'salida',
            -$restar,
            'Venta ID ' . $venta_id
        );

        $unidades_a_descontar -= $restar;
        $descontado_total     += $restar;
    }

    // ✅ actualizar contador rápido en productos.stock_actual (si lo estás usando en otros lados)
    if ($descontado_total > 0) {
        $sqlp = "UPDATE productos SET stock_actual = GREATEST(stock_actual - ?, 0) WHERE id = ?";
        $stmtp = $conexion->prepare($sqlp);
        $stmtp->bind_param("ii", $descontado_total, $producto_id);
        $stmtp->execute();
    }

    // ✅ Limpieza final
    autoDesactivarLotesSinStock($conexion);

    return $unidades_a_descontar <= 0;
}

/* =========================
   ✅ Desactivar un lote
   ========================= */
function desactivarLote($conexion, $lote_id) {
    $lote_id = (int)$lote_id;
    if ($lote_id <= 0) return false;

    // ✅ Si tiene unidades, registramos ajuste para auditoría
    $lote = obtenerLotePorId($conexion, $lote_id);
    if ($lote && (int)$lote['cantidad_unidades'] > 0) {
        registrarMovimiento(
            $conexion,
            (int)$lote['producto_id'],
            $lote_id,
            'ajuste',
            -((int)$lote['cantidad_unidades']),
            'Desactivación de lote'
        );
    }

    $sql = "UPDATE lotes 
            SET cantidad_unidades = 0, activo = 0 
            WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $lote_id);
    $ok = $stmt->execute();

    autoDesactivarLotesSinStock($conexion);

    return $ok;
}

/* =========================
   ✅ Activar lote
   ========================= */
function activarLote($conexion, $lote_id) {
    $lote_id = (int)$lote_id;
    if ($lote_id <= 0) return false;

    $sql = "UPDATE lotes SET activo = 1 WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $lote_id);
    return $stmt->execute();
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
    return $stmt->get_result()->fetch_assoc();
}

function actualizarFechaLote($conexion, $id, $fecha_vencimiento) {
    $id = (int)$id;
    $sql = "UPDATE lotes SET fecha_vencimiento = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $fecha_vencimiento, $id);
    $stmt->execute();
}

function actualizarCantidadLote($conexion, $id, $nueva_cantidad) {
    $id = (int)$id;
    $nueva_cantidad = (int)$nueva_cantidad;

    $sql = "UPDATE lotes 
            SET cantidad_unidades = ?, 
                activo = CASE WHEN ? <= 0 THEN 0 ELSE activo END
            WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iii", $nueva_cantidad, $nueva_cantidad, $id);
    $stmt->execute();

    autoDesactivarLotesSinStock($conexion);
}

/* =========================
   Movimientos inventario
   ========================= */
function registrarMovimiento($conexion, $producto_id, $lote_id, $tipo, $cantidad, $motivo) {
    $producto_id = (int)$producto_id;
    $lote_id     = (int)$lote_id;
    $tipo        = (string)$tipo;
    $cantidad    = (int)$cantidad;
    $motivo      = (string)$motivo;

    // ✅ FIX FK: si no hay lote válido, no insertes
    if ($producto_id <= 0 || $lote_id <= 0) {
        return false;
    }

    // ✅ confirmar existencia del lote
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
            (producto_id, lote_id, tipo, cantidad, motivo)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) return false;

    $stmt->bind_param("iisis", $producto_id, $lote_id, $tipo, $cantidad, $motivo);
    return $stmt->execute();
}

/* =========================
   ✅ DEVOLUCIONES
   ========================= */
function devolverStockPorVenta($conexion, $producto_id, $unidades_a_devolver, $venta_id, $motivoExtra = '') {
    $producto_id = (int)$producto_id;
    $unidades_a_devolver = (int)$unidades_a_devolver;
    $venta_id = (int)$venta_id;

    if ($producto_id <= 0) return false;
    if ($unidades_a_devolver <= 0) return true;

    $mot = 'Venta ID ' . $venta_id;

    $sql = "
      SELECT id, lote_id, cantidad
      FROM movimientos_inventario
      WHERE producto_id = ?
        AND tipo = 'salida'
        AND motivo = ?
      ORDER BY id DESC
    ";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("is", $producto_id, $mot);
    $stmt->execute();
    $res = $stmt->get_result();

    $restante = $unidades_a_devolver;

    while ($row = $res->fetch_assoc()) {
        if ($restante <= 0) break;

        $lote_id = (int)$row["lote_id"];
        $cantMov = (int)$row["cantidad"];
        $usado = abs($cantMov);

        if ($usado <= 0 || $lote_id <= 0) continue;

        $sumar = min($usado, $restante);

        $up = $conexion->prepare("
          UPDATE lotes
          SET cantidad_unidades = cantidad_unidades + ?,
              activo = 1
          WHERE id = ?
        ");
        $up->bind_param("ii", $sumar, $lote_id);
        $up->execute();

        $motivo = trim("Devolución Venta ID $venta_id " . $motivoExtra);
        registrarMovimiento($conexion, $producto_id, $lote_id, "entrada", $sumar, $motivo);

        $restante -= $sumar;
    }

    $devuelto = $unidades_a_devolver - $restante;
    if ($devuelto > 0) {
        $sqlp = "UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?";
        $stmtp = $conexion->prepare($sqlp);
        $stmtp->bind_param("ii", $devuelto, $producto_id);
        $stmtp->execute();
    }

    autoDesactivarLotesSinStock($conexion);
    return $restante <= 0;
}

function devolverStockProductoDesdeVenta($conexion, $venta_id, $producto_id, $unidades_devolver) {
    $venta_id = (int)$venta_id;
    $producto_id = (int)$producto_id;
    $unidades_devolver = (int)$unidades_devolver;

    if ($venta_id <= 0 || $producto_id <= 0) return false;
    if ($unidades_devolver <= 0) return true;

    $m1 = "Venta ID " . $venta_id;
    $m2 = "Corrección Venta ID " . $venta_id;

    $sql = "
        SELECT id, lote_id, cantidad, motivo
        FROM movimientos_inventario
        WHERE producto_id = ?
          AND tipo = 'salida'
          AND (motivo = ? OR motivo = ?)
        ORDER BY id DESC
    ";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iss", $producto_id, $m1, $m2);
    $stmt->execute();
    $res = $stmt->get_result();

    $devuelto_total = 0;

    while ($row = $res->fetch_assoc()) {
        if ($unidades_devolver <= 0) break;

        $lote_id = (int)$row["lote_id"];
        $cant = (int)$row["cantidad"];
        $salio = abs($cant);

        if ($lote_id <= 0 || $salio <= 0) continue;

        $a_devolver = min($salio, $unidades_devolver);

        $up = $conexion->prepare("
            UPDATE lotes
            SET cantidad_unidades = cantidad_unidades + ?,
                activo = 1
            WHERE id = ?
        ");
        $up->bind_param("ii", $a_devolver, $lote_id);
        $up->execute();

        registrarMovimiento(
            $conexion,
            $producto_id,
            $lote_id,
            'entrada',
            $a_devolver,
            'Devolución Venta ID ' . $venta_id
        );

        $unidades_devolver -= $a_devolver;
        $devuelto_total += $a_devolver;
    }

    if ($devuelto_total > 0) {
        $sqlp = "UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?";
        $stmtp = $conexion->prepare($sqlp);
        $stmtp->bind_param("ii", $devuelto_total, $producto_id);
        $stmtp->execute();
    }

    autoDesactivarLotesSinStock($conexion);
    return ($unidades_devolver <= 0);
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
   ✅ NUEVO: Corregir producto del lote
   - Cambia producto_id del lote
   - Registra auditoría (cantidad 0) con lote real
   ========================= */
function corregirProductoDeLote($conexion, $lote_id, $nuevo_producto_id) {
    $lote_id = (int)$lote_id;
    $nuevo_producto_id = (int)$nuevo_producto_id;

    if ($lote_id <= 0 || $nuevo_producto_id <= 0) return false;

    $lote = obtenerLotePorId($conexion, $lote_id);
    if (!$lote) return false;

    $producto_actual = (int)($lote['producto_id'] ?? 0);
    if ($producto_actual <= 0) return false;

    // si es el mismo producto, no hacemos nada
    if ($producto_actual === $nuevo_producto_id) return true;

    $stmt = $conexion->prepare("UPDATE lotes SET producto_id = ? WHERE id = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param("ii", $nuevo_producto_id, $lote_id);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) return false;

    // Auditoría: dejamos un rastro (cantidad 0)
    registrarMovimiento(
        $conexion,
        $nuevo_producto_id,
        $lote_id,
        'ajuste',
        0,
        "Corrección: lote movido de producto $producto_actual a $nuevo_producto_id"
    );

    return true;
}