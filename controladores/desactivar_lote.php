<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";

$lote_id = (int)($_GET['id'] ?? 0);

if ($lote_id <= 0) {
    header("Location: ../vistas/lotes/listar.php?err=id");
    exit;
}

// ✅ usar la función correcta del modelo
$ok = desactivarLote($conexion, $lote_id);

header("Location: ../vistas/lotes/listar.php?" . ($ok ? "ok=1" : "err=desactivar"));
exit;
?>