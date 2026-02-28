<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

require_once "../../config/conexion.php";
require_once "../../modelos/lote_modelo.php";
include "../layout/header.php";

$lotes = obtenerLotes($conexion);
?>

<div class="max-w-7xl mx-auto px-4 py-8">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-black text-chebs-black">Lotes</h1>
      <p class="text-sm text-gray-600">Control de vencimientos, cantidades y estado activo.</p>
    </div>

    <a href="registrar_lote.php"
       class="inline-flex items-center justify-center px-6 py-3 rounded-2xl bg-chebs-green text-white font-black
              hover:bg-chebs-greenDark transition shadow-soft">
      ➕ Nuevo lote
    </a>
  </div>

  <!-- Buscador + Filtro Activo/Inactivo -->
  <div class="bg-pink-50 border-2 border-pink-50 rounded-3xl shadow-soft p-4 mb-6">
    <div class="flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">

      <!-- Buscador -->
      <input id="buscador"
             type="text"
             placeholder="Buscar por nombre de producto..."
             class="w-full lg:flex-1 px-4 py-3 rounded-2xl 
                    bg-pink-50 border-2 border-pink-300 
                    outline-none focus:ring-4 focus:ring-pink-200 
                    focus:border-pink-500">

      <!-- Botones filtro -->
      <div class="flex gap-2">
        <button id="btn_activos" type="button"
                class="px-5 py-3 rounded-2xl font-black bg-green-600 text-white shadow-soft hover:bg-green-700 transition">
          Ver lotes activos
        </button>

        <button id="btn_inactivos" type="button"
                class="px-5 py-3 rounded-2xl font-black bg-red-600 text-white opacity-60 shadow-soft hover:bg-red-700 transition">
          Ver lotes desactivados
        </button>
      </div>

    </div>
  </div>

  <!-- Tabla -->
  <div class="bg-white border border-green-100 rounded-3xl shadow-soft overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-green-50">
          <tr class="text-left text-gray-800">
            <!-- ✅ CLICK PARA ORDENAR -->
            <th id="th_producto"
                class="px-4 py-3 font-black text-green-700 cursor-pointer select-none hover:bg-green-100">
              Producto ⬍
            </th>

            <!-- ✅ CLICK PARA ORDENAR -->
            <th id="th_venc"
                class="px-4 py-3 font-black text-green-700 cursor-pointer select-none hover:bg-green-100">
              Vencimiento ⬍
            </th>

            <th class="px-4 py-3 font-black text-green-700">Cantidad</th>
            <th class="px-4 py-3 font-black text-green-700">Estado</th>
            <th class="px-4 py-3 font-black text-right text-green-700">Acciones</th>
          </tr>
        </thead>

        <tbody id="tabla_body" class="divide-y divide-green-100">
        <?php while($l = $lotes->fetch_assoc()) {
          $fechaV = $l['fecha_vencimiento'] ?? '';
          $vencido = false;

          if (!empty($fechaV) && $fechaV !== '0000-00-00') {
            $vencido = (strtotime($fechaV) < strtotime(date('Y-m-d')));
          }

          $activo = !empty($l['activo']) ? 1 : 0;

          // ✅ datos para filtrar/ordenar (sin tocar backend)
          $nombreKey = mb_strtolower(trim((string)($l['nombre'] ?? '')));
          $vencKey   = ($fechaV && $fechaV !== '0000-00-00') ? $fechaV : '9999-12-31';
        ?>
          <tr class="border-t border-green-100 hover:bg-green-50 transition <?= $vencido ? 'bg-red-50' : '' ?>"
              data-activo="<?= (int)$activo ?>"
              data-nombre="<?= htmlspecialchars($nombreKey) ?>"
              data-venc="<?= htmlspecialchars($vencKey) ?>">

            <!-- Producto -->
            <td class="px-4 py-3">
              <div class="font-semibold text-chebs-black">
                <?= htmlspecialchars($l['nombre']) ?>
              </div>
              <div class="text-xs text-gray-500">Lote #<?= (int)$l['id'] ?></div>
            </td>

            <!-- Vencimiento -->
            <td class="px-4 py-3">
              <?php if (!$fechaV || $fechaV === '0000-00-00'): ?>
                <span class="text-xs font-bold text-gray-600 bg-gray-100 border border-gray-200 px-3 py-1 rounded-full">
                  Sin fecha
                </span>
              <?php else: ?>
                <div class="flex items-center gap-2">
                  <span class="font-semibold"><?= htmlspecialchars($fechaV) ?></span>

                  <?php if ($vencido): ?>
                    <span class="text-xs font-black text-red-800 bg-red-200 border border-red-300 px-3 py-1 rounded-full">
                      Vencido
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </td>

            <!-- Cantidad -->
            <td class="px-4 py-3">
              <span class="inline-flex px-3 py-1 rounded-xl text-xs font-black bg-green-50 border border-green-100 text-green-800">
                <?= (int)$l['cantidad_unidades'] ?>
              </span>
            </td>

            <!-- Estado -->
            <td class="px-4 py-3">
              <?php if ($activo): ?>
                <span class="inline-flex px-3 py-1 rounded-xl text-xs font-black bg-green-100 text-green-700 border border-green-200">
                  Activo
                </span>
              <?php else: ?>
                <span class="inline-flex px-3 py-1 rounded-xl text-xs font-black bg-gray-200 text-gray-700 border border-gray-300">
                  Inactivo
                </span>
              <?php endif; ?>
            </td>

            <!-- Acciones -->
            <td class="px-4 py-3">
              <div class="flex flex-wrap gap-2 justify-end">

                <a href="editar.php?id=<?= (int)$l['id'] ?>"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-green-100 bg-white
                          hover:bg-green-50 font-bold text-sm transition">
                  ✏️ Editar
                </a>

                <a href="corregir_producto.php?id=<?= (int)$l['id'] ?>"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-green-100 bg-white
                          hover:bg-green-50 font-bold text-sm transition">
                  🔁 Corregir
                </a>

                <?php if ($activo): ?>
                  <button type="button"
                          class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-red-200 bg-red-50
                                 hover:bg-red-100 font-black text-sm text-red-700 transition"
                          onclick="confirmarLink('<?= '../../controladores/desactivar_lote.php?id='.(int)$l['id'] ?>','Desactivar lote','¿Desactivar este lote?')">
                    ⛔ Desactivar
                  </button>
                <?php else: ?>
                  <span class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-gray-200 bg-gray-50
                               font-bold text-sm text-gray-600">
                    —
                  </span>
                <?php endif; ?>

              </div>
            </td>

          </tr>
        <?php } ?>

        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- ✅ Modal confirm Chebs -->
