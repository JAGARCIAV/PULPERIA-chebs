<?php
require_once "../config/conexion.php";
require_once "../modelos/lote_modelo.php";

$producto_id = $_POST['producto_id'];
$fecha_vencimiento = $_POST['fecha_vencimiento'];
$cantidad = $_POST['cantidad'];

guardarLote($conexion, $producto_id, $fecha_vencimiento, $cantidad);

header("Location: ../vistas/lotes/listar.php");
?>
