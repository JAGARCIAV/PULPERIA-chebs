<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../../config/conexion.php"; // ✅ TU CONEXION OFICIAL (mysqli)
require_once __DIR__ . "/../../modelos/producto_modelo.php";
require_once __DIR__ . "/../../modelos/venta_modelo.php";
require_once __DIR__ . "/../../modelos/turno_modelo.php";
require_once __DIR__ . "/../../modelos/lote_modelo.php"; // para stock FIFO si lo usas en ventas

include __DIR__ . "/../layout/header.php";

if (session_status() === PHP_SESSION_NONE) session_start();

$rol    = $_SESSION['user']['rol'] ?? '';
$userId = (int)($_SESSION['user']['id'] ?? 0);

// ✅ Turno abierto primero
$turnoAbierto = obtenerTurnoAbiertoHoy($conexion);

// ✅ Datos base
$productos = obtenerProductos($conexion);
$totalHoy  = obtenerTotalVentasHoy($conexion);

// ✅ Historial desde marca del turno
$desde = 0;
if ($turnoAbierto) {
    $desde = (int)($turnoAbierto["historial_desde_venta_id"] ?? 0);
}
$ultimasVentas = obtenerUltimasVentasDesde($conexion, $desde, 10);

// ✅ Total vendido en turno
$totalTurno = 0;
if ($turnoAbierto) {
    $totalTurno = totalVentasTurno($conexion, (int)$turnoAbierto["id"]);
}

// ✅ Retiros en turno (si tienes la función / tabla)
$totalRetiros = 0;
if ($turnoAbierto && function_exists('totalRetirosTurno')) {
    $totalRetiros = (float) totalRetirosTurno($conexion, (int)$turnoAbierto['id']);
}

// ✅ Efectivo inicial contado (si no existe columna, usa monto_inicial)
$montoInicialUI = 0;
if ($turnoAbierto) {
    $montoInicialUI = (float)($turnoAbierto['efectivo_inicial_contado'] ?? $turnoAbierto['monto_inicial'] ?? 0);
}

// ✅ Efectivo esperado = inicial + ventas - retiros
$efectivoEsperadoUI = 0;
if ($turnoAbierto) {
    $efectivoEsperadoUI = $montoInicialUI + $totalTurno - $totalRetiros;
}

