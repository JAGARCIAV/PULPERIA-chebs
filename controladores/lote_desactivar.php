<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";

$lote_id = isset($_POST['lote_id']) ? (int)$_POST['lote_id'] : 0;
if ($lote_id <= 0) {
  header("Location: /PULPERIA-CHEBS/vistas/notificacion/notificacion.php");
  exit;
}

desactivarLote($conexion, $lote_id);

header("Location: /PULPERIA-CHEBS/vistas/notificacion/notificacion.php");
exit;
