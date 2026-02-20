<?php ?>
<div id="notif_wrap" class="fixed top-4 right-4 z-[99999] space-y-3"></div>

<script>
(() => {
  const BASE = "/PULPERIA-CHEBS";
  const wrap = document.getElementById("notif_wrap");
  if (!wrap) return;

  // ✅ elementos SOLO de la página notificación
  const listEl = document.getElementById("notif_list");
  const pillEl = document.getElementById("notif_count_pill");

  function esc(s){
    return String(s ?? "")
      .replaceAll("&","&amp;")
      .replaceAll("<","&lt;")
      .replaceAll(">","&gt;");
  }

  function fmtFecha(iso){
    const s = String(iso || "").trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return esc(s);
    const [y,m,d] = s.split("-");
    return `${d}/${m}/${y}`;
  }

  function renderPopup(n){
    const editarUrl = `${BASE}/vistas/lotes/editar.php?id=${encodeURIComponent(n.lote_id)}`;
    return `
      <div data-lote="${esc(n.lote_id)}"
           class="w-[380px] max-w-[92vw] bg-white border border-red-200 shadow-xl rounded-2xl p-4 relative">

        <button data-act="cerrar"
                class="absolute top-2 right-2 w-9 h-9 rounded-xl
                       border border-gray-200 text-gray-500
                       hover:text-gray-900 hover:bg-gray-50 transition">✕</button>

        <div class="flex gap-3">
          <div class="text-2xl">⚠️</div>
          <div class="flex-1">
            <div class="font-black text-red-700 text-lg">Producto vencido</div>

            <div class="mt-1 text-sm text-gray-700">
              <div><b>${esc(n.producto_nombre)}</b></div>
              <div>Lote #${esc(n.lote_id)}</div>
              <div>Venció: <b>${fmtFecha(n.fecha_vencimiento)}</b></div>
              <div>Unidades: <b>${esc(n.cantidad_unidades)}</b></div>
            </div>

            <div class="mt-3 flex gap-2">
              <button data-act="desactivar"
                      class="px-3 py-2 rounded-xl bg-red-600 text-white font-black hover:bg-red-700 transition">
                Aceptar / Desactivar
              </button>

              <a href="${editarUrl}"
                 class="px-3 py-2 rounded-xl bg-yellow-500 text-white font-black hover:bg-yellow-600 transition">
                Editar
              </a>
            </div>

            <div class="mt-2 text-xs text-gray-500">
              Este lote está vencido y no debe venderse.
            </div>
          </div>
        </div>
      </div>
    `;
  }

  function renderFila(n){
    const editarUrl = `${BASE}/vistas/lotes/editar.php?id=${encodeURIComponent(n.lote_id)}`;
    return `
      <div class="rounded-2xl border border-red-200 bg-red-50/40 p-4 flex items-center justify-between gap-3">
        <div>
          <div class="font-black text-red-700">${esc(n.producto_nombre)}</div>
          <div class="text-sm text-gray-700">
            Lote #${esc(n.lote_id)} · Venció: <b>${fmtFecha(n.fecha_vencimiento)}</b> · Unidades: <b>${esc(n.cantidad_unidades)}</b>
          </div>
        </div>
        <div class="flex gap-2">
          <button class="px-3 py-2 rounded-xl bg-red-600 text-white font-black hover:bg-red-700"
                  data-act="desactivar" data-lote-btn="${esc(n.lote_id)}">
            Desactivar
          </button>
          <a class="px-3 py-2 rounded-xl bg-yellow-500 text-white font-black hover:bg-yellow-600"
             href="${editarUrl}">
            Editar
          </a>
        </div>
      </div>
    `;
  }

  async function cargar(){
    try{
      const r = await fetch(`${BASE}/controladores/notificacion_fetch.php`, {
        credentials: "include",
        cache: "no-store"
      });
      const j = await r.json();
      if (!j || j.ok !== true) return;

      const arr = Array.isArray(j.notificaciones) ? j.notificaciones : [];
      const count = Number(j.count ?? arr.length ?? 0);

      // ✅ pill de la página
      if (pillEl) {
        if (count > 0) {
          pillEl.classList.remove("hidden");
          pillEl.textContent = `${count} vencidos`;
        } else {
          pillEl.classList.add("hidden");
          pillEl.textContent = `0 vencidos`;
        }
      }

      // ✅ lista de la página
      if (listEl) {
        if (count <= 0) {
          listEl.innerHTML = `<div class="text-sm text-gray-600">✅ No hay lotes vencidos activos.</div>`;
        } else {
          listEl.innerHTML = arr.map(renderFila).join("");
        }
      }

      // ✅ popups (solo agrega si no existe)
      arr.forEach(n => {
        if (wrap.querySelector(`[data-lote="${n.lote_id}"]`)) return;
        wrap.insertAdjacentHTML("beforeend", renderPopup(n));
      });

    } catch {}
  }

  // ✅ clicks (popup y lista)
  document.addEventListener("click", async (e) => {
    const actBtn = e.target.closest("[data-act]");
    if (!actBtn) return;

    const act = actBtn.getAttribute("data-act");

    // popup card
    const popupCard = e.target.closest("[data-lote]");
    const loteIdPopup = popupCard ? popupCard.getAttribute("data-lote") : null;

    // lista
    const loteIdList = actBtn.getAttribute("data-lote-btn") || null;

    const loteId = loteIdPopup || loteIdList;
    if (!loteId) return;

    if (act === "cerrar" && popupCard) {
      popupCard.remove();
      return;
    }

    if (act === "desactivar") {
      actBtn.disabled = true;

      const fd = new FormData();
      fd.append("accion", "desactivar");
      fd.append("lote_id", loteId);

      try{
        const r = await fetch(`${BASE}/controladores/notificacion_accion.php`, {
          method: "POST",
          body: fd,
          credentials: "include"
        });
        const j = await r.json();
        if (j && j.ok === true) {
          if (popupCard) popupCard.remove();
          await cargar();
          return;
        }
      } catch {}

      actBtn.disabled = false;
    }
  });

  cargar();
  setInterval(cargar, 30000);
})();
</script>
