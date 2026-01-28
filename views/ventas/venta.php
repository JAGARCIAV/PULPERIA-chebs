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

<h2>Registrar Venta</h2>

<!-- 🔲 FUTURO: LECTOR DE CÓDIGO DE BARRAS -->
<!--
<input type="text" id="codigo_barras" placeholder="Escanee el código">
-->

<!-- FORMULARIO SOLO PARA AGREGAR AL CARRITO -->
<form id="form_producto">

    <label>Producto</label><br>
    <input type="text" name="producto_nombre" list="lista_productos"
           placeholder="🔍 Escriba el nombre del producto" required>

    <datalist id="lista_productos">
        <?php foreach ($productos as $p): ?>
            <option value="<?= $p['nombre'] ?>" data-id="<?= $p['id'] ?>"></option>
        <?php endforeach; ?>
    </datalist>

    <input type="hidden" id="producto_id">

    <br><br>

    <label>Tipo de venta</label><br>
    <select id="tipo_venta">
        <option value="unidad">Unidad</option>
        <option value="paquete">Paquete</option>
    </select>

    <br><br>

    <label>Cantidad</label><br>
    <input type="number" id="cantidad" min="1" value="1">

    <br><br>

    <button type="button" onclick="agregarDesdeFormulario()">Agregar</button>
</form>

<hr>

<h3>🧾 Detalle de la venta</h3>

<table border="1" width="100%" id="tabla_detalle">
    <thead>
        <tr>
            <th>Producto</th>
            <th>Tipo</th>
            <th>Cantidad</th>
            <th>Precio</th>
            <th>Subtotal</th>
            <th>❌</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<h3>Total: Bs <span id="total">0.00</span></h3>

<button id="btn_confirmar">Confirmar venta</button>

<script src="../../public/js/venta.js"></script>
</body>
</html>