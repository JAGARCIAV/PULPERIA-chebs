(() => {
  const BASE_URL = "/PULPERIA-CHEBS";

  // ====== Elementos ======
  const inputNombre  = document.getElementById("producto_nombre");
  const hiddenId     = document.getElementById("producto_id");
  const cantidadEl   = document.getElementById("cantidad");
  const totalEl      = document.getElementById("total");
  const tablaBody    = document.querySelector("#tabla_detalle tbody");
  const btnConfirmar = document.getElementById("btn_confirmar");

  // ⚠️ tipo_venta a veces no existe (en tu venta.php ya no lo usas visualmente)
  let tipoVentaEl = document.getElementById("tipo_venta");
  if (!tipoVentaEl) {
    // Creamos uno oculto para no romper lógica vieja
    tipoVentaEl = document.createElement("input");
    tipoVentaEl.type = "hidden";
    tipoVentaEl.id = "tipo_venta";
    tipoVentaEl.value = "unidad";
    document.body.appendChild(tipoVentaEl);
  }

  if (!inputNombre || !hiddenId || !cantidadEl || !totalEl || !tablaBody) {
    console.error("❌ Faltan elementos en la página (IDs). Revisa venta.php.");
    return;
  }

  // ====== Helpers ======
  function escapeHtml(str) {
    return String(str ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function parseMoney(v) {
    // Soporta coma o punto
    const s = (v ?? "").toString().trim().replace(",", ".");
    const n = parseFloat(s);
    return isNaN(n) ? 0 : n;
  }

  // ====== Estado ======
  let carrito = [];
  let total = 0;
  let enProcesoConfirmacion = false;

  function limpiarFormulario() {
    inputNombre.value = "";
    hiddenId.value = "";
    cantidadEl.value = 1;
    tipoVentaEl.value = "unidad";
    const stockInfo = document.getElementById("stock_info");
    if (stockInfo) stockInfo.textContent = "";
  }

  async function fetchJSON(url, payload) {
    let res;
    try {
      res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
    } catch (err) {
      console.error("Fetch error:", err);
      if (typeof mostrarMensaje === "function") {
        mostrarMensaje("❌ Error", "No se pudo conectar con el servidor.");
      } else {
        alert("❌ No se pudo conectar con el servidor.");
      }
      return { ok: false, error: "Network error" };
    }

    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch {
      console.error("Respuesta NO JSON:", text);
      if (typeof mostrarMensaje === "function") {
        mostrarMensaje("❌ Error", "Respuesta inválida del servidor (no es JSON). Revisa errores PHP.");
      } else {
        alert("❌ Respuesta inválida del servidor (no es JSON). Revisa errores PHP.");
      }
      return { ok: false, error: "Respuesta inválida (no JSON)" };
    }
  }

  // ✅ Trae precio + presentaciones desde tu backend
  async function obtenerProductoInfo(producto_id) {
    return await fetchJSON(`${BASE_URL}/controladores/producto_fetch.php`, {
      id: parseInt(producto_id, 10)
    });
  }

  function renderizarTabla() {
    tablaBody.innerHTML = "";
    total = 0;

    carrito.forEach((item, index) => {
      const subtotal = item.precio * item.cantidad;
      total += subtotal;

      const nombreSeguro = escapeHtml(item.nombre);

// alternar filas: rosado/blanco
const rowBg = (index % 2 === 0) ? "bg-pink-50" : "bg-white";

tablaBody.insertAdjacentHTML("beforeend", `
  <tr class="${rowBg} hover:bg-pink-100 transition">
    <!-- Producto -->
    <td class="px-4 py-3">
      <div class="text-lg font-black text-chebs-black leading-tight">
        ${nombreSeguro}
      </div>
      <div class="text-sm font-semibold text-gray-500">
        (${item.tipo === "paquete" ? "Paquete" : "Unidad"})
      </div>
    </td>

    <!-- Cantidad -->
    <td class="px-4 py-3 text-lg font-black text-chebs-black">
      ${item.cantidad}
    </td>

    <!-- Precio -->
    <td class="px-4 py-3 text-lg font-bold text-chebs-black">
      ${Number(item.precio).toFixed(2)}
    </td>

    <!-- Subtotal -->
    <td class="px-4 py-3 text-xl font-black text-chebs-green">
      ${Number(subtotal).toFixed(2)}
    </td>

    <!-- Acción (X roja con icono) -->
    <td class="px-4 py-3 text-center">
      <button type="button"
              title="Eliminar"
              class="w-10 h-10 inline-flex items-center justify-center rounded-xl
                     border border-red-200 bg-white
                     hover:bg-red-50 hover:border-red-300
                     transition"
              onclick="window.eliminarProducto(${index})">
        <span class="text-red-600 text-2xl font-black leading-none">×</span>
      </button>
    </td>
  </tr>
`);

    });

    totalEl.innerText = total.toFixed(2);
  }

  window.eliminarProducto = (index) => {
    carrito.splice(index, 1);
    renderizarTabla();
  };

  // ✅ agrega o suma si ya existe (incluye presentacion_id)
  function pushOrSum(item) {
    const idx = carrito.findIndex(x =>
      x.producto_id === item.producto_id &&
      x.tipo === item.tipo &&
      (x.presentacion_id ?? null) === (item.presentacion_id ?? null)
    );

    if (idx !== -1) {
      carrito[idx].cantidad += item.cantidad;
      carrito[idx].precio = item.precio;
      carrito[idx].unidades_reales += item.unidades_reales;
      return;
    }
    carrito.push(item);
  }

  // ✅ greedy: convierte unidades a presentaciones (DESC por unidades)
  function descomponerEnPresentaciones(info, unidadesSolicitadas) {
    const presentaciones = Array.isArray(info.presentaciones) ? info.presentaciones : [];
    presentaciones.sort((a, b) => (b.unidades || 0) - (a.unidades || 0));

    let restante = unidadesSolicitadas;
    const items = [];

    for (const pres of presentaciones) {
      const u = parseInt(pres.unidades || 0, 10);
      const precio = Number(pres.precio_venta || 0);

      if (u <= 0 || precio <= 0) continue;
      if (restante < u) continue;

      const packs = Math.floor(restante / u);
      if (packs > 0) {
        items.push({
          tipo: "paquete",
          presentacion_id: pres.id,
          nombre: `${info.nombre} - ${pres.nombre}`,
          cantidad: packs,
          precio: precio,
          unidades_reales: packs * u
        });
        restante -= packs * u;
      }
    }

    // resto por unidad
    if (restante > 0) {
      const pu = Number(info.precio_unidad || 0);
      if (!pu || isNaN(pu)) return { error: "Este producto no tiene precio por unidad." };

      items.push({
        tipo: "unidad",
        presentacion_id: null,
        nombre: info.nombre,
        cantidad: restante,
        precio: pu,
        unidades_reales: restante
      });
    }

    return { items };
  }

  // ✅ botón Agregar (tu venta.php lo llama)
  window.agregarDesdeFormulario = async () => {
    const nombre = inputNombre.value.trim();
    const producto_id = hiddenId.value;
    const cantidad = parseInt(cantidadEl.value, 10);

    if (!producto_id || !nombre) {
      if (typeof mostrarMensaje === "function") mostrarMensaje("⚠️ Atención", "Selecciona un producto válido de la lista.");
      else alert("⚠️ Selecciona un producto válido de la lista.");
      inputNombre.focus();
      return;
    }

    if (!cantidad || cantidad <= 0) {
      if (typeof mostrarMensaje === "function") mostrarMensaje("⚠️ Atención", "Cantidad inválida.");
      else alert("⚠️ Cantidad inválida.");
      cantidadEl.focus();
      return;
    }

    const info = await obtenerProductoInfo(producto_id);
    if (info.error || info.ok === false) {
      const msg = info.error || info.msg || "No se pudo obtener el producto";
      if (typeof mostrarMensaje === "function") mostrarMensaje("❌ Error", msg);
      else alert("❌ " + msg);
      return;
    }

    const r = descomponerEnPresentaciones(info, cantidad);
    if (r.error) {
      if (typeof mostrarMensaje === "function") mostrarMensaje("❌ Error", r.error);
      else alert("❌ " + r.error);
      return;
    }

    for (const it of r.items) {
      pushOrSum({
        producto_id: parseInt(producto_id, 10),
        nombre: it.nombre,
        tipo: it.tipo,
        presentacion_id: it.presentacion_id,
        cantidad: it.cantidad,
        precio: Number(it.precio),
        unidades_reales: it.unidades_reales
      });
    }

    renderizarTabla();
    limpiarFormulario();
    inputNombre.focus();
  };

  // ====== Modal cambio (cliente paga / cambio / falta) ======
  function modalExiste() {
    return !!document.getElementById("modalConfirmacion");
  }

  function setBtnOkEnabled(btnOk, enabled) {
    if (!btnOk) return;
    btnOk.disabled = !enabled;

    // Mantiene tu diseño, solo agrega estado disabled
    if (!enabled) {
      btnOk.classList.add("opacity-60", "cursor-not-allowed");
    } else {
      btnOk.classList.remove("opacity-60", "cursor-not-allowed");
    }
  }

  function recalcularCambioYEstado() {
    const pagoEl   = document.getElementById("confirm_pago");
    const cambioEl = document.getElementById("confirm_cambio_big");
    const faltaEl  = document.getElementById("confirm_falta");
    const btnOk    = document.getElementById("confirm_btn_ok");
    if (!pagoEl || !cambioEl || !faltaEl) return;

    const pago = parseMoney(pagoEl.value);
    const diff = pago - total;

    if (diff >= 0) {
      cambioEl.textContent = `Bs ${diff.toFixed(2)}`;
      faltaEl.classList.add("hidden");
      faltaEl.textContent = "";
      setBtnOkEnabled(btnOk, true);
    } else {
      cambioEl.textContent = `Bs 0.00`;
      faltaEl.classList.remove("hidden");
      faltaEl.textContent = `Falta: Bs ${Math.abs(diff).toFixed(2)}`;
      setBtnOkEnabled(btnOk, false);
    }
  }

  function abrirConfirmacionVenta() {
    if (carrito.length === 0) {
      if (typeof mostrarMensaje === "function") mostrarMensaje("⚠️ Atención", "Carrito vacío.");
      else alert("⚠️ Carrito vacío.");
      return;
    }

    // Si no existe modal o no existen funciones del modal => fallback confirm()
    if (!modalExiste() || typeof abrirModal !== "function" || typeof cerrarModal !== "function") {
      const ok = confirm(`¿Confirmar venta?\nTotal: Bs ${total.toFixed(2)}`);
      if (ok) ejecutarConfirmacionVenta();
      return;
    }

    // Pintar modal
    const t = document.getElementById("confirm_titulo");
    const p = document.getElementById("confirm_texto");
    if (t) t.textContent = "Confirmar venta";
    if (p) p.textContent = "Vas a registrar esta venta. ¿Deseas confirmar?";

    const body = document.getElementById("confirm_body");
    const footer = document.getElementById("confirm_footer");
    if (body) body.classList.remove("hidden");
    if (footer) footer.classList.remove("hidden");

    const totalBig = document.getElementById("confirm_total_big");
    if (totalBig) totalBig.textContent = total.toFixed(2);

    const pagoEl = document.getElementById("confirm_pago");
    if (pagoEl) {
      pagoEl.value = "";
      pagoEl.oninput = recalcularCambioYEstado;
      setTimeout(() => pagoEl.focus(), 0);
    }

    // Estado inicial: sin pago => OK deshabilitado
    setTimeout(recalcularCambioYEstado, 0);

    const btnCancel = document.getElementById("confirm_btn_cancel");
    if (btnCancel) {
      btnCancel.classList.remove("hidden");
      btnCancel.onclick = () => {
        cerrarModal("modalConfirmacion");
        setTimeout(() => inputNombre.focus(), 0);
      };
    }

    const btnOk = document.getElementById("confirm_btn_ok");
    if (btnOk) {
      btnOk.textContent = "Sí, confirmar";
      btnOk.onclick = () => {
        // Si está deshabilitado, no hace nada
        if (btnOk.disabled) return;
        ejecutarConfirmacionVenta();
      };
    }

    abrirModal("modalConfirmacion");
  }

  async function ejecutarConfirmacionVenta() {
    if (enProcesoConfirmacion) return;
    enProcesoConfirmacion = true;

    try {
      if (modalExiste() && typeof cerrarModal === "function") cerrarModal("modalConfirmacion");

      const data = await fetchJSON(`${BASE_URL}/controladores/venta_confirmar.php`, { carrito });

      if (!data || data.ok !== true) {
        const msg = data?.msg || data?.error || "No se pudo registrar la venta";
        if (typeof mostrarMensaje === "function") mostrarMensaje("❌ Error", msg);
        else alert("❌ " + msg);
        return;
      }

      carrito = [];
      renderizarTabla();
      limpiarFormulario();

      if (typeof mostrarExitoAuto === "function") mostrarExitoAuto();
      else {
        alert("✅ VENTA EXITOSA");
        location.reload();
      }
    } finally {
      enProcesoConfirmacion = false;
    }
  }

  // ✅ Click Confirmar
  if (btnConfirmar) {
    btnConfirmar.addEventListener("click", () => {
      if (btnConfirmar.disabled) return;
      abrirConfirmacionVenta();
    });
  }

  // ✅ Enter agrega (en producto/cantidad) — Ctrl+Enter confirma
  document.addEventListener("keydown", (e) => {
    const activo = document.activeElement;

    if (e.key === "Enter" && !e.ctrlKey) {
      if (activo && (activo.id === "producto_nombre" || activo.id === "cantidad")) {
        e.preventDefault();
        window.agregarDesdeFormulario();
      }
    }

    if (e.key === "Enter" && e.ctrlKey) {
      if (btnConfirmar && !btnConfirmar.disabled) btnConfirmar.click();
    }
  });

  cantidadEl.addEventListener("focus", () => cantidadEl.select());
})();