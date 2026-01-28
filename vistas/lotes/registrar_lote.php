<?php
require_once "../../config/conexion.php";
require_once "../../modelos/lote_modelo.php";
include "../layout/header.php";

$productos = obtenerProductos($conexion);
?>

<h2>Ingreso de Mercadería (Lote)</h2>

<form action="../../controladores/lote_controlador.php" method="POST">

    Producto:
    <select name="producto_id" required>
        <option value="">Seleccione</option>
        <?php while($p = $productos->fetch_assoc()) { ?>
            <option value="<?= $p['id'] ?>"><?= $p['nombre'] ?></option>
        <?php } ?>
    </select>
    <br><br>

    Fecha de vencimiento:
    <input type="date" name="fecha_vencimiento" required><br><br>

    Cantidad (unidades):
    <input type="number" name="cantidad" required><br><br>

    <button type="submit">Registrar Lote</button>
</form>

<?php include "../layout/footer.php"; ?>
