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

<!-- ✅ Antes: max-w-3xl (angosto). Ahora: max-w-7xl y menos padding vertical -->
<div class="max-w-7xl mx-auto px-4 py-6">

  <!-- ✅ Tarjeta grande -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">

    <!-- Header -->
    <div class="px-6 py-5 border-b border-chebs-line bg-chebs-soft/30">
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
        <div>
          <h1 class="text-2xl md:text-3xl font-black text-chebs-black">Crear producto</h1>
          <p class="text-sm text-gray-600 mt-1">
            Registra un nuevo producto con sus precios de venta.
          </p>
        </div>

        <a href="listar.php"
           class="inline-flex items-center justify-center px-5 py-2 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
          ← Volver a lista
        </a>
      </div>
    </div>

    <div class="p-6">

      <!-- ✅ Mensaje bonito si el controlador redirige con error -->
      <?php if(($_GET['error'] ?? '') === 'costo_mayor'): ?>
        <div class="p-4 mb-4 rounded-2xl bg-red-100 border border-red-300 text-red-800 font-bold">
          ⚠ El costo mayorista no puede ser mayor que el precio de venta.
        </div>
      <?php endif; ?>

      <!-- ✅ Mensaje de validación JS -->
      <div id="form_error" class="hidden p-4 mb-4 rounded-2xl bg-red-100 border border-red-300 text-red-800 font-bold"></div>

      <!-- ✅ Form en 2 columnas en pantallas grandes -->
      <form id="form_producto"
            action="../../controladores/producto_controlador.php"
            method="POST"
            enctype="multipart/form-data"
            class="grid grid-cols-1 lg:grid-cols-2 gap-6"
            onsubmit="return validarPrecioCosto();">

        <!-- =========================
             COLUMNA IZQUIERDA
             ========================= -->
        <div class="space-y-4">

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
    <label class="block text-sm font-bold mb-2 text-chebs-black">Precio unidad (venta)</label>

    <div class="relative">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 font-black text-gray-800 bg-white/70 px-2 rounded-lg">
        Bs
      </span>

      <input id="precio_unidad"
             type="number"
             step="0.01"
             min="0"
             name="precio_unidad"
             required
             class="w-full pl-14 px-4 py-3 rounded-2xl bg-pink-50 border-2 border-pink-300
                    outline-none focus:ring-4 focus:ring-pink-200 focus:border-pink-500"
             placeholder="0.00">
    </div>
  </div>

  <!-- Precio mayorista (costo) unidad -->
  <div>
    <label class="block text-sm font-bold mb-2 text-chebs-black">
      Precio mayorista (costo) unidad <span class="text-gray-500 font-semibold">(opcional)</span>
    </label>

    <div class="relative">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 font-black text-gray-800 bg-white/70 px-2 rounded-lg">
        Bs
      </span>

      <input id="costo_unidad"
             type="number"
             step="0.01"
             min="0"
             name="costo_unidad"
             class="w-full pl-14 px-4 py-3 rounded-2xl bg-white border-2 border-chebs-line
                    outline-none focus:ring-4 focus:ring-chebs-soft focus:border-chebs-green"
             placeholder="Ej: 0.80">
    </div>

    <div class="text-xs text-gray-500 mt-2">
      Se usa para calcular ganancia en reportes.
    </div>
  </div>

</div>


          <!-- ✅ Panelcito: Calculadora de costo por unidad -->
          <div class="mt-2 rounded-2xl border border-chebs-line p-4 bg-chebs-soft/40">
            <div class="font-black text-chebs-black mb-1">Calculadora de costo por unidad</div>


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
  <label class="block text-xs font-bold mb-1">Costo por unidad (resultado)</label>
  <input id="calc_result" type="text" readonly
    class="w-full rounded-xl bg-white border border-chebs-line px-3 py-2 font-black text-chebs-black"
    placeholder="—">
</div>

            </div>

            <div class="mt-3 flex flex-col sm:flex-row gap-2">
              <button type="button" id="btn_aplicar_costo"
                class="px-4 py-2 rounded-xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition">
                Aplicar al precio 
              </button>

              <button type="button" id="btn_limpiar_calc"
                class="px-4 py-2 rounded-xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
                Limpiar
              </button>


            </div>
          </div>

          <!-- ✅ Botones (en móviles quedan aquí; en desktop también los repetimos a la derecha sticky) -->
          <div class="flex flex-col sm:flex-row gap-3 pt-2 lg:hidden">
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

        </div>

        <!-- =========================
             COLUMNA DERECHA
             ========================= -->
        <div class="space-y-4 lg:sticky lg:top-6 self-start">

