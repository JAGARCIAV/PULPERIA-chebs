<?php
// ✅ FIX: Agregado auth — antes cualquiera podía ver detalles sin sesión
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../modelos/venta_modelo.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) exit("Venta inválida");

$venta   = obtenerVentaPorId($conexion, $id);
$detalle = obtenerDetalleVenta($conexion, $id);

if (!$venta) exit("Venta no encontrada");
?>

<h3>Detalle Venta #<?= (int)$venta['id'] ?></h3>
<p><b>Fecha:</b> <?= htmlspecialchars($venta['fecha']) ?></p>
<p><b>Total:</b> Bs <?= number_format((float)$venta['total'], 2) ?></p>

<table border="1" width="100%">
  <thead>
    <tr>
      <th>Producto</th>
      <th>Tipo</th>
      <th>Cantidad</th>
      <th>Precio</th>
      <th>Subtotal</th>
    </tr>
  </thead>
  <tbody>
  <?php while($d = $detalle->fetch_assoc()) { ?>
  <tr>
    <td><?= htmlspecialchars($d['nombre']) ?></td>
    <td><?= htmlspecialchars($d['tipo_venta']) ?></td>
    <td><?= (int)$d['cantidad'] ?></td>
    <td>Bs <?= number_format((float)$d['precio_unitario'], 2) ?></td>
    <td>Bs <?= number_format((float)$d['subtotal'], 2) ?></td>
  </tr>
  <?php } ?>
  </tbody>
</table>