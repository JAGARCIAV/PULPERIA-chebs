<?php
require_once "../config/conexion.php";
require_once "../modelos/lote_modelo.php";

$lote_id = $_POST['lote_id'];
$nuevo_producto_id = $_POST['nuevo_producto_id'];

$lote = obtenerLotePorId($conexion, $lote_id);

$producto_incorrecto = $lote['producto_id'];
$cantidad = $lote['cantidad_unidades'];
$fecha_vencimiento = $lote['fecha_vencimiento'];

// 1️⃣ Ajuste salida del lote incorrecto
registrarMovimiento($conexion, $producto_incorrecto, $lote_id, 'ajuste', -$cantidad, 'Corrección de producto');

// 2️⃣ Dejar lote en 0 y desactivar
$conexion->query("UPDATE lotes SET cantidad_unidades = 0, activo = FALSE WHERE id = $lote_id");

// 3️⃣ Crear lote nuevo correcto
$nuevo_lote_id = crearLote($conexion, $nuevo_producto_id, $fecha_vencimiento, $cantidad);

// 4️⃣ Registrar entrada del nuevo lote
registrarMovimiento($conexion, $nuevo_producto_id, $nuevo_lote_id, 'entrada', $cantidad, 'Corrección de producto');

header("Location: ../vistas/lotes/listar.php");
?>