<!-- ✅ IMAGEN DEL PRODUCTO (solo UI) -->
<div class="rounded-2xl border-2 border-chebs-green/30 p-4 bg-chebs-green/10">
  <div class="flex items-start justify-between gap-3">
    <div>
      <div class="font-black text-chebs-green">Imagen del producto</div>
      <div class="text-xs text-chebs-green/80">
        Sube una foto (JPG/PNG/WebP). Recomendado: cuadrada.
      </div>
    </div>

    <span class="text-[11px] font-black text-chebs-green bg-white/70 border border-chebs-green/30 px-2 py-1 rounded-xl">
      opcional
    </span>
  </div>

  <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
    <div>
      <label class="block text-xs font-bold mb-2 text-chebs-green">Archivo</label>
      <input id="imagen_producto"
             type="file"
             name="imagen"
             accept="image/*"
             class="w-full rounded-2xl bg-white border-2 border-chebs-green/30 px-4 py-3
                    outline-none focus:ring-4 focus:ring-chebs-green/20 focus:border-chebs-green">

      <div class="mt-2 flex gap-2">
        <button type="button" id="btn_quitar_img"
          class="px-4 py-2 rounded-xl border border-chebs-green/30 bg-white font-black hover:bg-chebs-green/10 transition">
          Quitar
        </button>

        <div class="text-xs text-chebs-green/80 self-center" id="img_hint">
          Sin imagen seleccionada
        </div>
      </div>
    </div>

    <div>
      <label class="block text-xs font-bold mb-2 text-chebs-green">Vista previa</label>

      <div class="rounded-2xl border border-chebs-green/30 bg-white p-3">
        <div class="w-full aspect-square rounded-2xl bg-white border border-chebs-green/20 overflow-hidden flex items-center justify-center">
          <img id="img_preview"
               src=""
               alt=""
               class="hidden w-full h-full object-cover">

          <div id="img_placeholder" class="text-center px-4">
            <div class="text-4xl">🧃</div>
            <div class="text-xs text-chebs-green font-bold mt-2">
              Aún sin imagen
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>


          <!-- ✅ Presentaciones -->
          <div class="rounded-2xl border border-chebs-line p-4 bg-white">
            <div class="flex items-center justify-between gap-3 mb-3">
              <div>
                <div class="font-black text-chebs-black">Venta por paquetes</div>

              </div>

<button type="button" id="btn_add_pres"
  class="px-4 py-2 rounded-xl bg-orange-400 text-black font-black
         hover:bg-orange-500 transition">
  + Agregar venta por paquete
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

          <!-- ✅ Botones en desktop (siempre visibles por sticky) -->


        </div>
                  <div class="hidden lg:flex flex-col sm:flex-row gap-3 pt-2">
            <button type="submit"
              class="flex-1 inline-flex items-center justify-center px-5 py-3 rounded-2xl
                     bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft">
              💾 Guardar
            </button>


          </div>
            <a href="listar.php"
              class="flex-1 inline-flex items-center justify-center px-5 py-3 rounded-2xl
                     border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
              ← Volver
            </a>
      </form>

    </div>
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

<script>
/* ✅ Preview imagen (solo UI, no toca backend) */
(function(){
  const input = document.getElementById('imagen_producto');
  const img   = document.getElementById('img_preview');
  const ph    = document.getElementById('img_placeholder');
  const hint  = document.getElementById('img_hint');
  const btnQ  = document.getElementById('btn_quitar_img');

  if(!input || !img || !ph || !hint || !btnQ) return;

  function limpiar(){
    input.value = '';
    img.src = '';
    img.classList.add('hidden');
    ph.classList.remove('hidden');
    hint.textContent = 'Sin imagen seleccionada';
  }

  input.addEventListener('change', ()=>{
    const file = input.files && input.files[0] ? input.files[0] : null;
    if(!file){
      limpiar();
      return;
    }
    const ok = /^image\//.test(file.type || '');
    if(!ok){
      limpiar();
      alert('Selecciona un archivo de imagen (JPG/PNG/WebP).');
      return;
    }

    const url = URL.createObjectURL(file);
    img.src = url;
    img.classList.remove('hidden');
    ph.classList.add('hidden');
    hint.textContent = file.name;
  });

  btnQ.addEventListener('click', limpiar);
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
      costoUnidad.value = outResult.value; // sigue editable
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
