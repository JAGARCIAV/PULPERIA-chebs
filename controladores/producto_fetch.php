<?php
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/producto_modelo.php";
require_once __DIR__ . "/../modelos/lote_modelo.php"; // ✅ para stock disponible (opcional)

header("Content-Type: application/json; charset=utf-8");

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$id = isset($data["id"]) ? (int)$data["id"] : 0;

if ($id <= 0) {
  echo json_encode(["ok" => false, "error" => "ID inválido"]);
  exit;
}

$p = obtenerProductoPorId($conexion, $id);
if (!$p) {
  echo json_encode(["ok" => false, "error" => "Producto no encontrado"]);
  exit;
}

// ✅ Presentaciones activas
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
    "nombre" => (string)$row["nombre"],
    "unidades" => (int)$row["unidades"],
    "precio_venta" => (float)$row["precio_venta"],
  ];
}
$stmt->close();

// ✅ opcional: stock real para debug/UX
$stock = function_exists("obtenerStockDisponible") ? (int)obtenerStockDisponible($conexion, $id) : null;

echo json_encode([
  "ok" => true,
  "id" => (int)$p["id"],
  "nombre" => (string)$p["nombre"],
  "precio_unidad" => (float)$p["precio_unidad"],
  "presentaciones" => $presentaciones,
  "stock_disponible" => $stock
], JSON_UNESCAPED_UNICODE);
exit;