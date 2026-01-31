function abrirModalGeneral(html) {
    document.getElementById("modalContenido").innerHTML = html;
    document.getElementById("modalGeneral").style.display = "flex";
}

function cerrarModalGeneral() {
    document.getElementById("modalGeneral").style.display = "none";
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
