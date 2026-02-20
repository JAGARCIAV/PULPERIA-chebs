<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";

header("Content-Type: application/json; charset=utf-8");

$accion = $_POST["accion"] ?? "";
$lote_id = isset($_POST["lote_id"]) ? (int)$_POST["lote_id"] : 0;

if ($accion !== "desactivar") {
  echo json_encode(["ok" => false, "msg" => "Acción inválida"]);
  exit;
}

if ($lote_id <= 0) {
  echo json_encode(["ok" => false, "msg" => "ID inválido"]);
  exit;
}

try {
  $ok = desactivarLote($conexion, $lote_id);
  echo json_encode(["ok" => (bool)$ok]);
} catch (Throwable $e) {
  echo json_encode(["ok" => false, "msg" => $e->getMessage()]);
}
