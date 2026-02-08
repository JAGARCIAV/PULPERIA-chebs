<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin','empleado']); // si solo admin, cambia a ['admin']

require_once "../../config/conexion.php";
require_once "../../modelos/venta_modelo.php";
include "../layout/header.php";

$fecha     = $_GET['fecha'] ?? null;
$turno     = $_GET['turno'] ?? null;
$tipo      = $_GET['tipo'] ?? null;
$busqueda  = $_GET['busqueda'] ?? null;

$ventasRs = obtenerVentasFiltradas($conexion, $fecha, $turno, $tipo, $busqueda);

/* =====================================================
   🎨 COLORES POR RESPONSABLE (FILA COMPLETA)
   + Colores distintos para ADMIN vs EMPLEADO
   + PRIMERA venta REAL = más temprana por (usuario + día)
   ===================================================== */

// Mapa: nombre -> rol (para saber si es admin)
$mapRolPorNombre = [];
$qRoles = $conexion->query("SELECT nombre, rol FROM usuarios");
if ($qRoles) {
  while($u = $qRoles->fetch_assoc()){
    $mapRolPorNombre[strtolower(trim($u['nombre']))] = strtolower(trim($u['rol']));
  }
}

// Paletas (fila completa) - SUAVE
$coloresFilaEmpleado = [
  ["bg-pink-50",   "hover:bg-pink-100/60",   "border-pink-300"],
  ["bg-green-50",  "hover:bg-green-100/60",  "border-green-300"],
  ["bg-blue-50",   "hover:bg-blue-100/60",   "border-blue-300"],
  ["bg-purple-50", "hover:bg-purple-100/60", "border-purple-300"],
  ["bg-yellow-50", "hover:bg-yellow-100/60", "border-yellow-300"],
  ["bg-red-50",    "hover:bg-red-100/60",    "border-red-300"],
];

$coloresFilaAdmin = [
  ["bg-amber-50",  "hover:bg-amber-100/60",  "border-amber-300"],
  ["bg-orange-50", "hover:bg-orange-100/60", "border-orange-300"],
  ["bg-rose-50",   "hover:bg-rose-100/60",   "border-rose-300"],
  ["bg-teal-50",   "hover:bg-teal-100/60",   "border-teal-300"],
];

// Paletas (fila completa) - FUERTE (para primera venta real)
$coloresFilaEmpleadoFuerte = [
  ["bg-pink-200",   "hover:bg-pink-300/70",   "border-pink-400"],
  ["bg-green-200",  "hover:bg-green-300/70",  "border-green-400"],
  ["bg-blue-200",   "hover:bg-blue-300/70",   "border-blue-400"],
  ["bg-purple-200", "hover:bg-purple-300/70", "border-purple-400"],
  ["bg-yellow-200", "hover:bg-yellow-300/70", "border-yellow-400"],
  ["bg-red-200",    "hover:bg-red-300/70",    "border-red-400"],
];

$coloresFilaAdminFuerte = [
  ["bg-amber-200",  "hover:bg-amber-300/70",  "border-amber-400"],
  ["bg-orange-200", "hover:bg-orange-300/70", "border-orange-400"],
  ["bg-rose-200",   "hover:bg-rose-300/70",   "border-rose-400"],
  ["bg-teal-200",   "hover:bg-teal-300/70",   "border-teal-400"],
];

// mismo responsable => mismo color (suave o fuerte)
function filaColorPorUsuario($responsable, $rol, $palEmp, $palAdm, $palEmpFuerte, $palAdmFuerte, $fuerte = false){
  $key = strtolower(trim((string)$responsable));
  $hash = crc32($key);

  if ($rol === 'admin') {
    $pal = $fuerte ? $palAdmFuerte : $palAdm;
  } else {
    $pal = $fuerte ? $palEmpFuerte : $palEmp;
  }

  return $pal[$hash % count($pal)]; // [bg, hover, border]
}

// badge más fuerte para que resalte sobre el fondo
function colorResponsableBadge($responsable){
  $pal = [
    "bg-pink-200 text-pink-900 border-pink-300",
    "bg-green-200 text-green-900 border-green-300",
    "bg-blue-200 text-blue-900 border-blue-300",
    "bg-purple-200 text-purple-900 border-purple-300",
    "bg-yellow-200 text-yellow-900 border-yellow-300",
    "bg-red-200 text-red-900 border-red-300",
  ];
  $hash = crc32(strtolower(trim((string)$responsable)));
  return $pal[$hash % count($pal)];
}

/* =====================================================
   ✅ PASO 1: traer todas las ventas a un array
   ✅ PASO 2: calcular la primera venta REAL por (responsable + día)
   ===================================================== */

