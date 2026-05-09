<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

include __DIR__ . "/../layout/header.php";
?>

<!-- MODAL: CONFIRMAR DESACTIVAR LOTE -->
<div id="modalDesact" class="hidden fixed inset-0 z-[9999]">
  <div id="modalDesact_backdrop" class="absolute inset-0 bg-black/60" style="backdrop-filter:blur(3px);"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
    <div class="w-full max-w-sm rounded-3xl bg-white overflow-hidden pointer-events-auto"
         style="box-shadow:0 24px 64px rgba(0,0,0,0.35),0 0 0 1px rgba(0,0,0,0.07);">
      <div class="px-6 pt-6 pb-5 border-b border-red-100" style="background:linear-gradient(160deg,#fff5f5 0%,#fff 100%);">
        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[10px] font-black tracking-widest uppercase mb-3"
              style="background:rgba(220,38,38,0.08);border-color:rgba(220,38,38,0.22);color:#dc2626;">
          Acción irreversible
        </span>
        <h3 class="text-xl font-black text-gray-900">¿Desactivar lote vencido?</h3>
        <p class="text-sm text-gray-600 mt-2 leading-relaxed" id="modalDesact_texto"></p>
      </div>
      <div class="px-6 py-5 flex gap-3 justify-end">
        <button id="modalDesact_cancelar"
                class="px-5 py-2.5 rounded-2xl border-2 border-gray-200 bg-white text-gray-700 font-bold hover:bg-gray-50 transition">
          Cancelar
        </button>
        <button id="modalDesact_ok"
                class="px-5 py-2.5 rounded-2xl bg-red-600 text-white font-black hover:bg-red-700 transition">
          Sí, desactivar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Toast de feedback -->
<div id="notif_toast"
     class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-[10000] px-5 py-3 rounded-2xl text-white text-sm font-bold shadow-lg transition-all"
     style="min-width:220px;text-align:center;">
</div>

<div class="max-w-[1440px] mx-auto px-6 py-6">
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">
    <div class="px-6 py-4 bg-chebs-soft/50 border-b border-chebs-line flex items-center justify-between">
      <div>
        <h1 class="text-xl font-black">Notificaciones</h1>
        <p class="text-sm text-gray-500">Lotes vencidos activos</p>
      </div>

      <div class="flex items-center gap-2">
        <span class="text-2xl">🔔</span>
        <span id="page_badge"
              class="hidden min-w-[26px] h-[26px] px-2 rounded-full bg-red-600
                     text-white text-xs font-black flex items-center justify-center">
        </span>
      </div>
    </div>

    <div class="p-6">
      <div id="notif_list" class="space-y-3">
        <div class="text-sm text-gray-600">Cargando…</div>
      </div>
    </div>
  </div>
</div>

