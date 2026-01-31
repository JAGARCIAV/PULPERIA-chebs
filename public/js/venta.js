(() => {
  // ✅ BASE FIJA
  const BASE_URL = "/PULPERIA-CHEBS";

  // ✅ Elementos (ahora por ID, no por datalist list="")
  const inputNombre = document.getElementById("producto_nombre");
  const hiddenId = document.getElementById("producto_id");
  const tipoVentaEl = document.getElementById("tipo_venta");
  const cantidadEl = document.getElementById("cantidad");
  const totalEl = document.getElementById("total");
  const tablaBody = document.querySelector("#tabla_detalle tbody");
  const btnConfirmar = document.getElementById("btn_confirmar");

  // Si por algo falta algo, no rompas todo
  if (!inputNombre || !hiddenId || !tipoVentaEl || !cantidadEl || !totalEl || !tablaBody) {
    console.error("❌ Faltan elementos en la página (IDs). Revisa venta.php.");
    return;
  }

  // 🔹 Carrito
  let carrito = [];
  let total = 0;

  // ✅ flag para recargar cuando el usuario cierre el modal
  let recargarDespuesDeOk = false;

  function limpiarFormulario() {
    inputNombre.value = "";
    hiddenId.value = "";
    cantidadEl.value = 1;
    tipoVentaEl.value = "unidad";
    // el stockInfo lo maneja tu script inline (autocomplete) si existe
  }

  // ✅ Helper: fetch JSON con manejo de errores
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

  // ✅ obtener precio real desde BD
  async function obtenerPrecioReal(producto_id, tipo) {
    return await fetchJSON(`${BASE_URL}/controladores/producto_fetch.php`, {
      id: parseInt(producto_id, 10),
      tipo
    });
  }

  // ✅ Render tabla
  function renderizarTabla() {
    tablaBody.innerHTML = "";
    total = 0;

    carrito.forEach((item, index) => {
      const subtotal = item.precio * item.cantidad;
      total += subtotal;

      tablaBody.innerHTML += `
        <tr class="hover:bg-chebs-soft/40">
          <td class="px-4 py-3">${item.nombre}</td>
          <td class="px-4 py-3">${item.tipo}</td>
          <td class="px-4 py-3">${item.cantidad}</td>
          <td class="px-4 py-3">${item.precio.toFixed(2)}</td>
          <td class="px-4 py-3">${subtotal.toFixed(2)}</td>
          <td class="px-4 py-3">
            <button type="button"
                    class="px-3 py-2 rounded-xl border border-chebs-line hover:bg-red-50 hover:border-red-200"
                    onclick="eliminarProducto(${index})">✕</button>
          </td>
        </tr>
      `;
    });

    totalEl.innerText = total.toFixed(2);
  }

  // ✅ Exponer funciones globales (porque tu HTML las llama)
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

    // ✅ precio real desde BD
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

    // ✅ ACUMULAR si ya existe el mismo producto con el mismo tipo
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

  // ✅ Confirmar venta (guardar en BD)
  if (btnConfirmar) {
    btnConfirmar.addEventListener("click", async () => {
      if (carrito.length === 0) {
        if (typeof mostrarMensaje === "function") {
          mostrarMensaje("⚠️ Atención", "Carrito vacío.");
        } else {
          alert("⚠️ Carrito vacío.");
        }
        return;
      }

      const data = await fetchJSON(`${BASE_URL}/controladores/venta_confirmar.php`, { carrito });

      if (!data.ok) {
        if (typeof mostrarMensaje === "function") {
          mostrarMensaje("❌ Error", (data.msg || data.error || "No se pudo registrar la venta"));
        } else {
          alert("❌ " + (data.msg || data.error || "No se pudo registrar la venta"));
        }
        return;
      }

      // ✅ Limpia carrito antes
      carrito = [];
      renderizarTabla();
      limpiarFormulario();

      // ✅ Marca que al dar Aceptar se recargue
      recargarDespuesDeOk = true;

      // ✅ Abre modal
      if (typeof mostrarMensaje === "function") {
        mostrarMensaje("✅ Venta registrada", "ID: " + data.venta_id);
      } else {
        alert("✅ Venta registrada. ID: " + data.venta_id);
        location.reload();
        return;
      }

      // ✅ Cuando el usuario presiona Aceptar, recién recargar
      setTimeout(() => {
        const btnOk = document.getElementById("confirm_btn_ok");
        if (btnOk) {
          btnOk.onclick = () => {
            // cerrar modal (si existe la función)
            if (typeof cerrarModal === "function") cerrarModal("modalConfirmacion");
            if (recargarDespuesDeOk) location.reload();
          };
        }
      }, 0);
    });
  }

  /* ===========================
     ✅ MEJORAS DE VELOCIDAD POS
     =========================== */

  // ✅ Enter = Agregar / Ctrl+Enter = Confirmar
  document.addEventListener("keydown", (e) => {
    const activo = document.activeElement;

    // Enter en producto o cantidad → agregar
    if (e.key === "Enter" && !e.ctrlKey) {
      if (activo && (activo.id === "producto_nombre" || activo.id === "cantidad")) {
        e.preventDefault();
        window.agregarDesdeFormulario();
      }
    }

    // Ctrl + Enter → confirmar
    if (e.key === "Enter" && e.ctrlKey) {
      if (btnConfirmar && !btnConfirmar.disabled) btnConfirmar.click();
    }
  });

  // ✅ Selecciona cantidad al enfocarla
  cantidadEl.addEventListener("focus", () => cantidadEl.select());
})();
