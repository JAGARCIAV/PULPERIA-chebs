<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";

header("Content-Type: application/json; charset=utf-8");

try {
  // ✅ tu modelo ya tiene esta función
  $arr = obtenerLotesVencidosActivos($conexion);

  echo json_encode([
    "ok" => true,
    "count" => is_array($arr) ? count($arr) : 0,
    "notificaciones" => $arr ?: []
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  echo json_encode([
    "ok" => false,
    "msg" => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
