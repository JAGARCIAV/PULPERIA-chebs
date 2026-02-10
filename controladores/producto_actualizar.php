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

// ✅ costo unidad (mayorista) opcional
$costo_unidad_raw = trim((string)($_POST['costo_unidad'] ?? ''));
$costo_unidad = ($costo_unidad_raw === '' ? null : (float)$costo_unidad_raw);

if ($id <= 0 || $nombre === '' || $precio_unidad <= 0) {
    header("Location: ../vistas/productos/editar.php?id={$id}&error=datos_invalidos");
    exit;
}

/* =========================================================
   ✅ VALIDACIÓN BACKEND (unidad + packs)
   ========================================================= */

// 1) costo_unidad no puede ser mayor que precio_unidad
if ($costo_unidad !== null && $costo_unidad > $precio_unidad) {
    header("Location: ../vistas/productos/editar.php?id={$id}&error=costo_mayor");
    exit;
}

// 2) Presentaciones (packs)
$pres_nombres  = $_POST['pres_nombre'] ?? [];
$pres_unidades = $_POST['pres_unidades'] ?? [];
$pres_precios  = $_POST['pres_precio'] ?? [];
$pres_costos   = $_POST['pres_costo'] ?? [];

if (!is_array($pres_nombres))  $pres_nombres = [];
if (!is_array($pres_unidades)) $pres_unidades = [];
if (!is_array($pres_precios))  $pres_precios = [];
if (!is_array($pres_costos))   $pres_costos = [];

$max = max(count($pres_nombres), count($pres_unidades), count($pres_precios), count($pres_costos));

for ($i = 0; $i < $max; $i++) {
    $n = trim((string)($pres_nombres[$i] ?? ''));
    if ($n === '') continue;

    $u_raw = trim((string)($pres_unidades[$i] ?? ''));
    $p_raw = trim((string)($pres_precios[$i] ?? ''));
    $c_raw = trim((string)($pres_costos[$i] ?? ''));

    $u = (int)$u_raw;
    $p = (float)$p_raw;
    $c = ($c_raw === '' ? null : (float)$c_raw);

    if ($u <= 0 || $p < 0) {
        header("Location: ../vistas/productos/editar.php?id={$id}&error=pres_invalida");
        exit;
    }

    if ($c !== null && $c > $p) {
        header("Location: ../vistas/productos/editar.php?id={$id}&error=pres_costo_mayor&idx=" . ($i+1));
        exit;
    }

    if ($c === null && $costo_unidad !== null) {
        $costo_derivado = $costo_unidad * $u;
        if ($costo_derivado > $p) {
            header("Location: ../vistas/productos/editar.php?id={$id}&error=pres_derivado_mayor&idx=" . ($i+1));
            exit;
        }
    }
}

/* =========================================================
   ✅ GUARDAR (transacción)
   ========================================================= */

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

    header("Location: ../vistas/productos/editar.php?id={$id}&ok=1");
    exit;

} catch (Throwable $e) {
    $conexion->rollback();
    // Error bonito (si el CHECK de BD bloquea o cualquier otro)
    header("Location: ../vistas/productos/editar.php?id={$id}&error=sql");
    exit;
}
