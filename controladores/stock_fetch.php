<?php
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";

header("Content-Type: application/json; charset=utf-8");

$data = json_decode(file_get_contents("php://input"), true);

$producto_id = isset($data["producto_id"]) ? (int)$data["producto_id"] : 0;

if ($producto_id <= 0) {
    echo json_encode(["error" => "ID inválido"]);
    exit;
}

/* =========================
   ✅ CAMBIO: limpieza rápida
   - si hay lotes con 0 y siguen activos, se desactivan
   ========================= */
autoDesactivarLotesSinStock($conexion);

$stock = obtenerStockDisponible($conexion, $producto_id);

echo json_encode(["stock" => (int)$stock]);
exit;
