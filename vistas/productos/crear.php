<?php 
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin','empleado']);

include "../layout/header.php";
?>

<?php if(isset($_GET['creado']) && isset($_GET['id'])): ?>
<div id="modalLote" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
  <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-soft border border-chebs-line">

    <h2 class="text-xl font-black mb-3">Producto guardado 🎉</h2>
    <p class="text-gray-600 mb-6">
      ¿Quieres crear el lote inicial para este producto?
    </p>

    <div class="flex gap-4">
      <a href="../lotes/registrar_lote.php?producto_id=<?= (int)$_GET['id'] ?>"
         class="flex-1 text-center px-4 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition">
        ➕ Crear lote
      </a>

      <a href="listar.php"
         class="flex-1 text-center px-4 py-3 rounded-2xl border border-chebs-line font-black hover:bg-chebs-soft transition">
        Solo guardar
      </a>
    </div>

  </div>
</div>
<?php endif; ?>

<div class="max-w-3xl mx-auto px-4 py-10">

  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft p-8">

    <div class="mb-6">
      <h1 class="text-2xl font-black text-chebs-black">Crear producto</h1>
      <p class="text-sm text-gray-600 mt-1">
        Registra un nuevo producto con sus precios de venta.
      </p>
    </div>

    <form action="../../controladores/producto_controlador.php" method="POST" class="space-y-4">

      <!-- Nombre -->
      <div>
        <label class="block text-sm font-bold mb-1">Nombre del producto</label>
        <input type="text" name="nombre" required
          class="w-full rounded-xl bg-pink-50 border-2 border-pink-300 px-3 py-2 text-gray-800
                 outline-none focus:ring-4 focus:ring-pink-200 focus:border-pink-500"
          placeholder="Ej: Cigarro, Coca 2L retornable">
      </div>

      <!-- ✅ PRECIOS: venta + costo -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- Precio unidad (venta) -->
        <div>
          <label class="block text-sm font-bold mb-1">Precio unidad (venta)</label>

          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-black text-gray-800 bg-white/70 px-2 rounded-lg">
              Bs
            </span>

            <input type="number" step="0.01" min="0" name="precio_unidad" required
              class="chebs-num w-full pl-14"
              placeholder="0.00">
          </div>
        </div>

        <!-- ✅ Precio mayorista (costo) unidad -->
        <div>
          <label class="block text-sm font-bold mb-1">
            Precio mayorista (costo) unidad <span class="text-gray-500 font-semibold">(opcional)</span>
          </label>

          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-black text-gray-800 bg-white/70 px-2 rounded-lg">
              Bs
            </span>

            <input type="number" step="0.01" min="0" name="costo_unidad"
              class="chebs-num w-full pl-14"
              placeholder="Ej: 0.80">
          </div>

          <div class="text-xs text-gray-500 mt-1">
            Se usa para calcular ganancia en reportes.
          </div>
        </div>

      </div>

      <!-- ✅ Presentaciones -->
      <div class="mt-4 rounded-2xl border border-chebs-line p-4 bg-white">
        <div class="flex items-center justify-between gap-3 mb-3">
          <div>
            <div class="font-black text-chebs-black">Presentaciones (packs)</div>
            <div class="text-xs text-gray-500">
              Ej: Cajetilla 10 (9 Bs), Cajetilla 20 (18 Bs), Pack 6 (60 Bs)…
            </div>
          </div>

          <button type="button" id="btn_add_pres"
            class="px-4 py-2 rounded-xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition">
            + Agregar
          </button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-chebs-line">
          <table class="w-full text-sm">
            <thead class="bg-chebs-soft/70">
              <tr>
                <th class="text-left px-3 py-2 font-black">Nombre</th>
                <th class="text-left px-3 py-2 font-black">Unidades</th>
                <th class="text-left px-3 py-2 font-black">Precio venta</th>
                <th class="text-left px-3 py-2 font-black">Costo (mayorista)</th>
                <th class="px-3 py-2 font-black text-center">×</th>
              </tr>
            </thead>
            <tbody id="pres_body" class="divide-y divide-chebs-line"></tbody>
          </table>
        </div>

        <div class="mt-2 text-xs text-gray-500">
          Si no agregas presentaciones, el producto se venderá solo por unidad.
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 pt-2">

        <button type="submit"
          class="flex-1 inline-flex items-center justify-center px-5 py-2 rounded-xl
                 bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft">
          💾 Guardar
        </button>

        <a href="listar.php"
          class="flex-1 inline-flex items-center justify-center px-5 py-2 rounded-xl
                 border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
          ← Volver
        </a>

      </div>

    </form>

  </div>

</div>

<script>
(function(){
  const body = document.getElementById('pres_body');
  const btn  = document.getElementById('btn_add_pres');
  if(!body || !btn) return;

  function addRow(nombre='', unidades='', precio='', costo=''){
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="px-3 py-2">
        <input name="pres_nombre[]" value="${nombre}" required
          class="w-full rounded-xl bg-pink-50 border-2 border-pink-300 px-3 py-2 outline-none focus:ring-4 focus:ring-pink-200 focus:border-pink-500"
          placeholder="Ej: Cajetilla 10">
      </td>

      <td class="px-3 py-2">
        <input name="pres_unidades[]" type="number" min="1" value="${unidades}" required
          class="w-full chebs-num"
          placeholder="10">
      </td>

      <td class="px-3 py-2">
        <input name="pres_precio[]" type="number" step="0.01" min="0" value="${precio}" required
          class="w-full chebs-num"
          placeholder="9.00">
      </td>

      <td class="px-3 py-2">
        <input name="pres_costo[]" type="number" step="0.01" min="0" value="${costo}"
          class="w-full chebs-num"
          placeholder="Ej: 5.00">
      </td>

      <td class="px-3 py-2 text-center">
        <button type="button"
          class="px-3 py-2 rounded-xl border border-chebs-line hover:bg-red-50 hover:border-red-200 font-black">
          ✕
        </button>
      </td>
    `;
    tr.querySelector('button').addEventListener('click', ()=> tr.remove());
    body.appendChild(tr);
  }

  btn.addEventListener('click', ()=> addRow());
})();
</script>

<?php include "../layout/footer.php"; ?>
