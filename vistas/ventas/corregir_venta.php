<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../../config/conexion.php";
require_once __DIR__ . "/../../modelos/producto_modelo.php";
require_once __DIR__ . "/../../modelos/venta_modelo.php";
require_once __DIR__ . "/../../modelos/venta_corregir_modelo.php"; // ✅ AQUÍ está obtenerDetalleVentaPorVenta()

include __DIR__ . "/../layout/header.php";

$venta_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if ($venta_id <= 0) {
  echo "<div class='p-6'>ID inválido</div>";
  include __DIR__ . "/../layout/footer.php";
  exit;
}

$venta = obtenerVentaPorId($conexion, $venta_id);
if (!$venta) {
  echo "<div class='p-6'>Venta no existe</div>";
  include __DIR__ . "/../layout/footer.php";
  exit;
}

$anulada = isset($venta["anulada"]) ? ((int)$venta["anulada"] === 1) : false;

// ✅ ESTA FUNCIÓN YA EXISTE EN venta_corregir_modelo.php
$detalles = obtenerDetalleVentaPorVenta($conexion, $venta_id);

// productos activos para datalist
$productos = obtenerProductos($conexion, true);
$productosArr = [];
if ($productos) {
  while($p = $productos->fetch_assoc()){
    $productosArr[] = [
      "id" => (int)$p["id"],
      "nombre" => (string)$p["nombre"]
    ];
  }
}
?>

<div class="max-w-[1200px] mx-auto px-6 py-6">
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">
    <div class="px-6 py-4 bg-chebs-soft/50 border-b border-chebs-line flex items-center justify-between">
      <div>
        <h1 class="text-xl font-black">Corregir Venta #<?= (int)$venta_id ?></h1>
        <p class="text-sm text-gray-500">Edita cantidades, elimina items o cambia productos.</p>
      </div>

      <a href="/PULPERIA-CHEBS/vistas/ventas/historial.php"
         class="text-sm font-bold underline text-gray-600 hover:text-gray-900">
        Volver
      </a>
    </div>

    <div class="p-6">

      <?php if (isset($_GET["ok"])): ?>
        <div class="mb-4 rounded-2xl bg-green-50 border border-green-200 text-green-700 font-bold p-4">
          ✅ Cambios guardados y stock ajustado.
        </div>
      <?php endif; ?>

      <?php if (isset($_GET["ok_anular"])): ?>
        <div class="mb-4 rounded-2xl bg-green-50 border border-green-200 text-green-700 font-bold p-4">
          ✅ Venta anulada y stock devuelto.
        </div>
      <?php endif; ?>

      <?php if (isset($_GET["err"])): ?>
        <div class="mb-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 font-bold p-4">
          ❌ <?= htmlspecialchars($_GET["err"]) ?>
        </div>
      <?php endif; ?>

      <?php if ($anulada): ?>
        <div class="mb-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 font-bold p-4">
          ⚠️ Esta venta está ANULADA. No se permiten más cambios.
        </div>
      <?php endif; ?>

      <form method="POST" action="/PULPERIA-CHEBS/controladores/venta_corregir_guardar.php" class="space-y-4">
        <input type="hidden" name="venta_id" value="<?= (int)$venta_id ?>">

        <div class="overflow-auto rounded-2xl border border-chebs-line">
          <table class="min-w-[900px] w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left p-4 font-black">PRODUCTO ORIGINAL</th>
                <th class="text-left p-4 font-black">CAMBIAR POR (OPCIONAL)</th>
                <th class="text-left p-4 font-black">CANTIDAD</th>
                <th class="text-left p-4 font-black">ELIMINAR</th>
              </tr>
            </thead>
            <tbody class="divide-y">

            <?php $i=0; while($d = $detalles->fetch_assoc()): ?>
              <tr class="bg-white">
                <td class="p-4">
                  <div class="font-black"><?= htmlspecialchars($d["nombre"]) ?></div>
                  <div class="text-xs text-gray-500">
                    Precio: Bs <?= number_format((float)$d["precio_unitario"],2) ?>
                    <span class="ml-2 inline-flex px-2 py-1 rounded-lg bg-gray-100 text-gray-700 font-bold">
                      <?= htmlspecialchars($d["tipo_venta"]) ?>
                    </span>
                  </div>

                  <input type="hidden" name="detalle_id[]" value="<?= (int)$d["id"] ?>">
                </td>

                <td class="p-4">
                  <input
                    <?= $anulada ? "disabled" : "" ?>
                    type="text"
                    list="lista_prod_<?= $i ?>"
                    placeholder="Buscar otro producto..."
                    class="w-full rounded-xl border border-chebs-line px-3 py-2"
                    oninput="window.__setNuevoProdId(this, <?= $i ?>)"
                  >
                  <input type="hidden" name="nuevo_producto_id[]" id="nuevo_producto_id_<?= $i ?>" value="">

                  <datalist id="lista_prod_<?= $i ?>">
                    <?php foreach($productosArr as $p): ?>
                      <option value="<?= htmlspecialchars($p["nombre"]) ?>" data-id="<?= (int)$p["id"] ?>"></option>
                    <?php endforeach; ?>
                  </datalist>

                  <div class="text-xs text-gray-400 mt-1">Déjalo vacío para mantener el original</div>
                </td>

                <td class="p-4">
                  <input
                    <?= $anulada ? "disabled" : "" ?>
                    type="number"
                    min="0"
                    name="cantidad[]"
                    value="<?= (int)$d["cantidad"] ?>"
                    class="w-24 rounded-xl border border-chebs-line px-3 py-2 font-black text-center"
                  >
                </td>

                <td class="p-4">
                  <label class="inline-flex items-center gap-2">
                    <input <?= $anulada ? "disabled" : "" ?> type="checkbox" value="1" name="eliminar[<?= $i ?>]">
                    <span class="font-bold text-red-600">Borrar</span>
                  </label>
                </td>
              </tr>
            <?php $i++; endwhile; ?>

            </tbody>
          </table>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 justify-end">

          <!-- ✅ ANULAR VENTA (abre modal bonito) -->
          <button
            type="button"
            id="btn_abrir_modal_anular"
            <?= $anulada ? "disabled" : "" ?>
            class="px-5 py-3 rounded-2xl bg-red-600 text-white font-black hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed"
          >
            Anular venta
          </button>

          <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
             class="px-5 py-3 rounded-2xl border border-chebs-line font-black hover:bg-gray-50">
            Cancelar
          </a>

          <button
            type="submit"
            <?= $anulada ? "disabled" : "" ?>
            class="px-6 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark disabled:opacity-60 disabled:cursor-not-allowed"
          >
            Guardar Cambios
          </button>
        </div>
      </form>

      <!-- ✅ FORM ANULAR SEPARADO (sin forms anidados) -->
      <form id="form_anular" method="POST" action="/PULPERIA-CHEBS/controladores/venta_anular.php">
        <input type="hidden" name="venta_id" value="<?= (int)$venta_id ?>">
      </form>

    </div>
  </div>
