<?php
require_once __DIR__ . '/../../config/auth.php';
require_role(['admin']);

require_once '../../config/conexion.php';
require_once '../../modelos/producto_modelo.php';
require_once '../../modelos/lote_modelo.php';
include '../layout/header.php';

$producto_preseleccionado = null;

if (isset($_GET['producto_id'])) {
    $id_pre = (int)$_GET['producto_id'];
    if ($id_pre > 0) {
        $producto_preseleccionado = obtenerProductoPorId($conexion, $id_pre);
    }
}
// La busqueda ahora es AJAX. Ya no se carga el datalist con todos los productos.
?>

<div class="max-w-3xl mx-auto px-4 py-10">

  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">

    <!-- Header -->
    <div class="px-8 py-6 border-b border-chebs-line">
      <h1 class="text-2xl font-black text-chebs-black">Ingreso de mercaderia</h1>
      <p class="text-sm text-gray-600 mt-1">Registra un lote nuevo (fecha de vencimiento y cantidad).</p>
    </div>

    <form id="form_lote" action="../../controladores/lote_controlador.php" method="POST"
          class="px-8 py-8 space-y-6" onsubmit="return validarLote();">

      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">

      <!-- ============================================================
           BUSQUEDA DE PRODUCTO (escaneo + nombre)
           ============================================================ -->
      <div class="relative">
        <label class="block text-sm font-bold mb-2 text-chebs-black">Producto</label>

        <input id="input_producto"
               type="text"
               placeholder="Escanea o escribe el nombre del producto..."
               autocomplete="off"
               spellcheck="false"
               class="w-full px-4 py-3 rounded-2xl border border-chebs-line
                      focus:outline-none focus:ring-2 focus:ring-chebs-green/40 bg-white">

        <!-- ID real que se envia al controlador -->
        <input type="hidden" name="producto_id" id="producto_id_real">

        <!-- Dropdown de resultados AJAX -->
        <div id="auto_box_lote"
             class="hidden absolute left-0 right-0 mt-1 z-50 rounded-2xl border border-chebs-line
                    bg-white shadow-soft overflow-hidden">
          <div class="px-4 py-2 text-xs text-gray-500 bg-gray-50 border-b border-chebs-line">
            Resultados de busqueda
          </div>
          <div id="auto_list_lote" class="max-h-64 overflow-y-auto"></div>
        </div>

        <!-- Hint de estado -->
        <div id="producto_hint" class="mt-2 text-xs text-gray-500">
          Escanea con el lector o escribe para buscar.
        </div>

        <!-- Error de validacion del formulario -->
        <div id="producto_error" class="hidden mt-2 text-sm font-semibold text-red-600"></div>

        <!-- Banner: asignar barcode al vuelo -->
        <div id="banner_asignar_bc"
             class="hidden mt-3 px-4 py-3 rounded-2xl border border-orange-300 bg-orange-50">
          <p id="banner_bc_texto" class="text-sm font-semibold text-orange-800 mb-3"></p>
          <div class="flex gap-2">
            <button type="button" id="btn_asignar_si"
                    class="px-4 py-2 rounded-xl bg-chebs-green text-white font-black text-sm hover:bg-chebs-greenDark transition">
              Si, asignar
            </button>
            <button type="button" id="btn_asignar_no"
                    class="px-4 py-2 rounded-xl border border-chebs-line bg-white font-black text-sm hover:bg-chebs-soft transition">
              No por ahora
            </button>
          </div>
        </div>

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
          Registrar lote
        </button>
        <a href="listar.php"
           class="flex-1 inline-flex items-center justify-center px-6 py-3 rounded-2xl
                  border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
          Volver a lotes
        </a>
      </div>

    </form>
  </div>
</div>

<style>
  #auto_list_lote::-webkit-scrollbar { width: 8px; }
  #auto_list_lote::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 8px; }
  #auto_list_lote::-webkit-scrollbar-track { background: transparent; }
</style>