$ventas = [];
$primeraPorRespDia = []; // clave "resp|YYYY-MM-DD" => fechaHoraMinima (string)

while($row = $ventasRs->fetch_assoc()){
  $ventas[] = $row;

  $resp = (string)($row['responsable'] ?? '');
  $respKey = strtolower(trim($resp));

  // fecha viene tipo "2026-02-06 20:48:22"
  $fechaHora = (string)($row['fecha'] ?? '');
  $dia = substr($fechaHora, 0, 10); // YYYY-MM-DD

  $k = $respKey . '|' . $dia;

  // guardamos la más pequeña (más temprana)
  if (!isset($primeraPorRespDia[$k]) || $fechaHora < $primeraPorRespDia[$k]) {
    $primeraPorRespDia[$k] = $fechaHora;
  }
}
?>

<div class="max-w-7xl mx-auto px-4 py-8">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-black text-chebs-black">Historial de ventas</h1>
      <p class="text-sm text-gray-600">Filtra por fecha, turno o responsable.</p>
    </div>

    <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
       class="inline-flex items-center justify-center px-6 py-3 rounded-2xl bg-chebs-green text-white font-black
              hover:bg-chebs-greenDark transition shadow-soft">
      🧾 Ir a ventas
    </a>
  </div>

  <!-- Filtros -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft p-6 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">

      <!-- Fecha -->
      <div class="md:col-span-1">
        <label class="block text-sm font-bold mb-2 text-chebs-black">Fecha</label>
        <input type="date"
               name="fecha"
               value="<?= htmlspecialchars($_GET['fecha'] ?? '') ?>"
               class="w-full px-4 py-3 rounded-2xl border border-chebs-line
                      focus:outline-none focus:ring-2 focus:ring-chebs-green/40">
      </div>

      <!-- Turno -->
      <div class="md:col-span-1">
        <label class="block text-sm font-bold mb-2 text-chebs-black">Turno</label>
        <select name="turno"
                class="w-full px-4 py-3 rounded-2xl border border-chebs-line bg-white
                       focus:outline-none focus:ring-2 focus:ring-chebs-green/40">
          <option value="">Todos</option>
          <option value="mañana" <?= (($_GET['turno'] ?? '') === 'mañana' ? 'selected' : '') ?>>Mañana</option>
          <option value="tarde" <?= (($_GET['turno'] ?? '') === 'tarde' ? 'selected' : '') ?>>Tarde</option>
        </select>
      </div>

      <!-- Tipo búsqueda -->
      <div class="md:col-span-1">
        <label class="block text-sm font-bold mb-2 text-chebs-black">Buscar por</label>
        <select name="tipo"
                class="w-full px-4 py-3 rounded-2xl border border-chebs-line bg-white
                       focus:outline-none focus:ring-2 focus:ring-chebs-green/40">
          <option value="id" <?= (($_GET['tipo'] ?? 'id') === 'id' ? 'selected' : '') ?>>ID Venta</option>
          <option value="responsable" <?= (($_GET['tipo'] ?? '') === 'responsable' ? 'selected' : '') ?>>Responsable</option>
        </select>
      </div>

      <!-- Texto búsqueda -->
      <div class="md:col-span-2">
        <label class="block text-sm font-bold mb-2 text-chebs-black">Texto</label>
        <input type="text"
               name="busqueda"
               placeholder="Ej: 120 | Juan | Maria..."
               value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>"
               class="w-full px-4 py-3 rounded-2xl border border-chebs-line
                      focus:outline-none focus:ring-2 focus:ring-chebs-green/40">
      </div>

      <!-- Botones -->
      <div class="md:col-span-1 flex gap-3 md:justify-end">
        <button type="submit"
                class="px-6 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft
                       whitespace-nowrap min-w-[120px]">
          Filtrar
        </button>

        <a href="historial.php"
           class="px-6 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition text-center
                  whitespace-nowrap min-w-[120px]">
          Limpiar
        </a>
      </div>

    </form>
  </div>

  <!-- Tabla -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-100">
          <tr class="text-left text-chebs-black">
            <th class="px-4 py-3 font-black">ID</th>
            <th class="px-4 py-3 font-black">Fecha</th>
            <th class="px-4 py-3 font-black">Turno</th>
            <th class="px-4 py-3 font-black">Responsable</th>
            <th class="px-4 py-3 font-black">Total</th>
            <th class="px-4 py-3 font-black text-right">Detalle</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-chebs-line">
          <?php foreach($ventas as $v) { ?>

          <?php
            $resp = $v['responsable'] ?? '';
            $respKey = strtolower(trim($resp));
            $rolResp = $mapRolPorNombre[$respKey] ?? 'empleado';

            $fechaHora = (string)($v['fecha'] ?? '');
            $dia = substr($fechaHora, 0, 10);
            $k = $respKey . '|' . $dia;

            // ✅ primera venta REAL del día para ese responsable
            $esPrimera = isset($primeraPorRespDia[$k]) && ($fechaHora === $primeraPorRespDia[$k]);

            [$bgFila, $hoverFila, $borderFila] = filaColorPorUsuario(
              $resp,
              $rolResp,
              $coloresFilaEmpleado,
              $coloresFilaAdmin,
              $coloresFilaEmpleadoFuerte,
              $coloresFilaAdminFuerte,
              $esPrimera
            );

            $badgeResp = colorResponsableBadge($resp);
          ?>

          <tr class="transition <?= $bgFila ?> <?= $hoverFila ?> border-l-4 <?= $borderFila ?>">

            <td class="px-4 py-3 font-semibold">#<?= (int)$v['id'] ?></td>

            <!-- ✅ fecha/hora grande y negrita SOLO si es primera -->
            <td class="px-4 py-3 whitespace-nowrap <?= $esPrimera ? 'font-black text-base' : '' ?>">
              <?= htmlspecialchars($v['fecha']) ?>
              <?php if($esPrimera){ ?>
                <div class="text-[11px] uppercase tracking-wide opacity-80">
                  primera venta
                </div>
              <?php } ?>
            </td>

            <td class="px-4 py-3">
              <?php
                $t = strtolower($v['turno'] ?? '');
                $badgeTurno = "bg-gray-100 text-gray-700 border-gray-200";
                if ($t === 'mañana') $badgeTurno = "bg-blue-100 text-blue-700 border-blue-200";
                if ($t === 'tarde')  $badgeTurno = "bg-purple-100 text-purple-700 border-purple-200";
              ?>
              <span class="inline-flex px-3 py-1 rounded-xl text-xs font-black border <?= $badgeTurno ?>">
                <?= htmlspecialchars($v['turno']) ?>
              </span>
            </td>

            <td class="px-4 py-3">
              <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-black border <?= $badgeResp ?>">
                <?= htmlspecialchars($resp) ?>
                <?php if($rolResp === 'admin'){ ?>
                  <span class="ml-2 text-[10px] font-black opacity-80">(ADMIN)</span>
                <?php } ?>
              </span>
            </td>

            <td class="px-4 py-3 font-black text-chebs-black">
              Bs <?= number_format((float)$v['total'], 2) ?>
            </td>

            <td class="px-4 py-3 text-right">
              <button type="button"
                      class="px-4 py-2 rounded-xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition"
                      onclick="location.href='corregir_venta.php?id=<?= (int)$v['id'] ?>'">
                Editar
              </button>

              <button type="button"
                      class="px-4 py-2 rounded-xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition"
                      onclick="verDetalleVenta(<?= (int)$v['id'] ?>)">
                Ver detalle
              </button>
            </td>

          </tr>

          <?php } ?>
        </tbody>

      </table>
    </div>
  </div>

