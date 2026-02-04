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

$ventas = obtenerVentasFiltradas($conexion, $fecha, $turno, $tipo, $busqueda);
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
          <?php while($v = $ventas->fetch_assoc()) { ?>
          <tr class="hover:bg-chebs-soft/40 transition">
            <td class="px-4 py-3 font-semibold">#<?= (int)$v['id'] ?></td>
            <td class="px-4 py-3 whitespace-nowrap"><?= htmlspecialchars($v['fecha']) ?></td>

            <td class="px-4 py-3">
              <?php
                $t = strtolower($v['turno'] ?? '');
                $badge = "bg-gray-100 text-gray-700 border-gray-200";
                if ($t === 'mañana') $badge = "bg-blue-100 text-blue-700 border-blue-200";
                if ($t === 'tarde')  $badge = "bg-purple-100 text-purple-700 border-purple-200";
              ?>
              <span class="inline-flex px-3 py-1 rounded-xl text-xs font-black border <?= $badge ?>">
                <?= htmlspecialchars($v['turno']) ?>
              </span>
            </td>

            <td class="px-4 py-3"><?= htmlspecialchars($v['responsable']) ?></td>
            <td class="px-4 py-3 font-black text-chebs-black">Bs <?= number_format((float)$v['total'],2) ?></td>

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
/* ✅ Mantener compatibilidad con tu JS actual */
function abrirModalGeneral(){
  document.getElementById('modalGeneral').classList.remove('hidden');
}
function cerrarModalGeneral(){
  document.getElementById('modalGeneral').classList.add('hidden');
  document.getElementById('modalContenido').innerHTML = '';
}

// ESC cierra modal
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') cerrarModalGeneral();
});
</script>

<script src="../../public/js/ventas_historial.js"></script>

<?php include "../layout/footer.php"; ?>
