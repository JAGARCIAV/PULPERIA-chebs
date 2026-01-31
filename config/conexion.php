<?php
$conexion = new mysqli("localhost", "root", "", "tienda",33065);
if ($conexion->connect_error) {
    die("Error de conexión");
}
?>
