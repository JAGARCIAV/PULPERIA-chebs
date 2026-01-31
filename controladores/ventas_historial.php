<?php
require_once "../config/conexion.php";
require_once "../modelos/venta_modelo.php";

$fecha     = $_GET['fecha'] ?? null;
$tipo      = $_GET['tipo'] ?? null;
$busqueda  = $_GET['busqueda'] ?? null;

$ventas = obtenerVentasFiltradas($conexion, $fecha, $tipo, $busqueda);

include "../vistas/ventas/historial.php";
