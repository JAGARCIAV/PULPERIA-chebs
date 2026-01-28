<?php
require_once "../config/conexion.php";
require_once "../modelos/lote_modelo.php";

header("Content-Type: application/json; charset=utf-8");

$producto_id = isset($_GET["producto_id"]) ? (int)$_GET["producto_id"] : 0;
if ($producto_id <= 0) {
    echo json_encode(["error"=>"ID inválido"]);
    exit;
}

$stock = obtenerStockDisponible($conexion, $producto_id);
echo json_encode(["stock"=>$stock]);
exit;
