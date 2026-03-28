<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/lote_modelo.php";

// ✅ Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../vistas/lotes/listar.php?err=metodo");
    exit;
}

// ✅ CSRF
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    header("Location: ../vistas/lotes/listar.php?err=" . urlencode("Error de seguridad. Recarga la página."));
    exit;
}

$lote_id = (int)($_POST['id'] ?? 0);

if ($lote_id <= 0) {
    header("Location: ../vistas/lotes/listar.php?err=id");
    exit;
}

// ✅ usar la función correcta del modelo
$ok = desactivarLote($conexion, $lote_id);

header("Location: ../vistas/lotes/listar.php?" . ($ok ? "ok=1" : "err=desactivar"));
exit;
?>