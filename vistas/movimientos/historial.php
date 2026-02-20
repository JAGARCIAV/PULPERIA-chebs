<?php 
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

require_once "../../config/conexion.php";
require_once "../../modelos/movimiento_modelo.php";
include "../layout/header.php";

$producto_id = $_GET['producto_id'] ?? null;
$producto_id = ($producto_id !== null && $producto_id !== '') ? (int)$producto_id : null;

/* ======================================================
   ✅ RESUMEN MENSUAL: TOTAL VENDIDO + GANANCIA
   - Unidad: usa productos.costo_unidad
   - Paquete: usa producto_presentaciones.costo si existe,
              si no existe usa productos.costo_unidad * pp.unidades
   ====================================================== */
$filtroSql = "";
$params = [];
$types  = "";

if ($producto_id) {
  $filtroSql = " AND d.producto_id = ? ";
  $params[] = $producto_id;
  $types .= "i";
}

$sqlResumen = "
SELECT
  DATE_FORMAT(v.fecha, '%Y-%m') AS mes,
  SUM(d.subtotal) AS total_vendido,

  SUM(
    CASE
      WHEN d.tipo_venta='unidad'
        AND p.costo_unidad IS NOT NULL AND p.costo_unidad > 0
        THEN d.subtotal

      WHEN d.tipo_venta='paquete'
        AND pp.id IS NOT NULL
        AND (
          (pp.costo IS NOT NULL AND pp.costo > 0)
          OR (p.costo_unidad IS NOT NULL AND p.costo_unidad > 0)
        )
        THEN d.subtotal

      ELSE 0
    END
  ) AS total_con_costo,

  SUM(
    CASE
      WHEN d.tipo_venta='unidad'
        AND p.costo_unidad IS NOT NULL AND p.costo_unidad > 0
        THEN (d.precio_unitario - p.costo_unidad) * d.cantidad

      WHEN d.tipo_venta='paquete'
        AND pp.id IS NOT NULL
        THEN (
          d.precio_unitario
          -
          COALESCE(
            NULLIF(pp.costo, 0),
            (p.costo_unidad * pp.unidades)
          )
        ) * d.cantidad

      ELSE 0
    END
  ) AS ganancia
FROM ventas v
JOIN detalle_venta d ON d.venta_id = v.id
JOIN productos p ON p.id = d.producto_id
LEFT JOIN producto_presentaciones pp ON pp.id = d.presentacion_id
WHERE 1=1
$filtroSql
GROUP BY DATE_FORMAT(v.fecha, '%Y-%m')
ORDER BY mes DESC
";

$stmtResumen = $conexion->prepare($sqlResumen);
if (!empty($params)) $stmtResumen->bind_param($types, ...$params);
$stmtResumen->execute();
$resumenMensual = $stmtResumen->get_result();

/* ✅ Guardar filas en array (para usar en tabla + mes actual) */
$resumenRows = [];
while($row = $resumenMensual->fetch_assoc()){
  $resumenRows[] = $row;
}

/* ======================================================
   ✅ TOTAL DEL MES ACTUAL (POR DEFECTO)
   ====================================================== */
$mesActual = date('Y-m'); // ejemplo: 2026-02
$mesActualData = [
  'mes' => $mesActual,
  'total_vendido' => 0,
  'total_con_costo' => 0,
  'ganancia' => 0,
];



foreach($resumenRows as $r){
  if(($r['mes'] ?? '') === $mesActual){
    $mesActualData = [
      'mes' => $mesActual,
      'total_vendido' => (float)($r['total_vendido'] ?? 0),
      'total_con_costo' => (float)($r['total_con_costo'] ?? 0),
      'ganancia' => (float)($r['ganancia'] ?? 0),
    ];
    break;
  }
}

/* ======================================================
   ✅ GANANCIA DIARIA (HOY)
   ====================================================== */
$hoy = date('Y-m-d'); // ejemplo: 2026-02-07

