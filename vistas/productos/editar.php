<?php
require_once "../../config/conexion.php";
require_once "../../modelos/producto_modelo.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("ID inválido");

$producto = obtenerProductoPorIds($conexion, $id);
if (!$producto) die("Producto no encontrado");
?>

<h2>Editar producto</h2>

<form action="../../controladores/producto_actualizar.php" method="POST">
    <input type="hidden" name="id" value="<?= $producto['id'] ?>">

    <label>Nombre</label><br>
    <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required><br><br>

    <label>Precio Unidad</label><br>
    <input type="number" step="0.01" name="precio_unidad" value="<?= $producto['precio_unidad'] ?>" required><br><br>

    <label>Precio Paquete</label><br>
    <input type="number" step="0.01" name="precio_paquete" value="<?= $producto['precio_paquete'] ?>"><br><br>

    <label>Estado</label><br>
    <select name="activo">
        <option value="1" <?= $producto['activo'] ? 'selected' : '' ?>>Activo</option>
        <option value="0" <?= !$producto['activo'] ? 'selected' : '' ?>>Desactivado</option>
    </select><br><br>

    <button type="submit">Guardar cambios</button>
</form>
