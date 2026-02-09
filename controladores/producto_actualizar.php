<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/producto_modelo.php";

// ✅ Para que mysqli lance excepciones y no falle “silencioso”
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = (int)($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$precio_unidad = (float)($_POST['precio_unidad'] ?? 0);
$precio_paquete = (float)($_POST['precio_paquete'] ?? 0); // legacy
$activo = (int)($_POST['activo'] ?? 1);

// ✅ NUEVO: costo unidad (mayorista) opcional
$costo_unidad = $_POST['costo_unidad'] ?? null;

if ($id <= 0 || $nombre === '' || $precio_unidad <= 0) {
    die("Datos inválidos");
}

// ✅ Presentaciones (packs)
$pres_nombres  = $_POST['pres_nombre'] ?? [];
$pres_unidades = $_POST['pres_unidades'] ?? [];
$pres_precios  = $_POST['pres_precio'] ?? [];
$pres_costos   = $_POST['pres_costo'] ?? [];

$conexion->begin_transaction();

try {
    // 1) Actualizar producto base (incluye costo_unidad)
    $ok = actualizarProducto($conexion, $id, $nombre, $precio_unidad, $precio_paquete, $activo, $costo_unidad);
    if (!$ok) {
        throw new Exception("No se pudo actualizar producto");
    }

    // 2) Reemplazar presentaciones: borrar y volver a insertar
    eliminarPresentacionesProducto($conexion, $id);
    guardarPresentacionesProducto($conexion, $id, $pres_nombres, $pres_unidades, $pres_precios, $pres_costos);

    $conexion->commit();

    header("Location: ../vistas/productos/listar.php?ok=1");
    exit;

} catch (Throwable $e) {
    $conexion->rollback();
    die("Error: " . $e->getMessage());
}