$sqlDiario = "
SELECT
  DATE(v.fecha) AS dia,
  SUM(d.subtotal) AS total_vendido_dia,

  SUM(
    CASE
      WHEN d.tipo_venta='unidad'
        AND p.costo_unidad IS NOT NULL AND p.costo_unidad > 0
        THEN (d.precio_unitario - p.costo_unidad) * d.cantidad

      WHEN d.tipo_venta='paquete'
        AND pp.id IS NOT NULL
        THEN (
          d.precio_unitario
          -
          COALESCE(
            NULLIF(pp.costo, 0),
            (p.costo_unidad * pp.unidades)
          )
        ) * d.cantidad

      ELSE 0
    END
  ) AS ganancia_dia
FROM ventas v
JOIN detalle_venta d ON d.venta_id = v.id
JOIN productos p ON p.id = d.producto_id
LEFT JOIN producto_presentaciones pp ON pp.id = d.presentacion_id
WHERE DATE(v.fecha) = ?
$filtroSql
";

$stmtDia = $conexion->prepare($sqlDiario);

if ($producto_id) {
  $stmtDia->bind_param("si", $hoy, $producto_id);
} else {
  $stmtDia->bind_param("s", $hoy);
}

$stmtDia->execute();
$diaRow = $stmtDia->get_result()->fetch_assoc();

$diaData = [
  'dia' => $hoy,
  'total_vendido_dia' => (float)($diaRow['total_vendido_dia'] ?? 0),
  'ganancia_dia' => (float)($diaRow['ganancia_dia'] ?? 0),
];


/* ======================================================
   Historial de movimientos
   ====================================================== */
$productos = $conexion->query("SELECT id, nombre FROM productos");
$movimientos = obtenerMovimientos($conexion, $producto_id);
?>

<div class="max-w-7xl mx-auto px-4 py-6">

  <!-- Header PRO -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden mb-6">
    <div class="px-6 py-5 bg-chebs-soft/60 border-b border-chebs-line">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-2 mb-2">
            <span class="px-3 py-1 rounded-full text-xs font-black bg-chebs-green/10 text-chebs-green border border-chebs-green/20">
              ADMIN
            </span>
            <span class="text-xs text-gray-500">Inventario / Movimientos</span>
          </div>

          <h1 class="text-3xl font-black text-chebs-black leading-tight">
            Historial de inventario
          </h1>
          <p class="text-sm text-gray-600 mt-1">
            Entradas, salidas y ajustes + resumen de ventas y ganancia mensual.
          </p>
        </div>

        <a href="/PULPERIA-CHEBS/vistas/lotes/listar.php"
          class="inline-flex items-center justify-center px-6 py-3 rounded-2xl
                 bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft whitespace-nowrap">
          📦 Ver lotes
        </a>
      </div>
    </div>

    <div class="px-6 py-4">
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">

        <!-- Mes actual -->
        <div class="bg-white border border-chebs-line rounded-3xl shadow-soft p-6 relative overflow-hidden">
          <div class="absolute left-0 top-0 bottom-0 w-2 bg-chebs-green/60"></div>
          <div class="pl-3">
            <div class="text-xs text-gray-500 font-bold flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-chebs-green"></span> Mes actual
            </div>
            <div class="text-xl font-black text-chebs-black mt-1"><?= htmlspecialchars($mesActualData['mes']) ?></div>
            <div class="text-xs text-gray-500 mt-2">Por defecto</div>
          </div>
        </div>

        <!-- Total vendido -->
        <div class="bg-white border border-chebs-line rounded-3xl shadow-soft p-6 relative overflow-hidden">
          <div class="absolute left-0 top-0 bottom-0 w-2 bg-chebs-green/30"></div>
          <div class="pl-3">
            <div class="text-xs text-gray-500 font-bold">Total vendido (mes)</div>
            <div class="text-2xl font-black text-chebs-black mt-1">
              Bs <?= number_format($mesActualData['total_vendido'], 2) ?>
            </div>
            <div class="text-xs text-gray-500 mt-2">Suma de todas las ventas del mes</div>
          </div>
        </div>

        <!-- Ganancia del mes-->
        <div class="bg-white border border-chebs-line rounded-3xl shadow-soft p-6 relative overflow-hidden">
          <div class="absolute left-0 top-0 bottom-0 w-2 <?= $mesActualData['ganancia'] >= 0 ? 'bg-green-500/40' : 'bg-red-500/40' ?>"></div>
          <div class="pl-3">
            <div class="text-xs text-gray-500 font-bold">Ganancia (mes)</div>
            <div class="text-2xl font-black mt-1 <?= $mesActualData['ganancia'] >= 0 ? 'text-green-700' : 'text-red-700' ?>">
              Bs <?= number_format($mesActualData['ganancia'], 2) ?>
            </div>
            <div class="text-xs text-gray-500 mt-2">
              Solo calcula cuando hay costo (mayorista)
            </div>
          </div>
        </div>

