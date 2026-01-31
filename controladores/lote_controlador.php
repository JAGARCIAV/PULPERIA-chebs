<?php
require_once "../config/conexion.php";
require_once "../modelos/lote_modelo.php";

$producto_id = $_POST['producto_id'];
$fecha_vencimiento = $_POST['fecha_vencimiento'];
$cantidad = $_POST['cantidad'];

$lote_id = guardarLote($conexion, $producto_id, $fecha_vencimiento, $cantidad);
if ($lote_id === false) {
    // Manejar error al guardar el lote
    die("Error al guardar el lote.");
}else {
    // Registrar movimiento de inventario
    registrarMovimiento(
        $conexion,
        $producto_id,
        $lote_id,
        'entrada',
        $cantidad,
        'Nuevo lote registrado'
    );
}


header("Location: ../vistas/lotes/listar.php");
?>
