<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin']);

require_once "../config/conexion.php";
require_once "../modelos/producto_modelo.php";

$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precio_unidad = (float)($_POST['precio_unidad'] ?? 0);

// ✅ NUEVO: costo por unidad (mayorista) opcional
// - Si viene vacío => NULL
// - Si viene número => float
$costo_unidad_raw = trim((string)($_POST['costo_unidad'] ?? ''));
$costo_unidad = ($costo_unidad_raw === '' ? null : (float)$costo_unidad_raw);

// legacy (ya no lo usaremos para caja)
$precio_paquete = 0.00;
$unidades_paquete = 1;

if ($nombre === '' || $precio_unidad <= 0) {
    die("Datos inválidos (nombre / precio unidad).");
}

// ✅ 1) guardar producto (incluye costo_unidad)
$id_nuevo = guardarProducto(
    $conexion,
    $nombre,
    $descripcion,
    $precio_unidad,
    $precio_paquete,
    $unidades_paquete,
    $costo_unidad
);

if ($id_nuevo <= 0) {
    die("Error al guardar producto");
}

// ✅ 2) guardar presentaciones si llegaron
$pres_nombres  = $_POST['pres_nombre'] ?? [];
$pres_unidades = $_POST['pres_unidades'] ?? [];
$pres_precios  = $_POST['pres_precio'] ?? [];
$pres_costos   = $_POST['pres_costo'] ?? [];

guardarPresentacionesProducto(
    $conexion,
    (int)$id_nuevo,
    $pres_nombres,
    $pres_unidades,
    $pres_precios,
    $pres_costos
);

header("Location: ../vistas/productos/crear.php?creado=1&id=" . (int)$id_nuevo);
exit;
