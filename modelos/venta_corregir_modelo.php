<?php
require_once __DIR__ . "/venta_modelo.php"; // usa obtenerVentaPorId sin redeclare

function ventaEstaAnulada($conexion, $venta_id) {
    $v = obtenerVentaPorId($conexion, (int)$venta_id);
    return $v && isset($v["anulada"]) && (int)$v["anulada"] === 1;
}

function marcarVentaAnulada($conexion, $venta_id) {
    $venta_id = (int)$venta_id;

    // AND anulada=0: garantía atómica contra doble anulación concurrente.
    // Si affected_rows=0, otro proceso ya anuló esta venta.
    // total se conserva para auditoría histórica.
    $stmt = $conexion->prepare("UPDATE ventas SET anulada=1 WHERE id=? AND anulada=0");
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    return $affected === 1;
}

function obtenerDetalleVentaPorVenta($conexion, $venta_id) {
    $venta_id = (int)$venta_id;
    $stmt = $conexion->prepare("
        SELECT dv.*, p.nombre
        FROM detalle_venta dv
        INNER JOIN productos p ON p.id = dv.producto_id
        WHERE dv.venta_id=?
        ORDER BY dv.id ASC
    ");
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    return $stmt->get_result();
}

function obtenerDetalleEspecifico($conexion, $detalle_id) {
    $detalle_id = (int)$detalle_id;
    $stmt = $conexion->prepare("SELECT * FROM detalle_venta WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $detalle_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r;
}

function obtenerPrecioProducto($conexion, $producto_id) {
    $producto_id = (int)$producto_id;
    $stmt = $conexion->prepare("SELECT precio_unidad FROM productos WHERE id=?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res ? (float)$res['precio_unidad'] : 0.0;
}

function obtenerUnidadesPresentacion($conexion, $presentacion_id) {
    $presentacion_id = (int)$presentacion_id;
    if ($presentacion_id <= 0) return 0;

    $stmt = $conexion->prepare("SELECT unidades FROM producto_presentaciones WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $presentacion_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $r ? (int)$r["unidades"] : 0;
}

function actualizarLineaDetalleCorregida($conexion, $id_detalle, $producto_id, $cantidad, $precio, $subtotal, $presentacion_id, $tipo_venta, $unidades_reales) {

    $id_detalle = (int)$id_detalle;
    $producto_id = (int)$producto_id;
    $cantidad = (int)$cantidad;
    $precio = (float)$precio;
    $subtotal = (float)$subtotal;
    $unidades_reales = (int)$unidades_reales;
    $tipo_venta = (string)$tipo_venta;

    if ($presentacion_id === null) {
        $stmt = $conexion->prepare("
            UPDATE detalle_venta
            SET producto_id=?,
                presentacion_id=NULL,
                tipo_venta=?,
                cantidad=?,
                precio_unitario=?,
                subtotal=?,
                unidades_reales=?
            WHERE id=?
        ");
        $stmt->bind_param("isiddii", $producto_id, $tipo_venta, $cantidad, $precio, $subtotal, $unidades_reales, $id_detalle);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    $presentacion_id = (int)$presentacion_id;
    $stmt = $conexion->prepare("
        UPDATE detalle_venta
        SET producto_id=?,
            presentacion_id=?,
            tipo_venta=?,
            cantidad=?,
            precio_unitario=?,
            subtotal=?,
            unidades_reales=?
        WHERE id=?
    ");
    $stmt->bind_param("iisiddii", $producto_id, $presentacion_id, $tipo_venta, $cantidad, $precio, $subtotal, $unidades_reales, $id_detalle);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function eliminarLineaDetalle($conexion, $id_detalle) {
    $id_detalle = (int)$id_detalle;
    $stmt = $conexion->prepare("DELETE FROM detalle_venta WHERE id = ?");
    $stmt->bind_param("i", $id_detalle);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/* ============================================================
   VENTANA DE TIEMPO OPERATIVA
   Minutos máximos para que un empleado pueda corregir o anular
   una venta. Cambiar este valor para ajustar la política.
   ============================================================ */
if (!defined('CHEBS_VENTANA_CORRECCION_MIN')) {
    define('CHEBS_VENTANA_CORRECCION_MIN', 10);
}

/* ============================================================
   VALIDAR PERMISO DE EDICIÓN / ANULACIÓN
   Reglas:
     admin    → puede modificar cualquier venta no anulada
     empleado → solo su turno actual, propio, dentro de ventana
   Devuelve: ['ok' => bool, 'msg' => string]
   ============================================================ */
function validarPermisoEdicionVenta(
    $conexion,
    array $venta,
    int $user_id,
    string $rol,
    int $minutos_ventana = CHEBS_VENTANA_CORRECCION_MIN
): array {

    if ((int)($venta['anulada'] ?? 0) === 1) {
        return ['ok' => false, 'msg' => 'La venta ya está anulada.'];
    }

    // Admin: sin restricciones de turno ni tiempo
    if ($rol === 'admin') {
        return ['ok' => true];
    }

    // Empleado: verificar que hay turno abierto hoy
    $turno = obtenerTurnoAbiertoHoy($conexion);
    if (!$turno) {
        return ['ok' => false, 'msg' => 'No hay turno abierto. No se pueden modificar ventas.'];
    }

    // La venta debe pertenecer al turno actual
    if ((int)$venta['turno_id'] !== (int)$turno['id']) {
        return ['ok' => false, 'msg' => 'Solo se pueden modificar ventas del turno actual.'];
    }

    // El turno debe ser del propio empleado
    if ((int)$turno['usuario_id'] !== $user_id) {
        return ['ok' => false, 'msg' => 'Solo puedes modificar ventas de tu propio turno.'];
    }

    // Verificar ventana de tiempo
    $stmt = $conexion->prepare(
        "SELECT TIMESTAMPDIFF(MINUTE, fecha, NOW()) AS minutos FROM ventas WHERE id = ? LIMIT 1"
    );
    $ventaId = (int)($venta['id'] ?? 0);
    $stmt->bind_param("i", $ventaId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $minutos = (int)($row['minutos'] ?? 9999);
    if ($minutos > $minutos_ventana) {
        return [
            'ok'  => false,
            'msg' => "Solo se pueden modificar ventas de los últimos {$minutos_ventana} min. Esta venta tiene {$minutos} min.",
        ];
    }

    return ['ok' => true];
}

/* ============================================================
   AUDITORÍA: quién anuló (se llama FUERA de la transacción)
   Si las columnas aún no existen (migración pendiente),
   falla silenciosamente — no bloquea la anulación.
   ============================================================ */
function registrarAuditoriaAnulacion($conexion, int $venta_id, int $usuario_id): void {
    try {
        $stmt = $conexion->prepare(
            "UPDATE ventas SET anulado_por = ?, anulado_en = NOW() WHERE id = ?"
        );
        $stmt->bind_param("ii", $usuario_id, $venta_id);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        // No bloquear si la migración V3 aún no se corrió
    }
}

/* ============================================================
   AUDITORÍA: quién corrigió (se llama FUERA de la transacción)
   ============================================================ */
function registrarAuditoriaCorreccion($conexion, int $venta_id, int $usuario_id): void {
    try {
        $stmt = $conexion->prepare(
            "UPDATE ventas SET corregido_por = ?, corregido_en = NOW() WHERE id = ?"
        );
        $stmt->bind_param("ii", $usuario_id, $venta_id);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        // No bloquear si la migración V3 aún no se corrió
    }
}

function actualizarTotalVentaPorDetalle($conexion, $venta_id) {
    $venta_id = (int)$venta_id;

    $stmt = $conexion->prepare("SELECT COALESCE(SUM(subtotal),0) AS total FROM detalle_venta WHERE venta_id=?");
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total = (float)($row["total"] ?? 0);

    $up = $conexion->prepare("UPDATE ventas SET total=? WHERE id=?");
    $up->bind_param("di", $total, $venta_id);
    $up->execute();
    $up->close();

    return $total;
}