</div>

<!-- ✅ MODAL ANULAR VENTA (Tailwind) -->
<div id="modal_anular" class="hidden fixed inset-0 z-[99999]">
  <!-- backdrop -->
  <div class="absolute inset-0 bg-black/50" data-close="1"></div>

  <!-- caja -->
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-3xl bg-white shadow-soft border border-chebs-line overflow-hidden">
      <div class="px-6 py-5 border-b border-chebs-line flex items-start justify-between gap-4">
        <div>
          <h3 class="text-lg font-black text-chebs-black">Anular venta</h3>
          <p class="text-sm text-gray-600 mt-1">
            ¿Seguro que deseas <b>ANULAR</b> esta venta? Se devolverá todo el stock a sus lotes.
          </p>
        </div>

        <button type="button"
                class="w-10 h-10 rounded-2xl border border-chebs-line font-black hover:bg-chebs-soft transition"
                data-close="1">
          ✕
        </button>
      </div>

      <div class="px-6 py-5">
        <div class="rounded-2xl bg-red-50 border border-red-200 text-red-700 p-4 text-sm">
          ⚠️ Esta acción no se puede deshacer.
        </div>
      </div>

      <div class="px-6 py-5 border-t border-chebs-line flex justify-end gap-3">
        <button type="button"
                class="px-5 py-3 rounded-2xl border border-chebs-line font-black hover:bg-chebs-soft transition"
                data-close="1">
          Cancelar
        </button>

        <button type="button"
                id="btn_confirmar_anular"
                class="px-6 py-3 rounded-2xl bg-red-600 text-white font-black hover:bg-red-700 transition">
          Sí, anular
        </button>
      </div>
    </div>
  </div>
</div>

<script>
window.__setNuevoProdId = function(input, idx){
  const dl = document.getElementById("lista_prod_" + idx);
  const hid = document.getElementById("nuevo_producto_id_" + idx);
  if(!dl || !hid) return;

  const val = (input.value || "").trim();
  if(!val){ hid.value = ""; return; }

  const opts = dl.querySelectorAll("option");
  for(const o of opts){
    if((o.value || "").trim() === val){
      hid.value = o.getAttribute("data-id") || "";
      return;
    }
  }
  hid.value = "";
};

(() => {
  const modal = document.getElementById("modal_anular");
  const btnAbrir = document.getElementById("btn_abrir_modal_anular");
  const btnOk = document.getElementById("btn_confirmar_anular");
  const form = document.getElementById("form_anular");

  if (!modal || !btnAbrir || !btnOk || !form) return;

  function abrir(){
    modal.classList.remove("hidden");
    document.body.classList.add("overflow-hidden");
  }

  function cerrar(){
    modal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  }

  btnAbrir.addEventListener("click", abrir);

  modal.addEventListener("click", (e) => {
    if (e.target && e.target.getAttribute("data-close") === "1") cerrar();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !modal.classList.contains("hidden")) cerrar();
  });

  btnOk.addEventListener("click", () => {
    btnOk.disabled = true;
    btnOk.classList.add("opacity-70", "cursor-not-allowed");
    form.submit();
  });
})();
</script>

<?php include __DIR__ . "/../layout/footer.php"; ?>