(() => {
  const BASE_URL = "/PULPERIA-CHEBS";

  const inputNombre = document.getElementById("producto_nombre");
  const hiddenId = document.getElementById("producto_id");
  const tipoVentaEl = document.getElementById("tipo_venta");
  const cantidadEl = document.getElementById("cantidad");
  const totalEl = document.getElementById("total");
  const tablaBody = document.querySelector("#tabla_detalle tbody");
  const btnConfirmar = document.getElementById("btn_confirmar");

  if (!inputNombre || !hiddenId || !tipoVentaEl || !cantidadEl || !totalEl || !tablaBody) {
    console.error("❌ Faltan elementos en la página (IDs). Revisa venta.php.");
    return;
  }

  let carrito = [];
  let total = 0;

  let enProcesoConfirmacion = false;

  function limpiarFormulario() {
    inputNombre.value = "";
    hiddenId.value = "";
    cantidadEl.value = 1;
    tipoVentaEl.value = "unidad";
  }

  async function fetchJSON(url, payload) {
    const res = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch {
      console.error("Respuesta no JSON:", text);
      if (typeof mostrarMensaje === "function") {
        mostrarMensaje("❌ Error", "Respuesta inválida del servidor (no es JSON). Revisa errores PHP.");
      } else {
        alert("❌ Respuesta inválida del servidor (no es JSON). Revisa errores PHP.");
      }
      return { error: "Respuesta inválida del servidor (no es JSON)." };
    }
  }

  async function obtenerPrecioReal(producto_id, tipo) {
    return await fetchJSON(`${BASE_URL}/controladores/producto_fetch.php`, {
      id: parseInt(producto_id, 10),
      tipo
    });
  }

  function renderizarTabla() {
    tablaBody.innerHTML = "";
    total = 0;

    carrito.forEach((item, index) => {
      const subtotal = item.precio * item.cantidad;
      total += subtotal;

      tablaBody.innerHTML += `
        <tr class="hover:bg-chebs-soft/40">
          <td class="px-4 py-3">${item.nombre}</td>
          <td class="px-4 py-3">${item.cantidad}</td>
          <td class="px-4 py-3">${item.precio.toFixed(2)}</td>
          <td class="px-4 py-3">${subtotal.toFixed(2)}</td>
          <td class="px-4 py-3 text-center">
            <button type="button"
                    class="px-3 py-2 rounded-xl border border-chebs-line hover:bg-red-50 hover:border-red-200"
                    onclick="eliminarProducto(${index})">✕</button>
          </td>
        </tr>
      `;
    });

    totalEl.innerText = total.toFixed(2);
  }

  window.eliminarProducto = (index) => {
    carrito.splice(index, 1);
    renderizarTabla();
  };

  window.agregarDesdeFormulario = async () => {
    const nombre = inputNombre.value.trim();
    const producto_id = hiddenId.value;
    const tipo = tipoVentaEl.value;
    const cantidad = parseInt(cantidadEl.value, 10);

    if (!producto_id || !nombre) {
      if (typeof mostrarMensaje === "function") {
        mostrarMensaje("⚠️ Atención", "Selecciona un producto válido de la lista.");
      } else {
        alert("⚠️ Selecciona un producto válido de la lista.");
      }
      inputNombre.focus();
      return;
    }

    if (!cantidad || cantidad <= 0) {
      if (typeof mostrarMensaje === "function") {
        mostrarMensaje("⚠️ Atención", "Cantidad inválida.");
      } else {
        alert("⚠️ Cantidad inválida.");
      }
      cantidadEl.focus();
      return;
    }

    const data = await obtenerPrecioReal(producto_id, tipo);
    if (data.error) {
      if (typeof mostrarMensaje === "function") {
        mostrarMensaje("❌ Error", data.error);
      } else {
        alert("❌ " + data.error);
      }
      return;
    }

    const precio = parseFloat(data.precio);

    const idx = carrito.findIndex(x =>
      x.producto_id === parseInt(producto_id, 10) && x.tipo === tipo
    );

    if (idx !== -1) {
      carrito[idx].cantidad += cantidad;
      carrito[idx].precio = precio;
      renderizarTabla();
      limpiarFormulario();
      inputNombre.focus();
      return;
    }

    carrito.push({
      producto_id: parseInt(producto_id, 10),
      nombre,
      tipo,
      cantidad,
      precio
    });

    renderizarTabla();
    limpiarFormulario();
    inputNombre.focus();
  };

  // ✅ Calcula cambio dinámico
  function recalcularCambio() {
    const pagoEl   = document.getElementById("confirm_pago");
    const cambioEl = document.getElementById("confirm_cambio_big");
    const faltaEl  = document.getElementById("confirm_falta");

    if (!pagoEl || !cambioEl || !faltaEl) return;

    const pago = parseFloat((pagoEl.value || "0").toString().replace(",", "."));
    const cambio = (isNaN(pago) ? 0 : pago) - total;

    if (cambio >= 0) {
      cambioEl.textContent = `Bs ${cambio.toFixed(2)}`;
      faltaEl.classList.add("hidden");
      faltaEl.textContent = "";
    } else {
      cambioEl.textContent = `Bs 0.00`;
      faltaEl.classList.remove("hidden");
      faltaEl.textContent = `Falta: Bs ${Math.abs(cambio).toFixed(2)}`;
    }
  }

  function abrirConfirmacionVenta() {
    if (carrito.length === 0) {
      if (typeof mostrarMensaje === "function") {
        mostrarMensaje("⚠️ Atención", "Carrito vacío.");
      } else {
        alert("⚠️ Carrito vacío.");
      }
      return;
    }

    if (typeof abrirModal !== "function") {
      const ok = confirm(`¿Confirmar venta?\nTotal: Bs ${total.toFixed(2)}`);
      if (ok) ejecutarConfirmacionVenta();
      return;
    }

    // Título
    const t = document.getElementById("confirm_titulo");
    const p = document.getElementById("confirm_texto");
    if (t) t.textContent = "Confirmar venta";
    if (p) p.textContent = "Vas a registrar esta venta. ¿Deseas confirmar?";

    // Mostrar body extra (pago/cambio)
    const body = document.getElementById("confirm_body");
    const footer = document.getElementById("confirm_footer");
    if (body) body.classList.remove("hidden");
    if (footer) footer.classList.remove("hidden");

    // Total grande
    const totalBig = document.getElementById("confirm_total_big");
    if (totalBig) totalBig.textContent = total.toFixed(2);

    // Input pago
    const pagoEl = document.getElementById("confirm_pago");
    if (pagoEl) {
      pagoEl.value = "";
      pagoEl.oninput = recalcularCambio;
      setTimeout(() => pagoEl.focus(), 0);
    }

    // Inicializa cambio
    setTimeout(recalcularCambio, 0);

    // Botón Cancelar
    const btnCancel = document.getElementById("confirm_btn_cancel");
    if (btnCancel) {
      btnCancel.classList.remove("hidden");
      btnCancel.onclick = () => {
        if (typeof cerrarModal === "function") cerrarModal("modalConfirmacion");
        setTimeout(() => inputNombre.focus(), 0);
      };
    }

    // Botón Confirmar
    const btnOk = document.getElementById("confirm_btn_ok");
    if (btnOk) {
      btnOk.textContent = "Sí, confirmar";
      btnOk.onclick = () => {
        // Evitar confirmar si falta dinero
        const pago = parseFloat((pagoEl?.value || "0").toString().replace(",", "."));
        if (isNaN(pago) || pago < total) {
          if (typeof mostrarMensaje === "function") {
            mostrarMensaje("⚠️ Pago insuficiente", `Falta: Bs ${(total - (isNaN(pago) ? 0 : pago)).toFixed(2)}`);
          } else {
            alert("Pago insuficiente.");
          }
          return;
        }
        ejecutarConfirmacionVenta();
      };
    }

    abrirModal("modalConfirmacion");
  }

  async function ejecutarConfirmacionVenta() {
    if (enProcesoConfirmacion) return;
    enProcesoConfirmacion = true;

    if (typeof cerrarModal === "function") cerrarModal("modalConfirmacion");

    const data = await fetchJSON(`${BASE_URL}/controladores/venta_confirmar.php`, { carrito });

    if (!data.ok) {
      enProcesoConfirmacion = false;
      if (typeof mostrarMensaje === "function") {
        mostrarMensaje("❌ Error", (data.msg || data.error || "No se pudo registrar la venta"));
      } else {
        alert("❌ " + (data.msg || data.error || "No se pudo registrar la venta"));
      }
      return;
    }

    // Limpia carrito
    carrito = [];
    renderizarTabla();
    limpiarFormulario();

    // ✅ Modal verde auto 1.5s
    if (typeof mostrarExitoAuto === "function") {
      mostrarExitoAuto();
    } else {
      alert("✅ VENTA EXITOSA");
      location.reload();
    }

    enProcesoConfirmacion = false;
  }

  if (btnConfirmar) {
    btnConfirmar.addEventListener("click", () => {
      if (btnConfirmar.disabled) return;
      abrirConfirmacionVenta();
    });
  }

  // Enter agrega / Ctrl+Enter abre confirmación
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
