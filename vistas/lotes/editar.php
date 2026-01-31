<?php
require_once "../../config/conexion.php";
require_once "../../modelos/lote_modelo.php";
include "../layout/header.php";

$lote = obtenerLotePorId($conexion, $_GET['id']);
?>

<h2>Editar Lote</h2>

<form action="../../controladores/lote_editar_controlador.php" method="POST">
    <input type="hidden" name="lote_id" value="<?= $lote['id'] ?>">

    Producto ID: <?= $lote['producto_id'] ?> <br><br>

    Fecha vencimiento:
    <input type="date" name="fecha_vencimiento" value="<?= $lote['fecha_vencimiento'] ?>"><br><br>

    Cantidad actual: <?= $lote['cantidad_unidades'] ?><br><br>

    Nueva cantidad física:
    <input type="number" name="cantidad_unidades" value="<?= $lote['cantidad_unidades'] ?>"><br><br>

    Motivo ajuste (si cambia cantidad):
    <select name="motivo">
        <option value="">-- Seleccionar si hay cambio --</option>
        <option>Conteo físico</option>
        <option>Producto dañado</option>
        <option>Producto vencido</option>
        <option>Error de registro</option>
    </select><br><br>

    <button type="submit">Guardar cambios</button>
</form>

<?php include "../layout/footer.php"; ?>