</div>

<!-- ✅ MODAL CHEBS (reemplaza modal viejo) -->
<div id="modalGeneral" class="hidden fixed inset-0 z-[9999]">
  <div class="absolute inset-0 bg-black/40" onclick="cerrarModalGeneral()"></div>

  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-3xl rounded-3xl bg-white shadow-soft border border-chebs-line overflow-hidden">

      <div class="px-6 py-5 border-b border-chebs-line flex items-center justify-between">
        <div>
          <h3 class="text-lg font-black text-chebs-black">Detalle de venta</h3>
          <p class="text-sm text-gray-600">Revisa los productos vendidos y subtotales.</p>
        </div>

        <button type="button"
                class="px-4 py-2 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition"
                onclick="cerrarModalGeneral()">
          ✕
        </button>
      </div>

      <div id="modalContenido" class="px-6 py-6">
        <!-- aquí tu JS imprime el detalle -->
      </div>

      <div class="px-6 py-5 border-t border-chebs-line flex justify-end">
        <button type="button"
                class="px-6 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft"
                onclick="cerrarModalGeneral()">
          Cerrar
        </button>
      </div>

    </div>
  </div>
</div>

<script>
function abrirModalGeneral(){
  document.getElementById('modalGeneral').classList.remove('hidden');
}
function cerrarModalGeneral(){
  document.getElementById('modalGeneral').classList.add('hidden');
  document.getElementById('modalContenido').innerHTML = '';
}
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') cerrarModalGeneral();
});
</script>

<script src="../../public/js/ventas_historial.js"></script>

<?php include "../layout/footer.php"; ?>
