<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../vistas/lotes/listar.php");
    exit;
}

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    header("Location: ../vistas/lotes/listar.php?err=seguridad");
    exit;
}

$lote_id = (int)($_POST['lote_id'] ?? 0);
$fecha_vencimiento = trim((string)($_POST['fecha_vencimiento'] ?? ''));
$nueva_cantidad = (int)($_POST['cantidad_unidades'] ?? -1);
$motivo = trim((string)($_POST['motivo'] ?? ''));

if ($lote_id <= 0) {
    header("Location: ../vistas/lotes/listar.php?err=id");
    exit;
}

$lote = obtenerLotePorId($conexion, $lote_id);
if (!$lote) {
    header("Location: ../vistas/lotes/listar.php?err=noexiste");
    exit;
}

$cantidad_actual = (int)($lote['cantidad_unidades'] ?? 0);
$producto_id     = (int)($lote['producto_id'] ?? 0);

// ⚠️ Con tu BD actual (fecha_vencimiento NOT NULL) => NO permitimos vacío
if ($fecha_vencimiento === '') {
    header("Location: ../vistas/lotes/editar.php?id=" . $lote_id . "&err=fecha");
    exit;
}

// ✅ Transacción para mantener consistencia
$conexion->begin_transaction();

try {
    // 1) Actualizar fecha si cambió
    $fecha_actual = (string)($lote['fecha_vencimiento'] ?? '');
    if ($fecha_vencimiento !== $fecha_actual) {
        actualizarFechaLote($conexion, $lote_id, $fecha_vencimiento);
    }

    // 2) Ajuste cantidad
    if ($nueva_cantidad < 0 || $nueva_cantidad === $cantidad_actual) {
        $conexion->commit();
        header("Location: ../vistas/lotes/editar.php?id=" . $lote_id . "&ok=1");
        exit;
    }

    $diferencia = $nueva_cantidad - $cantidad_actual;

    if ($diferencia != 0) {

        if ($motivo === '') {
            $conexion->rollback();
            header("Location: ../vistas/lotes/editar.php?id=" . $lote_id . "&err=motivo");
            exit;
        }

        // ✅ actualizarCantidadLote registra el movimiento y sincroniza stock_actual internamente
        actualizarCantidadLote($conexion, $lote_id, $nueva_cantidad);
    }

    $conexion->commit();
    header("Location: ../vistas/lotes/listar.php?ok=1");
    exit;

} catch (Throwable $e) {
    $conexion->rollback();
    error_log("[lote_editar_controlador] lote_id=$lote_id — " . $e->getMessage());
    header("Location: ../vistas/lotes/editar.php?id=" . $lote_id . "&err=sql");
    exit;
}
?>