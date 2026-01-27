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

// 🔹 Carrito de venta
let carrito = [];
let total = 0;

function agregarDesdeFormulario() {
    const nombre = input.value;
    const producto_id = hidden.value;
    const tipo = document.getElementById("tipo_venta").value;
    const cantidad = parseInt(document.getElementById("cantidad").value);

    if (!producto_id || cantidad <= 0) {
        alert("Seleccione un producto válido");
        return;
    }

    // ⚠️ Precio temporal (luego AJAX)
    const precio = (tipo === "unidad") ? 2.5 : 10;

    carrito.push({
        producto_id,
        nombre,
        tipo,
        cantidad,
        precio
    });

    renderizarTabla();
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
                <td>
                    <button onclick="eliminarProducto(${index})">❌</button>
                </td>
            </tr>
        `;
    });

    document.getElementById("total").innerText = total.toFixed(2);
}

/* =====================================================
   🔲 FUTURO: LECTOR DE CÓDIGO DE BARRAS
   -----------------------------------------------------
   El lector funciona como un teclado:
   - Escribe el código
   - Presiona ENTER
   - Se captura el código
   - Se busca el producto por AJAX
===================================================== */

/*
document.getElementById("codigo_barras").addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
        e.preventDefault();

        const codigo = this.value.trim();
        if (!codigo) return;

        buscarProductoPorCodigo(codigo);
        this.value = "";
    }
});

function buscarProductoPorCodigo(codigo) {
    fetch("../../controllers/buscarProductoCodigo.php?codigo=" + codigo)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert("Producto no encontrado");
                return;
            }

            agregarProducto({
                nombre: data.nombre,
                tipo: "unidad",
                cantidad: 1,
                precio: data.precio
            });
        });
}
*/