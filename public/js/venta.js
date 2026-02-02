(() => {
  // ✅ BASE FIJA
  const BASE_URL = "/PULPERIA-CHEBS";

  // ✅ Elementos
  const inputNombre = document.getElementById("producto_nombre");
  const hiddenId = document.getElementById("producto_id");
  const tipoVentaEl = document.getElementById("tipo_venta"); // existe pero está oculto
  const cantidadEl = document.getElementById("cantidad");
  const totalEl = document.getElementById("total");
  const tablaBody = document.querySelector("#tabla_detalle tbody");
  const btnConfirmar = document.getElementById("btn_confirmar");

  if (!inputNombre || !hiddenId || !cantidadEl || !totalEl || !tablaBody) {
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

    // ✅ Forzar siempre unidad (aunque el select esté oculto)
    if (tipoVentaEl) tipoVentaEl.value = "unidad";
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

  // ✅ Render tabla (SIN columna tipo, productos en negrita, X rojas)
  function renderizarTabla() {
    tablaBody.innerHTML = "";
    total = 0;

    carrito.forEach((item, index) => {
      const subtotal = item.precio * item.cantidad;
      total += subtotal;

      tablaBody.innerHTML += `
        <tr class="hover:bg-chebs-soft/40">
          <td class="px-4 py-3 font-bold text-[15px]">${item.nombre}</td>
          <td class="px-4 py-3 text-[15px]">${item.cantidad}</td>
          <td class="px-4 py-3 text-[15px]">Bs ${item.precio.toFixed(2)}</td>
          <td class="px-4 py-3 font-bold text-[15px]">Bs ${subtotal.toFixed(2)}</td>
          <td class="px-4 py-3 text-center">
            <button type="button"
                    class="px-3 py-2 rounded-xl border border-red-200 bg-red-50 text-red-600 font-black
                           hover:bg-red-100 hover:border-red-300 transition"
                    onclick="eliminarProducto(${index})"
                    title="Quitar">
              ✕
            </button>
          </td>
        </tr>
      `;
    });

    totalEl.innerText = total.toFixed(2);
  }

  // ✅ Exponer funciones globales
  window.eliminarProducto = (index) => {
    carrito.splice(index, 1);
    renderizarTabla();
  };

  window.agregarDesdeFormulario = async () => {
    const nombre = inputNombre.value.trim();
    const producto_id = hiddenId.value;

    // ✅ Forzar venta por unidad (no mostrar tipo de venta)
    const tipo = "unidad";
    if (tipoVentaEl) tipoVentaEl.value = "unidad";

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

      carrito = [];
      renderizarTabla();
      limpiarFormulario();

      recargarDespuesDeOk = true;

      if (typeof mostrarMensaje === "function") {
        mostrarMensaje("✅ Venta registrada", "ID: " + data.venta_id);
      } else {
        alert("✅ Venta registrada. ID: " + data.venta_id);
        location.reload();
        return;
      }

      setTimeout(() => {
        const btnOk = document.getElementById("confirm_btn_ok");
        if (btnOk) {
          btnOk.onclick = () => {
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

  // ✅ Selecciona cantidad al enfocarla
  cantidadEl.addEventListener("focus", () => cantidadEl.select());

  // ✅ Asegurar unidad desde inicio
  if (tipoVentaEl) tipoVentaEl.value = "unidad";
})();
