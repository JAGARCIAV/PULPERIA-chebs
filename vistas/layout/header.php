<?php
require_once __DIR__ . "/../../config/auth.php";
require_login();
$rol = $_SESSION['user']['rol'] ?? '';
?>
<nav style="display:flex; gap:10px; padding:10px; background:#eee;">
  <?php if ($rol === 'admin'): ?>
    <a href="/PULPERIA-CHEBS/index.php">Inicio</a>
    <a href="/PULPERIA-CHEBS/vistas/productos/listar.php">Productos</a>
    <a href="/PULPERIA-CHEBS/vistas/lotes/listar.php">Lotes</a>
  <?php endif; ?>

  <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php">Ventas</a>
  <a href="/PULPERIA-CHEBS/controladores/logout.php">Salir</a>
</nav>
<hr>
