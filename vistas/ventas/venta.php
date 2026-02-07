<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../../config/conexion.php";
require_once __DIR__ . "/../../modelos/producto_modelo.php";
require_once __DIR__ . "/../../modelos/venta_modelo.php";
require_once __DIR__ . "/../../modelos/turno_modelo.php";
require_once __DIR__ . "/../../modelos/lote_modelo.php";

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

// ✅ Retiros en turno
$totalRetiros = 0;
if ($turnoAbierto && function_exists('totalRetirosTurno')) {
  $totalRetiros = (float) totalRetirosTurno($conexion, (int)$turnoAbierto['id']);
}

// ✅ Efectivo inicial contado
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

<style>
  /* Scrollbar (listas) */
  .chebs-scroll::-webkit-scrollbar{ width: 10px; height:10px; }
  .chebs-scroll::-webkit-scrollbar-thumb{ background: rgba(78,122,43,.35); border-radius: 10px; }
  .chebs-scroll::-webkit-scrollbar-thumb:hover{ background: rgba(78,122,43,.55); }
  .chebs-scroll::-webkit-scrollbar-track{ background: transparent; }

  /* Tabla detalle: encabezado fijo */
  .chebs-table thead th{ position: sticky; top: 0; z-index: 2; }
  .tabular-nums{ font-variant-numeric: tabular-nums; }

  /* Modales */
  .chebs-hidden { display:none; }

  /* ✅ FIX: Botón X no pegado al borde */
  #tabla_detalle th:last-child,
  #tabla_detalle td:last-child{
    width: 72px;
  }
  #tabla_detalle td:last-child{
    padding-right: 14px !important;
    padding-left: 10px !important;
  }
  /* Estilo pro para el botón eliminar (cubre casi cualquier botón que renderice venta.js) */
  #tabla_detalle td:last-child button,
  #tabla_detalle td:last-child .btn-eliminar,
  #tabla_detalle td:last-child .btn-remove,
  #tabla_detalle td:last-child a{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 14px;
    border: 2px solid #f9a8d4;   /* pink-300 */
    background: #fff;
    color: #ef4444;              /* red-500 */
    font-weight: 900;
    line-height: 1;
    margin-right: 4px;
  }
  #tabla_detalle td:last-child button:hover,
  #tabla_detalle td:last-child .btn-eliminar:hover,
  #tabla_detalle td:last-child .btn-remove:hover,
  #tabla_detalle td:last-child a:hover{
    background: #fdf2f8;         /* pink-50 */
  }

  /* ✅ Autocomplete: borde rosado para separar visualmente */
  #auto_box{
    border: 2px solid #f9a8d4 !important; /* pink-300 */
    box-shadow: 0 18px 40px rgba(0,0,0,.10);
  }
.stock-zero{
  font-size: 14px;
  font-weight: 900;
  color: #dc2626;
}


</style>

