<?php
require_once "../config/conexion.php";
require_once "../modelos/producto_modelo.php";

$id = (int)$_POST['id'];
$nombre = trim($_POST['nombre']);
$precio_unidad = (float)$_POST['precio_unidad'];
$precio_paquete = (float)$_POST['precio_paquete'];
$activo = (int)$_POST['activo'];

if ($id <= 0 || $nombre === '') {
    die("Datos inválidos");
}

if (actualizarProducto($conexion, $id, $nombre, $precio_unidad, $precio_paquete, $activo)) {
    header("Location: ../vistas/productos/listar.php?ok=1");
} else {
    header("Location: ../vistas/productos/listar.php?error=1");
}
exit;