// ✅ Últimos turnos: admin ve todos / empleado solo los suyos
$limite = 5;
if ($rol === 'admin') {
    $stmtT = $conexion->prepare("SELECT * FROM turnos ORDER BY id DESC LIMIT ?");
    $stmtT->bind_param("i", $limite);
} else {
    $stmtT = $conexion->prepare("SELECT * FROM turnos WHERE usuario_id=? ORDER BY id DESC LIMIT ?");
    $stmtT->bind_param("ii", $userId, $limite);
}
$stmtT->execute();
$ultTurnos = $stmtT->get_result();
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

    <!-- ✅ TURNO PRO (con modales) -->
    <div style="border:2px solid #000; padding:10px; margin-bottom:15px;">
      <h3 style="margin:0;">⏱ Turno / Caja</h3>

      <?php if (isset($_GET["turno_ok"])) { ?>
        <div style="color:green; margin-top:8px;">✅ Turno abierto.</div>
      <?php } ?>
      <?php if (isset($_GET["turno_cerrado"])) { ?>
        <div style="color:green; margin-top:8px;">✅ Turno cerrado.</div>
      <?php } ?>
      <?php if (isset($_GET["turno_err"])) { ?>
        <div style="color:red; margin-top:8px;">❌ <?= htmlspecialchars($_GET["turno_err"]) ?></div>
      <?php } ?>
      <?php if (isset($_GET["ret_ok"])) { ?>
        <div style="color:green; margin-top:8px;">✅ Retiro registrado.</div>
      <?php } ?>
      <?php if (isset($_GET["ret_err"])) { ?>
        <div style="color:red; margin-top:8px;">❌ <?= htmlspecialchars($_GET["ret_err"]) ?></div>
      <?php } ?>

      <?php if (!$turnoAbierto) { ?>

        <div style="margin-top:10px; font-size:13px; color:#333;">
          Para vender primero debes <b>abrir turno</b> contando el efectivo real que hay en caja.
        </div>

        <button type="button"
                style="width:100%; padding:10px; font-weight:bold; margin-top:10px;"
                onclick="abrirModal('modalAbrirTurno')">
          Abrir turno
        </button>

      <?php } else { ?>

        <div style="margin-top:10px; font-size:13px;">
          <b>Turno ID:</b> <?= (int)$turnoAbierto["id"] ?><br>
          <b>Responsable:</b> <?= htmlspecialchars($turnoAbierto["responsable"]) ?><br>
          <b>Abierto:</b> <?= htmlspecialchars($turnoAbierto["abierto_en"]) ?><br>

          <hr style="margin:10px 0;">
          <b>Efectivo inicial contado:</b> Bs <?= number_format($montoInicialUI,2) ?><br>
          <b>Total vendido (turno):</b> Bs <?= number_format($totalTurno,2) ?><br>
          <b>Retiros admin:</b> Bs <?= number_format($totalRetiros,2) ?><br>
          <b>Efectivo esperado:</b> Bs <?= number_format($efectivoEsperadoUI,2) ?><br>
        </div>

        <?php if ($rol === 'admin') { ?>
          <button type="button"
                  style="width:100%; padding:10px; font-weight:bold; margin-top:10px;"
                  onclick="abrirModal('modalRetiro')">
            Retiro de caja (Admin)
          </button>
        <?php } ?>

        <button type="button"
                style="width:100%; padding:10px; font-weight:bold; margin-top:10px;"
                onclick="abrirModal('modalCerrarTurno')">
          Cerrar turno
        </button>

      <?php } ?>

      <?php if ($rol === 'admin') { ?>

        <hr>
        <h4 style="margin:8px 0;">Últimos turnos</h4>

        <?php while($t = $ultTurnos->fetch_assoc()) { ?>
          <div style="font-size:13px; margin-bottom:8px; border-bottom:1px dashed #ccc; padding-bottom:6px;">
            <b>#<?= (int)$t["id"] ?></b> — <?= htmlspecialchars($t["fecha"]) ?><br>

            <b>Entrada:</b> <?= htmlspecialchars($t["abierto_en"]) ?><br>
            <b>Salida:</b> <?= htmlspecialchars($t["cerrado_en"] ?? '-') ?><br>
            <b>Estado:</b> <?= htmlspecialchars($t["estado"]) ?><br>
            <b>Resp:</b> <?= htmlspecialchars($t["responsable"]) ?>
          </div>
        <?php } ?>

      <?php } ?>

    </div>

    <!-- ✅ HISTORIAL -->
    <h3>Últimas ventas (hoy)</h3>

    <form action="../../controladores/historial_limpiar.php" method="POST" style="margin-bottom:10px;">
      <button type="submit" onclick="return confirm('¿Ocultar ventas anteriores en el historial?')">
        Limpiar historial (ocultar)
      </button>
    </form>

        <div style="margin-top:15px; padding-top:10px; border-top:2px solid #000;">
      <h3 style="margin:0;">Total vendido hoy</h3>
      <p style="font-size:18px; font-weight:bold; margin:6px 0; text-align:right;">
        Bs <?= number_format($totalHoy, 2) ?>
      </p>
    </div>

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

<!-- ========================= -->
<!-- ✅ MODAL: ABRIR TURNO -->
<!-- ========================= -->
<div id="modalAbrirTurno" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:9999;">
  <div style="background:#fff; width:360px; max-width:90vw; margin:12vh auto; padding:15px; border-radius:8px;">
    <h3 style="margin:0 0 10px;">Abrir turno</h3>

    <form action="../../controladores/turno_abrir.php" method="POST" onsubmit="return confirmarAbrirTurno();">
      <label><b>¿Cuánto efectivo hay en caja ahora?</b></label>
      <div style="font-size:12px; color:#666; margin:6px 0;">
        (Cuenta el dinero físico antes de empezar)
      </div>

      <input type="number" step="0.01" name="monto_inicial" id="abrir_monto"
             value="0" required
             style="width:100%; padding:8px; box-sizing:border-box;">

      <div style="display:flex; gap:10px; margin-top:12px;">
        <button type="button" style="flex:1; padding:10px;" onclick="cerrarModal('modalAbrirTurno')">Cancelar</button>
        <button type="submit" style="flex:1; padding:10px; font-weight:bold;">Abrir</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================= -->
