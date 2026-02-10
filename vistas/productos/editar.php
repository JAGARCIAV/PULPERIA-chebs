<?php 
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin','empleado']);

require_once "../../config/conexion.php";
require_once "../../modelos/producto_modelo.php";
include "../layout/header.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { ?>
  <div class="max-w-7xl mx-auto px-4 py-6">
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
  <div class="max-w-7xl mx-auto px-4 py-6">
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

<div class="max-w-7xl mx-auto px-4 py-6">

  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">

    <!-- Header -->
    <div class="px-6 py-5 border-b border-chebs-line bg-chebs-soft/30">
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
        <div>
          <h1 class="text-2xl md:text-3xl font-black text-chebs-black">Editar producto</h1>
          <p class="text-sm text-gray-600 mt-1">
            Actualiza nombre, precios, costo y estado.
          </p>
        </div>

        <a href="listar.php"
           class="inline-flex items-center justify-center px-5 py-2 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
          ← Volver a lista
        </a>
      </div>

      <?php if(isset($_GET['ok'])){ ?>
        <div class="mt-4 rounded-2xl bg-chebs-soft/70 border border-chebs-line px-4 py-3 text-sm font-semibold text-chebs-green">
          ✅ Guardado correctamente.
        </div>
      <?php } ?>

      <!-- ✅ Mensajes de error amigables -->
      <?php if(($_GET['error'] ?? '') === 'costo_mayor'){ ?>
        <div class="mt-4 rounded-2xl bg-red-100 border border-red-200 px-4 py-3 text-sm font-semibold text-red-700">
          ⚠ El costo mayorista no puede ser mayor que el precio de venta.
        </div>
      <?php } elseif(($_GET['error'] ?? '') === 'pres_costo_mayor'){ ?>
        <div class="mt-4 rounded-2xl bg-red-100 border border-red-200 px-4 py-3 text-sm font-semibold text-red-700">
          ⚠ En una presentación, el costo mayorista del pack no puede ser mayor que su precio de venta.
          <?php if(isset($_GET['idx'])){ ?> (Fila #<?= (int)$_GET['idx'] ?>)<?php } ?>
        </div>
      <?php } elseif(($_GET['error'] ?? '') === 'pres_derivado_mayor'){ ?>
        <div class="mt-4 rounded-2xl bg-red-100 border border-red-200 px-4 py-3 text-sm font-semibold text-red-700">
          ⚠ En una presentación, el costo derivado (costo_unidad × unidades) sería mayor que el precio del pack.
          <?php if(isset($_GET['idx'])){ ?> (Fila #<?= (int)$_GET['idx'] ?>)<?php } ?>
        </div>
      <?php } elseif(($_GET['error'] ?? '') === 'sql'){ ?>
        <div class="mt-4 rounded-2xl bg-red-100 border border-red-200 px-4 py-3 text-sm font-semibold text-red-700">
          ⚠ No se pudo guardar. Revisa precios/costos (la base de datos bloqueó la operación).
        </div>
      <?php } ?>
    </div>

    <div class="p-6">

      <!-- ✅ Mensaje de validación JS -->
      <div id="form_error" class="hidden mb-4 p-4 rounded-2xl bg-red-100 border border-red-300 text-red-800 font-bold"></div>

      <!-- ✅ Form en 2 columnas -->
      <form id="form_producto"
            action="../../controladores/producto_actualizar.php"
            method="POST"
            class="grid grid-cols-1 lg:grid-cols-2 gap-6"
            onsubmit="return validarPrecioCosto();">

        <input type="hidden" name="id" value="<?= (int)$producto['id'] ?>">

        <!-- =========================
             COLUMNA IZQUIERDA
             ========================= -->
        <div class="space-y-4">

          <div>
            <label class="block text-sm font-bold mb-2 text-chebs-black">Nombre</label>
            <input type="text"
                   name="nombre"
                   value="<?= htmlspecialchars($producto['nombre']) ?>"
                   required
                   class="w-full px-4 py-3 rounded-2xl bg-pink-50 border-2 border-pink-300
                          outline-none focus:ring-4 focus:ring-pink-200 focus:border-pink-500">
          </div>

          <!-- ✅ PRECIOS -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div>
              <label class="block text-sm font-bold mb-2 text-chebs-black">Precio unidad (venta)</label>
              <input id="precio_unidad"
                     type="number"
                     step="0.01"
                     name="precio_unidad"
                     value="<?= htmlspecialchars($producto['precio_unidad']) ?>"
                     required
                     class="w-full px-4 py-3 rounded-2xl bg-pink-50 border-2 border-pink-300
                            outline-none focus:ring-4 focus:ring-pink-200 focus:border-pink-500">
            </div>

            <div>
              <label class="block text-sm font-bold mb-2 text-chebs-black">
                Precio mayorista (costo) unidad <span class="text-gray-500 font-semibold">(opcional)</span>
              </label>
              <input id="costo_unidad"
                     type="number"
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
<!-- ✅ Calculadora -->
<div class="rounded-2xl border border-chebs-line p-4 bg-chebs-soft/40">

  <div class="font-black text-chebs-black mb-1">
    Calculadora de costo por unidad
  </div>

  <div class="text-xs text-gray-600 mb-3">
    Escribe cuántas unidades recibiste y cuánto pagaste en total.
    Se calculará el costo por unidad y se llenará en “Precio mayorista”.
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

    <div>
      <label class="block text-xs font-bold mb-1">Total unidades</label>
      <input id="calc_unidades" type="number" min="1" step="1"
        class="w-full px-3 py-2 rounded-xl bg-pink-50 border-2 border-pink-300
               outline-none focus:ring-4 focus:ring-pink-200 focus:border-pink-500"
        placeholder="Ej: 18">
    </div>

    <div>
      <label class="block text-xs font-bold mb-1">Total pagado (Bs)</label>
      <input id="calc_total" type="number" min="0" step="0.01"
        class="w-full px-3 py-2 rounded-xl bg-green-50 border-2 border-green-300
               outline-none focus:ring-4 focus:ring-green-200 focus:border-green-500"
        placeholder="Ej: 54.00">
    </div>

    <div>
      <label class="block text-xs font-bold mb-1">
        Costo por unidad (resultado)
      </label>
      <input id="calc_result" type="text" readonly
        class="w-full rounded-xl bg-white border border-chebs-line px-3 py-2 font-black text-chebs-black"
        placeholder="—">
    </div>

  </div>

  <div class="mt-3 flex flex-col sm:flex-row gap-2">
    <button type="button" id="btn_aplicar_costo"
      class="px-4 py-2 rounded-xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition">
      Aplicar al precio mayorista
    </button>

    <button type="button" id="btn_limpiar_calc"
      class="px-4 py-2 rounded-xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
      Limpiar
    </button>

    <div class="text-xs text-gray-600 sm:ml-auto sm:text-right">
      Tip: también puedes escribir directo el costo si ya lo sabes.
    </div>
  </div>

</div>

          <!-- ✅ Estado -->
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

          <!-- Botones móviles -->
          <div class="flex flex-col sm:flex-row gap-3 pt-2 lg:hidden">
            <button type="submit"
                    class="flex-1 px-6 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft">
              💾 Guardar cambios
            </button>

            <a href="listar.php"
               class="flex-1 px-6 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition text-center">
              ← Volver
            </a>
          </div>

        </div>

        <!-- =========================
             COLUMNA DERECHA (sticky)
             ========================= -->
        <div class="space-y-4 lg:sticky lg:top-6 self-start">

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
  class="px-4 py-2 rounded-xl bg-orange-400 text-black font-black
         hover:bg-orange-500 transition">
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

          <!-- Botones desktop (siempre visibles) -->
          <div class="hidden lg:flex flex-col sm:flex-row gap-3 pt-2">
            <button type="submit"
                    class="flex-1 px-6 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft">
              💾 Guardar cambios
            </button>

            <a href="listar.php"
               class="flex-1 px-6 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition text-center">
              ← Volver
            </a>
          </div>

        </div>

      </form>

    </div>
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

<script>
/* ✅ Calculadora costo por unidad -> llena costo_unidad */
(function(){
  function toNum(v){
    const n = parseFloat(String(v ?? '').replace(',', '.'));
    return Number.isFinite(n) ? n : NaN;
  }

  const inpUnidades = document.getElementById('calc_unidades');
  const inpTotal    = document.getElementById('calc_total');
  const outResult   = document.getElementById('calc_result');
  const btnAplicar  = document.getElementById('btn_aplicar_costo');
  const btnLimpiar  = document.getElementById('btn_limpiar_calc');
  const costoUnidad = document.getElementById('costo_unidad');

  if(!inpUnidades || !inpTotal || !outResult || !btnAplicar || !btnLimpiar || !costoUnidad) return;

  function recalcular(){
    const u = toNum(inpUnidades.value);
    const t = toNum(inpTotal.value);

    if(!Number.isFinite(u) || u <= 0 || !Number.isFinite(t) || t < 0){
      outResult.value = '';
      outResult.placeholder = '—';
      return;
    }

    const r = t / u;
    outResult.value = r.toFixed(2);
  }

  inpUnidades.addEventListener('input', recalcular);
  inpTotal.addEventListener('input', recalcular);

  btnAplicar.addEventListener('click', ()=>{
    recalcular();
    if(outResult.value){
      costoUnidad.value = outResult.value;
      costoUnidad.dispatchEvent(new Event('input', { bubbles: true }));
    }
  });

  btnLimpiar.addEventListener('click', ()=>{
    inpUnidades.value = '';
    inpTotal.value = '';
    outResult.value = '';
    outResult.placeholder = '—';
  });
})();
</script>

<script>
/* ✅ Validaciones: costo <= precio (unidad y packs) */
function showFormError(msg){
  const el = document.getElementById('form_error');
  if(!el) return;
  el.textContent = msg;
  el.classList.remove('hidden');
}

function clearFormError(){
  const el = document.getElementById('form_error');
  if(!el) return;
  el.textContent = '';
  el.classList.add('hidden');
}

function toNum(v){
  const n = parseFloat(String(v ?? '').replace(',', '.'));
  return Number.isFinite(n) ? n : NaN;
}

function validarPrecioCosto(){
  clearFormError();

  const precioUnidad = toNum(document.getElementById('precio_unidad')?.value);
  const costoUnidad  = toNum(document.getElementById('costo_unidad')?.value);

  if(Number.isFinite(costoUnidad) && Number.isFinite(precioUnidad) && costoUnidad > precioUnidad){
    showFormError("⚠ El costo mayorista por unidad no puede ser mayor que el precio de venta.");
    document.getElementById('costo_unidad')?.focus();
    return false;
  }

  const rows = Array.from(document.querySelectorAll('#pres_body tr'));
  for(let idx=0; idx<rows.length; idx++){
    const tr = rows[idx];
    const unidades = toNum(tr.querySelector('input[name="pres_unidades[]"]')?.value);
    const pVenta   = toNum(tr.querySelector('input[name="pres_precio[]"]')?.value);
    const pCosto   = toNum(tr.querySelector('input[name="pres_costo[]"]')?.value);

    if(Number.isFinite(pCosto) && Number.isFinite(pVenta) && pCosto > pVenta){
      showFormError(`⚠ En la presentación #${idx+1}, el costo mayorista del pack no puede ser mayor que su precio de venta.`);
      tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return false;
    }

    if(!Number.isFinite(pCosto) && Number.isFinite(costoUnidad) && Number.isFinite(unidades) && Number.isFinite(pVenta)){
      const costoDerivado = costoUnidad * unidades;
      if(costoDerivado > pVenta){
        showFormError(`⚠ En la presentación #${idx+1}, el costo derivado (costo_unidad × unidades = ${costoDerivado.toFixed(2)}) sería mayor que el precio del pack. Ajusta el precio del pack o el costo.`);
        tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
      }
    }
  }

  return true;
}
</script>

<?php include "../layout/footer.php"; ?>
