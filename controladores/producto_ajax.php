<?php
require_once "../config/conexion.php";
require_once "../modelos/producto_modelo.php";

header("Content-Type: application/json; charset=utf-8");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : "unidad";

if ($id <= 0) {
    echo json_encode(["error" => "ID inválido"]);
    exit;
}

if ($tipo !== "unidad" && $tipo !== "paquete") {
    $tipo = "unidad";
}

$p = obtenerProductoPorId($conexion, $id);

if (!$p) {
    echo json_encode(["error" => "Producto no encontrado"]);
    exit;
}

$precio = ($tipo === "paquete") ? (float)$p['precio_paquete'] : (float)$p['precio_unidad'];

echo json_encode([
    "id" => (int)$p['id'],
    "nombre" => $p['nombre'],
    "precio" => $precio,
    "unidades_por_paquete" => (int)$p['unidades_por_paquete']
]);
exit;
