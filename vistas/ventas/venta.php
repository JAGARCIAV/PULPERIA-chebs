<?php
require_once "../../config/conexion.php";
require_once "../../modelos/producto_modelo.php";
require_once "../../modelos/venta_modelo.php";
require_once "../../modelos/caja_turno_modelo.php";

include "../layout/header.php";

$turno = turnoActual();
$totalTurno = totalTurnoHoy($conexion, $turno);
$yaCerrado = existeCierreTurnoHoy($conexion, $turno);
$cierresHoy = obtenerCierresHoy($conexion);
$totalDiaCierres = totalDiaDesdeCierres($conexion); // ✅ total del día sumando cierres

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
        <input id="producto_nombre" type="text" name="producto_nombre" list="lista_productos"
               placeholder="Escriba el nombre de" required>

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

        <div id="stock_info" style="color:#555; font-size:13px; margin-bottom:10px;"></div>

        <button type="button" onclick="agregarDesdeFormulario()">Agregar</button>
    </form>

    <hr>

    <h3>Detalle de la venta</h3>

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

  <!-- COLUMNA DERECHA: HISTORIAL + CAJA -->
  <div style="flex:1; border:1px solid #ccc; padding:10px; max-height:80vh; overflow:auto;">

    <h3>Últimas ventas (hoy)</h3>

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

    <!-- TOTAL DEL DÍA -->
    <div style="margin-top:15px; padding-top:10px; border-top:2px solid #000;">
      <h3 style="margin:0;">Total vendido hoy</h3>
      <p style="font-size:18px; font-weight:bold; margin:6px 0; text-align:right;">
        Bs <?= number_format($totalHoy, 2) ?>
      </p>
    </div>

    <!-- ✅ CAJA POR TURNO (DENTRO DE LA COLUMNA DERECHA) -->
    <div style="margin-top:15px; padding-top:10px; border-top:2px solid #000;">
      <h3 style="margin:0;">📦 Caja por turno</h3>

      <?php if (isset($_GET["turno_ok"])) { ?>
        <div style="background:#e9ffe9; border:1px solid #6c6; padding:8px; margin:10px 0;">
          ✅ Turno cerrado correctamente.
        </div>
      <?php } ?>

      <?php if (isset($_GET["turno_err"])) { ?>
        <div style="background:#ffe9e9; border:1px solid #c66; padding:8px; margin:10px 0;">
          ❌ <?= htmlspecialchars($_GET["turno_err"]) ?>
        </div>
      <?php } ?>

      <p style="margin:6px 0;">
        <b>Turno actual:</b> <?= htmlspecialchars($turno) ?><br>
        <b>Total turno:</b> Bs <?= number_format($totalTurno, 2) ?>
      </p>

      <form action="../../controladores/cerrar_turno.php" method="POST">
        <input type="hidden" name="turno" value="<?= htmlspecialchars($turno) ?>">
        <input type="text" name="observacion" placeholder="Observación (opcional)" style="width:100%; margin-bottom:8px; box-sizing:border-box;">
        <button type="submit" style="width:100%; padding:10px; font-weight:bold;"
          <?= $yaCerrado ? "disabled" : "" ?>
          onclick="return confirm('¿Cerrar caja del turno <?= $turno ?>?')">
          ✅ Cerrar turno
        </button>
      </form>

      <?php if ($yaCerrado) { ?>
        <div style="color:green; margin-top:6px;">✅ Turno ya cerrado.</div>
      <?php } ?>

      <hr>

      <h4 style="margin:6px 0;">Cierres de hoy</h4>
      <?php
        $hay = false;
        while($c = $cierresHoy->fetch_assoc()) {
          $hay = true;
      ?>
        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
          <span><?= htmlspecialchars($c["turno"]) ?></span>
          <b>Bs <?= number_format((float)$c["total_ventas"],2) ?></b>
        </div>
      <?php } ?>

      <?php if (!$hay) { ?>
        <div style="color:#666;">Aún no hay cierres hoy.</div>
      <?php } ?>

      <div style="border-top:1px solid #aaa; margin-top:10px; padding-top:10px; text-align:right;">
        <div style="font-size:12px; color:#666;">Total del día (sumando cierres)</div>
        <div style="font-size:18px; font-weight:bold;">
          Bs <?= number_format($totalDiaCierres,2) ?>
        </div>
      </div>
    </div>

  </div>

</div>

<script src="../../public/js/venta.js"></script>
<?php include "../layout/footer.php"; ?>
