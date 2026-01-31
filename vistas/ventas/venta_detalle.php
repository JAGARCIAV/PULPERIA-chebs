<?php
require_once "../../config/conexion.php";
require_once "../../modelos/venta_modelo.php";
include "../layout/header.php";
$id = (int)($_GET['id'] ?? 0);

$detalle = obtenerDetalleVenta($conexion, $id);
?>

<h2>Detalle Venta #<?= $id ?></h2>

<table border="1" width="100%">
<tr>
    <th>Producto</th>
    <th>Tipo</th>
    <th>Cantidad</th>
    <th>Precio</th>
    <th>Subtotal</th>
</tr>

<?php while($d = $detalle->fetch_assoc()) { ?>
<tr>
    <td><?= htmlspecialchars($d['nombre']) ?></td>
    <td><?= $d['tipo_venta'] ?></td>
    <td><?= $d['cantidad'] ?></td>
    <td><?= $d['precio_unitario'] ?></td>
    <td><?= $d['subtotal'] ?></td>
</tr>
<?php } ?>
</table>

<?php include "../layout/footer.php"; ?>
