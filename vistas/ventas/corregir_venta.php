<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin','empleado']);

require_once __DIR__ . "/../../config/conexion.php";
require_once __DIR__ . "/../../modelos/producto_modelo.php";
require_once __DIR__ . "/../../modelos/venta_modelo.php";
require_once __DIR__ . "/../../modelos/venta_corregir_modelo.php";

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

$anulada = (int)($venta["anulada"] ?? 0) === 1;

$userId = (int)($_SESSION['user']['id'] ?? 0);
$rol    = (string)($_SESSION['user']['rol'] ?? '');

// Permiso solo aplica cuando no está anulada
$permiso = $anulada
    ? ['ok' => false, 'msg' => 'La venta ya está anulada.']
    : validarPermisoEdicionVenta($conexion, $venta, $userId, $rol);

// Convertir detalles a array reutilizable
$detallesArr = [];
$detallesRes = obtenerDetalleVentaPorVenta($conexion, $venta_id);
while ($d = $detallesRes->fetch_assoc()) {
    $detallesArr[] = $d;
}

// Productos activos para datalist (solo si se va a editar)
$productosArr = [];
if (!$anulada && $permiso['ok']) {
    $productos = obtenerProductos($conexion, true);
    if ($productos) {
        while ($p = $productos->fetch_assoc()) {
            $productosArr[] = ["id" => (int)$p["id"], "nombre" => (string)$p["nombre"]];
        }
    }
}
?>

