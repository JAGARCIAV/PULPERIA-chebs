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

  <!-- Buscador -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft p-4 mb-6">
    <input id="buscador"
           type="text"
           placeholder="Buscar por nombre de producto..."
           class="w-full px-4 py-3 rounded-2xl border border-chebs-line focus:outline-none focus:ring-2 focus:ring-chebs-green/40">
  </div>

  <!-- Tabla -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-100">
          <tr class="text-left text-chebs-black">
            <th class="px-4 py-3 font-black">Producto</th>
            <th class="px-4 py-3 font-black">Vencimiento</th>
            <th class="px-4 py-3 font-black">Cantidad</th>
            <th class="px-4 py-3 font-black">Estado</th>
            <th class="px-4 py-3 font-black text-right">Acciones</th>
          </tr>
        </thead>

        <tbody id="tabla_body" class="divide-y divide-chebs-line">

        <?php while($l = $lotes->fetch_assoc()) {
          $fechaV = $l['fecha_vencimiento'] ?? '';
          $vencido = false;

          if (!empty($fechaV) && $fechaV !== '0000-00-00') {
            $vencido = (strtotime($fechaV) < strtotime(date('Y-m-d')));
          }
        ?>
          <tr class="hover:bg-chebs-soft/40 transition <?= $vencido ? 'bg-red-50' : '' ?>">

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
                    <span class="text-xs font-black text-red-700 bg-red-100 border border-red-200 px-3 py-1 rounded-full">
                      Vencido
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </td>

            <!-- Cantidad -->
            <td class="px-4 py-3">
              <span class="inline-flex px-3 py-1 rounded-xl text-xs font-black bg-chebs-soft/70 border border-chebs-line text-chebs-black">
                <?= (int)$l['cantidad_unidades'] ?>
              </span>
            </td>

            <!-- Estado -->
            <td class="px-4 py-3">
              <?php if (!empty($l['activo'])): ?>
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
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-chebs-line bg-white
                          hover:bg-chebs-soft font-bold text-sm transition">
                  ✏️ Editar
                </a>

                <a href="corregir_producto.php?id=<?= (int)$l['id'] ?>"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-chebs-line bg-white
                          hover:bg-chebs-soft font-bold text-sm transition">
                  🔁 Corregir
                </a>

                <?php if (!empty($l['activo'])): ?>
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
// 🔍 Buscador
const buscador = document.getElementById("buscador");
const filas = document.querySelectorAll("#tabla_body tr");

buscador.addEventListener("input", () => {
  const q = buscador.value.toLowerCase();
  filas.forEach(tr => {
    const nombre = tr.children[0].innerText.toLowerCase();
    tr.style.display = nombre.includes(q) ? "" : "none";
  });
});

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

<?php include "../layout/footer.php"; ?>
