// 🔹 Manejo del datalist → obtener ID del producto
const input = document.querySelector('input[list="lista_productos"]');
const hidden = document.getElementById('producto_id');
const options = document.querySelectorAll('#lista_productos option');

input.addEventListener('input', () => {
    hidden.value = '';
    options.forEach(option => {
        if (option.value === input.value) {
            hidden.value = option.dataset.id;
        }
    });
});

// 🔹 Carrito
let carrito = [];
let total = 0;

function limpiarFormulario() {
    input.value = "";
    hidden.value = "";
    document.getElementById("cantidad").value = 1;
    document.getElementById("tipo_venta").value = "unidad";

    // opcional: limpiar texto de stock si existe
    const stockInfo = document.getElementById("stock_info");
    if (stockInfo) stockInfo.innerText = "";
}

// ✅ NUEVO: obtener precio real usando POST JSON
async function obtenerPrecioReal(producto_id, tipo) {
    const res = await fetch("../../controladores/producto_fetch.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: parseInt(producto_id), tipo })
    });
    return await res.json();
}

// (Opcional) obtener stock usando POST JSON
async function obtenerStock(producto_id) {
    const res = await fetch("../../controladores/stock_fetch.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ producto_id: parseInt(producto_id) })
    });
    return await res.json();
}

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

    // ✅ (Opcional) validar stock antes de agregar
    // Si NO quieres validar aquí, puedes comentar este bloque y validar solo al confirmar
    /*
    const stockData = await obtenerStock(producto_id);
    if (stockData.error) { alert(stockData.error); return; }
    // ojo: si tipo paquete necesitas calcular unidades, aquí solo mostramos stock disponible
    */

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

    const res = await fetch("../../controladores/venta_confirmar.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ carrito })
    });

    const data = await res.json();

    if (!data.ok) {
        alert("❌ " + data.msg);
        return;
    }

    alert("✅ Venta registrada. ID: " + data.venta_id);

    carrito = [];
    renderizarTabla();
    limpiarFormulario();
    location.reload();
});