<div class="max-w-[1200px] mx-auto px-6 py-6">
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">

    <!-- Cabecera -->
    <div class="px-6 py-4 bg-chebs-soft/50 border-b border-chebs-line flex items-center justify-between">
      <div>
        <h1 class="text-xl font-black">Venta #<?= (int)$venta_id ?></h1>
        <p class="text-sm text-gray-500">
          <?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?>
          &nbsp;·&nbsp;
          Bs <?= number_format((float)$venta['total'], 2) ?>
        </p>
      </div>
      <a href="/PULPERIA-CHEBS/vistas/ventas/historial.php"
         class="text-sm font-bold underline text-gray-600 hover:text-gray-900">
        Ver historial
      </a>
    </div>

    <div class="p-6">

      <!-- ============================================================
           ESTADO 1: VENTA ANULADA — solo información, sin formulario
           ============================================================ -->
      <?php if ($anulada): ?>

        <?php if (isset($_GET["ok_anular"])): ?>
          <!-- Vino de anular ahora mismo → countdown redirect a caja -->
          <div class="mb-4 rounded-2xl bg-green-50 border border-green-200 text-green-800 p-5">
            <div class="flex items-start gap-3">
              <span class="text-2xl leading-none">✅</span>
              <div>
                <p class="font-black text-lg">Venta anulada y stock devuelto.</p>
                <p class="text-sm mt-1 text-green-700">
                  Todos los productos fueron devueltos a sus lotes correspondientes.
                </p>
              </div>
            </div>
          </div>

          <div class="rounded-2xl bg-gray-50 border border-chebs-line p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-600">
              Regresando a caja en <span id="countdown" class="font-black text-chebs-black">2</span>s...
            </p>
            <div class="flex gap-3">
              <a href="/PULPERIA-CHEBS/vistas/ventas/historial.php"
                 class="px-4 py-2 rounded-xl border border-chebs-line font-bold text-sm hover:bg-gray-100">
                Ver historial
              </a>
              <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
                 class="px-5 py-2 rounded-xl bg-chebs-green text-white font-black text-sm hover:bg-chebs-greenDark">
                Ir a caja
              </a>
            </div>
          </div>

          <script>
          (function() {
            let n = 2;
            const el = document.getElementById('countdown');
            const iv = setInterval(function() {
              n--;
              if (el) el.textContent = n;
              if (n <= 0) {
                clearInterval(iv);
                window.location.href = '/PULPERIA-CHEBS/vistas/ventas/venta.php';
              }
            }, 1000);
          })();
          </script>

        <?php else: ?>
          <!-- Llegó a esta venta ya anulada desde otra navegación -->
          <div class="mb-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 p-5">
            <div class="flex items-start gap-3">
              <span class="text-2xl leading-none">⛔</span>
              <div>
                <p class="font-black text-lg">Esta venta está anulada.</p>
                <p class="text-sm mt-1 text-red-700">
                  El stock fue devuelto a sus lotes en el momento de la anulación.
                  No se permiten más cambios.
                </p>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap gap-3 mt-2">
            <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
               class="px-5 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark">
              Ir a caja
            </a>
            <a href="/PULPERIA-CHEBS/vistas/ventas/historial.php"
               class="px-5 py-3 rounded-2xl border border-chebs-line font-black hover:bg-gray-50">
              Ver historial
            </a>
          </div>
        <?php endif; ?>

      <!-- ============================================================
           ESTADO 2: SIN PERMISO — vista de solo lectura con detalle
           ============================================================ -->
      <?php elseif (!$permiso['ok']): ?>

        <?php if (isset($_GET["err"])): ?>
          <div class="mb-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 font-bold p-4">
            ❌ <?= htmlspecialchars($_GET["err"]) ?>
          </div>
        <?php endif; ?>

        <div class="mb-5 rounded-2xl bg-amber-50 border border-amber-200 p-5">
          <div class="flex items-start gap-3">
            <span class="text-2xl leading-none">🔒</span>
            <div>
              <p class="font-black text-amber-900">No puedes modificar esta venta.</p>
              <p class="text-sm mt-1 text-amber-800"><?= htmlspecialchars($permiso['msg']) ?></p>
            </div>
          </div>
        </div>

        <!-- Detalle de la venta en modo lectura -->
        <?php if (!empty($detallesArr)): ?>
        <div class="overflow-auto rounded-2xl border border-chebs-line mb-5">
          <table class="min-w-[600px] w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left p-4 font-black">PRODUCTO</th>
                <th class="text-left p-4 font-black">TIPO</th>
                <th class="text-center p-4 font-black">CANT.</th>
                <th class="text-right p-4 font-black">PRECIO</th>
                <th class="text-right p-4 font-black">SUBTOTAL</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <?php foreach ($detallesArr as $d): ?>
              <tr class="bg-white">
                <td class="p-4 font-bold"><?= htmlspecialchars($d["nombre"]) ?></td>
                <td class="p-4 text-gray-500">
                  <span class="inline-flex px-2 py-1 rounded-lg bg-gray-100 text-gray-700 font-bold text-xs">
                    <?= htmlspecialchars($d["tipo_venta"]) ?>
                  </span>
                </td>
                <td class="p-4 text-center"><?= (int)$d["cantidad"] ?></td>
                <td class="p-4 text-right">Bs <?= number_format((float)$d["precio_unitario"], 2) ?></td>
                <td class="p-4 text-right font-bold">Bs <?= number_format((float)$d["subtotal"], 2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <div class="flex flex-wrap gap-3">
          <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
             class="px-5 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark">
            Ir a caja
          </a>
          <a href="/PULPERIA-CHEBS/vistas/ventas/historial.php"
             class="px-5 py-3 rounded-2xl border border-chebs-line font-black hover:bg-gray-50">
            Ver historial
          </a>
        </div>

      <!-- ============================================================
           ESTADO 3: CON PERMISO — formulario editable completo
           ============================================================ -->
      <?php else: ?>

        <?php if (isset($_GET["ok"])): ?>
          <div class="mb-4 rounded-2xl bg-green-50 border border-green-200 text-green-700 font-bold p-4">
            ✅ Cambios guardados y stock ajustado.
          </div>
        <?php endif; ?>

        <?php if (isset($_GET["err"])): ?>
          <div class="mb-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 font-bold p-4">
            ❌ <?= htmlspecialchars($_GET["err"]) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="/PULPERIA-CHEBS/controladores/venta_corregir_guardar.php" class="space-y-4">
          <input type="hidden" name="venta_id" value="<?= (int)$venta_id ?>">
          <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

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

              <?php $i = 0; foreach ($detallesArr as $d): ?>
                <tr class="bg-white">
                  <td class="p-4">
                    <div class="font-black"><?= htmlspecialchars($d["nombre"]) ?></div>
                    <div class="text-xs text-gray-500">
                      Precio: Bs <?= number_format((float)$d["precio_unitario"], 2) ?>
                      <span class="ml-2 inline-flex px-2 py-1 rounded-lg bg-gray-100 text-gray-700 font-bold">
                        <?= htmlspecialchars($d["tipo_venta"]) ?>
                      </span>
                    </div>
                    <input type="hidden" name="detalle_id[]" value="<?= (int)$d["id"] ?>">
                  </td>

                  <td class="p-4">
                    <input
                      type="text"
                      list="lista_prod_<?= $i ?>"
                      placeholder="Buscar otro producto..."
                      class="w-full rounded-xl border border-chebs-line px-3 py-2"
                      oninput="window.__setNuevoProdId(this, <?= $i ?>)"
                    >
                    <input type="hidden" name="nuevo_producto_id[]" id="nuevo_producto_id_<?= $i ?>" value="">

                    <datalist id="lista_prod_<?= $i ?>">
                      <?php foreach ($productosArr as $p): ?>
                        <option value="<?= htmlspecialchars($p["nombre"]) ?>" data-id="<?= (int)$p["id"] ?>"></option>
                      <?php endforeach; ?>
                    </datalist>

                    <div class="text-xs text-gray-400 mt-1">Déjalo vacío para mantener el original</div>
                  </td>

                  <td class="p-4">
                    <div class="inline-flex items-center rounded-xl border border-chebs-line overflow-hidden">
                      <button type="button" onclick="qtyStep(this,-1)"
                              class="w-9 h-9 flex items-center justify-center bg-gray-50
                                     hover:bg-gray-100 active:bg-gray-200 text-gray-600
                                     font-black text-lg transition-colors select-none border-r border-chebs-line">−</button>
                      <input
                        type="number"
                        min="0"
                        name="cantidad[]"
                        value="<?= (int)$d["cantidad"] ?>"
                        class="qty-stepper-input w-14 h-9 bg-white text-center font-black text-sm outline-none"
                      >
                      <button type="button" onclick="qtyStep(this,1)"
                              class="w-9 h-9 flex items-center justify-center bg-gray-50
                                     hover:bg-gray-100 active:bg-gray-200 text-gray-600
                                     font-black text-lg transition-colors select-none border-l border-chebs-line">+</button>
                    </div>
                  </td>

                  <td class="p-4">
                    <label class="inline-flex items-center gap-2">
                      <input type="checkbox" value="1" name="eliminar[<?= $i ?>]">
                      <span class="font-bold text-red-600">Borrar</span>
                    </label>
                  </td>
                </tr>
              <?php $i++; endforeach; ?>

              </tbody>
            </table>
          </div>

          <div class="flex flex-col sm:flex-row gap-3 justify-end">

            <button
              type="button"
              id="btn_abrir_modal_anular"
              class="px-5 py-3 rounded-2xl bg-red-600 text-white font-black hover:bg-red-700"
            >
              Anular venta
            </button>

            <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
               class="px-5 py-3 rounded-2xl border border-chebs-line font-black hover:bg-gray-50 text-center">
              Cancelar
            </a>

            <button
              type="submit"
              class="px-6 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark"
            >
              Guardar Cambios
            </button>
          </div>
        </form>

        <!-- Form de anulación separado (evita forms anidados) -->
        <form id="form_anular" method="POST" action="/PULPERIA-CHEBS/controladores/venta_anular.php">
          <input type="hidden" name="venta_id" value="<?= (int)$venta_id ?>">
          <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
        </form>

      <?php endif; ?>

    </div>
  </div>
</div>

<!-- Modal de confirmación de anulación (solo se carga cuando hay permiso) -->
<?php if (!$anulada && $permiso['ok']): ?>
<div id="modal_anular" class="hidden fixed inset-0 z-[99999]">
  <div class="absolute inset-0 bg-black/50" data-close="1"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-3xl bg-white shadow-soft border border-chebs-line overflow-hidden">

      <div class="px-6 py-5 border-b border-chebs-line flex items-start justify-between gap-4">
        <div>
          <h3 class="text-lg font-black text-chebs-black">Anular venta #<?= (int)$venta_id ?></h3>
          <p class="text-sm text-gray-600 mt-1">
            ¿Seguro? Se devolverá todo el stock a sus lotes.
          </p>
        </div>
        <button type="button"
                class="w-10 h-10 rounded-2xl border border-chebs-line font-black hover:bg-chebs-soft transition"
                data-close="1">✕</button>
      </div>

      <div class="px-6 py-5">
        <div class="rounded-2xl bg-red-50 border border-red-200 text-red-700 p-4 text-sm">
          ⚠️ Esta acción no se puede deshacer.
        </div>
      </div>

      <div class="px-6 py-5 border-t border-chebs-line flex justify-end gap-3">
        <button type="button"
                class="px-5 py-3 rounded-2xl border border-chebs-line font-black hover:bg-chebs-soft transition"
                data-close="1">Cancelar</button>

        <button type="button"
                id="btn_confirmar_anular"
                class="px-6 py-3 rounded-2xl bg-red-600 text-white font-black hover:bg-red-700 transition">
          Sí, anular
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<style>
  .qty-stepper-input::-webkit-outer-spin-button,
  .qty-stepper-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
  .qty-stepper-input { -moz-appearance: textfield; }
</style>

<script>
function qtyStep(btn, delta) {
  const input = btn.parentNode.querySelector('input[type="number"]');
  if (!input) return;
  const min = parseInt(input.min ?? 0, 10);
  const next = parseInt(input.value || 0, 10) + delta;
  input.value = next < min ? min : next;
}

window.__setNuevoProdId = function(input, idx) {
  const dl  = document.getElementById("lista_prod_" + idx);
  const hid = document.getElementById("nuevo_producto_id_" + idx);
  if (!dl || !hid) return;

  const val = (input.value || "").trim();
  if (!val) { hid.value = ""; return; }

  const opts = dl.querySelectorAll("option");
  for (const o of opts) {
    if ((o.value || "").trim() === val) {
      hid.value = o.getAttribute("data-id") || "";
      return;
    }
  }
  hid.value = "";
};

(function() {
  const modal   = document.getElementById("modal_anular");
  const btnAbrir = document.getElementById("btn_abrir_modal_anular");
  const btnOk   = document.getElementById("btn_confirmar_anular");
  const form    = document.getElementById("form_anular");

  if (!modal || !btnAbrir || !btnOk || !form) return;

  function abrir()  { modal.classList.remove("hidden"); document.body.classList.add("overflow-hidden"); }
  function cerrar() { modal.classList.add("hidden");    document.body.classList.remove("overflow-hidden"); }

  btnAbrir.addEventListener("click", abrir);

  modal.addEventListener("click", function(e) {
    if (e.target && e.target.getAttribute("data-close") === "1") cerrar();
  });

  document.addEventListener("keydown", function(e) {
    if (e.key === "Escape" && !modal.classList.contains("hidden")) cerrar();
  });

  btnOk.addEventListener("click", function() {
    btnOk.disabled = true;
    btnOk.classList.add("opacity-70", "cursor-not-allowed");
    form.submit();
  });
})();
</script>

<?php include __DIR__ . "/../layout/footer.php"; ?>
