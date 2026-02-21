<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../vistas/lotes/listar.php");
    exit;
}

$lote_id = (int)($_POST['lote_id'] ?? 0);
$nuevo_producto_id = (int)($_POST['nuevo_producto_id'] ?? 0);

if ($lote_id <= 0 || $nuevo_producto_id <= 0) {
    header("Location: ../vistas/lotes/listar.php?err=datos");
    exit;
}

// ✅ Cambia producto_id del MISMO lote (no crea lote nuevo, no rompe historial)
$ok = corregirProductoDeLote($conexion, $lote_id, $nuevo_producto_id);

header("Location: ../vistas/lotes/listar.php?" . ($ok ? "ok=1" : "err=corregir"));
exit;
?>