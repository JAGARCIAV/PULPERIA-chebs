<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/producto_modelo.php";

header("Content-Type: application/json; charset=utf-8");

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

// Búsqueda delegada al modelo optimizado
$res = obtenerProductosVendibles($conexion, $q, 20);

$out = [];
$base = '/PULPERIA-CHEBS/';
while ($r = $res->fetch_assoc()) {
    $img = trim($r['imagen'] ?? '');
    $out[] = [
        'id'            => (int)$r['id'],
        'label'         => (string)$r['nombre'], // Compatible con renderAuto() actual
        'imagen'        => ($img !== '') ? $base . ltrim($img, './') : '', // Compatible con renderAuto() actual
        'precio_unidad' => (float)$r['precio_unidad'],
        'stock_total'   => (int)$r['stock_total']
    ];
}

echo json_encode($out);
