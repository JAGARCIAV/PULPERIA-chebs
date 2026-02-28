<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/producto_modelo.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /PULPERIA-CHEBS/vistas/productos/listar.php");
  exit;
}

$id = (int)($_POST['id'] ?? 0);
$confirm = trim((string)($_POST['confirm'] ?? ''));

if ($id <= 0) {
  header("Location: /PULPERIA-CHEBS/vistas/productos/listar.php?err=id");
  exit;
}

if (mb_strtoupper($confirm) !== 'ELIMINAR') {
  header("Location: /PULPERIA-CHEBS/vistas/productos/eliminar.php?id={$id}&err=Confirmación incorrecta");
  exit;
}

$res = eliminarProductoConLotesSeguro($conexion, $id);

if (!empty($res['ok'])) {
  $msg = urlencode((string)($res['msg'] ?? 'OK'));
  header("Location: /PULPERIA-CHEBS/vistas/productos/listar.php?ok=1&msg={$msg}");
  exit;
}

$err = urlencode((string)($res['msg'] ?? 'Error al eliminar'));
header("Location: /PULPERIA-CHEBS/vistas/productos/eliminar.php?id={$id}&err={$err}");
exit;