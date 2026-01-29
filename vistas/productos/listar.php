<?php
require_once "../../config/conexion.php";
require_once "../../modelos/producto_modelo.php";
include "../layout/header.php";

$productos = obtenerProductos($conexion);
?>

<h2>Lista de Productos</h2>

<a href="crear.php">➕ Nuevo Producto</a><br><br>

<table border="1">
<tr>
    <th>ID</th>
    <th>Nombre</th>
    <th>Precio Unidad</th>
    <th>Precio Paquete</th>
    <th>Stock</th>
</tr>

<?php while($p = $productos->fetch_assoc()) { ?>
<tr>
    <td><?= $p['id'] ?></td>
    <td><?= $p['nombre'] ?></td>
    <td><?= $p['precio_unidad'] ?></td>
    <td><?= $p['precio_paquete'] ?></td>
    <td><?= $cantidad = obtenerStockTotal($conexion, $p['id']); ?></td>
</tr>
<?php } ?>

</table>

<?php include "../layout/footer.php"; ?>
