<?php
require_once __DIR__ . "/config/auth.php";
require_role(['admin']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sistema de Tienda - Admin</title>
</head>
<body>
<h1>Sistema de Tienda (ADMIN)</h1>

<a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"><button>Ventas / Caja</button></a>
<hr>

<a href="/PULPERIA-CHEBS/vistas/productos/listar.php">Gestionar Productos</a><br>
<a href="/PULPERIA-CHEBS/vistas/lotes/listar.php">Control de Lotes</a><br>
<a href="/PULPERIA-CHEBS/vistas/movimientos/historial.php">Historial de Inventario</a><br>
<a href="/PULPERIA-CHEBS/vistas/ventas/historial.php">Historial de Ventas</a><br>

<hr>
<a href="/PULPERIA-CHEBS/controladores/logout.php">Cerrar sesión</a>
</body>
</html>
