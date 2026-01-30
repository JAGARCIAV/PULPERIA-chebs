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

  // Si el servidor devuelve HTML (error PHP), esto evita que explote el .json()
  const text = await res.text();
  try {
    return JSON.parse(text);
  } catch {
    console.error("Respuesta no JSON:", text);
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
input.addEventListener('input', async () => {
  hidden.value = '';

  // buscar match exacto
  options.forEach(option => {
    if (option.value === input.value) {
      hidden.value = option.dataset.id;
    }
  });

  // (Opcional) Mostrar stock cuando el producto es válido
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

async function agregarDesdeFormulario() {
  const nombre = input.value.trim();
  const producto_id = hidden.value;
  const tipo = document.getElementById("tipo_venta").value;
  const cantidad = parseInt(document.getElementById("cantidad").value);

  if (!producto_id || !nombre) {
    alert("Seleccione un producto válido de la lista");
    return;
  }
  if (!cantidad || cantidad <= 0) {
    alert("Cantidad inválida");
    return;
  }

  // ✅ precio real desde BD
  const data = await obtenerPrecioReal(producto_id, tipo);
  if (data.error) {
    alert(data.error);
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
      <tr>
        <td>${item.nombre}</td>
        <td>${item.tipo}</td>
        <td>${item.cantidad}</td>
        <td>${item.precio.toFixed(2)}</td>
        <td>${subtotal.toFixed(2)}</td>
        <td><button type="button" onclick="eliminarProducto(${index})">❌</button></td>
      </tr>
    `;
  });

  document.getElementById("total").innerText = total.toFixed(2);
}

// ✅ Confirmar venta (guardar en BD)
document.getElementById("btn_confirmar").addEventListener("click", async () => {
  if (carrito.length === 0) {
    alert("Carrito vacío");
    return;
  }

  const data = await fetchJSON(`${BASE_URL}/controladores/venta_confirmar.php`, {
    carrito
  });

  if (!data.ok) {
    alert("❌ " + (data.msg || data.error || "No se pudo registrar la venta"));
    return;
  }

  alert("✅ Venta registrada. ID: " + data.venta_id);

  carrito = [];
  renderizarTabla();
  limpiarFormulario();
  location.reload();
});