<!-- Ganancia diaria (HOY) -->
<div class="bg-white border border-chebs-line rounded-3xl shadow-soft p-6 relative overflow-hidden">
  <div class="absolute left-0 top-0 bottom-0 w-2 <?= $diaData['ganancia_dia'] >= 0 ? 'bg-green-500/40' : 'bg-red-500/40' ?>"></div>
  <div class="pl-3">
    <div class="text-xs text-gray-500 font-bold">Ganancia (diaria)</div>

    <div class="text-2xl font-black mt-1 <?= $diaData['ganancia_dia'] >= 0 ? 'text-green-700' : 'text-red-700' ?>">
      Bs <?= number_format($diaData['ganancia_dia'], 2) ?>
    </div>

    <div class="text-xs text-gray-500 mt-2">
      Hoy: <?= htmlspecialchars($diaData['dia']) ?> · Vendido: Bs <?= number_format($diaData['total_vendido_dia'], 2) ?>
    </div>
  </div>
</div>



      </div>
    </div>
  </div>

  <!-- ✅ RESUMEN MENSUAL (más vivo) -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-chebs-line bg-chebs-soft/50">
      <div class="flex items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-black text-chebs-black">Resumen de ventas por mes</h2>
          <p class="text-xs text-gray-600 mt-1">
            Ganancia = venta - costo. Unidad usa costo_unidad; Packs usan costo(pack) o costo_unidad*unidades(pack).
          </p>
        </div>
        <span class="hidden md:inline px-3 py-1 rounded-full text-xs font-black bg-chebs-green/10 text-chebs-green border border-chebs-green/20">
          <?= count($resumenRows) ?> meses
        </span>
      </div>
    </div>

<div class="overflow-x-auto">
  <table class="w-full text-sm table-fixed">
    <colgroup>
      <col class="w-[22%]">
      <col class="w-[26%]">
      <col class="w-[26%]">
      <col class="w-[26%]">
    </colgroup>

    <thead class="bg-white">
      <tr class="text-chebs-black border-b border-chebs-line">
        <th class="px-4 py-3 font-black text-left">Mes</th>
        <th class="px-4 py-3 font-black text-right">Total vendido</th>
        <th class="px-4 py-3 font-black text-right">Total con costo</th>
        <th class="px-4 py-3 font-black text-right">Ganancia</th>
      </tr>
    </thead>

    <tbody class="divide-y divide-chebs-line">
      <?php if(count($resumenRows) > 0): ?>
        <?php $i=0; foreach($resumenRows as $r): $i++; ?>
          <tr class="<?= ($i % 2 ? 'bg-chebs-soft/40' : 'bg-white') ?> hover:bg-chebs-soft/70 transition">
            <td class="px-4 py-3 font-bold whitespace-nowrap">
              <span class="inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-chebs-green/70"></span>
                <?= htmlspecialchars($r['mes']) ?>
              </span>
            </td>

            <td class="px-4 py-3 text-right font-semibold tabular-nums whitespace-nowrap">
              Bs <?= number_format((float)$r['total_vendido'],2) ?>
            </td>

            <td class="px-4 py-3 text-right text-gray-800 tabular-nums whitespace-nowrap">
              Bs <?= number_format((float)$r['total_con_costo'],2) ?>
            </td>

            <td class="px-4 py-3 text-right font-black tabular-nums whitespace-nowrap <?= ((float)$r['ganancia'] >= 0) ? 'text-green-700' : 'text-red-700' ?>">
              Bs <?= number_format((float)$r['ganancia'],2) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="4" class="px-4 py-6 text-gray-600">No hay ventas registradas.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
  
