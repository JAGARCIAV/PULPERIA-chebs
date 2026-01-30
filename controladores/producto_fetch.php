<?php
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/producto_modelo.php";

header("Content-Type: application/json; charset=utf-8");

$data = json_decode(file_get_contents("php://input"), true);

$id   = isset($data["id"]) ? (int)$data["id"] : 0;
$tipo = isset($data["tipo"]) ? $data["tipo"] : "unidad";

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

$precio = ($tipo === "paquete")
    ? (float)$p["precio_paquete"]
    : (float)$p["precio_unidad"];

echo json_encode([
    "id" => (int)$p["id"],
    "nombre" => $p["nombre"],
    "precio" => $precio,
    "unidades_por_paquete" => (int)$p["unidades_por_paquete"]
]);
exit;
