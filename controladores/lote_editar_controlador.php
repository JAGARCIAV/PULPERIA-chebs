<?php
require_once "../config/conexion.php";
require_once "../modelos/lote_modelo.php";

$lote_id = $_POST['lote_id'];
$fecha_vencimiento = $_POST['fecha_vencimiento'];
$nueva_cantidad = intval($_POST['cantidad_unidades']);
$motivo = $_POST['motivo'] ?? '';

$lote = obtenerLotePorId($conexion, $lote_id);
$cantidad_actual = $lote['cantidad_unidades'];
$producto_id = $lote['producto_id'];

// 1. Actualizar fecha si cambió
if ($fecha_vencimiento != $lote['fecha_vencimiento']) {
    actualizarFechaLote($conexion, $lote_id, $fecha_vencimiento);
}

// 2. Manejar ajuste de cantidad
$diferencia = $nueva_cantidad - $cantidad_actual;

if ($diferencia != 0) {

    if (empty($motivo)) {
        die("Debes indicar el motivo del ajuste.");
    }

    // Registrar movimiento
    registrarMovimiento($conexion, $producto_id, $lote_id, 'ajuste', $diferencia, $motivo);

    // Actualizar lote
    actualizarCantidadLote($conexion, $lote_id, $nueva_cantidad);
}

header("Location: ../vistas/lotes/listar.php");
?>
