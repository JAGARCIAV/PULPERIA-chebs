<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";

header("Content-Type: application/json; charset=utf-8");

// Para que no se rompa el JSON por warnings
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // 1) Soporta JSON (fetch) y también POST normal / GET
    $raw  = file_get_contents("php://input");
    $data = json_decode($raw, true);

    $producto_id = 0;

    if (is_array($data) && isset($data["producto_id"])) {
        $producto_id = (int)$data["producto_id"];
    } elseif (isset($_POST["producto_id"])) {
        $producto_id = (int)$_POST["producto_id"];
    } elseif (isset($_GET["producto_id"])) {
        $producto_id = (int)$_GET["producto_id"];
    }

    if ($producto_id <= 0) {
        echo json_encode(["ok" => false, "stock" => 0, "msg" => "ID inválido"]);
        exit;
    }

    // Limpieza por si hay lotes con 0 activos
    autoDesactivarLotesSinStock($conexion);

    // Stock real (lotes activos + no vencidos + unidades>0)
    $stock = obtenerStockDisponible($conexion, $producto_id);

    echo json_encode(["ok" => true, "stock" => (int)$stock]);
    exit;

} catch (Throwable $e) {
    echo json_encode(["ok" => false, "stock" => 0, "msg" => "Error interno"]);
    exit;
}