<!-- ✅ MODAL: CERRAR TURNO -->
<!-- ========================= -->
<div id="modalCerrarTurno" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:9999;">
  <div style="background:#fff; width:380px; max-width:90vw; margin:12vh auto; padding:15px; border-radius:8px;">
    <h3 style="margin:0 0 10px;">Cerrar turno</h3>

    <div style="font-size:13px; color:#333; margin-bottom:10px;">
      <b>Esperado:</b> Bs <?= number_format($efectivoEsperadoUI,2) ?><br>
      <span style="color:#666;">(Inicial + Ventas - Retiros)</span>
    </div>

    <form action="../../controladores/turno_cerrar.php" method="POST" onsubmit="return confirmarCerrarTurno();">
      <input type="hidden" name="turno_id" value="<?= $turnoAbierto ? (int)$turnoAbierto["id"] : 0 ?>">

      <label><b>¿Cuánto efectivo estás dejando?</b></label>
      <div style="font-size:12px; color:#666; margin:6px 0;">
        (Cuenta el dinero físico en caja y escribe el total)
      </div>

      <input type="number" step="0.01" name="efectivo_cierre" id="cerrar_efectivo"
             value="<?= number_format($efectivoEsperadoUI,2,'.','') ?>"
             required style="width:100%; padding:8px; box-sizing:border-box;">

      <div style="display:flex; gap:10px; margin-top:12px;">
        <button type="button" style="flex:1; padding:10px;" onclick="cerrarModal('modalCerrarTurno')">Cancelar</button>
        <button type="submit" style="flex:1; padding:10px; font-weight:bold;">Cerrar</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================= -->
<!-- ✅ MODAL: RETIRO ADMIN -->
<!-- ========================= -->
<?php if ($rol === 'admin') { ?>
<div id="modalRetiro" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:9999;">
  <div style="background:#fff; width:380px; max-width:90vw; margin:12vh auto; padding:15px; border-radius:8px;">
    <h3 style="margin:0 0 10px;">Retiro de caja (Admin)</h3>

    <div style="font-size:13px; color:#333; margin-bottom:10px;">
      <b>Turno actual:</b> #<?= $turnoAbierto ? (int)$turnoAbierto["id"] : 0 ?><br>
      <b>Efectivo esperado ahora:</b> Bs <?= number_format($efectivoEsperadoUI,2) ?>
    </div>

    <form action="../../controladores/retiro_guardar.php" method="POST" onsubmit="return confirmarRetiro();">
      <input type="hidden" name="turno_id" value="<?= $turnoAbierto ? (int)$turnoAbierto["id"] : 0 ?>">

      <label><b>Monto a retirar</b></label>
      <input type="number" step="0.01" name="monto" id="retiro_monto"
             value="0" required
             style="width:100%; padding:8px; box-sizing:border-box;">

      <label style="margin-top:10px; display:block;"><b>Motivo</b> (opcional)</label>
      <input type="text" name="motivo" maxlength="255"
             placeholder="Ej: deposito, guardar dinero..."
             style="width:100%; padding:8px; box-sizing:border-box;">

      <div style="display:flex; gap:10px; margin-top:12px;">
        <button type="button" style="flex:1; padding:10px;" onclick="cerrarModal('modalRetiro')">Cancelar</button>
        <button type="submit" style="flex:1; padding:10px; font-weight:bold;">Guardar</button>
      </div>
    </form>
  </div>
</div>
<?php } ?>

<script>
function abrirModal(id){
  document.getElementById(id).style.display = 'block';
}
function cerrarModal(id){
  document.getElementById(id).style.display = 'none';
}
function confirmarAbrirTurno(){
  const v = parseFloat(document.getElementById('abrir_monto').value || '0');
  if (isNaN(v) || v < 0) { alert('Monto inválido'); return false; }
  return true;
}
function confirmarCerrarTurno(){
  const v = parseFloat(document.getElementById('cerrar_efectivo').value || '0');
  if (isNaN(v) || v < 0) { alert('Efectivo inválido'); return false; }
  return confirm('¿Seguro que deseas cerrar el turno?');
}
function confirmarRetiro(){
  const v = parseFloat(document.getElementById('retiro_monto').value || '0');
  if (isNaN(v) || v <= 0) { alert('El retiro debe ser mayor a 0'); return false; }
  return confirm('¿Registrar retiro de caja?');
}
</script>

<script src="../../public/js/venta.js"></script>
<?php include __DIR__ . "/../layout/footer.php"; ?>
