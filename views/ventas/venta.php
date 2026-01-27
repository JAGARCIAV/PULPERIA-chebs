<?php
require_once "../../model/producto/Producto.php";
$productos = Producto::obtenerTodos();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Venta</title>
    <link rel="stylesheet" href="../../public/css/a.css">
</head>
<body>

<?php if (isset($_GET['ok'])): ?>
    <p style="color:green;">
        ✅ Venta registrada correctamente (ID: <?= $_GET['venta_id'] ?>)
    </p>
<?php endif; ?>

<h2>Registrar Venta</h2>

<form action="../../controllers/ventaController.php" method="POST">

    <!-- PRODUCTO -->
    <label>Producto</label><br>

    <input type="text" name="producto_nombre" list="lista_productos"
        placeholder="🔍 Escriba el nombre del producto" required>

    <datalist id="lista_productos">
        <!-- productos cargados con PHP -->
        datalist id="lista_productos">
        <?php foreach ($productos as $p): ?>
            <option value="<?= $p['nombre'] ?>" data-id="<?= $p['id'] ?>">
        <?php endforeach; ?>
        </datalist>
    </datalist>

    <input type="hidden" name="producto_id" id="producto_id">    <br><br>

    <!-- TIPO DE VENTA -->
    <label>Tipo de venta</label><br>
    <select name="tipo_venta" id="tipo_venta" required>
        <option value="unidad">Unidad</option>
        <option value="paquete">Paquete</option>
    </select><br><br>

    <!-- CANTIDAD -->
    <label>Cantidad</label><br>
    <input type="number" name="cantidad" min="1" required><br><br>

    <button type="submit">Vender</button>

</form>

</body>
</html>