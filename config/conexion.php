<?php
// ✅ Zona horaria PHP (Bolivia)
date_default_timezone_set('America/La_Paz');

$conexion = new mysqli("localhost", "root", "", "tienda", 3306);
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// ✅ charset seguro
$conexion->set_charset("utf8mb4");

// ✅ Zona horaria MySQL (Bolivia -04:00)
// Esto arregla CURDATE(), NOW(), DATE(fecha) y comparaciones por día.
$conexion->query("SET time_zone = '-04:00'");
?>