<?php
require_once "../../config/conexion.php";
require_once "../../modelos/producto_modelo.php";
require_once "../../modelos/venta_modelo.php";
require_once "../../modelos/turno_modelo.php";

include "../layout/header.php";

// ✅ Turno abierto primero
$turnoAbierto = obtenerTurnoAbiertoHoy($conexion);
$ultTurnos = obtenerUltimosTurnos($conexion, 5);

// ✅ Datos base
$productos = obtenerProductos($conexion);
$totalHoy = obtenerTotalVentasHoy($conexion);

// ✅ Historial desde marca del turno
$desde = 0;
if ($turnoAbierto) {
    $desde = (int)$turnoAbierto["historial_desde_venta_id"];
}
$ultimasVentas = obtenerUltimasVentasDesde($conexion, $desde, 10);

// ✅ Total vendido en turno
$totalTurno = 0;
if ($turnoAbierto) {
    $totalTurno = totalVentasTurno($conexion, (int)$turnoAbierto["id"]);
}
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

    <?php if(!$turnoAbierto){ ?>
      <div style="color:red; margin:8px 0;">❌ Abra turno para poder vender.</div>
    <?php } ?>

    <button id="btn_confirmar" type="button" <?= (!$turnoAbierto ? "disabled" : "") ?>>
      Confirmar venta
    </button>

  </div>

  <!-- COLUMNA DERECHA: TURNO + HISTORIAL -->
  <div style="flex:1; border:1px solid #ccc; padding:10px; max-height:80vh; overflow:auto;">

    <!-- ✅ TURNO PRO -->
    <div style="border:2px solid #000; padding:10px; margin-bottom:15px;">
      <h3 style="margin:0;">⏱ Turno</h3>

      <?php if (isset($_GET["turno_ok"])) { ?>
        <div style="color:green; margin-top:8px;">✅ Turno abierto.</div>
      <?php } ?>
      <?php if (isset($_GET["turno_cerrado"])) { ?>
        <div style="color:green; margin-top:8px;">✅ Turno cerrado.</div>
      <?php } ?>
      <?php if (isset($_GET["turno_err"])) { ?>
        <div style="color:red; margin-top:8px;">❌ <?= htmlspecialchars($_GET["turno_err"]) ?></div>
      <?php } ?>
      <?php if (isset($_GET["hist_ok"])) { ?>
        <div style="color:green; margin-top:8px;">✅ Historial ocultado.</div>
      <?php } ?>

      <?php if (!$turnoAbierto) { ?>
        <form action="../../controladores/turno_abrir.php" method="POST" style="margin-top:10px;">
          <label>Responsable</label><br>
          <select name="responsable" style="width:100%; margin-bottom:8px;">
            <option>Dueña</option>
            <option>Empleado A</option>
            <option>Empleado B</option>
          </select>

          <label>Monto inicial (cambio)</label><br>
          <input type="number" step="0.01" name="monto_inicial" value="0"
                 style="width:100%; margin-bottom:8px; box-sizing:border-box;">

          <button type="submit" style="width:100%; padding:10px; font-weight:bold;">
             Abrir turno
          </button>
        </form>

        <div style="margin-top:8px; font-size:12px; color:#666;">
          * Debe abrir turno para poder vender.
        </div>

      <?php } else { ?>
        <div style="margin-top:10px;">
          <b>Turno ID:</b> <?= (int)$turnoAbierto["id"] ?><br>
          <b>Responsable:</b> <?= htmlspecialchars($turnoAbierto["responsable"]) ?><br>
          <b>Abierto:</b> <?= htmlspecialchars($turnoAbierto["abierto_en"]) ?><br>
          <b>Cambio inicial:</b> Bs <?= number_format((float)$turnoAbierto["monto_inicial"],2) ?><br>
          <b>Total vendido en turno:</b> Bs <?= number_format($totalTurno,2) ?>
        </div>

        <form action="../../controladores/turno_cerrar.php" method="POST" style="margin-top:10px;">
          <input type="hidden" name="turno_id" value="<?= (int)$turnoAbierto["id"] ?>">
          <button type="submit"
                  style="width:100%; padding:10px; font-weight:bold;"
                  onclick="return confirm('¿Cerrar este turno?')">
             Cerrar turno
          </button>
        </form>
      <?php } ?>

      <hr>
      <h4 style="margin:8px 0;">Últimos turnos</h4>
      <?php while($t = $ultTurnos->fetch_assoc()) { ?>
        <div style="font-size:13px; margin-bottom:6px;">
          #<?= (int)$t["id"] ?> - <?= htmlspecialchars($t["fecha"]) ?> - <?= htmlspecialchars($t["estado"]) ?>
          (<?= htmlspecialchars($t["responsable"]) ?>)
        </div>
      <?php } ?>
    </div>

    <!-- ✅ HISTORIAL -->
    <h3>Últimas ventas (hoy)</h3>

    <form action="../../controladores/historial_limpiar.php" method="POST" style="margin-bottom:10px;">
      <button type="submit" onclick="return confirm('¿Ocultar ventas anteriores en el historial?')">
        Limpiar historial (ocultar)
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
              <span>Bs <?= number_format((float)$d['subtotal'], 2) ?></span>
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

    <div style="margin-top:15px; padding-top:10px; border-top:2px solid #000;">
      <h3 style="margin:0;">Total vendido hoy</h3>
      <p style="font-size:18px; font-weight:bold; margin:6px 0; text-align:right;">
        Bs <?= number_format($totalHoy, 2) ?>
      </p>
    </div>

  </div>
</div>

<script src="../../public/js/venta.js"></script>
<?php include "../layout/footer.php"; ?>
