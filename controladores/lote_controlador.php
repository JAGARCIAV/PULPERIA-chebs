<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../vistas/lotes/listar.php");
    exit;
}

$producto_id       = (int)($_POST['producto_id'] ?? 0);
$fecha_vencimiento = trim((string)($_POST['fecha_vencimiento'] ?? ''));
$cantidad          = (int)($_POST['cantidad'] ?? 0);

if ($producto_id <= 0 || $cantidad <= 0 || $fecha_vencimiento === '') {
    die("Datos inválidos. Revisa producto, fecha y cantidad.");
}

// ✅ Transacción (para que no quede lote sin movimiento)
$conexion->begin_transaction();

try {
    // 1) crear lote (devuelve ID real)
    $lote_id = guardarLote($conexion, $producto_id, $fecha_vencimiento, $cantidad);

    if (!$lote_id || (int)$lote_id <= 0) {
        throw new Exception("Error al guardar el lote.");
    }

    // 2) registrar movimiento (ya está protegido en el modelo)
    $okMov = registrarMovimiento(
        $conexion,
        $producto_id,
        (int)$lote_id,
        'entrada',
        $cantidad,
        'Nuevo lote registrado'
    );

    if (!$okMov) {
        throw new Exception("Lote creado (ID $lote_id) pero no se pudo registrar el movimiento.");
    }

    $conexion->commit();

    header("Location: ../vistas/lotes/listar.php?ok=1");
    exit;

} catch (Throwable $e) {
    $conexion->rollback();
    die($e->getMessage());
}
?>