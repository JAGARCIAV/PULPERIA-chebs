<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../vistas/lotes/listar.php");
    exit;
}

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    header("Location: ../vistas/lotes/listar.php?err=seguridad");
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$lote_id = (int)($_POST['lote_id'] ?? 0);
$nuevo_producto_id = (int)($_POST['nuevo_producto_id'] ?? 0);

if ($lote_id <= 0 || $nuevo_producto_id <= 0) {
    header("Location: ../vistas/lotes/listar.php?err=datos");
    exit;
}

$conexion->begin_transaction();

try {
    // ✅ Cambia producto_id del MISMO lote (no crea lote nuevo, no rompe historial)
    $ok = corregirProductoDeLote($conexion, $lote_id, $nuevo_producto_id);
    if (!$ok) {
        throw new Exception("No se pudo corregir el producto del lote.");
    }
    $conexion->commit();
    header("Location: ../vistas/lotes/listar.php?ok=1");
    exit;
} catch (Throwable $e) {
    $conexion->rollback();
    header("Location: ../vistas/lotes/listar.php?err=corregir");
    exit;
}
?>