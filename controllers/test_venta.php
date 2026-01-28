<?php
require_once "../model/producto/Producto.php";
require_once "../model/lote/Lote.php";
require_once "../model/venta/Venta.php";
require_once "../model/venta/DetalleVenta.php";

// DATOS DE PRUEBA
$producto_id = 1;
$tipo_venta  = "paquete"; // unidad | paquete
$cantidad    = 2;

// 1️⃣ Obtener producto
$producto = Producto::obtenerPorId($producto_id);

if (!$producto) {
    die("Producto no encontrado");
}

// 2️⃣ Calcular unidades y precio
if ($tipo_venta === "unidad") {
    $unidades = $cantidad;
    $precio   = $producto['precio_unidad'];
} else {
    $unidades = $cantidad * $producto['unidades_por_paquete'];
    $precio   = $producto['precio_paquete'];
}

// 3️⃣ Descontar stock
if (!Lote::descontarStock($producto_id, $unidades)) {
    die("No hay stock suficiente");
}

// 4️⃣ Crear venta
$venta_id = Venta::crearVenta();

// 5️⃣ Calcular subtotal
$subtotal = $precio * $cantidad;

// 6️⃣ Agregar detalle
DetalleVenta::agregarDetalle(
    $venta_id,
    $producto_id,
    $tipo_venta,
    $cantidad,
    $precio,
    $subtotal
);

// 7️⃣ Actualizar total
Venta::actualizarTotal($venta_id);

echo "✅ Venta registrada correctamente. ID: " . $venta_id;