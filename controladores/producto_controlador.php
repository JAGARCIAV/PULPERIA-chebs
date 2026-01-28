<?php
require_once "../config/conexion.php";
require_once "../modelos/producto_modelo.php";

$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$precio_unidad = $_POST['precio_unidad'];
$precio_paquete = $_POST['precio_paquete'];
$unidades_paquete = $_POST['unidades_paquete'];

guardarProducto($conexion, $nombre, $descripcion, $precio_unidad, $precio_paquete, $unidades_paquete);

header("Location: ../vistas/productos/listar.php");
?>