</div>




  </div>

  <!-- Filtro (más pro, menos gris) -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft p-6 mb-6 relative overflow-hidden">
    <div class="absolute left-0 top-0 bottom-0 w-2 bg-chebs-green/30"></div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 md:items-center" onsubmit="return validarFiltro();">

      <!-- Autocomplete producto -->
      <div class="md:col-span-2 relative pl-3">
        <label class="block text-sm font-black mb-2 text-chebs-black">Filtrar por producto</label>

        <input id="producto_buscar"
               type="text"
               placeholder="Escribe para buscar… (o deja vacío para todos)"
               autocomplete="off"
               class="w-full px-4 py-3 rounded-2xl border border-chebs-line bg-white
                      focus:outline-none focus:ring-2 focus:ring-chebs-green/40
                      hover:border-chebs-green/40 transition">

        <!-- este es el que se envía -->
        <input type="hidden" name="producto_id" id="producto_id_real" value="<?= $producto_id ? (int)$producto_id : '' ?>">

        <!-- Fuente de datos -->
        <div class="hidden">
          <datalist id="lista_productos_mov">
            <?php
              $productos2 = $conexion->query("SELECT id, nombre FROM productos");
              while($p = $productos2->fetch_assoc()) { ?>
              <option value="<?= htmlspecialchars($p['nombre']) ?>" data-id="<?= (int)$p['id'] ?>"></option>
            <?php } ?>
          </datalist>
        </div>

        <!-- Dropdown (con colores chebs, no gris muerto) -->
        <div id="auto_box"
             class="hidden absolute left-0 right-0 mt-2 z-50 rounded-2xl border border-chebs-line bg-white shadow-soft overflow-hidden">
          <div class="px-4 py-2 text-xs text-chebs-black bg-chebs-soft/60 border-b border-chebs-line flex items-center justify-between">
            <span class="font-bold">Resultados</span>
            <span class="hidden sm:inline text-gray-600">↑ ↓ · Enter</span>
          </div>

          <div id="auto_list" class="max-h-64 overflow-auto"></div>

          <div id="auto_empty" class="hidden px-4 py-3 text-sm text-gray-500">
            Sin resultados
          </div>
        </div>

        <div class="mt-2 text-xs text-gray-500">
          Tip: si quieres ver todo, deja vacío y presiona “Aplicar filtro”.
        </div>

        <div id="prod_error" class="hidden mt-2 text-sm font-semibold text-red-600"></div>
      </div>

      <!-- Botones -->
      <div class="md:col-span-1 flex flex-col sm:flex-row gap-3 md:justify-end">
        <button type="submit"
          class="px-6 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft
                 whitespace-nowrap min-w-[160px]">
          🔎 Aplicar filtro
        </button>

        <a href="historial.php"
           class="px-6 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition text-center
                  whitespace-nowrap min-w-[120px]">
          Limpiar
        </a>
      </div>

    </form>
  </div>

  <!-- Tabla movimientos (más viva) -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">
    <div class="px-6 py-4 border-b border-chebs-line bg-chebs-soft/40">
      <h3 class="text-lg font-black text-chebs-black">Movimientos</h3>
      <p class="text-xs text-gray-600 mt-1">Detalle por fecha, tipo, lote y motivo.</p>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-white">
          <tr class="text-left text-chebs-black border-b border-chebs-line">
            <th class="px-4 py-3 font-black">Fecha</th>
            <th class="px-4 py-3 font-black">Producto</th>
            <th class="px-4 py-3 font-black">Tipo</th>
            <th class="px-4 py-3 font-black">Cantidad</th>
            <th class="px-4 py-3 font-black">Lote (Vence)</th>
            <th class="px-4 py-3 font-black">Motivo</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-chebs-line">
          <?php while($m = $movimientos->fetch_assoc()) {
            $tipo = strtolower($m['tipo'] ?? '');
            $badgeClass = "bg-gray-100 text-gray-700 border-gray-200";
            $rowAccent = "";
            $tipoLabel = strtoupper($tipo ?: '-');

            if ($tipo === 'entrada') {
              $badgeClass = "bg-green-100 text-green-800 border-green-200";
              $rowAccent  = "shadow-[inset_4px_0_0_0_rgba(34,197,94,0.35)]";
              $tipoLabel = "ENTRADA";
            } elseif ($tipo === 'salida') {
              $badgeClass = "bg-red-100 text-red-800 border-red-200";
              $rowAccent  = "shadow-[inset_4px_0_0_0_rgba(239,68,68,0.28)]";
              $tipoLabel = "SALIDA";
            } else {
              $badgeClass = "bg-yellow-100 text-yellow-800 border-yellow-200";
              $rowAccent  = "shadow-[inset_4px_0_0_0_rgba(234,179,8,0.28)]";
              $tipoLabel = strtoupper($tipo ?: 'AJUSTE');
            }

            $fechaV = $m['fecha_vencimiento'] ?? '-';
          ?>
            <tr class="hover:bg-chebs-soft/60 transition <?= $rowAccent ?>">
              <td class="px-4 py-3 whitespace-nowrap text-gray-700 font-semibold">
                <?= htmlspecialchars($m['fecha'] ?? '-') ?>
              </td>

              <td class="px-4 py-3 font-black text-chebs-black">
                <?= htmlspecialchars($m['producto'] ?? '-') ?>
              </td>

              <td class="px-4 py-3">
                <span class="inline-flex px-3 py-1 rounded-xl text-xs font-black border <?= $badgeClass ?>">
                  <?= htmlspecialchars($tipoLabel) ?>
                </span>
              </td>

              <td class="px-4 py-3">
                <span class="inline-flex px-3 py-1 rounded-xl text-xs font-black bg-chebs-soft/70 border border-chebs-line text-chebs-black">
                  <?= (int)($m['cantidad'] ?? 0) ?>
                </span>
              </td>

              <td class="px-4 py-3 whitespace-nowrap">
                <?php if (!$fechaV || $fechaV === '0000-00-00') { ?>
                  <span class="text-xs font-bold text-gray-600 bg-gray-100 border border-gray-200 px-3 py-1 rounded-full">
                    -
                  </span>
                <?php } else { ?>
                  <span class="text-sm font-semibold text-gray-700">
                    <?= htmlspecialchars($fechaV) ?>
                  </span>
                <?php } ?>
              </td>

              <td class="px-4 py-3 text-gray-700">
                <?= htmlspecialchars($m['motivo'] ?? '-') ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>

      </table>
    </div>
  </div>

</div>

<style>
  /* scrollbar pro (autocomplete) */
  #auto_list::-webkit-scrollbar { width: 10px; }
  #auto_list::-webkit-scrollbar-thumb { background: rgba(78,122,43,.35); border-radius: 10px; }
  #auto_list::-webkit-scrollbar-thumb:hover { background: rgba(78,122,43,.55); }
  #auto_list::-webkit-scrollbar-track { background: transparent; }
</style>

<script>
/* =========================
   ✅ AUTOCOMPLETE FILTRO
   ========================= */

const inputP = document.getElementById('producto_buscar');
const hiddenP = document.getElementById('producto_id_real');

const autoBox  = document.getElementById('auto_box');
const autoList = document.getElementById('auto_list');
const autoEmpty= document.getElementById('auto_empty');

const dataOptions = Array.from(document.querySelectorAll('#lista_productos_mov option'))
  .map(o => ({ label: o.value, id: o.dataset.id }));

let autoIndex = -1;
let autoItems = [];

(function preloadSelected(){
  const selectedId = hiddenP.value;
  if(!selectedId) return;
  const found = dataOptions.find(x => String(x.id) === String(selectedId));
  if(found) inputP.value = found.label;
})();

function abrirAuto(){ autoBox.classList.remove('hidden'); }
function cerrarAuto(){ autoBox.classList.add('hidden'); autoIndex = -1; }

function filtrar(q){
  const t = q.trim().toLowerCase();
  if(t.length === 0){ autoItems = []; return; }

  autoItems = dataOptions
    .filter(x => x.label.toLowerCase().includes(t))
    .slice(0, 12);

  autoIndex = autoItems.length ? 0 : -1;
}

function renderAuto(){
  autoList.innerHTML = '';
  autoEmpty.classList.add('hidden');

  if(autoItems.length === 0){
    autoEmpty.classList.remove('hidden');
    return;
  }

  autoItems.forEach((it, idx) => {
    const div = document.createElement('div');
    div.className =
      "px-4 py-3 text-sm cursor-pointer border-b border-chebs-line last:border-b-0 " +
      (idx === autoIndex ? "bg-chebs-soft/70 font-black" : "hover:bg-chebs-soft/60");

    const q = inputP.value.trim();
    if(q.length > 0){
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

function seleccionarItem(idx){
  const it = autoItems[idx];
  if(!it) return;

  inputP.value = it.label;
  hiddenP.value = it.id;
  cerrarAuto();
  setError('');
}

inputP.addEventListener('input', () => {
  hiddenP.value = '';
  setError('');

  filtrar(inputP.value);
  if(inputP.value.trim().length > 0){
    abrirAuto();
    renderAuto();
  } else {
    cerrarAuto();
  }
});

inputP.addEventListener('focus', () => {
  if(inputP.value.trim().length > 0 && autoItems.length > 0){
    abrirAuto();
    renderAuto();
  }
});

inputP.addEventListener('keydown', (e) => {
  if(autoBox.classList.contains('hidden')) return;

  if(e.key === 'ArrowDown'){
    e.preventDefault();
    autoIndex = Math.min(autoIndex + 1, autoItems.length - 1);
    renderAuto();
    return;
  }
  if(e.key === 'ArrowUp'){
    e.preventDefault();
    autoIndex = Math.max(autoIndex - 1, 0);
    renderAuto();
    return;
  }
  if(e.key === 'Enter'){
    if(autoIndex >= 0 && autoItems[autoIndex]){
      e.preventDefault();
      seleccionarItem(autoIndex);
      return;
    }
  }
  if(e.key === 'Escape'){
    cerrarAuto();
  }
});

document.addEventListener('click', (e) => {
  if(!autoBox.contains(e.target) && e.target !== inputP){
    cerrarAuto();
  }
});

function setError(msg){
  const el = document.getElementById('prod_error');
  if(!msg){
    el.classList.add('hidden');
    el.textContent = '';
    return;
  }
  el.classList.remove('hidden');
  el.textContent = msg;
}

function validarFiltro(){
  if(inputP.value.trim() === ''){
    hiddenP.value = '';
    setError('');
    return true;
  }

  if(!hiddenP.value){
    setError('Selecciona un producto válido de la lista o deja vacío para ver todos.');
    inputP.focus();
    return false;
  }

  setError('');
  return true;
}

document.addEventListener('keydown', (e) => {
  if(e.key === 'Escape') cerrarAuto();
});
</script>

<?php include "../layout/footer.php"; ?>