<script>
(function () {
    'use strict';

    /* ------------------------------------------------------------------
       CONFIGURACION
       ------------------------------------------------------------------ */
    var BASE = '/PULPERIA-CHEBS';
    var CSRF = <?= json_encode(get_csrf_token()) ?>;

    /* ------------------------------------------------------------------
       REFERENCIAS DOM
       ------------------------------------------------------------------ */
    var inputP    = document.getElementById('input_producto');
    var hiddenP   = document.getElementById('producto_id_real');
    var autoBox   = document.getElementById('auto_box_lote');
    var autoList  = document.getElementById('auto_list_lote');
    var hint      = document.getElementById('producto_hint');
    var errDiv    = document.getElementById('producto_error');
    var banner    = document.getElementById('banner_asignar_bc');
    var bannerTxt = document.getElementById('banner_bc_texto');
    var btnSi     = document.getElementById('btn_asignar_si');
    var btnNo     = document.getElementById('btn_asignar_no');

    /* ------------------------------------------------------------------
       ESTADO INTERNO
       ------------------------------------------------------------------ */
    var debounceTimer    = null;

    // barcodePendiente: codigo escaneado sin match, guardado para oferta de asignacion.
    // Se limpia SOLO cuando: (a) el campo queda vacio, (b) un escaneo nuevo tiene match,
    // (c) el usuario resuelve el banner (si/no).
    // NO se limpia cuando el usuario escribe el nombre para buscar manualmente.
    var barcodePendiente = '';

    var productoSelId  = 0;
    var productoSelNom = '';
    var productoSelBc  = null;

    /* ------------------------------------------------------------------
       HELPERS
       ------------------------------------------------------------------ */
    function mostrarHint(msg, color) {
        hint.textContent = msg || '';
        hint.className = 'mt-2 text-xs font-semibold ' + (color || 'text-gray-500');
    }

    function ocultarDropdown() {
        autoBox.classList.add('hidden');
        autoList.innerHTML = '';
    }

    function ocultarBanner() {
        banner.classList.add('hidden');
        bannerTxt.textContent = '';
        banner.dataset.barcode    = '';
        banner.dataset.productoId = '';
    }

    function mostrarError(msg) {
        errDiv.textContent = msg || '';
        errDiv.classList.toggle('hidden', !msg);
    }

    // Limpia el estado de "producto seleccionado".
    // NO toca barcodePendiente: ese estado es independiente y sobrevive
    // mientras el usuario pasa de escaneo sin match a busqueda por nombre.
    function limpiarSeleccion() {
        hiddenP.value  = '';
        productoSelId  = 0;
        productoSelNom = '';
        productoSelBc  = null;
        ocultarBanner();
        mostrarError('');
    }

    // Limpia absolutamente todo, incluyendo barcodePendiente.
    // Se usa solo cuando el campo queda completamente vacio.
    function limpiarTodo() {
        limpiarSeleccion();
        barcodePendiente = '';
        ocultarDropdown();
        mostrarHint('Escanea con el lector o escribe para buscar.', 'text-gray-500');
    }

    /* ------------------------------------------------------------------
       SELECCIONAR PRODUCTO
       ------------------------------------------------------------------ */
    function seleccionar(id, nombre, barcode) {
        hiddenP.value  = id;
        productoSelId  = id;
        productoSelNom = nombre;
        productoSelBc  = barcode;
        inputP.value   = nombre;

        ocultarDropdown();
        mostrarError('');
        mostrarHint('Seleccionado: ' + nombre, 'text-chebs-green');

        // Si hay un barcode pendiente de asignar y el producto no tiene codigo: mostrar banner
        if (barcodePendiente !== '' && !barcode) {
            bannerTxt.textContent = 'Asignar el codigo ' + barcodePendiente + ' a este producto?';
            banner.dataset.barcode    = barcodePendiente;
            banner.dataset.productoId = id;
            banner.classList.remove('hidden');
        } else {
            ocultarBanner();
        }

        // Pasar foco a fecha de vencimiento
        setTimeout(function () {
            var f = document.getElementById('fecha_vencimiento');
            if (f) f.focus();
        }, 80);
    }

    /* ------------------------------------------------------------------
       RENDERIZAR DROPDOWN
       ------------------------------------------------------------------ */
    function renderDropdown(resultados, termino) {
        autoList.innerHTML = '';

        if (resultados.length === 0) {
            var vacio = document.createElement('div');
            vacio.className = 'px-4 py-3 text-sm text-gray-500';
            vacio.textContent = 'Sin resultados para "' + termino + '"';
            autoList.appendChild(vacio);
            autoBox.classList.remove('hidden');
            return;
        }

        resultados.forEach(function (r) {
            var fila = document.createElement('div');
            fila.className = 'px-4 py-3 text-sm cursor-pointer border-b border-chebs-line last:border-b-0 hover:bg-gray-50 flex items-center justify-between gap-2';

            var spanNom = document.createElement('span');
            var safe = termino.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            spanNom.innerHTML = r.nombre.replace(new RegExp(safe, 'ig'), function (m) {
                return '<strong class="text-chebs-green">' + m + '</strong>';
            });

            var badge = document.createElement('span');
            badge.className = 'shrink-0 text-xs font-mono ' + (r.barcode ? 'text-gray-400' : 'text-orange-400');
            badge.textContent = r.barcode ? r.barcode : 'sin codigo';

            fila.appendChild(spanNom);
            fila.appendChild(badge);

            fila.addEventListener('mousedown', function (e) {
                e.preventDefault();
                seleccionar(r.id, r.nombre, r.barcode);
            });

            autoList.appendChild(fila);
        });

        autoBox.classList.remove('hidden');
    }

    /* ------------------------------------------------------------------
       BUSQUEDA AJAX
       ------------------------------------------------------------------ */
    function buscar(q) {
        mostrarHint('Buscando...', 'text-blue-400');

        fetch(BASE + '/controladores/producto_buscar_lote_ajax.php?q=' + encodeURIComponent(q))
            .then(function (resp) {
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                return resp.json();
            })
            .then(function (data) {

                if (data.modo === 'barcode') {
                    // Match exacto por barcode: limpiar pendiente y autoseleccionar
                    barcodePendiente = '';
                    var r = data.resultados[0];
                    seleccionar(r.id, r.nombre, r.barcode);
                    return;
                }

                if (data.modo === 'barcode_no_encontrado') {
                    // Guardar el barcode escaneado. Se mantendra durante la busqueda manual.
                    barcodePendiente = q;
                    inputP.value = '';
                    ocultarDropdown();
                    mostrarHint('Codigo no registrado. Busca el producto por nombre.', 'text-orange-500');
                    inputP.focus();
                    return;
                }

                if (data.modo === 'nombre') {
                    // Hint recordatorio si hay barcode pendiente
                    if (barcodePendiente !== '') {
                        mostrarHint('Codigo ' + barcodePendiente + ' pendiente. Selecciona el producto.', 'text-orange-500');
                    } else {
                        mostrarHint('', '');
                    }
                    renderDropdown(data.resultados, q);
                    return;
                }

                mostrarHint('Error al buscar. Intenta de nuevo.', 'text-red-500');
            })
            .catch(function () {
                mostrarHint('Sin conexion. Intenta de nuevo.', 'text-red-500');
            });
    }

    /* ------------------------------------------------------------------
       EVENTOS DEL INPUT
       ------------------------------------------------------------------ */
    inputP.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // evitar submit del formulario
            clearTimeout(debounceTimer);
            var q = inputP.value.trim();
            if (q.length >= 1) buscar(q);
            return;
        }
        if (e.key === 'Escape') {
            ocultarDropdown();
        }
    });

    inputP.addEventListener('input', function () {
        // Limpiar estado de seleccion pero NO barcodePendiente
        limpiarSeleccion();
        clearTimeout(debounceTimer);

        var q = inputP.value.trim();

        // Campo vacio: reinicio completo incluyendo barcodePendiente
        if (q.length < 1) {
            limpiarTodo();
            return;
        }

        // Menos de 2 caracteres: no buscar aun
        if (q.length < 2) {
            ocultarDropdown();
            // Mantener hint recordatorio si hay barcode pendiente
            if (barcodePendiente !== '') {
                mostrarHint('Codigo ' + barcodePendiente + ' pendiente. Escribe para buscar.', 'text-orange-500');
            } else {
                mostrarHint('', 'text-gray-500');
            }
            return;
        }

        // Debounce 300ms para escritura manual
        debounceTimer = setTimeout(function () { buscar(q); }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!autoBox.contains(e.target) && e.target !== inputP) {
            ocultarDropdown();
        }
    });

    /* ------------------------------------------------------------------
       BOTON "SI, ASIGNAR" BARCODE
       ------------------------------------------------------------------ */
    btnSi.addEventListener('click', function () {
        var bc  = banner.dataset.barcode    || '';
        var pid = banner.dataset.productoId || '';

        if (!bc || !pid) { ocultarBanner(); return; }

        btnSi.disabled = true;
        btnSi.textContent = 'Guardando...';

        var fd = new FormData();
        fd.append('csrf_token',  CSRF);
        fd.append('producto_id', pid);
        fd.append('barcode',     bc);

        fetch(BASE + '/controladores/asignar_barcode_ajax.php', { method: 'POST', body: fd })
            .then(function (resp) { return resp.json(); })
            .then(function (data) {
                if (data.ok) {
                    mostrarHint('Codigo ' + bc + ' asignado a ' + productoSelNom, 'text-chebs-green');
                } else {
                    mostrarHint('No se pudo asignar: ' + data.msg, 'text-red-500');
                }
                barcodePendiente = ''; // resuelto
                ocultarBanner();
            })
            .catch(function () {
                mostrarHint('Error al asignar codigo.', 'text-red-500');
                ocultarBanner();
            })
            .finally(function () {
                btnSi.disabled = false;
                btnSi.textContent = 'Si, asignar';
            });
    });

    /* ------------------------------------------------------------------
       BOTON "NO" BARCODE
       ------------------------------------------------------------------ */
    btnNo.addEventListener('click', function () {
        barcodePendiente = ''; // descartado explicitamente
        ocultarBanner();
        mostrarHint('Seleccionado: ' + productoSelNom, 'text-chebs-green');
    });

    /* ------------------------------------------------------------------
       PRESELECCION (cuando se llega desde ?producto_id=X)
       ------------------------------------------------------------------ */
    <?php if ($producto_preseleccionado): ?>
    (function () {
        var id  = <?= (int)$producto_preseleccionado['id'] ?>;
        var nom = <?= json_encode($producto_preseleccionado['nombre']) ?>;
        var bc  = <?= json_encode($producto_preseleccionado['barcode'] ?? null) ?>;
        seleccionar(id, nom, bc);
    })();
    <?php else: ?>
    inputP.focus();
    <?php endif; ?>

    /* ------------------------------------------------------------------
       VALIDACION DEL FORMULARIO
       ------------------------------------------------------------------ */
    function setError(id, msg) {
        var el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('hidden', !msg);
        el.textContent = msg || '';
    }

    window.validarLote = function () {
        var ok = true;

        if (!hiddenP.value) {
            setError('producto_error', 'Selecciona un producto de la lista.');
            inputP.focus();
            ok = false;
        } else {
            setError('producto_error', '');
        }

        if (!document.getElementById('fecha_vencimiento').value) {
            setError('fecha_error', 'Selecciona una fecha de vencimiento.');
            ok = false;
        } else {
            setError('fecha_error', '');
        }

        var c = parseInt(document.getElementById('cantidad').value || '0', 10);
        if (!c || c <= 0) {
            setError('cantidad_error', 'La cantidad debe ser mayor a 0.');
            ok = false;
        } else {
            setError('cantidad_error', '');
        }

        return ok;
    };

})();
</script>

<?php include '../layout/footer.php'; ?>