<div id="modalConfirmacion" class="hidden fixed inset-0 z-[9999]">
  <div class="absolute inset-0 bg-black/40" onclick="cerrarConfirm()"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl bg-white shadow-soft border border-chebs-line overflow-hidden">
      <div class="px-6 py-5 border-b border-chebs-line">
        <h3 class="text-lg font-black text-chebs-black" id="confirm_titulo">Confirmar</h3>
        <p class="text-sm text-gray-600" id="confirm_texto">¿Estás seguro?</p>
      </div>

      <div class="px-6 py-5 border-t border-chebs-line flex justify-end gap-2">
        <button type="button"
                class="px-5 py-3 rounded-2xl border border-chebs-line font-semibold hover:bg-gray-50"
                onclick="cerrarConfirm()">
          Cancelar
        </button>

        <a id="confirm_link"
           href="#"
           class="px-5 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition">
          Sí, continuar
        </a>
      </div>
    </div>
  </div>
</div>

<script>
// ✅ Modal Confirmación para links
function confirmarLink(url, titulo, texto){
  document.getElementById('confirm_titulo').textContent = titulo || 'Confirmar';
  document.getElementById('confirm_texto').textContent = texto || '¿Estás seguro?';
  document.getElementById('confirm_link').setAttribute('href', url);

  document.getElementById('modalConfirmacion').classList.remove('hidden');
}

function cerrarConfirm(){
  document.getElementById('modalConfirmacion').classList.add('hidden');
  document.getElementById('confirm_link').setAttribute('href', '#');
}

// ESC cierra modal
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') cerrarConfirm();
});
</script>

<script>
/* ==========================================================
   ✅ Buscador (debounce) + filtro activo/inactivo + orden
   ========================================================== */

const buscador = document.getElementById("buscador");
const tbody = document.getElementById("tabla_body");

const btnAct = document.getElementById("btn_activos");
const btnIna = document.getElementById("btn_inactivos");

const thProducto = document.getElementById("th_producto");
const thVenc     = document.getElementById("th_venc");

let mostrarActivos = true;
let q = "";
let orden = { campo: "nombre", dir: "asc" };
let t = null;

function filas() {
  return Array.from(tbody.querySelectorAll("tr"));
}

function aplicarFiltro() {
  const lista = filas();

  for (const tr of lista) {
    const activo = tr.dataset.activo === "1";
    const nombre = tr.dataset.nombre || "";

    const okEstado = mostrarActivos ? activo : !activo;
    const okSearch = !q || nombre.includes(q);

    tr.style.display = (okEstado && okSearch) ? "" : "none";
  }
}

function ordenarPor(campo) {
  const lista = filas();

  if (orden.campo === campo) {
    orden.dir = (orden.dir === "asc") ? "desc" : "asc";
  } else {
    orden.campo = campo;
    orden.dir = "asc";
  }

  lista.sort((a,b) => {
    const A = a.dataset[campo] || "";
    const B = b.dataset[campo] || "";
    if (A === B) return 0;

    if (orden.dir === "asc") return (A > B) ? 1 : -1;
    return (A < B) ? 1 : -1;
  });

  tbody.innerHTML = "";
  lista.forEach(tr => tbody.appendChild(tr));

  // ✅ actualizar texto del encabezado
  if (campo === "nombre") {
    thProducto.textContent = `Producto ${orden.dir === "asc" ? "A→Z" : "Z→A"}`;
    thVenc.textContent = "Vencimiento ⬍";
  } else {
    thVenc.textContent = `Vencimiento ${orden.dir === "asc" ? "↑" : "↓"}`;
    thProducto.textContent = "Producto ⬍";
  }

  aplicarFiltro();
}

// ✅ Botones de filtro
btnAct.addEventListener("click", () => {
  mostrarActivos = true;
  btnAct.classList.remove("opacity-60");
  btnIna.classList.add("opacity-60");
  aplicarFiltro();
});

btnIna.addEventListener("click", () => {
  mostrarActivos = false;
  btnIna.classList.remove("opacity-60");
  btnAct.classList.add("opacity-60");
  aplicarFiltro();
});

// ✅ Buscador con debounce (no congela)
buscador.addEventListener("input", () => {
  clearTimeout(t);
  t = setTimeout(() => {
    q = buscador.value.trim().toLowerCase();
    aplicarFiltro();
  }, 150);
});

// ✅ Click para ordenar
thProducto.addEventListener("click", () => ordenarPor("nombre"));
thVenc.addEventListener("click", () => ordenarPor("venc"));

// Inicial
aplicarFiltro();
ordenarPor("nombre"); // arranca A→Z
</script>

<?php include "../layout/footer.php"; ?>