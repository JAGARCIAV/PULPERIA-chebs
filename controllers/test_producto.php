<?php
require_once "../model/producto/Producto.php";

$productos = Producto::obtenerTodos();

echo "<pre>";
print_r($productos);
echo "</pre>";