
<?php
require_once "../model/producto/Producto.php";
require_once "../model/lote/Lote.php";
require_once "../model/venta/Venta.php";
require_once "../model/venta/DetalleVenta.php";


// 1️⃣ Validar que venga por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso no permitido");
}

// 2️⃣ Recibir datos del formulario
$producto_id = $_POST['producto_id'] ?? null;
$tipo_venta  = $_POST['tipo_venta'] ?? null;
$cantidad    = $_POST['cantidad'] ?? null;

// 3️⃣ Validaciones básicas
if (!$producto_id || !$tipo_venta || !$cantidad || $cantidad <= 0) {
    die("Datos inválidos");
}

// 4️⃣ Obtener producto
$producto = Producto::obtenerPorId($producto_id);
if (!$producto) {
    die("Producto no encontrado");
}

// 5️⃣ Calcular unidades a descontar y precio
if ($tipo_venta === "unidad") {
    $unidades = (int)$cantidad;
    $precio   = (float)$producto['precio_unidad'];
} elseif ($tipo_venta === "paquete") {
    $unidades = (int)$cantidad * (int)$producto['unidades_por_paquete'];
    $precio   = (float)$producto['precio_paquete'];
} else {
    die("Tipo de venta inválido");
}

// 🔍 Validar stock disponible
$stock_disponible = Lote::obtenerStockDisponible($producto_id);

if ($stock_disponible < $unidades) {
    die("❌ Stock insuficiente. Disponible: $stock_disponible unidades");
}

// 6️⃣ Descontar stock por lotes (FIFO)
$stock_ok = Lote::descontarStock($producto_id, $unidades);

if (!$stock_ok) {
    die("No hay stock suficiente");
}

// 7️⃣ Crear venta
$venta_id = Venta::crearVenta();

// 8️⃣ Calcular subtotal
$subtotal = $precio * (int)$cantidad;

// 9️⃣ Guardar detalle de venta
DetalleVenta::agregarDetalle(
    $venta_id,
    $producto_id,
    $tipo_venta,
    $cantidad,
    $precio,
    $subtotal
);

// 🔟 Actualizar total de la venta
Venta::actualizarTotal($venta_id);

// 1️⃣1️⃣ Respuesta final
header("Location: ../views/ventas/venta.php?ok=1&venta_id=" . $venta_id);
exit;