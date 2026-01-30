<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);
?>

<?php 
include "../layout/header.php"; ?>

<h2>Crear Producto</h2>

<form action="../../controladores/producto_controlador.php" method="POST">

    Nombre: <input type="text" name="nombre" required><br><br>
    Descripción: <textarea name="descripcion"></textarea><br><br>

    Precio por unidad: <input type="number" step="0.01" name="precio_unidad" required><br><br>
    Precio por paquete: <input type="number" step="0.01" name="precio_paquete"><br><br>
    Unidades por paquete: <input type="number" name="unidades_paquete" value="1"><br><br>

    <button type="submit">Guardar Producto</button>
</form>

<?php include "../layout/footer.php"; ?>
