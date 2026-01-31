<?php
require_once "../../config/conexion.php";
require_once "../../modelos/lote_modelo.php";
include "../layout/header.php";

$lote = obtenerLotePorId($conexion, $_GET['id']);
$productos = $conexion->query("SELECT id, nombre FROM productos");
?>

<h2>Corregir Producto del Lote</h2>

<p><b>Lote ID:</b> <?= $lote['id'] ?></p>
<p><b>Producto actual:</b> <?= $lote['producto_id'] ?></p>
<p><b>Cantidad:</b> <?= $lote['cantidad_unidades'] ?></p>

<form action="../../controladores/corregir_producto_lote.php" method="POST">
    <input type="hidden" name="lote_id" value="<?= $lote['id'] ?>">

    Nuevo producto:
    <select name="nuevo_producto_id" required>
        <?php while($p = $productos->fetch_assoc()) { ?>
            <option value="<?= $p['id'] ?>"><?= $p['nombre'] ?></option>
        <?php } ?>
    </select><br><br>

    <button type="submit">Corregir</button>
</form>

<?php include "../layout/footer.php"; ?>
