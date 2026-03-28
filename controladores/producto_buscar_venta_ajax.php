<?php
require_once __DIR__ . '/../config/auth.php';
require_role(['admin', 'empleado']);
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

// Solo GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['modo' => 'error', 'resultado' => null]);
    exit;
}

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode(['modo' => 'no_encontrado', 'resultado' => null]);
    exit;
}

// ---------------------------------------------------------------------------
// Detectar barcode: solo digitos, longitud 6-50
// El lector HID envia digitos + Enter en ~50ms; este patron lo detecta
// ---------------------------------------------------------------------------
if (!preg_match('/^\d{6,50}$/', $q)) {
    // No es un barcode valido — este endpoint solo atiende barcodes
    echo json_encode(['modo' => 'no_encontrado', 'resultado' => null]);
    exit;
}

// ---------------------------------------------------------------------------
// PASO 1: Buscar el producto por barcode exacto (activo o no, con o sin stock)
// Necesitamos saber si el barcode existe en el sistema antes de verificar stock
// ---------------------------------------------------------------------------
$stmt = $conexion->prepare(
    'SELECT id, nombre, imagen, precio_unidad, activo
     FROM productos
     WHERE barcode = ?
     LIMIT 1'
);
if (!$stmt) {
    echo json_encode(['modo' => 'error', 'resultado' => null]);
    exit;
}
$stmt->bind_param('s', $q);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prod) {
    // El barcode no existe en ningun producto
    echo json_encode(['modo' => 'no_encontrado', 'resultado' => null]);
    exit;
}

$producto_id = (int)$prod['id'];

// ---------------------------------------------------------------------------
// PASO 2: Verificar stock vendible con la misma regla que ventas usa
// Regla identica a obtenerProductosVendibles():
//   - producto activo
//   - lote activo
//   - lote con unidades > 0
//   - fecha de vencimiento >= hoy
// ---------------------------------------------------------------------------
$stmt2 = $conexion->prepare(
    'SELECT SUM(l.cantidad_unidades) AS stock_total
     FROM lotes l
     WHERE l.producto_id = ?
       AND l.activo = 1
       AND l.cantidad_unidades > 0
       AND l.fecha_vencimiento >= CURDATE()'
);
if (!$stmt2) {
    echo json_encode(['modo' => 'error', 'resultado' => null]);
    exit;
}
$stmt2->bind_param('i', $producto_id);
$stmt2->execute();
$stockRow = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

$stock_total = (int)($stockRow['stock_total'] ?? 0);

// ---------------------------------------------------------------------------
// PASO 3: Presentaciones activas del producto (para modal en caja)
// Mismos criterios que obtenerPresentacionesPorProducto(): activa=1, ORDER unidades ASC
// Siempre devuelve array (vacio si no tiene presentaciones)
// ---------------------------------------------------------------------------
$stmt3 = $conexion->prepare(
    'SELECT id, nombre, unidades, precio_venta
     FROM producto_presentaciones
     WHERE producto_id = ? AND activa = 1
     ORDER BY unidades ASC'
);
$presentaciones = [];
if ($stmt3) {
    $stmt3->bind_param('i', $producto_id);
    $stmt3->execute();
    $pres_res = $stmt3->get_result();
    while ($p = $pres_res->fetch_assoc()) {
        $presentaciones[] = [
            'id'          => (int)$p['id'],
            'nombre'      => (string)$p['nombre'],
            'unidades'    => (int)$p['unidades'],
            'precio_venta' => (float)$p['precio_venta'],
        ];
    }
    $stmt3->close();
}

// ---------------------------------------------------------------------------
// Construir imagen URL (misma logica que producto_buscador_ajax.php)
// ---------------------------------------------------------------------------
$base = '/PULPERIA-CHEBS/';
$img_db = trim($prod['imagen'] ?? '');
$img_url = ($img_db !== '') ? $base . ltrim($img_db, './') : '';

$resultado = [
    'id'           => $producto_id,
    'label'        => (string)$prod['nombre'],
    'imagen'       => $img_url,
    'precio_unidad' => (float)$prod['precio_unidad'],
    'stock_total'  => $stock_total,
    'presentaciones' => $presentaciones,
];

// ---------------------------------------------------------------------------
// PASO 3: Determinar modo de respuesta
//   barcode          -> producto encontrado y con stock vendible
//   barcode_sin_stock -> producto encontrado pero sin stock disponible
//                       (incluye: producto inactivo, lotes vencidos, stock 0)
//   no_encontrado    -> barcode no registrado en ningun producto
// ---------------------------------------------------------------------------
if ($stock_total > 0 && (int)$prod['activo'] === 1) {
    echo json_encode(['modo' => 'barcode', 'resultado' => $resultado]);
} else {
    // Producto existe pero no se puede vender: sin stock, vencido o inactivo
    echo json_encode(['modo' => 'barcode_sin_stock', 'resultado' => $resultado]);
}
