(() => {
  const BASE = "/PULPERIA-CHEBS";

  const badgeNotif = document.getElementById("nav_notif_badge");
  const badgeLotes = document.getElementById("nav_lotes_badge");
  const navLotes   = document.getElementById("nav_lotes");

  function setBadge(el, count){
    if (!el) return;
    const n = Number(count || 0);

    if (n > 0) {
      el.textContent = String(n);
      el.classList.remove("hidden");
    } else {
      el.textContent = "";
      el.classList.add("hidden");
    }
  }

  function pintarLotes(count){
    if (!navLotes || !badgeLotes) return;
    const n = Number(count || 0);

    if (n > 0) {
      navLotes.classList.add("bg-red-50", "border", "border-red-200", "text-red-700");
      navLotes.classList.remove("hover:bg-chebs-soft");
      badgeLotes.textContent = String(n);
      badgeLotes.className =
        "ml-2 inline-flex items-center justify-center min-w-[22px] h-[22px] px-2 " +
        "rounded-full bg-red-600 text-white text-xs font-black";
    } else {
      navLotes.classList.remove("bg-red-50", "border", "border-red-200", "text-red-700");
      navLotes.classList.add("hover:bg-chebs-soft");
      badgeLotes.textContent = "";
      badgeLotes.className = "";
    }
  }

  async function cargar(){
    try{
      const r = await fetch(`${BASE}/controladores/notificacion_fetch.php`, {
        credentials: "include",
        cache: "no-store"
      });

      // ✅ si NO es 200, igual intentamos leer json
      const j = await r.json().catch(() => null);
      if (!j || j.ok !== true) {
        setBadge(badgeNotif, 0);
        pintarLotes(0);
        return;
      }

      const count = Number(j.count || 0);
      setBadge(badgeNotif, count);
      pintarLotes(count);
    } catch {
      // si falla conexión, no rompemos la página
    }
  }

  cargar();
  setInterval(cargar, 30000);
})();
