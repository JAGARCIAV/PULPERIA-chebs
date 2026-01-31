<?php
require_once "../../config/conexion.php";
require_once "../../modelos/movimiento_modelo.php";
include "../layout/header.php";

$producto_id = $_GET['producto_id'] ?? null;

$productos = $conexion->query("SELECT id, nombre FROM productos");
$movimientos = obtenerMovimientos($conexion, $producto_id);
?>

<h2>Historial General de Inventario</h2>

<form method="GET">
    Filtrar por producto:
    <select name="producto_id">
        <option value="">-- Todos --</option>
        <?php while($p = $productos->fetch_assoc()) { ?>
            <option value="<?= $p['id'] ?>" <?= ($producto_id == $p['id']) ? 'selected' : '' ?>>
                <?= $p['nombre'] ?>
            </option>
        <?php } ?>
    </select>
    <button type="submit">Filtrar</button>
</form>

<br>

<table border="1" cellpadding="5">
<tr>
    <th>Fecha</th>
    <th>Producto</th>
    <th>Tipo</th>
    <th>Cantidad</th>
    <th>Lote (Vence)</th>
    <th>Motivo</th>
</tr>

<?php while($m = $movimientos->fetch_assoc()) { ?>
<tr style="
    <?php 
        if ($m['tipo'] == 'entrada') echo 'background:#d4edda;';
        elseif ($m['tipo'] == 'salida') echo 'background:#f8d7da;';
        else echo 'background:#fff3cd;';
    ?>
">
    <td><?= $m['fecha'] ?></td>
    <td><?= $m['producto'] ?></td>
    <td><?= strtoupper($m['tipo']) ?></td>
    <td><?= $m['cantidad'] ?></td>
    <td><?= $m['fecha_vencimiento'] ?? '-' ?></td>
    <td><?= $m['motivo'] ?></td>
</tr>
<?php } ?>

</table>

<?php include "../layout/footer.php"; ?>
