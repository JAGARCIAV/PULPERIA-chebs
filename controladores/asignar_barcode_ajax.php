<?php
require_once __DIR__ . '/../config/auth.php';
require_role(['admin']);
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Metodo no permitido']);
    exit;
}

// Validar CSRF
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Token de seguridad invalido. Recarga la pagina.']);
    exit;
}

$producto_id = (int)($_POST['producto_id'] ?? 0);
$barcode     = trim($_POST['barcode'] ?? '');

// Validaciones basicas
if ($producto_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID de producto invalido']);
    exit;
}

if ($barcode === '') {
    echo json_encode(['ok' => false, 'msg' => 'El codigo de barras no puede estar vacio']);
    exit;
}

if (mb_strlen($barcode) > 50) {
    echo json_encode(['ok' => false, 'msg' => 'El codigo es demasiado largo (max 50 caracteres)']);
    exit;
}

// ---------------------------------------------------------------------------
// Verificar que el producto existe y esta activo
// ---------------------------------------------------------------------------
$stmt = $conexion->prepare(
    'SELECT id, nombre, barcode FROM productos WHERE id = ? AND activo = 1 LIMIT 1'
);
if (!$stmt) {
    echo json_encode(['ok' => false, 'msg' => 'Error interno al buscar el producto']);
    exit;
}
$stmt->bind_param('i', $producto_id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prod) {
    echo json_encode(['ok' => false, 'msg' => 'Producto no encontrado o inactivo']);
    exit;
}

// ---------------------------------------------------------------------------
// Proteccion: no sobrescribir barcode si el producto ya tiene uno
// ---------------------------------------------------------------------------
if ($prod['barcode'] !== null) {
    echo json_encode([
        'ok'  => false,
        'msg' => 'El producto ya tiene un codigo asignado: ' . $prod['barcode']
    ]);
    exit;
}

// ---------------------------------------------------------------------------
// Verificar que el barcode no este ya asignado a otro producto
// ---------------------------------------------------------------------------
$stmt = $conexion->prepare(
    'SELECT id, nombre FROM productos WHERE barcode = ? LIMIT 1'
);
if (!$stmt) {
    echo json_encode(['ok' => false, 'msg' => 'Error interno al verificar duplicado']);
    exit;
}
$stmt->bind_param('s', $barcode);
$stmt->execute();
$dup = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($dup) {
    echo json_encode([
        'ok'  => false,
        'msg' => 'Ese codigo ya esta asignado al producto: ' . $dup['nombre']
    ]);
    exit;
}

// ---------------------------------------------------------------------------
// Asignar barcode
// La clausula AND barcode IS NULL en el UPDATE es la ultima linea de defensa:
// si entre el check anterior y este UPDATE otro proceso asigno el barcode,
// affected_rows sera 0 y no habra corrupcion ni excepcion.
// ---------------------------------------------------------------------------
$stmt = $conexion->prepare(
    'UPDATE productos SET barcode = ? WHERE id = ? AND barcode IS NULL LIMIT 1'
);
if (!$stmt) {
    echo json_encode(['ok' => false, 'msg' => 'Error interno al guardar el codigo']);
    exit;
}
$stmt->bind_param('si', $barcode, $producto_id);
$stmt->execute();
$guardado = ($stmt->affected_rows > 0);
$stmt->close();

if ($guardado) {
    echo json_encode([
        'ok'      => true,
        'msg'     => 'Codigo asignado correctamente',
        'barcode' => $barcode,
        'nombre'  => $prod['nombre'],
    ]);
} else {
    echo json_encode([
        'ok'  => false,
        'msg' => 'No se pudo asignar el codigo. El producto pudo haber sido modificado. Recarga la pagina.'
    ]);
}
