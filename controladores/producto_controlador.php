<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once "../config/conexion.php";
require_once "../modelos/producto_modelo.php";

$nombre = trim($_POST['nombre'] ?? '');
$nombre = preg_replace('/\s+/', ' ', $nombre); // ✅ quita dobles espacios (anti-trampa)
$descripcion = trim($_POST['descripcion'] ?? '');
$precio_unidad = (float)($_POST['precio_unidad'] ?? 0);

// ✅ costo por unidad (mayorista) opcional
$costo_unidad_raw = trim((string)($_POST['costo_unidad'] ?? ''));
$costo_unidad = ($costo_unidad_raw === '' ? null : (float)$costo_unidad_raw);

// legacy (ya no lo usaremos para caja)
$precio_paquete = 0.00;
$unidades_paquete = 1;

if ($nombre === '' || $precio_unidad <= 0) {
    header("Location: ../vistas/productos/crear.php?error=datos_invalidos");
    exit;
}

/* =========================================================
   ✅ BLOQUEAR DUPLICADOS (activo=1) por nombre
   - iguala may/min y TRIM + normaliza espacios
   ========================================================= */
$stmt = $conexion->prepare("
  SELECT id, nombre
  FROM productos
  WHERE activo=1
    AND LOWER(TRIM(nombre)) = LOWER(TRIM(?))
  LIMIT 1
");
if ($stmt) {
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existe) {
        $idExist = (int)($existe['id'] ?? 0);
        $nomExist = urlencode((string)($existe['nombre'] ?? $nombre));
        header("Location: ../vistas/productos/crear.php?err=duplicado&id_existente={$idExist}&nombre={$nomExist}");
        exit;
    }
}

/* =========================================================
   ✅ VALIDACIÓN BACKEND (NO se puede saltar)
   ========================================================= */

// 1) costo_unidad no puede ser mayor que precio_unidad
if ($costo_unidad !== null && $costo_unidad > $precio_unidad) {
    header("Location: ../vistas/productos/crear.php?error=costo_mayor");
    exit;
}

// 2) Validar presentaciones si llegaron
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
        header("Location: ../vistas/productos/crear.php?error=pres_invalida");
        exit;
    }

    if ($c !== null && $c > $p) {
        header("Location: ../vistas/productos/crear.php?error=pres_costo_mayor&idx=" . ($i+1));
        exit;
    }

    if ($c === null && $costo_unidad !== null) {
        $costo_derivado = $costo_unidad * $u;
        if ($costo_derivado > $p) {
            header("Location: ../vistas/productos/crear.php?error=pres_derivado_mayor&idx=" . ($i+1));
            exit;
        }
    }
}

/* =========================================================
   ✅ GUARDAR PRODUCTO + PRESENTACIONES + IMAGEN
   ========================================================= */
try {
    $id_nuevo = guardarProducto(
        $conexion,
        $nombre,
        $descripcion,
        $precio_unidad,
        $precio_paquete,
        $unidades_paquete,
        $costo_unidad
    );

    if ((int)$id_nuevo <= 0) {
        header("Location: ../vistas/productos/crear.php?error=guardar");
        exit;
    }

    guardarPresentacionesProducto(
        $conexion,
        (int)$id_nuevo,
        $pres_nombres,
        $pres_unidades,
        $pres_precios,
        $pres_costos
    );

    /* =========================================================
       ✅ SUBIR IMAGEN (OPCIONAL) Y GUARDAR EN BD
       ========================================================= */
    if (isset($_FILES['imagen']) && is_array($_FILES['imagen']) && ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {

        if (($_FILES['imagen']['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK) {

            $tmp  = $_FILES['imagen']['tmp_name'] ?? '';
            $name = $_FILES['imagen']['name'] ?? '';
            $size = (int)($_FILES['imagen']['size'] ?? 0);

            // límite 5MB
            if ($size <= 5 * 1024 * 1024) {

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $permitidas = ['jpg','jpeg','png','webp'];

                if (in_array($ext, $permitidas, true)) {

                    $dir = __DIR__ . '/../uploads/productos/';
                    if (!is_dir($dir)) @mkdir($dir, 0777, true);

                    $nuevoNombre = 'prod_' . (int)$id_nuevo . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $destino = $dir . $nuevoNombre;

                    if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $destino)) {

                        $imagenPath = 'uploads/productos/' . $nuevoNombre;

                        $stmt = $conexion->prepare("UPDATE productos SET imagen = ? WHERE id = ?");
                        if ($stmt) {
                            $stmt->bind_param("si", $imagenPath, $id_nuevo);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }
        }
    }

    header("Location: ../vistas/productos/crear.php?creado=1&id=" . (int)$id_nuevo);
    exit;

} catch (Throwable $e) {
    header("Location: ../vistas/productos/crear.php?error=sql");
    exit;
}