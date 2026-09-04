<?php
require_once __DIR__ . "/venta_modelo.php"; // usa obtenerVentaPorId sin redeclare

/* ============================================================
   NOTA (limpieza de scope):
   Antes este archivo también tenía la lógica para "corregir" una
   venta (cambiar producto / editar cantidad / borrar línea).
   Se quitó a propósito: si una venta está mal, se anula y se
   rehace en caja. Es más simple y elimina la parte del sistema
   con más riesgo de descuadrar stock o dinero.
   Lo que queda aquí es solo lo necesario para ANULAR ventas.
   ============================================================ */

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

/* ============================================================
   VENTANA DE TIEMPO OPERATIVA
   Minutos máximos para que un empleado pueda anular una venta.
   Cambiar este valor para ajustar la política.
   ============================================================ */
if (!defined('CHEBS_VENTANA_CORRECCION_MIN')) {
    define('CHEBS_VENTANA_CORRECCION_MIN', 10);
}

/* ============================================================
   VALIDAR PERMISO DE ANULACIÓN
   Reglas:
     admin    → puede anular cualquier venta no anulada
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