<!-- =========================
     LAYOUT PRO (19")
========================= -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

  <!-- =========================
       IZQUIERDA (VENTA)
  ========================= -->
  <section class="lg:col-span-8 space-y-6">

    <!-- CABECERA CAJA (✅ overflow visible para dropdown) -->
<div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-visible">
  <div class="px-6 py-4 bg-chebs-soft/50 border-b border-chebs-line flex items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-2xl bg-chebs-green/10 border border-chebs-green/20 flex items-center justify-center">
        <span class="text-chebs-green font-black">💳</span>
      </div>
      <div class="leading-tight">
        <div class="text-xs text-gray-500 font-bold">MÓDULO</div>
        <h1 class="text-xl font-black text-chebs-black">CAJA</h1>
      </div>
    </div>

    <?php if(!$turnoAbierto){ ?>
      <span class="text-xs font-black text-red-700 bg-red-50 border border-red-200 px-3 py-2 rounded-full">
        Turno cerrado
      </span>
    <?php } else { ?>
      <span class="text-xs font-black text-chebs-green bg-chebs-green/10 border border-chebs-green/20 px-3 py-2 rounded-full">
        Turno abierto
      </span>
    <?php } ?>
  </div>

<!-- FORM PRODUCTO -->
  <div class="px-6 py-5">
    <!-- ⬇️ CAMBIO: items-start para que TODO alinee por arriba (no por el alto extra del producto) -->
    <form id="form_producto" onsubmit="return false;" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">

      <!-- Producto -->
<div class="md:col-span-8 z-50 flex flex-col">
  <label class="block text-sm font-black text-pink-600 mb-2 leading-none">
    Buscar producto
  </label>

  <?php
    // ✅ IMPORTANTE: convertimos el resultset a array UNA SOLA VEZ
    // para que NO se “consuma” y el autocomplete siempre tenga datos.
    $productosArr = [];
    if ($productos) {
      $productos->data_seek(0);
      while($p = $productos->fetch_assoc()){
        $productosArr[] = [
          'id' => (int)$p['id'],
          'nombre' => (string)$p['nombre'],
        ];
      }
    }
  ?>

  <!-- ✅ Wrapper relative SOLO para input + dropdown -->
  <div class="relative">
    <input id="producto_nombre" type="text" name="producto_nombre"
          placeholder="Escribe el nombre del producto…"
          autocomplete="off"
          required
          class="w-full h-[52px] rounded-2xl bg-pink-50 border-2 border-pink-300 px-4 text-gray-800 placeholder-pink-400
                outline-none focus:ring-4 focus:ring-pink-200 focus:border-pink-500 text-[16px] font-semibold">

    <!-- Fuente de datos (se deja, pero el JS ya NO depende de esto) -->
    <div class="hidden">
      <datalist id="lista_productos">
        <?php foreach($productosArr as $p) { ?>
          <option value="<?= htmlspecialchars($p['nombre']) ?>" data-id="<?= (int)$p['id'] ?>"></option>
        <?php } ?>
      </datalist>
    </div>

    <!-- ✅ Autocomplete: siempre debajo del input -->
    <div id="auto_box"
        class="hidden absolute left-0 right-0 top-full mt-2 z-[999]
              rounded-2xl bg-white overflow-hidden border-2 border-pink-400
              shadow-[0_18px_40px_rgba(236,72,153,0.20)]">
      <div class="px-4 py-2 text-xs text-chebs-black bg-pink-50 border-b border-pink-200 flex items-center justify-between">
        <span class="font-black">Resultados</span>
        <span class="hidden sm:inline text-gray-600">↑ ↓ · Enter</span>
      </div>

      <div id="auto_list" class="max-h-64 overflow-auto chebs-scroll"></div>

      <div id="auto_empty" class="hidden px-4 py-3 text-sm text-gray-500">
        Sin resultados
      </div>
    </div>
  </div>

  <input type="hidden" id="producto_id">

  <!-- ✅ Stock: reservamos altura para que no desalineé nada -->
  <div id="stock_info" class="mt-2 text-xs text-gray-600 min-h-[18px]"></div>

  <!-- Presentación (si hay packs) -->
  <div id="presentacion_box" class="hidden mt-3">
    <label class="block text-xs font-black text-pink-600 mb-2 leading-none">Presentación</label>
    <select id="presentacion_select"
            class="w-full h-[52px] rounded-2xl bg-white border-2 border-pink-300 px-4 text-gray-800
                   outline-none focus:ring-4 focus:ring-pink-200 focus:border-pink-500 font-semibold">
      <option value="">Unidad</option>
    </select>
    <input type="hidden" id="presentacion_id" value="">
  </div>

  <!-- ✅ PASAMOS LISTA A JS (esto evita que desaparezcan los elementos) -->
  <script>
    window.__CHEBS_PRODUCTOS__ = <?= json_encode($productosArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  </script>

</div>


      <!-- Cantidad -->
      <div class="md:col-span-2 flex flex-col">
        <label class="block text-sm font-black text-pink-600 mb-2 leading-none">
          Cantidad
        </label>

        <!-- ⬇️ CAMBIO: misma altura -->
        <input type="number" id="cantidad" min="1" value="1"
              class="w-full h-[52px] rounded-2xl bg-pink-50 border-2 border-pink-300 px-4 text-gray-800
                     outline-none focus:ring-4 focus:ring-pink-200 focus:border-pink-500 text-[16px] font-semibold">
      </div>

<!-- Botón agregar -->
<div class="md:col-span-2 flex flex-col">
  <!-- label fantasma para que el botón quede EXACTO a la misma altura visual -->
  <div class="mb-2 text-sm font-black opacity-0 leading-none select-none">.</div>

  <button type="button" onclick="agregarDesdeFormulario()"
          class="relative w-full h-[52px] rounded-2xl text-white font-black
                 bg-gradient-to-b from-[#6fa83c] to-[#4e7a2b]
                 shadow-[0_6px_0_#35541d,0_14px_26px_rgba(0,0,0,0.25)]
                 transition-all duration-150
                 hover:from-[#7fb94a] hover:to-[#5a8b33]
                 hover:shadow-[0_6px_0_#35541d,0_16px_28px_rgba(0,0,0,0.30)]
                 hover:-translate-y-[1px]
                 active:translate-y-[4px]
                 active:shadow-[0_2px_0_#35541d,0_8px_14px_rgba(0,0,0,0.22)]
                 focus:outline-none focus:ring-4 focus:ring-chebs-green/30
                 after:content-[''] after:absolute after:inset-[-10px] after:rounded-[26px]
                 after:bg-[#35541d]/35 after:blur-2xl after:animate-pulse after:-z-10">
    Agregar
  </button>
</div>


    </form>
  </div>
</div>


    <!-- DETALLE DE VENTA (tabla + total + confirmar) -->
    <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">

      <div class="px-6 py-4 border-b border-chebs-line bg-white flex items-center justify-between gap-4">
        <div>
          <h3 class="text-lg font-black text-chebs-black">Detalle de la venta</h3>
          <p class="text-sm text-gray-500">Revisa productos antes de confirmar</p>
        </div>

        <div class="text-right">
          <div class="text-xs text-gray-500 font-bold">TOTAL</div>
          <div class="text-3xl font-black text-chebs-green tabular-nums">
            Bs <span id="total">0.00</span>
          </div>
        </div>
      </div>

      <div class="px-6 py-5">

        <div class="rounded-2xl border border-chebs-line overflow-hidden">
          <div class="max-h-[44vh] overflow-auto chebs-scroll">
            <table class="w-full text-[15px] chebs-table table-fixed" id="tabla_detalle">
              <colgroup>
                <col class="w-[44%]">
                <col class="w-[14%]">
                <col class="w-[18%]">
                <col class="w-[18%]">
                <col class="w-[6%]">
              </colgroup>
              <thead class="bg-chebs-soft/60 text-chebs-black">
                <tr>
                  <th class="text-left font-black px-4 py-3">Producto</th>
                  <th class="text-right font-black px-4 py-3">Cant.</th>
                  <th class="text-right font-black px-4 py-3">Precio</th>
                  <th class="text-right font-black px-4 py-3">Subtotal</th>
                  <th class="text-center font-black px-2 py-3">
                    <span class="text-black font-black text-lg leading-none">×</span>
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-chebs-line bg-white"></tbody>
            </table>
          </div>
        </div>

        <?php if(!$turnoAbierto){ ?>
          <div class="mt-4 text-sm font-semibold text-red-700 bg-red-50 border border-red-200 px-4 py-3 rounded-2xl">
            ❌ Abra turno para poder vender.
          </div>
        <?php } ?>

        <div class="mt-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
          <div class="text-xs text-gray-500">
            Tip: usa ↑ ↓ y Enter en el buscador para seleccionar rápido.
          </div>
<button id="btn_confirmar" type="button"
        <?= (!$turnoAbierto ? "disabled" : "") ?>
        class="relative px-8 h-[52px] rounded-2xl font-black transition-all duration-150
        <?= (!$turnoAbierto
            ? "bg-gray-200 text-gray-500 cursor-not-allowed
               shadow-[0_4px_0_#bdbdbd,0_8px_14px_rgba(0,0,0,0.15)]"
            : "text-white
               bg-gradient-to-b from-[#6fa83c] to-[#4e7a2b]
               shadow-[0_6px_0_#35541d,0_14px_26px_rgba(0,0,0,0.25)]
               hover:from-[#7fb94a] hover:to-[#5a8b33]
               hover:shadow-[0_6px_0_#35541d,0_16px_28px_rgba(0,0,0,0.30)]
               hover:-translate-y-[1px]
               active:translate-y-[4px]
               active:shadow-[0_2px_0_#35541d,0_8px_14px_rgba(0,0,0,0.22)]
               focus:outline-none focus:ring-4 focus:ring-chebs-green/30
               after:content-[''] after:absolute after:inset-[-10px]
               after:rounded-[26px] after:bg-[#35541d]/35
               after:blur-2xl after:animate-pulse after:-z-10") ?>">
  Confirmar venta
</button>

        </div>

      </div>
    </div>

  </section>

  <!-- =========================
       DERECHA (TURNO + HISTORIAL)
  ========================= -->
  <aside class="lg:col-span-4 space-y-6">

    <!-- TURNO / CAJA -->
    <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">
      <div class="px-6 py-4 bg-chebs-soft/50 border-b border-chebs-line flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <h3 class="text-lg font-black">⏱ Turno / Caja</h3>
          <?php if(!$turnoAbierto){ ?>
            <span class="text-xs font-black text-red-700 bg-red-50 border border-red-200 px-3 py-1 rounded-full">Cerrado</span>
          <?php } else { ?>
            <span class="text-xs font-black text-chebs-green bg-chebs-green/10 border border-chebs-green/20 px-3 py-1 rounded-full">Abierto</span>
          <?php } ?>
        </div>

        <button type="button"
                id="btn_toggle_turno"
                class="px-3 py-2 rounded-xl border border-chebs-line font-black hover:bg-chebs-soft transition"
                aria-expanded="true">
          Minimizar
        </button>
      </div>

      <div class="px-6 py-5" id="turno_contenido">

        <div class="space-y-2">
          <?php if (isset($_GET["turno_ok"])) { ?>
            <div class="text-sm font-semibold text-chebs-green bg-chebs-green/10 border border-chebs-green/20 px-4 py-3 rounded-2xl">
              ✅ Turno abierto.
            </div>
          <?php } ?>
          <?php if (isset($_GET["turno_cerrado"])) { ?>
            <div class="text-sm font-semibold text-chebs-green bg-chebs-green/10 border border-chebs-green/20 px-4 py-3 rounded-2xl">
              ✅ Turno cerrado.
            </div>
          <?php } ?>
          <?php if (isset($_GET["turno_err"])) { ?>
            <div class="text-sm font-semibold text-red-700 bg-red-50 border border-red-200 px-4 py-3 rounded-2xl">
              ❌ <?= htmlspecialchars($_GET["turno_err"]) ?>
            </div>
          <?php } ?>
          <?php if (isset($_GET["ret_ok"])) { ?>
            <div class="text-sm font-semibold text-chebs-green bg-chebs-green/10 border border-chebs-green/20 px-4 py-3 rounded-2xl">
              ✅ Retiro registrado.
            </div>
          <?php } ?>
          <?php if (isset($_GET["ret_err"])) { ?>
            <div class="text-sm font-semibold text-red-700 bg-red-50 border border-red-200 px-4 py-3 rounded-2xl">
              ❌ <?= htmlspecialchars($_GET["ret_err"]) ?>
            </div>
          <?php } ?>
        </div>

        <?php if (!$turnoAbierto) { ?>

          <p class="mt-4 text-sm text-gray-700">
            Para vender primero debes <b>abrir turno</b> contando el efectivo real que hay en caja.
          </p>

          <button type="button"
                  class="mt-4 w-full px-5 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft"
                  onclick="abrirModal('modalAbrirTurno')">
            Abrir turno
          </button>

        <?php } else { ?>

          <div class="mt-4 space-y-2 text-sm text-gray-700">
            <div class="flex justify-between gap-3"><span class="font-semibold">Turno ID</span><span class="tabular-nums">#<?= (int)$turnoAbierto["id"] ?></span></div>
            <div class="flex justify-between gap-3"><span class="font-semibold">Responsable</span><span><?= htmlspecialchars($turnoAbierto["responsable"]) ?></span></div>
            <div class="flex justify-between gap-3"><span class="font-semibold">Abierto</span><span><?= htmlspecialchars($turnoAbierto["abierto_en"]) ?></span></div>

            <div class="h-px bg-chebs-line my-2"></div>

            <div class="flex justify-between gap-3"><span class="font-semibold">Inicial contado</span><span class="tabular-nums">Bs <?= number_format($montoInicialUI,2) ?></span></div>
            <div class="flex justify-between gap-3"><span class="font-semibold">Total vendido</span><span class="tabular-nums">Bs <?= number_format($totalTurno,2) ?></span></div>
            <div class="flex justify-between gap-3"><span class="font-semibold">Retiros</span><span class="tabular-nums">Bs <?= number_format($totalRetiros,2) ?></span></div>

            <div class="mt-3 rounded-2xl border border-chebs-line bg-chebs-soft/50 p-4 flex items-center justify-between">
              <div class="text-sm font-semibold text-gray-700">Efectivo esperado</div>
              <div class="text-xl font-black text-chebs-green tabular-nums">
                Bs <?= number_format($efectivoEsperadoUI,2) ?>
              </div>
            </div>
          </div>

          <?php if ($rol === 'admin') { ?>
            <button type="button"
                    class="mt-4 w-full px-5 py-3 rounded-2xl border border-chebs-line font-black hover:bg-chebs-soft transition"
                    onclick="abrirModal('modalRetiro')">
              Retiro de caja (Admin)
            </button>
          <?php } ?>

          <button type="button"
                  class="mt-3 w-full px-5 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft"
                  onclick="abrirModal('modalCerrarTurno')">
            Cerrar turno
          </button>

        <?php } ?>

        <?php if ($rol === 'admin') { ?>
          <div class="h-px bg-chebs-line my-5"></div>
          <h4 class="font-black mb-3">Últimos turnos</h4>

          <div class="space-y-3">
            <?php while($t = $ultTurnos->fetch_assoc()) { ?>
              <div class="rounded-2xl border border-chebs-line p-4 bg-white">
                <div class="flex items-center justify-between">
                  <div class="font-black">#<?= (int)$t["id"] ?></div>
                  <div class="text-xs text-gray-500"><?= htmlspecialchars($t["fecha"]) ?></div>
                </div>
                <div class="mt-2 text-xs text-gray-600 space-y-1">
                  <div><b>Entrada:</b> <?= htmlspecialchars($t["abierto_en"]) ?></div>
                  <div><b>Salida:</b> <?= htmlspecialchars($t["cerrado_en"] ?? '-') ?></div>
                  <div><b>Estado:</b> <?= htmlspecialchars($t["estado"]) ?></div>
                  <div><b>Resp:</b> <?= htmlspecialchars($t["responsable"]) ?></div>
                </div>
              </div>
            <?php } ?>
          </div>
        <?php } ?>

      </div>
    </div>

    <!-- HISTORIAL HOY -->
    <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">
      <div class="px-6 py-4 bg-white border-b border-chebs-line flex items-center justify-between gap-4">
        <div>
          <h3 class="text-lg font-black text-chebs-black">Historial (hoy)</h3>
          <p class="text-sm text-gray-500">Últimas ventas del turno</p>
        </div>

        <div class="w-[76px] h-[76px] rounded-full bg-chebs-green/10 border border-chebs-green/20 flex flex-col items-center justify-center">
          <div class="text-[10px] font-black text-gray-600 leading-none">HOY</div>
          <div class="text-sm font-black text-chebs-black tabular-nums leading-tight">
            Bs <?= number_format($totalHoy, 2) ?>
          </div>
        </div>
      </div>

<div class="px-3 py-5 md:px-4">
        <div class="max-h-[54vh] overflow-auto pr-1 chebs-scroll space-y-4">
          <?php while($v = $ultimasVentas->fetch_assoc()) { ?>
<div class="rounded-2xl border border-chebs-line px-3 py-3 bg-white hover:bg-chebs-soft/40 transition">
              <div class="flex items-center justify-between gap-3">
                <div class="font-black">Venta #<?= (int)$v['id'] ?></div>
                <div class="text-xs text-gray-500"><?= htmlspecialchars($v['fecha']) ?></div>
              </div>

              <div class="mt-3 space-y-2">
                <?php
                  $det = obtenerDetalleVenta($conexion, (int)$v['id']);
                  while($d = $det->fetch_assoc()) {
                ?>
                  <div class="flex items-start justify-between gap-3 text-sm">
                    <div class="flex-1">
                      <div class="font-semibold text-chebs-black">
                        <?= htmlspecialchars($d['nombre']) ?>
                        <span class="text-xs text-gray-500">(<?= htmlspecialchars($d['tipo_venta']) ?>)</span>
                      </div>
                      <div class="text-xs text-gray-500 tabular-nums">
                        x<?= (int)$d['cantidad'] ?> · Bs <?= number_format((float)$d['precio_unitario'], 2) ?> c/u
                      </div>
                    </div>
                    <div class="font-bold tabular-nums">Bs <?= number_format((float)$d['subtotal'], 2) ?></div>
                  </div>
                <?php } ?>
              </div>

              <div class="mt-3 flex items-center justify-between">
                <a href="/PULPERIA-CHEBS/vistas/ventas/corregir_venta.php?id=<?= (int)$v['id'] ?>"
                   class="text-xs font-black text-red-600 bg-red-50 px-3 py-2 rounded-xl hover:bg-red-100 transition border border-red-100">
                  Corregir venta
                </a>

                <div class="font-black text-chebs-green tabular-nums">
                  Total: Bs <?= number_format((float)$v['total'], 2) ?>
                </div>
              </div>
            </div>
          <?php } ?>
        </div>
      </div>

    </div>

  </aside>
</div>

<!-- ========================= -->
<!-- ✅ MODALES CHEBS -->
<!-- ========================= -->

<!-- ✅ MODAL: ABRIR TURNO -->
<div id="modalAbrirTurno" class="chebs-hidden fixed inset-0 z-[9999]">
  <div class="absolute inset-0 bg-black/40" onclick="cerrarModal('modalAbrirTurno')"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-3xl bg-white shadow-soft border border-chebs-line overflow-hidden">
      <div class="px-6 py-5 border-b border-chebs-line">
        <h3 class="text-lg font-black text-chebs-black">Abrir turno</h3>
        <p class="text-sm text-gray-500">¿Cuánto efectivo hay en caja ahora?</p>
      </div>

      <form action="../../controladores/turno_abrir.php" method="POST" onsubmit="return validarAbrirTurno();">
        <div class="px-6 py-5 space-y-2">
          <div class="text-xs text-gray-500">(Cuenta el dinero físico antes de empezar)</div>

          <label class="text-sm font-semibold text-chebs-black">Efectivo inicial</label>
          <input type="number" step="0.01" name="monto_inicial" id="abrir_monto"
                 value="0" required
                 class="w-full rounded-2xl border border-chebs-line px-4 py-3 outline-none focus:ring-4 focus:ring-chebs-soft bg-white">

          <div id="abrir_error" class="hidden text-sm font-semibold text-red-600"></div>
        </div>

        <div class="px-6 py-5 border-t border-chebs-line flex justify-end gap-2">
          <button type="button"
                  class="px-5 py-3 rounded-2xl border border-chebs-line font-semibold hover:bg-gray-50"
                  onclick="cerrarModal('modalAbrirTurno')">
            Cancelar
          </button>
          <button type="submit"
                  class="px-5 py-3 rounded-2xl bg-chebs-green text-white font-bold hover:bg-chebs-greenDark">
            Abrir
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ✅ MODAL: CERRAR TURNO -->
<div id="modalCerrarTurno" class="chebs-hidden fixed inset-0 z-[9999]">
  <div class="absolute inset-0 bg-black/40" onclick="cerrarModal('modalCerrarTurno')"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-3xl bg-white shadow-soft border border-chebs-line overflow-hidden">
      <div class="px-6 py-5 border-b border-chebs-line">
        <h3 class="text-lg font-black text-chebs-black">Cerrar turno</h3>
        <p class="text-sm text-gray-500">Confirma el efectivo final antes de cerrar.</p>
      </div>

      <form action="../../controladores/turno_cerrar.php" method="POST" onsubmit="return abrirConfirmacion('confirmCerrarTurno');">
        <div class="px-6 py-5 space-y-3">
          <input type="hidden" name="turno_id" value="<?= $turnoAbierto ? (int)$turnoAbierto["id"] : 0 ?>">

          <div class="rounded-2xl bg-chebs-soft/60 border border-chebs-line p-4">
            <div class="text-sm text-gray-600">Efectivo esperado</div>
            <div class="text-2xl font-black text-chebs-green">
              Bs <?= number_format($efectivoEsperadoUI,2) ?>
            </div>
            <div class="text-xs text-gray-500">(Inicial + Ventas - Retiros)</div>
          </div>

          <label class="text-sm font-semibold text-chebs-black">¿Cuánto efectivo estás dejando?</label>
          <input type="number" step="0.01" name="efectivo_cierre" id="cerrar_efectivo"
                 value="<?= number_format($efectivoEsperadoUI,2,'.','') ?>"
                 required
                 class="w-full rounded-2xl border border-chebs-line px-4 py-3 outline-none focus:ring-4 focus:ring-chebs-soft bg-white">
          <div class="text-xs text-gray-500">(Cuenta el dinero físico en caja y escribe el total)</div>

          <div id="cerrar_error" class="hidden text-sm font-semibold text-red-600"></div>
        </div>

        <div class="px-6 py-5 border-t border-chebs-line flex justify-end gap-2">
          <button type="button"
                  class="px-5 py-3 rounded-2xl border border-chebs-line font-semibold hover:bg-gray-50"
                  onclick="cerrarModal('modalCerrarTurno')">
            Cancelar
          </button>
          <button type="submit"
                  class="px-5 py-3 rounded-2xl bg-chebs-green text-white font-bold hover:bg-chebs-greenDark">
            Cerrar turno
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ✅ MODAL: RETIRO ADMIN -->
<?php if ($rol === 'admin') { ?>
<div id="modalRetiro" class="chebs-hidden fixed inset-0 z-[9999]">
  <div class="absolute inset-0 bg-black/40" onclick="cerrarModal('modalRetiro')"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-3xl bg-white shadow-soft border border-chebs-line overflow-hidden">
      <div class="px-6 py-5 border-b border-chebs-line">
        <h3 class="text-lg font-black text-chebs-black">Retiro de caja</h3>
        <p class="text-sm text-gray-500">Solo Admin. Registra un retiro del turno actual.</p>
      </div>

      <form action="../../controladores/retiro_guardar.php" method="POST" onsubmit="return abrirConfirmacion('confirmRetiro');">
        <div class="px-6 py-5 space-y-3">
          <input type="hidden" name="turno_id" value="<?= $turnoAbierto ? (int)$turnoAbierto["id"] : 0 ?>">

          <div class="rounded-2xl bg-chebs-soft/60 border border-chebs-line p-4">
            <div class="text-sm text-gray-600">Efectivo esperado ahora</div>
            <div class="text-2xl font-black text-chebs-green">
              Bs <?= number_format($efectivoEsperadoUI,2) ?>
            </div>
            <div class="text-xs text-gray-500">Turno #<?= $turnoAbierto ? (int)$turnoAbierto["id"] : 0 ?></div>
          </div>

          <label class="text-sm font-semibold text-chebs-black">Monto a retirar</label>
          <input type="number" step="0.01" name="monto" id="retiro_monto"
                 value="0" required
                 class="w-full rounded-2xl border border-chebs-line px-4 py-3 outline-none focus:ring-4 focus:ring-chebs-soft bg-white">

          <label class="text-sm font-semibold text-chebs-black">Motivo (opcional)</label>
          <input type="text" name="motivo" maxlength="255"
                 placeholder="Ej: deposito, guardar dinero…"
                 class="w-full rounded-2xl border border-chebs-line px-4 py-3 outline-none focus:ring-4 focus:ring-chebs-soft bg-white">

          <div id="retiro_error" class="hidden text-sm font-semibold text-red-600"></div>
        </div>

        <div class="px-6 py-5 border-t border-chebs-line flex justify-end gap-2">
          <button type="button"
                  class="px-5 py-3 rounded-2xl border border-chebs-line font-semibold hover:bg-gray-50"
                  onclick="cerrarModal('modalRetiro')">
            Cancelar
          </button>
          <button type="submit"
                  class="px-5 py-3 rounded-2xl bg-chebs-green text-white font-bold hover:bg-chebs-greenDark">
            Guardar retiro
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php } ?>

<!-- ✅ MODAL: CONFIRMACIÓN / MENSAJE -->
<div id="modalConfirmacion" class="chebs-hidden fixed inset-0 z-[9999]">
  <div class="absolute inset-0 bg-black/40" onclick="cerrarModal('modalConfirmacion')"></div>

  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div id="confirm_card"
         class="w-full max-w-md rounded-3xl bg-white shadow-soft border border-chebs-line overflow-hidden">

      <div id="confirm_header" class="px-6 py-5 border-b border-chebs-line">
        <h3 class="text-lg font-black text-chebs-black" id="confirm_titulo">Confirmar</h3>
        <p class="text-sm text-gray-500" id="confirm_texto">¿Estás seguro?</p>
      </div>

      <div id="confirm_body" class="hidden px-6 py-5 space-y-4">
        <div class="rounded-2xl border border-chebs-line bg-chebs-soft/50 p-4">
          <div class="text-xs text-gray-500">TOTAL A COBRAR</div>
          <div class="text-4xl font-black text-chebs-green">
            Bs <span id="confirm_total_big">0.00</span>
          </div>
        </div>

        <div>
          <label class="text-sm font-black text-chebs-black">Cliente paga</label>
          <input id="confirm_pago"
                 type="number"
                 step="0.01"
                 inputmode="decimal"
                 placeholder="Ej: 200"
                 class="mt-2 w-full rounded-2xl border-2 border-chebs-line px-4 py-3 outline-none
                        focus:ring-4 focus:ring-chebs-soft bg-white text-lg font-bold">
          <div class="text-xs text-gray-500 mt-1">Escribe cuánto te dio el cliente.</div>
        </div>

        <div class="rounded-2xl border border-chebs-line bg-white p-4">
          <div class="text-xs text-gray-500">CAMBIO</div>
          <div id="confirm_cambio_big" class="text-4xl font-black text-chebs-black">
            Bs 0.00
          </div>
          <div id="confirm_falta" class="hidden mt-2 text-sm font-black text-red-600">
            Falta: Bs 0.00
          </div>
        </div>
      </div>

      <div id="confirm_footer" class="px-6 py-5 border-t border-chebs-line flex justify-end gap-2">
        <button type="button"
                id="confirm_btn_cancel"
                class="px-5 py-3 rounded-2xl bg-red-600 text-white font-black hover:bg-red-700 transition"
                onclick="cerrarModal('modalConfirmacion')">
          ✕ Cancelar
        </button>

        <button type="button"
                class="px-5 py-3 rounded-2xl bg-chebs-green text-white font-bold hover:bg-chebs-greenDark"
                id="confirm_btn_ok">
          Sí, confirmar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function abrirModal(id){
  document.getElementById(id).classList.remove('chebs-hidden');
}
function cerrarModal(id){
  document.getElementById(id).classList.add('chebs-hidden');
}

// ESC para cerrar
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    ['modalAbrirTurno','modalCerrarTurno','modalRetiro','modalConfirmacion'].forEach(id=>{
      const el = document.getElementById(id);
      if (el && !el.classList.contains('chebs-hidden')) el.classList.add('chebs-hidden');
    });
    cerrarAuto();
  }
});

function setError(id, msg){
  const el = document.getElementById(id);
  if(!el) return;
  if(!msg){
    el.classList.add('hidden');
    el.textContent = '';
    return;
  }
  el.classList.remove('hidden');
  el.textContent = msg;
}

function validarAbrirTurno(){
  const v = parseFloat(document.getElementById('abrir_monto').value || '0');
  if (isNaN(v) || v < 0) {
    setError('abrir_error', 'Monto inválido (debe ser 0 o mayor).');
    document.getElementById('abrir_monto').focus();
    return false;
  }
  setError('abrir_error', '');
  return true;
}

// ✅ Modal tipo MENSAJE (solo Aceptar)
function mostrarMensaje(titulo, texto){
  const card   = document.getElementById('confirm_card');
  const header = document.getElementById('confirm_header');
  const body   = document.getElementById('confirm_body');
  const footer = document.getElementById('confirm_footer');

  document.getElementById('confirm_titulo').textContent = titulo || 'Mensaje';
  document.getElementById('confirm_texto').textContent  = texto || '';

  card.className = "w-full max-w-md rounded-3xl bg-white shadow-soft border border-chebs-line overflow-hidden";
  header.className = "px-6 py-5 border-b border-chebs-line";
  footer.className = "px-6 py-5 border-t border-chebs-line flex justify-end gap-2";

  if (body) body.classList.add('hidden');

  const btnCancel = document.getElementById('confirm_btn_cancel');
  if (btnCancel) btnCancel.classList.add('hidden');

  const btnOk = document.getElementById('confirm_btn_ok');
  if (btnOk) {
    btnOk.textContent = 'Aceptar';
    btnOk.onclick = () => cerrarModal('modalConfirmacion');
  }

  abrirModal('modalConfirmacion');
}

// ✅ ✅ ÉXITO VERDE auto-cierre 1.5s (VENTA EXITOSA)
function mostrarExitoAuto(){
  const card   = document.getElementById('confirm_card');
  const header = document.getElementById('confirm_header');
  const body   = document.getElementById('confirm_body');
  const footer = document.getElementById('confirm_footer');

  card.className = "w-full max-w-md rounded-3xl bg-green-600 shadow-soft border-4 border-green-700 overflow-hidden";
  header.className = "px-6 py-6 border-b border-green-700";

  const titulo = document.getElementById('confirm_titulo');
  const texto  = document.getElementById('confirm_texto');

  if (titulo) {
    titulo.textContent = "VENTA EXITOSA";
    titulo.className = "text-2xl font-black text-white text-center";
  }
  if (texto) {
    texto.textContent = "";
    texto.className = "hidden";
  }

  if (body) {
    body.classList.remove('hidden');
    body.innerHTML = `
      <div class="flex items-center justify-center py-6">
        <div class="w-24 h-24 rounded-full bg-white/15 flex items-center justify-center border-4 border-white">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6 9 17l-5-5"></path>
          </svg>
        </div>
      </div>
    `;
  }

  if (footer) footer.classList.add('hidden');

  abrirModal('modalConfirmacion');

  setTimeout(() => {
    cerrarModal('modalConfirmacion');
    location.reload();
  }, 1500);
}

// ✅ Modal confirm normal (restaura Cancelar)
function confirmarSubmit(formSelector, titulo, texto){
  document.getElementById('confirm_titulo').textContent = titulo || 'Confirmar';
  document.getElementById('confirm_texto').textContent = texto || '¿Estás seguro?';

  const btnCancel = document.getElementById('confirm_btn_cancel');
  btnCancel.classList.remove('hidden');

  const btnOk = document.getElementById('confirm_btn_ok');
  btnOk.textContent = 'Sí, confirmar';
  btnOk.onclick = () => {
    cerrarModal('modalConfirmacion');
    const form = document.querySelector(formSelector);
    if(form) form.submit();
  };

  abrirModal('modalConfirmacion');
}

function abrirConfirmacion(tipo){
  if(tipo === 'confirmCerrarTurno'){
    const v = parseFloat(document.getElementById('cerrar_efectivo').value || '0');
    if (isNaN(v) || v < 0) {
      setError('cerrar_error', 'Efectivo inválido (debe ser 0 o mayor).');
      document.getElementById('cerrar_efectivo').focus();
      return false;
    }
    setError('cerrar_error', '');
    confirmarSubmit('#modalCerrarTurno form', 'Cerrar turno', '¿Seguro que deseas cerrar el turno?');
    return false;
  }

  if(tipo === 'confirmRetiro'){
    const v = parseFloat(document.getElementById('retiro_monto').value || '0');
    if (isNaN(v) || v <= 0) {
      setError('retiro_error', 'El retiro debe ser mayor a 0.');
      document.getElementById('retiro_monto').focus();
      return false;
    }
    setError('retiro_error', '');
    confirmarSubmit('#modalRetiro form', 'Registrar retiro', '¿Deseas registrar este retiro de caja?');
    return false;
  }

  return true;
}

/* =========================
   ✅ AUTOCOMPLETE CHEBS
   ========================= */

const inputProducto = document.getElementById('producto_nombre');
const hiddenId = document.getElementById('producto_id');
const stockInfo = document.getElementById('stock_info');

const autoBox  = document.getElementById('auto_box');
const autoList = document.getElementById('auto_list');
const autoEmpty= document.getElementById('auto_empty');

const dataOptions = Array.from(document.querySelectorAll('#lista_productos option'))
  .map(o => ({ label: o.value, id: o.dataset.id }));

let autoIndex = -1;
let autoItems = [];

function abrirAuto(){ autoBox.classList.remove('hidden'); }
function cerrarAuto(){ autoBox.classList.add('hidden'); autoIndex = -1; }

function renderAuto(){
  autoList.innerHTML = '';
  autoEmpty.classList.add('hidden');

  if (autoItems.length === 0) {
    autoEmpty.classList.remove('hidden');
    return;
  }

  autoItems.forEach((it, idx) => {
    const div = document.createElement('div');

    div.className =
      "px-4 py-3 text-sm cursor-pointer border-b border-pink-100 last:border-b-0 transition " +
      (idx === autoIndex
        ? "bg-pink-200 border-l-4 border-pink-500 font-black text-gray-900"
        : "hover:bg-pink-100");

    const q = inputProducto.value.trim();
    if (q.length > 0) {
      const safe = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const re = new RegExp(safe, 'ig');
      div.innerHTML = it.label.replace(re, (m)=>`<span class="font-black text-chebs-green">${m}</span>`);
    } else {
      div.textContent = it.label;
    }

    div.addEventListener('mousedown', (e) => {
      e.preventDefault();
      seleccionarItem(idx);
    });

    autoList.appendChild(div);
  });
}

async function seleccionarItem(idx){
  const it = autoItems[idx];
  if(!it) return;

  inputProducto.value = it.label;
  hiddenId.value = it.id;
  cerrarAuto();

  try{
    const s = await fetch(`${"/PULPERIA-CHEBS"}/controladores/stock_fetch.php`, {
      method: "POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify({ producto_id: parseInt(it.id) })
    });
    const txt = await s.text();
    const js = JSON.parse(txt);
    if(js.error){
      stockInfo.textContent = "Stock: error";
    } else {
      const st = parseInt(js.stock);

if (Number.isFinite(st) && st <= 0) {
  stockInfo.classList.add('stock-zero');
  stockInfo.classList.remove('text-xs','text-gray-600');
  stockInfo.textContent = 'SIN STOCK (0)';
} else if (Number.isFinite(st)) {
  stockInfo.classList.remove('stock-zero');
  stockInfo.classList.add('text-xs','text-gray-600');
  stockInfo.textContent = `Stock disponible: ${st}`;
} else {
  stockInfo.classList.remove('stock-zero');
  stockInfo.classList.add('text-xs','text-gray-600');
  stockInfo.textContent = 'Stock: -';
}

    }
  } catch {
    stockInfo.textContent = "Stock: error";
  }
}

function filtrar(q){
  const t = q.trim().toLowerCase();
  if(t.length === 0){ autoItems = []; return; }

  autoItems = dataOptions
    .filter(x => x.label.toLowerCase().includes(t))
    .slice(0, 14);

  autoIndex = autoItems.length ? 0 : -1;
}

inputProducto.addEventListener('input', () => {
  hiddenId.value = '';
  stockInfo.textContent = '';
  filtrar(inputProducto.value);

  if (inputProducto.value.trim().length > 0) {
    abrirAuto();
    renderAuto();
  } else {
    cerrarAuto();
  }
});

inputProducto.addEventListener('focus', () => {
  filtrar(inputProducto.value);
  if (inputProducto.value.trim().length > 0 && autoItems.length > 0) {
    abrirAuto();
    renderAuto();
  }
});

inputProducto.addEventListener('keydown', (e) => {
  if (autoBox.classList.contains('hidden')) return;

  if (e.key === 'ArrowDown') {
    e.preventDefault();
    autoIndex = Math.min(autoIndex + 1, autoItems.length - 1);
    renderAuto();
    return;
  }
  if (e.key === 'ArrowUp') {
    e.preventDefault();
    autoIndex = Math.max(autoIndex - 1, 0);
    renderAuto();
    return;
  }
  if (e.key === 'Enter') {
    if (autoIndex >= 0 && autoItems[autoIndex]) {
      e.preventDefault();
      seleccionarItem(autoIndex);
      return;
    }
  }
  if (e.key === 'Escape') {
    cerrarAuto();
  }
});

document.addEventListener('click', (e) => {
  if (!autoBox.contains(e.target) && e.target !== inputProducto) {
    cerrarAuto();
  }
});

/* =========================
   ✅ MINIMIZAR TURNO/CAJA
   ========================= */
(function(){
  const btn  = document.getElementById('btn_toggle_turno');
  const cont = document.getElementById('turno_contenido');
  if(!btn || !cont) return;

  const KEY = "chebs_turno_min";

  function pintar(minimizado){
    if(minimizado){
      cont.classList.add('hidden');
      btn.textContent = 'Mostrar';
      btn.setAttribute('aria-expanded', 'false');
    } else {
      cont.classList.remove('hidden');
      btn.textContent = 'Minimizar';
      btn.setAttribute('aria-expanded', 'true');
    }
  }

  const guardado = localStorage.getItem(KEY);
  const minimizadoInicial = (guardado === null) ? true : (guardado === "1");
  pintar(minimizadoInicial);

  btn.addEventListener('click', () => {
    const estaMin = cont.classList.contains('hidden');
    const nuevoMin = !estaMin;
    pintar(nuevoMin);
    localStorage.setItem(KEY, nuevoMin ? "1" : "0");
  });
})();
</script>

<script src="../../public/js/venta.js"></script>
<?php include __DIR__ . "/../layout/footer.php"; ?>
