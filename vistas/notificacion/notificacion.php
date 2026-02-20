<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

include __DIR__ . "/../layout/header.php";
?>

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
  const list = document.getElementById("notif_list");
  const pageBadge = document.getElementById("page_badge");

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

    const id = btn.getAttribute("data-id");
    btn.disabled = true;
    btn.classList.add("opacity-60","cursor-not-allowed");

    const fd = new FormData();
    fd.append("accion","desactivar");
    fd.append("lote_id", id);

    const j = await fetchJSON(`${BASE}/controladores/notificacion_accion.php`, {
      method:"POST",
      body: fd
    });

    if(j && j.ok === true){
      await cargar();
    } else {
      alert(j?.msg || "No se pudo desactivar.");
    }

    btn.disabled = false;
    btn.classList.remove("opacity-60","cursor-not-allowed");
  });

  cargar();
  setInterval(cargar, 30000);
})();
</script>

<?php include __DIR__ . "/../layout/footer.php"; ?>
