<?php
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/producto_modelo.php";

header("Content-Type: application/json; charset=utf-8");

$data = json_decode(file_get_contents("php://input"), true);

$id = isset($data["id"]) ? (int)$data["id"] : 0;

if ($id <= 0) {
  echo json_encode(["error" => "ID inválido"]);
  exit;
}

$p = obtenerProductoPorId($conexion, $id);

if (!$p) {
  echo json_encode(["error" => "Producto no encontrado"]);
  exit;
}

// ✅ Presentaciones activas (para cajetillas, packs, etc.)
$stmt = $conexion->prepare("
  SELECT id, nombre, unidades, precio_venta
  FROM producto_presentaciones
  WHERE producto_id = ? AND activa = 1
  ORDER BY unidades DESC
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

$presentaciones = [];
while ($row = $res->fetch_assoc()) {
  $presentaciones[] = [
    "id" => (int)$row["id"],
    "nombre" => $row["nombre"],
    "unidades" => (int)$row["unidades"],
    "precio_venta" => (float)$row["precio_venta"],
  ];
}

echo json_encode([
  "id" => (int)$p["id"],
  "nombre" => $p["nombre"],
  "precio_unidad" => (float)$p["precio_unidad"],
  "presentaciones" => $presentaciones
]);
exit;
