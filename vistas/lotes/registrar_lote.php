<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

require_once "../../config/conexion.php";
require_once "../../modelos/producto_modelo.php";
require_once "../../modelos/lote_modelo.php";
include "../layout/header.php";

$producto_preseleccionado = null;

if (isset($_GET['producto_id'])) {
    $id_pre = (int)$_GET['producto_id'];
    if ($id_pre > 0) {
        $producto_preseleccionado = obtenerProductoPorId($conexion, $id_pre);
    }
}


$productos = obtenerProductos($conexion);
?>

<div class="max-w-3xl mx-auto px-4 py-10">

  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">

    <!-- Header -->
    <div class="px-8 py-6 border-b border-chebs-line">
      <h1 class="text-2xl font-black text-chebs-black">Ingreso de mercadería</h1>
      <p class="text-sm text-gray-600 mt-1">Registra un lote nuevo (fecha de vencimiento y cantidad).</p>
    </div>

    <form action="../../controladores/lote_controlador.php" method="POST" class="px-8 py-8 space-y-6" onsubmit="return validarLote();">

      <!-- Producto (Autocomplete Chebs) -->
      <div class="relative">
        <label class="block text-sm font-bold mb-2 text-chebs-black">Producto</label>

        <input id="producto_buscar"
               type="text"
               placeholder="Escribe para buscar un producto..."
               autocomplete="off"
               class="w-full px-4 py-3 rounded-2xl border border-chebs-line
                      focus:outline-none focus:ring-2 focus:ring-chebs-green/40 bg-white">
                      <?php if($producto_preseleccionado): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('producto_buscar').value = <?= json_encode($producto_preseleccionado['nombre']) ?>;
    document.getElementById('producto_id_real').value = <?= (int)$producto_preseleccionado['id'] ?>;
});
</script>
<?php endif; ?>


        <!-- input real que se envía -->
        <input type="hidden" name="producto_id" id="producto_id_real" required>

        <!-- Fuente de datos (oculta) -->
        <div class="hidden">
          <datalist id="lista_productos_lote">
            <?php while($p = $productos->fetch_assoc()) { ?>
              <option value="<?= htmlspecialchars($p['nombre']) ?>" data-id="<?= (int)$p['id'] ?>"></option>
            <?php } ?>
          </datalist>
        </div>

        <!-- Dropdown gris -->
        <div id="auto_box_lote"
             class="hidden absolute left-0 right-0 mt-2 z-50 rounded-2xl border border-chebs-line bg-gray-100 shadow-soft overflow-hidden">
          <div class="px-4 py-2 text-xs text-gray-500 bg-gray-200/60 border-b border-chebs-line flex items-center justify-between">
            <span>Resultados</span>
            <span class="hidden sm:inline">↑ ↓ · Enter</span>
          </div>

          <div id="auto_list_lote" class="max-h-64 overflow-auto"></div>

          <div id="auto_empty_lote" class="hidden px-4 py-3 text-sm text-gray-500">
            Sin resultados
          </div>
        </div>

        <div id="producto_hint" class="mt-2 text-xs text-gray-500">
          Selecciona un producto de la lista para registrar el lote.
        </div>

        <div id="producto_error" class="hidden mt-2 text-sm font-semibold text-red-600"></div>
      </div>

      <!-- Fecha vencimiento -->
      <div>
        <label class="block text-sm font-bold mb-2 text-chebs-black">Fecha de vencimiento</label>
        <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" required
               class="w-full px-4 py-3 rounded-2xl border border-chebs-line
                      focus:outline-none focus:ring-2 focus:ring-chebs-green/40">
        <div id="fecha_error" class="hidden mt-2 text-sm font-semibold text-red-600"></div>
      </div>

      <!-- Cantidad -->
      <div>
        <label class="block text-sm font-bold mb-2 text-chebs-black">Cantidad (unidades)</label>
        <input type="number" name="cantidad" id="cantidad" required min="1"
               class="w-full px-4 py-3 rounded-2xl border border-chebs-line
                      focus:outline-none focus:ring-2 focus:ring-chebs-green/40"
               placeholder="Ej: 24">
        <div id="cantidad_error" class="hidden mt-2 text-sm font-semibold text-red-600"></div>
      </div>

      <!-- Botones -->
      <div class="flex flex-col sm:flex-row gap-4 pt-2">
        <button type="submit"
                class="flex-1 inline-flex items-center justify-center px-6 py-3 rounded-2xl
                       bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft">
          📦 Registrar lote
        </button>

        <a href="listar.php"
           class="flex-1 inline-flex items-center justify-center px-6 py-3 rounded-2xl
                  border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
          ← Volver a lotes
        </a>
      </div>

    </form>

  </div>
</div>

<style>
  #auto_list_lote::-webkit-scrollbar { width: 10px; }
  #auto_list_lote::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
  #auto_list_lote::-webkit-scrollbar-track { background: transparent; }
</style>

<script>
/* =========================
   ✅ AUTOCOMPLETE PRODUCTO
   ========================= */

const inputP = document.getElementById('producto_buscar');
const hiddenP = document.getElementById('producto_id_real');

const autoBox = document.getElementById('auto_box_lote');
const autoList = document.getElementById('auto_list_lote');
const autoEmpty = document.getElementById('auto_empty_lote');

const dataOptions = Array.from(document.querySelectorAll('#lista_productos_lote option'))
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

  // limpiar error si había
  setError('producto_error', '');
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
   ✅ VALIDACIONES
   ========================= */
function setError(id, msg){
  const el = document.getElementById(id);
  if(!el) return;
  if(!msg){
    el.classList.add('hidden');
    el.textContent = '';
    return;
  }
  el.classList.remove('hidden');
  el.textContent = msg;
}

function validarLote(){
  let ok = true;

  // producto
  if(!hiddenP.value){
    setError('producto_error', 'Selecciona un producto de la lista.');
    inputP.focus();
    ok = false;
  } else {
    setError('producto_error', '');
  }

  // fecha
  const f = document.getElementById('fecha_vencimiento').value;
  if(!f){
    setError('fecha_error', 'Selecciona una fecha de vencimiento.');
    ok = false;
  } else {
    setError('fecha_error', '');
  }

  // cantidad
  const c = parseInt(document.getElementById('cantidad').value || '0', 10);
  if(!c || c <= 0){
    setError('cantidad_error', 'La cantidad debe ser mayor a 0.');
    ok = false;
  } else {
    setError('cantidad_error', '');
  }

  return ok;
}

// ESC cierra dropdown
document.addEventListener('keydown', (e) => {
  if(e.key === 'Escape') cerrarAuto();
});
</script>

<?php include "../layout/footer.php"; ?>
