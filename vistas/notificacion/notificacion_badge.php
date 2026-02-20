<?php /* NOTIFICACION BADGE GLOBAL */ ?>

<script>
(() => {
  const BASE = "/PULPERIA-CHEBS";

  const nav = document.getElementById("nav_notif");
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
    try{
      const r = await fetch(`${BASE}/controladores/notificacion_fetch.php`, {
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

      const arr = Array.isArray(j.notificaciones) ? j.notificaciones : [];
      const count = Number(j.count ?? arr.length ?? 0);
      pintar(count);

    } catch (e) {}
  }

  cargar();
  setInterval(cargar, 30000);
})();
</script>
