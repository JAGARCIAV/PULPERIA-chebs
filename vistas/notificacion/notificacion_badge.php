<?php /* NOTIFICACION BADGE GLOBAL */ ?>

<script>
(() => {
  const BASE      = "/PULPERIA-CHEBS";
  const CACHE_KEY = "chebs_notif";
  const CACHE_TTL = 20000; // reusar si se navegó hace menos de 20 s

  const nav   = document.getElementById("nav_notif");
  const badge = document.getElementById("nav_notif_badge");
  if (!nav || !badge) return;

  function pintar(count){
    count = Number(count || 0);
    if (count > 0) {
      badge.textContent = String(count);
      badge.classList.remove("hidden");
    } else {
      badge.textContent = "";
      badge.classList.add("hidden");
    }
  }

  async function cargar(){
    // Leer caché de sessionStorage para evitar golpear el servidor en cada navegación
    try {
      const raw = sessionStorage.getItem(CACHE_KEY);
      if (raw) {
        const { ts, count } = JSON.parse(raw);
        if (Date.now() - ts < CACHE_TTL) {
          pintar(count);
          return;
        }
      }
    } catch (e) {}

    // Fetch real
    try {
      const r   = await fetch(`${BASE}/controladores/notificacion_fetch.php`, {
        credentials: "include",
        cache: "no-store"
      });
      const txt = await r.text();
      let j;
      try { j = JSON.parse(txt); }
      catch(e){
        console.error("❌ notificacion_fetch.php NO devolvió JSON:", txt);
        return;
      }
      if (!j || j.ok !== true) return;

      const arr   = Array.isArray(j.notificaciones) ? j.notificaciones : [];
      const count = Number(j.count ?? arr.length ?? 0);

      // Guardar en caché
      try {
        sessionStorage.setItem(CACHE_KEY, JSON.stringify({ ts: Date.now(), count }));
      } catch (e) {}

      pintar(count);
    } catch (e) {}
  }

  cargar();
  setInterval(() => {
    // Forzar fetch limpio en cada ciclo del intervalo (ignora caché)
    try { sessionStorage.removeItem(CACHE_KEY); } catch(e) {}
    cargar();
  }, 30000);
})();
</script>
