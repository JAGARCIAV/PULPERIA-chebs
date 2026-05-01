<?php
require_once __DIR__ . '/../config/auth.php';
require_role(['admin']);
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

// Solo GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['modo' => '', 'resultados' => []]);
    exit;
}

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 1) {
    echo json_encode(['modo' => '', 'resultados' => []]);
    exit;
}

// ---------------------------------------------------------------------------
// PASO 1: si el texto es solo digitos (longitud 6-50) => buscar por barcode
// El lector HID envia digitos + Enter; este patron detecta el escaneo
// ---------------------------------------------------------------------------
if (preg_match('/^\d{6,50}$/', $q)) {
    $stmt = $conexion->prepare(
        'SELECT id, nombre, barcode FROM productos WHERE barcode = ? AND activo = 1 LIMIT 1'
    );
    if (!$stmt) {
        echo json_encode(['modo' => 'error', 'resultados' => []]);
        exit;
    }
    $stmt->bind_param('s', $q);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        // Match exacto por barcode: devolver un solo resultado con flag 'barcode'
        echo json_encode([
            'modo'       => 'barcode',
            'resultados' => [
                [
                    'id'      => (int)$row['id'],
                    'nombre'  => $row['nombre'],
                    'barcode' => $row['barcode'],
                ]
            ]
        ]);
        exit;
    }

    // Barcode escaneado pero no registrado en ningun producto
    echo json_encode(['modo' => 'barcode_no_encontrado', 'resultados' => []]);
    exit;
}

// ---------------------------------------------------------------------------
// PASO 2: busqueda por nombre LIKE (minimo 2 caracteres)
// ---------------------------------------------------------------------------
if (mb_strlen($q) < 2) {
    echo json_encode(['modo' => 'nombre', 'resultados' => []]);
    exit;
}

$like = '%' . $q . '%';
$stmt = $conexion->prepare(
    'SELECT id, nombre, barcode FROM productos
     WHERE activo = 1 AND nombre LIKE ?
     ORDER BY nombre ASC
     LIMIT 15'
);
if (!$stmt) {
    echo json_encode(['modo' => 'error', 'resultados' => []]);
    exit;
}
$stmt->bind_param('s', $like);
$stmt->execute();
$res = $stmt->get_result();

$resultados = [];
while ($row = $res->fetch_assoc()) {
    $resultados[] = [
        'id'      => (int)$row['id'],
        'nombre'  => $row['nombre'],
        'barcode' => $row['barcode'], // null si el producto aun no tiene codigo
    ];
}
$stmt->close();

echo json_encode(['modo' => 'nombre', 'resultados' => $resultados]);
