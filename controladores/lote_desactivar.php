<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /PULPERIA-CHEBS/vistas/notificacion/notificacion.php");
    exit;
}

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    header("Location: /PULPERIA-CHEBS/vistas/notificacion/notificacion.php?err=seguridad");
    exit;
}

$lote_id = isset($_POST['lote_id']) ? (int)$_POST['lote_id'] : 0;
if ($lote_id <= 0) {
    header("Location: /PULPERIA-CHEBS/vistas/notificacion/notificacion.php");
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexion->begin_transaction();

try {
    $ok = desactivarLote($conexion, $lote_id);
    if (!$ok) {
        throw new Exception("No se pudo desactivar el lote.");
    }
    $conexion->commit();
} catch (Throwable $e) {
    $conexion->rollback();
    error_log("[lote_desactivar] lote_id=$lote_id — " . $e->getMessage());
}

header("Location: /PULPERIA-CHEBS/vistas/notificacion/notificacion.php");
exit;
