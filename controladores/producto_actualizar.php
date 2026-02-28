<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/producto_modelo.php";

// ✅ Para que mysqli lance excepciones y no falle “silencioso”
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = (int)($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$nombre = preg_replace('/\s+/', ' ', $nombre); // ✅ quita dobles espacios (anti-trampa)

$precio_unidad = (float)($_POST['precio_unidad'] ?? 0);
$precio_paquete = (float)($_POST['precio_paquete'] ?? 0); // legacy
$activo = (int)($_POST['activo'] ?? 1);

// ✅ costo unidad (mayorista) opcional
$costo_unidad_raw = trim((string)($_POST['costo_unidad'] ?? ''));
$costo_unidad = ($costo_unidad_raw === '' ? null : (float)$costo_unidad_raw);

// ✅ imagen actual desde el form (hidden)
$imagenActual = trim((string)($_POST['imagen_actual'] ?? ''));

if ($id <= 0 || $nombre === '' || $precio_unidad <= 0) {
    header("Location: ../vistas/productos/editar.php?id={$id}&error=datos_invalidos");
    exit;
}

/* =========================================================
   ✅ NUEVO: BLOQUEAR DUPLICADOS EN EDITAR (activo=1)
   - Excluye el mismo ID
   ========================================================= */
$stmt = $conexion->prepare("
  SELECT id, nombre
  FROM productos
  WHERE activo = 1
    AND id <> ?
    AND LOWER(TRIM(nombre)) = LOWER(TRIM(?))
  LIMIT 1
");
$stmt->bind_param("is", $id, $nombre);
$stmt->execute();
$existe = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existe) {
    $idExist = (int)($existe['id'] ?? 0);
    $nomExist = urlencode((string)($existe['nombre'] ?? $nombre));
    header("Location: ../vistas/productos/editar.php?id={$id}&err=duplicado&id_existente={$idExist}&nombre={$nomExist}");
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

    /* =========================================================
       ✅ SUBIR IMAGEN (OPCIONAL) Y GUARDAR EN BD
       ========================================================= */

    $nuevaImagenPath = null;

    if (isset($_FILES['imagen']) && is_array($_FILES['imagen']) && ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {

        if (($_FILES['imagen']['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK) {

            $tmp  = $_FILES['imagen']['tmp_name'] ?? '';
            $name = $_FILES['imagen']['name'] ?? '';
            $size = (int)($_FILES['imagen']['size'] ?? 0);

            // límite opcional: 5MB
            if ($size <= 5 * 1024 * 1024) {

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $permitidas = ['jpg','jpeg','png','webp'];

                if (in_array($ext, $permitidas, true)) {

                    $dir = __DIR__ . '/../uploads/productos/';
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0777, true);
                    }

                    $nuevoNombre = 'prod_' . (int)$id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $destino = $dir . $nuevoNombre;

                    if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $destino)) {
                        $nuevaImagenPath = 'uploads/productos/' . $nuevoNombre;

                        // Update en BD
                        $stmt = $conexion->prepare("UPDATE productos SET imagen = ? WHERE id = ?");
                        if ($stmt) {
                            $stmt->bind_param("si", $nuevaImagenPath, $id);
                            $stmt->execute();
                            $stmt->close();
                        }

                        // (opcional) borrar la anterior si era local
                        if ($imagenActual !== '' && strpos($imagenActual, 'uploads/productos/') === 0) {
                            $old = __DIR__ . '/../' . $imagenActual;
                            if (is_file($old)) {
                                @unlink($old);
                            }
                        }
                    }
                }
            }
        }
    }

    $conexion->commit();

    header("Location: ../vistas/productos/editar.php?id={$id}&ok=1");
    exit;

} catch (Throwable $e) {
    $conexion->rollback();
    header("Location: ../vistas/productos/editar.php?id={$id}&error=sql");
    exit;
}