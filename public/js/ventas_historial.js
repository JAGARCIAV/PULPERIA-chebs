function abrirModalGeneral(html) {
  const modal = document.getElementById("modalGeneral");
  const cont = document.getElementById("modalContenido");

  // ✅ envolver el contenido en layout Chebs (por si el HTML viene "pelado")
  cont.innerHTML = `
    <div class="w-full">
      ${html}
    </div>
  `;

  // ✅ mostrar modal
  modal.style.display = "flex";

  // ✅ aplicar estilos Chebs a lo que venga del backend (tabla/botones)
  aplicarEstilosChebsEnModal();
}

function cerrarModalGeneral() {
  document.getElementById("modalGeneral").style.display = "none";
  document.getElementById("modalContenido").innerHTML = "";
}

function verDetalleVenta(idVenta) {
  fetch("../../controladores/venta_detalle_ajax.php?id=" + idVenta)
    .then(res => res.text())
    .then(html => abrirModalGeneral(html))
    .catch(err => {
      console.error(err);
      alert("Error cargando detalle");
    });
}

// ✅ convierte el HTML del backend a estilo Chebs (sin tocar PHP)
function aplicarEstilosChebsEnModal() {
  const cont = document.getElementById("modalContenido");
  if (!cont) return;

  // 1) Convertir <h2> si existe
  const h2 = cont.querySelector("h2");
  if (h2) {
    h2.className = "text-xl sm:text-2xl font-black text-chebs-black";
  }

  // 2) Si hay <table border=...> darle estilo Chebs
  const table = cont.querySelector("table");
  if (table) {
    // envolver en scroll horizontal
    if (!table.parentElement.classList.contains("overflow-x-auto")) {
      const wrap = document.createElement("div");
      wrap.className = "overflow-x-auto mt-4";
      table.parentNode.insertBefore(wrap, table);
      wrap.appendChild(table);
    }

    table.removeAttribute("border");
    table.removeAttribute("width");
    table.removeAttribute("cellpadding");
    table.className = "w-full text-sm";

    const thead = table.querySelector("thead");
    if (thead) {
      thead.className = "bg-gray-100";
      thead.querySelectorAll("th").forEach(th => {
        th.className = "px-4 py-3 font-black text-left text-chebs-black whitespace-nowrap";
      });
    } else {
      // si no hay thead, el primer <tr> lo tratamos como cabecera
      const firstTr = table.querySelector("tr");
      if (firstTr) {
        firstTr.className = "bg-gray-100";
        firstTr.querySelectorAll("th,td").forEach(cell => {
          cell.className = "px-4 py-3 font-black text-left text-chebs-black whitespace-nowrap";
        });
      }
    }

    const tbody = table.querySelector("tbody");
    const bodyRows = (tbody ? tbody.querySelectorAll("tr") : table.querySelectorAll("tr"));
    bodyRows.forEach((tr, idx) => {
      // saltar la cabecera si no hay thead
      if (!thead && idx === 0) return;

      tr.className = "hover:bg-chebs-soft/40 transition";
      tr.querySelectorAll("td").forEach(td => {
        td.className = "px-4 py-3 align-top";
      });
    });
  }

  // 3) Botones dentro del html (si vienen)
  cont.querySelectorAll("button").forEach(btn => {
    // si ya tiene clases no lo piso fuerte
    if (!btn.className || btn.className.trim() === "") {
      btn.className = "px-5 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft whitespace-nowrap";
    } else {
      btn.className += " whitespace-nowrap";
    }
  });

  // 4) Links como botones (si vienen)
  cont.querySelectorAll("a").forEach(a => {
    // evita tocar links normales si no parecen botón
    const text = (a.textContent || "").toLowerCase();
    if (text.includes("cerrar") || text.includes("volver") || text.includes("aceptar")) {
      a.className = "inline-flex items-center justify-center px-5 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition whitespace-nowrap";
    }
  });

  // 5) Si tu backend trae un botón "Cerrar" y quieres forzarlo a verde:
  cont.querySelectorAll("button").forEach(btn => {
    const t = (btn.textContent || "").trim().toLowerCase();
    if (t === "cerrar") {
      btn.className = "px-6 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft whitespace-nowrap";
    }
  });
}

// ✅ cerrar modal al apretar ESC
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") cerrarModalGeneral();
});
