<?php
$conexion = new mysqli("localhost", "root", "", "tienda",3306);
if ($conexion->connect_error) {
    die("Error de conexión");
}
?>
