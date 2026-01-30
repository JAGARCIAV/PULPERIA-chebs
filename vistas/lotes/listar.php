<?php
require_once "../../config/conexion.php";
require_once "../../modelos/lote_modelo.php";
include "../layout/header.php";

$lotes = obtenerLotes($conexion);
?>

<h2>Lotes Registrados</h2>

<a href="registrar_lote.php">➕ Nuevo Lote</a><br><br>

<table border="1">
<tr>
    <th>Producto</th>
    <th>Vencimiento</th>
    <th>Cantidad</th>
</tr>

<?php while($l = $lotes->fetch_assoc()) { ?>
<tr style="<?= (strtotime($l['fecha_vencimiento']) < time()) ? 'background:red;color:white;' : '' ?>">
    <td><?= $l['nombre'] ?></td>
    <td><?= $l['fecha_vencimiento'] ?></td>
    <td><?= $l['cantidad_unidades'] ?></td>
</tr>
<?php } ?>

</table>

<?php include "../layout/footer.php"; ?>