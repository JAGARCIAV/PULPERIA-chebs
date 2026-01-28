<?php
require_once "../../config/conexion.php";
require_once "../../modelos/producto_modelo.php";
require_once "../../modelos/venta_modelo.php";

include "../layout/header.php";

$productos = obtenerProductos($conexion);
$totalHoy = obtenerTotalVentasHoy($conexion);
$ultimasVentas = obtenerUltimasVentas($conexion, 10);
?>

<h2>Registrar Venta</h2>

<div style="display:flex; gap:20px; align-items:flex-start;">

  <!-- COLUMNA IZQUIERDA: VENTA -->
  <div style="flex:2;">

    <form id="form_producto" onsubmit="return false;">

        <label>Producto</label><br>
        <input type="text" name="producto_nombre" list="lista_productos"
               placeholder="🔍 Escriba el nombre de" required>

        <datalist id="lista_productos">
            <?php while($p = $productos->fetch_assoc()) { ?>
                <option value="<?= htmlspecialchars($p['nombre']) ?>" data-id="<?= (int)$p['id'] ?>"></option>
            <?php } ?>
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

    <button id="btn_confirmar" type="button">Confirmar venta</button>

  </div>

  <!-- COLUMNA DERECHA: HISTORIAL -->
  <div style="flex:1; border:1px solid #ccc; padding:10px; max-height:80vh; overflow:auto;">
    <h3>🧾 Últimas ventas</h3>

    <form action="../../controladores/ventas_limpiar.php" method="POST" style="margin-bottom:10px;">
      <button type="submit" onclick="return confirm('¿Seguro que desea limpiar el historial del día?')">
        Limpiar historial (hoy)
      </button>
    </form>

    <?php while($v = $ultimasVentas->fetch_assoc()) { ?>
      <div style="border-bottom:1px solid #ddd; padding:10px 0;">
        <b>Venta #<?= (int)$v['id'] ?></b><br>
        <small><?= htmlspecialchars($v['fecha']) ?></small>

        <div style="margin-top:6px; padding-left:8px;">
          <?php
            $det = obtenerDetalleVenta($conexion, (int)$v['id']);
            while($d = $det->fetch_assoc()) {
          ?>
            <div style="display:flex; justify-content:space-between; gap:8px;">
              <span style="flex:1;">
                <?= htmlspecialchars($d['nombre']) ?>
                (<?= htmlspecialchars($d['tipo_venta']) ?>) x<?= (int)$d['cantidad'] ?>
              </span>
              <span>
                Bs <?= number_format((float)$d['subtotal'], 2) ?>
              </span>
            </div>
            <div style="color:#666; font-size:12px; margin-bottom:6px;">
              Bs <?= number_format((float)$d['precio_unitario'], 2) ?> c/u
            </div>
          <?php } ?>
        </div>

        <div style="margin-top:8px; font-weight:bold; text-align:right;">
          Total: Bs <?= number_format((float)$v['total'], 2) ?>
        </div>
      </div>
    <?php } ?>

    <!-- ✅ TOTAL DEL DÍA / CAJA -->
    <div style="margin-top:15px; padding-top:10px; border-top:2px solid #000;">
      <h3 style="margin:0;">💰 Total vendido hoy</h3>
      <p style="font-size:18px; font-weight:bold; margin:6px 0; text-align:right;">
        Bs <?= number_format($totalHoy, 2) ?>
      </p>
    </div>

  </div>

</div>

<script src="../../public/js/venta.js"></script>

<?php include "../layout/footer.php"; ?>
