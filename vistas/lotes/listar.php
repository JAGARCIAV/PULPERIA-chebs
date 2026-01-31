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
    <th>Editar</th>
    <th>Corregir Producto</th>
    <th>Desactivar</th>
</tr>

<?php while($l = $lotes->fetch_assoc()) { ?>
<tr style="<?= (strtotime($l['fecha_vencimiento']) < time()) ? 'background:red;color:white;' : '' ?>">
    <td><?= $l['nombre'] ?></td>
    <td><?= $l['fecha_vencimiento'] ?></td>
    <td><?= $l['cantidad_unidades'] ?></td>
    <td><a href="editar.php?id=<?= $l['id'] ?>">Editar</a></td>
    <td><a href="corregir_producto.php?id=<?= $l['id'] ?>">Corregir producto</a></td>
    <td>
        <?php if ($l['activo']) { ?>
            <a href="../../controladores/desactivar_lote.php?id=<?= $l['id'] ?>" onclick="return confirm('¿Desactivar este lote?');">Desactivar</a>
        <?php } else { ?>
            Inactivo
        <?php } ?></td>
</tr>
<?php } ?>
</table>
<?php include "../layout/footer.php"; ?>