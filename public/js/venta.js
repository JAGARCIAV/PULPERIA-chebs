// ✅ BASE FIJA (así no fallan rutas)
const BASE_URL = "/PULPERIA-CHEBS";

// 🔹 Manejo del datalist → obtener ID del producto
const input = document.querySelector('input[list="lista_productos"]');
const hidden = document.getElementById('producto_id');
const options = document.querySelectorAll('#lista_productos option');

const stockInfo = document.getElementById("stock_info");

// 🔹 Carrito
let carrito = [];
let total = 0;

// ✅ flag para recargar cuando el usuario cierre el modal
let recargarDespuesDeOk = false;

function limpiarFormulario() {
  input.value = "";
  hidden.value = "";
  document.getElementById("cantidad").value = 1;
  document.getElementById("tipo_venta").value = "unidad";
  if (stockInfo) stockInfo.innerText = "";
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
    }

    return { error: "Respuesta inválida del servidor (no es JSON). Revisa errores PHP." };
  }
}

// ✅ obtener precio real desde BD
async function obtenerPrecioReal(producto_id, tipo) {
  return await fetchJSON(`${BASE_URL}/controladores/producto_fetch.php`, {
    id: parseInt(producto_id),
    tipo
  });
}

// ✅ obtener stock (solo lotes activos)
async function obtenerStock(producto_id) {
  return await fetchJSON(`${BASE_URL}/controladores/stock_fetch.php`, {
    producto_id: parseInt(producto_id)
  });
}

// ✅ cuando escribe/elige producto del datalist
if (input) {
  input.addEventListener('input', async () => {
    hidden.value = '';

    // buscar match exacto
    options.forEach(option => {
      if (option.value === input.value) {
        hidden.value = option.dataset.id;
      }
    });

    // Mostrar stock cuando el producto es válido
    if (hidden.value && stockInfo) {
      const s = await obtenerStock(hidden.value);
      if (s.error) {
        stockInfo.innerText = "Stock: error";
      } else {
        stockInfo.innerText = `Stock disponible: ${parseInt(s.stock)}`;
      }
    } else {
      if (stockInfo) stockInfo.innerText = "";
    }
  });
}

async function agregarDesdeFormulario() {
  const nombre = input.value.trim();
  const producto_id = hidden.value;
  const tipo = document.getElementById("tipo_venta").value;
  const cantidad = parseInt(document.getElementById("cantidad").value);

  if (!producto_id || !nombre) {
    mostrarMensaje?.("⚠️ Atención", "Selecciona un producto válido de la lista.");
    input.focus();
    return;
  }
  if (!cantidad || cantidad <= 0) {
    mostrarMensaje?.("⚠️ Atención", "Cantidad inválida.");
    document.getElementById("cantidad").focus();
    return;
  }

  // ✅ precio real desde BD
  const data = await obtenerPrecioReal(producto_id, tipo);
  if (data.error) {
    mostrarMensaje?.("❌ Error", data.error);
    return;
  }
  const precio = parseFloat(data.precio);

  // ✅ ACUMULAR si ya existe el mismo producto con el mismo tipo
  const idx = carrito.findIndex(x =>
    x.producto_id === parseInt(producto_id) && x.tipo === tipo
  );

  if (idx !== -1) {
    carrito[idx].cantidad += cantidad;
    carrito[idx].precio = precio;
    renderizarTabla();
    limpiarFormulario();
    input.focus();
    return;
  }

  carrito.push({
    producto_id: parseInt(producto_id),
    nombre,
    tipo,
    cantidad,
    precio
  });

  renderizarTabla();
  limpiarFormulario();
  input.focus();
}

function eliminarProducto(index) {
  carrito.splice(index, 1);
  renderizarTabla();
}

function renderizarTabla() {
  const tbody = document.querySelector("#tabla_detalle tbody");
  tbody.innerHTML = "";
  total = 0;

  carrito.forEach((item, index) => {
    const subtotal = item.precio * item.cantidad;
    total += subtotal;

    tbody.innerHTML += `
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

  document.getElementById("total").innerText = total.toFixed(2);
}

// ✅ Confirmar venta (guardar en BD)
const btnConfirmar = document.getElementById("btn_confirmar");
if (btnConfirmar) {
  btnConfirmar.addEventListener("click", async () => {
    if (carrito.length === 0) {
      mostrarMensaje?.("⚠️ Atención", "Carrito vacío.");
      return;
    }

    const data = await fetchJSON(`${BASE_URL}/controladores/venta_confirmar.php`, { carrito });

    if (!data.ok) {
      mostrarMensaje?.("❌ Error", (data.msg || data.error || "No se pudo registrar la venta"));
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
      // fallback por si no está el modal
      alert("✅ Venta registrada. ID: " + data.venta_id);
      location.reload();
    }

    // ✅ Hook: cuando el usuario presiona Aceptar, recargar
    setTimeout(() => {
      const btnOk = document.getElementById("confirm_btn_ok");
      if (btnOk) {
        btnOk.onclick = () => {
          // cerrar modal si existe
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
      agregarDesdeFormulario();
    }
  }

  // Ctrl + Enter → confirmar
  if (e.key === "Enter" && e.ctrlKey) {
    const btn = document.getElementById("btn_confirmar");
    if (btn && !btn.disabled) btn.click();
  }
});

// ✅ Selecciona cantidad al enfocarla
const cantidadInput = document.getElementById("cantidad");
if (cantidadInput) {
  cantidadInput.addEventListener("focus", () => cantidadInput.select());
}
