<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

require_once "../../config/conexion.php";
require_once "../../modelos/producto_modelo.php";
include "../layout/header.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { ?>
  <div class="max-w-3xl mx-auto px-4 py-10">
    <div class="bg-white border border-red-200 rounded-3xl shadow-soft p-6 text-red-700 font-semibold">
      ❌ ID inválido
    </div>
    <a href="listar.php"
       class="mt-4 inline-flex px-6 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
      ← Volver
    </a>
  </div>
<?php
  include "../layout/footer.php";
  exit;
}

$producto = obtenerProductoPorId($conexion, $id);
if (!$producto) { ?>
  <div class="max-w-3xl mx-auto px-4 py-10">
    <div class="bg-white border border-red-200 rounded-3xl shadow-soft p-6 text-red-700 font-semibold">
      ❌ Producto no encontrado
    </div>
    <a href="listar.php"
       class="mt-4 inline-flex px-6 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
      ← Volver
    </a>
  </div>
<?php
  include "../layout/footer.php";
  exit;
}

$presentaciones = obtenerPresentacionesPorProducto($conexion, $id);
?>

<div class="max-w-3xl mx-auto px-4 py-10">

  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">

    <div class="px-8 py-6 border-b border-chebs-line">
      <h1 class="text-2xl font-black text-chebs-black">Editar producto</h1>
      <p class="text-sm text-gray-600 mt-1">
        Actualiza nombre, precios, costo y estado.
      </p>

      <?php if(isset($_GET['ok'])){ ?>
        <div class="mt-4 rounded-2xl bg-chebs-soft/70 border border-chebs-line px-4 py-3 text-sm font-semibold text-chebs-green">
          ✅ Guardado correctamente.
        </div>
      <?php } ?>
    </div>

    <form action="../../controladores/producto_actualizar.php"
          method="POST"
          class="px-8 py-8 space-y-6">

      <input type="hidden" name="id" value="<?= (int)$producto['id'] ?>">

      <div>
        <label class="block text-sm font-bold mb-2 text-chebs-black">Nombre</label>
        <input type="text"
               name="nombre"
               value="<?= htmlspecialchars($producto['nombre']) ?>"
               required
               class="w-full px-4 py-3 rounded-2xl bg-pink-50 border-2 border-pink-300
                      outline-none focus:ring-4 focus:ring-pink-200 focus:border-pink-500">
      </div>

      <!-- ✅ PRECIOS: venta + costo -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div>
          <label class="block text-sm font-bold mb-2 text-chebs-black">Precio unidad (venta)</label>
          <input type="number"
                 step="0.01"
                 name="precio_unidad"
                 value="<?= htmlspecialchars($producto['precio_unidad']) ?>"
                 required
                 class="w-full px-4 py-3 rounded-2xl bg-pink-50 border-2 border-pink-300
                        outline-none focus:ring-4 focus:ring-pink-200 focus:border-pink-500">
        </div>

        <!-- ✅ NUEVO: costo unidad -->
        <div>
          <label class="block text-sm font-bold mb-2 text-chebs-black">
            Precio mayorista (costo) unidad <span class="text-gray-500 font-semibold">(opcional)</span>
          </label>
          <input type="number"
                 step="0.01"
                 min="0"
                 name="costo_unidad"
                 value="<?= htmlspecialchars($producto['costo_unidad'] ?? '') ?>"
                 class="w-full px-4 py-3 rounded-2xl bg-white border-2 border-chebs-line
                        outline-none focus:ring-4 focus:ring-chebs-soft focus:border-chebs-green"
                 placeholder="Ej: 8.30">
          <p class="mt-2 text-xs text-gray-500">
            Se usa para calcular ganancia en reportes.
          </p>
        </div>

      </div>


      <!-- ✅ Presentaciones -->
      <div class="rounded-2xl border border-chebs-line p-4 bg-white">
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
            <thead class="bg-chebs-soft/60">
              <tr>
                <th class="text-left px-3 py-2 font-black">Nombre</th>
                <th class="text-left px-3 py-2 font-black">Unidades</th>
                <th class="text-left px-3 py-2 font-black">Precio venta</th>
                <th class="text-left px-3 py-2 font-black">Costo (opcional)</th>
                <th class="px-3 py-2 font-black text-center">×</th>
              </tr>
            </thead>

            <tbody id="pres_body" class="divide-y divide-chebs-line">
              <?php foreach($presentaciones as $pr){ ?>
                <tr>
                  <td class="px-3 py-2">
                    <input name="pres_nombre[]" value="<?= htmlspecialchars($pr['nombre']) ?>" required
                      class="w-full rounded-xl border border-chebs-line px-3 py-2 outline-none focus:ring-2 focus:ring-chebs-soft">
                  </td>
                  <td class="px-3 py-2">
                    <input name="pres_unidades[]" type="number" min="1" value="<?= (int)$pr['unidades'] ?>" required
                      class="w-full rounded-xl border border-chebs-line px-3 py-2 outline-none focus:ring-2 focus:ring-chebs-soft">
                  </td>
                  <td class="px-3 py-2">
                    <input name="pres_precio[]" type="number" step="0.01" min="0" value="<?= htmlspecialchars($pr['precio_venta']) ?>" required
                      class="w-full rounded-xl border border-chebs-line px-3 py-2 outline-none focus:ring-2 focus:ring-chebs-soft">
                  </td>
                  <td class="px-3 py-2">
                    <input name="pres_costo[]" type="number" step="0.01" min="0" value="<?= htmlspecialchars($pr['costo'] ?? '') ?>"
                      class="w-full rounded-xl border border-chebs-line px-3 py-2 outline-none focus:ring-2 focus:ring-chebs-soft"
                      placeholder="Opcional">
                  </td>
                  <td class="px-3 py-2 text-center">
                    <button type="button"
                      class="px-3 py-2 rounded-xl border border-chebs-line hover:bg-red-50 hover:border-red-200 font-black"
                      onclick="this.closest('tr').remove()">✕</button>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

        <div class="mt-2 text-xs text-gray-500">
          Si no agregas presentaciones, el producto se venderá solo por unidad.
        </div>
      </div>

      <div>
        <label class="block text-sm font-bold mb-2 text-chebs-black">Estado</label>
        <select name="activo"
                class="w-full px-4 py-3 rounded-2xl bg-pink-50 border-2 border-pink-300
                       outline-none focus:ring-4 focus:ring-pink-200 focus:border-pink-500">
          <option value="1" <?= !empty($producto['activo']) ? 'selected' : '' ?>>Activo</option>
          <option value="0" <?= empty($producto['activo']) ? 'selected' : '' ?>>Desactivado</option>
        </select>

        <div class="mt-3 rounded-2xl border border-chebs-line bg-chebs-soft/60 px-4 py-3 text-sm text-gray-700">
          <span class="font-bold">Tip:</span> Si desactivas un producto, no debería aparecer para vender.
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 pt-2">
        <button type="submit"
                class="flex-1 px-6 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft">
          💾 Guardar cambios
        </button>

        <a href="listar.php"
           class="flex-1 px-6 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition text-center">
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

  btn.addEventListener('click', () => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="px-3 py-2">
        <input name="pres_nombre[]" required
          class="w-full rounded-xl border border-chebs-line px-3 py-2 outline-none focus:ring-2 focus:ring-chebs-soft"
          placeholder="Ej: Cajetilla 10">
      </td>
      <td class="px-3 py-2">
        <input name="pres_unidades[]" type="number" min="1" required
          class="w-full rounded-xl border border-chebs-line px-3 py-2 outline-none focus:ring-2 focus:ring-chebs-soft"
          placeholder="10">
      </td>
      <td class="px-3 py-2">
        <input name="pres_precio[]" type="number" step="0.01" min="0" required
          class="w-full rounded-xl border border-chebs-line px-3 py-2 outline-none focus:ring-2 focus:ring-chebs-soft"
          placeholder="9.00">
      </td>
      <td class="px-3 py-2">
        <input name="pres_costo[]" type="number" step="0.01" min="0"
          class="w-full rounded-xl border border-chebs-line px-3 py-2 outline-none focus:ring-2 focus:ring-chebs-soft"
          placeholder="Opcional">
      </td>
      <td class="px-3 py-2 text-center">
        <button type="button"
          class="px-3 py-2 rounded-xl border border-chebs-line hover:bg-red-50 hover:border-red-200 font-black"
          onclick="this.closest('tr').remove()">✕</button>
      </td>
    `;
    body.appendChild(tr);
  });
})();
</script>

<?php include "../layout/footer.php"; ?>