<script>
(() => {
  const BASE = "/PULPERIA-CHEBS";
  const CSRF = <?= json_encode(get_csrf_token()) ?>;
  const list = document.getElementById("notif_list");
  const pageBadge = document.getElementById("page_badge");

  // ---- Toast ----
  let _toastTimer = null;
  function showToast(msg, ok = false) {
    const t = document.getElementById('notif_toast');
    t.textContent = msg;
    t.style.background = ok ? '#4e7a2b' : '#dc2626';
    t.classList.remove('hidden');
    clearTimeout(_toastTimer);
    _toastTimer = setTimeout(() => t.classList.add('hidden'), 3500);
  }

  // ---- Modal de confirmación custom ----
  let _resolveConfirm = null;

  function confirmar(nombre, unidades) {
    return new Promise(resolve => {
      _resolveConfirm = resolve;
      document.getElementById('modalDesact_texto').textContent =
        `Se darán de baja ${unidades} unidad(es) de "${nombre}". Las unidades se eliminarán del inventario de forma permanente.`;
      document.getElementById('modalDesact').classList.remove('hidden');
      setTimeout(() => document.getElementById('modalDesact_ok').focus(), 30);
    });
  }

  function cerrarModalDesact(result) {
    document.getElementById('modalDesact').classList.add('hidden');
    if (_resolveConfirm) { _resolveConfirm(result); _resolveConfirm = null; }
  }

  document.getElementById('modalDesact_ok').addEventListener('click', () => cerrarModalDesact(true));
  document.getElementById('modalDesact_cancelar').addEventListener('click', () => cerrarModalDesact(false));
  document.getElementById('modalDesact_backdrop').addEventListener('click', () => cerrarModalDesact(false));
  document.addEventListener('keydown', (e) => {
    const modal = document.getElementById('modalDesact');
    if (modal.classList.contains('hidden')) return;
    if (e.key === 'Escape') { e.preventDefault(); cerrarModalDesact(false); }
    if (e.key === 'Enter')  { e.preventDefault(); cerrarModalDesact(true); }
  });

  // ---- Utilidades ----
  function esc(s){
    return String(s ?? "")
      .replaceAll("&","&amp;")
      .replaceAll("<","&lt;")
      .replaceAll(">","&gt;");
  }

  function pintarBadge(count){
    count = Number(count || 0);
    if(count > 0){
      pageBadge.textContent = String(count);
      pageBadge.classList.remove("hidden");
    } else {
      pageBadge.textContent = "";
      pageBadge.classList.add("hidden");
    }
  }

  function fila(n){
    const editar = `${BASE}/vistas/lotes/editar.php?id=${encodeURIComponent(n.lote_id)}`;

    return `
      <div class="rounded-2xl border border-red-200 bg-red-50/40 p-4 flex items-center justify-between gap-3">
        <div class="min-w-0">
          <div class="font-black text-red-700 truncate">${esc(n.producto_nombre)}</div>
          <div class="text-sm text-gray-700">
            Lote <b>#${esc(n.lote_id)}</b> · Venció: <b>${esc(n.fecha_vencimiento)}</b> · Unidades: <b>${esc(n.cantidad_unidades)}</b>
          </div>
          <div class="text-xs text-gray-500 mt-1">
            Este lote está vencido y no debe venderse.
          </div>
        </div>

        <div class="flex gap-2 shrink-0">
          <button
            class="px-3 py-2 rounded-xl bg-red-600 text-white font-black hover:bg-red-700 transition"
            data-act="desactivar" data-id="${esc(n.lote_id)}">
            Desactivar
          </button>

          <a class="px-3 py-2 rounded-xl bg-yellow-500 text-white font-black hover:bg-yellow-600 transition"
             href="${editar}">
            Editar
          </a>
        </div>
      </div>
    `;
  }

  async function fetchJSON(url, opts = {}){
    const r = await fetch(url, { credentials:"include", cache:"no-store", ...opts });
    const txt = await r.text();
    try { return JSON.parse(txt); }
    catch(e){
      console.error("❌ NO JSON:", url, txt);
      return null;
    }
  }

  async function cargar(){
    const j = await fetchJSON(`${BASE}/controladores/notificacion_fetch.php`);
    if(!j || j.ok !== true){
      pintarBadge(0);
      list.innerHTML = `<div class="text-sm text-gray-600">Notificaciones vacías.</div>`;
      return;
    }

    const arr = Array.isArray(j.notificaciones) ? j.notificaciones : [];
    const count = Number(j.count ?? arr.length ?? 0);
    pintarBadge(count);

    if(arr.length === 0){
      list.innerHTML = `<div class="text-sm text-gray-600">Notificaciones vacías.</div>`;
      return;
    }

    list.innerHTML = arr.map(fila).join("");
  }

  document.addEventListener("click", async (e) => {
    const btn = e.target.closest("[data-act='desactivar']");
    if(!btn) return;

    const row = btn.closest(".rounded-2xl");
    const nombre   = row ? (row.querySelector(".font-black")?.textContent.trim() || "este lote") : "este lote";
    const unidades = row ? ((row.textContent.match(/Unidades:\s*(\d+)/) || [])[1] || "?") : "?";

    const confirmado = await confirmar(nombre, unidades);
    if (!confirmado) return;

    const id = btn.getAttribute("data-id");
    btn.disabled = true;
    btn.classList.add("opacity-60","cursor-not-allowed");

    const fd = new FormData();
    fd.append("accion","desactivar");
    fd.append("lote_id", id);
    fd.append("csrf_token", CSRF);

    const j = await fetchJSON(`${BASE}/controladores/notificacion_accion.php`, {
      method:"POST",
      body: fd
    });

    if(j && j.ok === true){
      await cargar();
    } else {
      showToast(j?.msg || "No se pudo desactivar.");
      btn.disabled = false;
      btn.classList.remove("opacity-60","cursor-not-allowed");
    }
  });

  cargar();
  setInterval(cargar, 30000);
})();
</script>

<?php include __DIR__ . "/../layout/footer.php"; ?>
