<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin','empleado']);

require_once "../../config/conexion.php";
require_once "../../modelos/lote_modelo.php";
include "../layout/header.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$lote = obtenerLotePorId($conexion, $id);

$productos = $conexion->query("SELECT id, nombre FROM productos");
?>

<div class="max-w-3xl mx-auto px-4 py-10">

  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">

    <!-- Header -->
    <div class="px-8 py-6 border-b border-chebs-line">
      <h1 class="text-2xl font-black text-chebs-black">Corregir producto del lote</h1>
      <p class="text-sm text-gray-600 mt-1">
        Cambia el producto asignado a este lote (solo si fue un error).
      </p>
    </div>

    <?php if (!$lote) { ?>
      <div class="px-8 py-8">
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-700 font-semibold">
          ❌ Lote no encontrado.
        </div>

        <a href="listar.php"
           class="mt-5 inline-flex items-center justify-center px-6 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
          ← Volver a lotes
        </a>
      </div>

    <?php } else { ?>

      <!-- Info del lote -->
      <div class="px-8 py-6 border-b border-chebs-line">
        <div class="grid sm:grid-cols-3 gap-4">
          <div class="rounded-2xl border border-chebs-line bg-chebs-soft/60 p-4">
            <div class="text-xs text-gray-500">Lote ID</div>
            <div class="text-lg font-black text-chebs-black">#<?= (int)$lote['id'] ?></div>
          </div>

          <div class="rounded-2xl border border-chebs-line bg-chebs-soft/60 p-4">
            <div class="text-xs text-gray-500">Producto actual</div>
            <div class="text-lg font-black text-chebs-black"><?= (int)$lote['producto_id'] ?></div>
          </div>

          <div class="rounded-2xl border border-chebs-line bg-chebs-soft/60 p-4">
            <div class="text-xs text-gray-500">Cantidad</div>
            <div class="text-lg font-black text-chebs-black"><?= (int)$lote['cantidad_unidades'] ?></div>
          </div>
        </div>
      </div>

      <!-- Form -->
      <form action="../../controladores/corregir_producto_lote.php"
            method="POST"
            class="px-8 py-8 space-y-6"
            onsubmit="return validarCambio();">

        <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">

        <!-- Nuevo producto (autocomplete) -->
        <div class="relative">
          <label class="block text-sm font-bold mb-2 text-chebs-black">Nuevo producto</label>

          <input id="producto_buscar"
                 type="text"
                 placeholder="Escribe para buscar un producto..."
                 autocomplete="off"
                 class="w-full px-4 py-3 rounded-2xl border border-chebs-line
                        focus:outline-none focus:ring-2 focus:ring-chebs-green/40 bg-white">

          <!-- input real que se envía -->
          <input type="hidden" name="nuevo_producto_id" id="nuevo_producto_id" required>

          <!-- Fuente de datos (oculta) -->
          <div class="hidden">
            <datalist id="lista_productos_corregir">
              <?php while($p = $productos->fetch_assoc()) { ?>
                <option value="<?= htmlspecialchars($p['nombre']) ?>" data-id="<?= (int)$p['id'] ?>"></option>
              <?php } ?>
            </datalist>
          </div>

          <!-- Dropdown gris -->
          <div id="auto_box"
               class="hidden absolute left-0 right-0 mt-2 z-50 rounded-2xl border border-chebs-line bg-gray-100 shadow-soft overflow-hidden">
            <div class="px-4 py-2 text-xs text-gray-500 bg-gray-200/60 border-b border-chebs-line flex items-center justify-between">
              <span>Resultados</span>
              <span class="hidden sm:inline">↑ ↓ · Enter</span>
            </div>

            <div id="auto_list" class="max-h-64 overflow-auto"></div>

            <div id="auto_empty" class="hidden px-4 py-3 text-sm text-gray-500">
              Sin resultados
            </div>
          </div>

          <div id="prod_error" class="hidden mt-2 text-sm font-semibold text-red-600"></div>

          <p class="mt-2 text-xs text-gray-500">
            Selecciona un producto de la lista para evitar errores.
          </p>
        </div>

        <!-- Botones -->
        <div class="flex flex-col sm:flex-row gap-4 pt-2">
          <button type="button"
                  class="flex-1 inline-flex items-center justify-center px-6 py-3 rounded-2xl
                         border border-chebs-line bg-white font-black hover:bg-chebs-soft transition"
                  onclick="abrirConfirmacion()">
            🔁 Corregir producto
          </button>

          <a href="listar.php"
             class="flex-1 inline-flex items-center justify-center px-6 py-3 rounded-2xl
                    border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
            ← Volver a lotes
          </a>
        </div>

        <!-- submit real oculto -->
        <button id="submit_real" type="submit" class="hidden"></button>
      </form>

    <?php } ?>

  </div>
</div>

<!-- ✅ Modal confirm Chebs -->
<div id="modalConfirmacion" class="hidden fixed inset-0 z-[9999]">
  <div class="absolute inset-0 bg-black/40" onclick="cerrarConfirm()"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl bg-white shadow-soft border border-chebs-line overflow-hidden">
      <div class="px-6 py-5 border-b border-chebs-line">
        <h3 class="text-lg font-black text-chebs-black">Confirmar corrección</h3>
        <p class="text-sm text-gray-600">
          ¿Seguro que deseas cambiar el producto de este lote?
        </p>
      </div>

      <div class="px-6 py-5 border-t border-chebs-line flex justify-end gap-2">
        <button type="button"
                class="px-5 py-3 rounded-2xl border border-chebs-line font-semibold hover:bg-gray-50"
                onclick="cerrarConfirm()">
          Cancelar
        </button>

        <button type="button"
                class="px-5 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition"
                onclick="document.getElementById('submit_real').click()">
          Sí, corregir
        </button>
      </div>
    </div>
  </div>
</div>

<style>
  #auto_list::-webkit-scrollbar { width: 10px; }
  #auto_list::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
  #auto_list::-webkit-scrollbar-track { background: transparent; }
</style>

<script>
/* =========================
   ✅ AUTOCOMPLETE
   ========================= */
const inputP = document.getElementById('producto_buscar');
const hiddenP = document.getElementById('nuevo_producto_id');

const autoBox  = document.getElementById('auto_box');
const autoList = document.getElementById('auto_list');
const autoEmpty= document.getElementById('auto_empty');

const dataOptions = Array.from(document.querySelectorAll('#lista_productos_corregir option'))
  .map(o => ({ label: o.value, id: o.dataset.id }));

let autoIndex = -1;
let autoItems = [];

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
      (idx === autoIndex ? "bg-gray-200" : "hover:bg-gray-200");

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

/* =========================
   ✅ VALIDACIÓN + MODAL
   ========================= */
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

function validarCambio(){
  if(!hiddenP.value){
    setError('Selecciona un producto válido de la lista.');
    inputP.focus();
    return false;
  }
  setError('');
  return true;
}

function abrirConfirmacion(){
  if(!validarCambio()) return;
  document.getElementById('modalConfirmacion').classList.remove('hidden');
}

function cerrarConfirm(){
  document.getElementById('modalConfirmacion').classList.add('hidden');
}

// ESC
document.addEventListener('keydown', (e) => {
  if(e.key === 'Escape'){
    cerrarConfirm();
    cerrarAuto();
  }
});
</script>

<?php include "../layout/footer.php"; ?>
