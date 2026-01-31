<?php
require_once "../../config/conexion.php";
require_once "../../modelos/venta_modelo.php";
include "../layout/header.php";

$fecha     = $_GET['fecha'] ?? null;
$turno     = $_GET['turno'] ?? null;
$tipo      = $_GET['tipo'] ?? null;
$busqueda  = $_GET['busqueda'] ?? null;

$ventas = obtenerVentasFiltradas($conexion, $fecha, $turno, $tipo, $busqueda);
?>
<link rel="stylesheet" href="../../public/CSS/modal.css">

<h2>Historial de Ventas</h2>

<form method="GET" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">

    <!-- FILTRO POR FECHA -->
    <label>Fecha:</label>
    <input type="date" name="fecha" value="<?= htmlspecialchars($_GET['fecha'] ?? '') ?>">

    <!-- FILTRO POR TURNO (select separado) -->
    <label>Turno:</label>
    <select name="turno">
        <option value="">Todos</option>
        <option value="mañana" <?= (($_GET['turno'] ?? '') === 'mañana' ? 'selected' : '') ?>>Mañana</option>
        <option value="tarde" <?= (($_GET['turno'] ?? '') === 'tarde' ? 'selected' : '') ?>>Tarde</option>
    </select>

    <!-- FILTRO DE BUSCADOR GENERAL (ID / Responsable) -->
    <label>Buscar por:</label>
    <select name="tipo">
        <option value="id">ID Venta</option>
        <option value="responsable">Responsable</option>
    </select>

    <input type="text" name="busqueda" placeholder="Buscar..." value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">

    <button type="submit">Filtrar</button>
</form>


<br>

<table border="1" width="100%">
    <tr>
        <th>ID</th>
        <th>Fecha</th>
        <th>Turno</th>
        <th>Responsable</th>
        <th>Total</th>
        <th>Detalle</th>
    </tr>


    
    <?php while($v = $ventas->fetch_assoc()) { ?>
    <tr>
        <td><?= $v['id'] ?></td>
        <td><?= $v['fecha'] ?></td>
        <td><?= $v['turno'] ?></td>
        <td><?= htmlspecialchars($v['responsable']) ?></td>
        <td>Bs <?= number_format($v['total'],2) ?></td>
        <td>
        <button onclick="verDetalleVenta(<?= (int)$v['id'] ?>)">
            Ver detalle
        </button>
        </td>
    </tr>
    <?php } ?>
</table>

<div id="modalGeneral" class="modal-bg" style="display:none;">
  <div class="modal-box">
    <span class="cerrar" onclick="cerrarModalGeneral()">✖</span>
    <div id="modalContenido"></div>
  </div>
</div>

<script src="../../public/js/ventas_historial.js"></script>

<?php include "../layout/footer.php"; ?>