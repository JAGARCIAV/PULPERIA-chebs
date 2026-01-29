<?php
require_once "../config/conexion.php";
require_once "../modelos/turno_modelo.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Método no permitido");
}

$turno = obtenerTurnoAbiertoHoy($conexion);
if (!$turno) {
    header("Location: ../vistas/ventas/venta.php?turno_err=" . urlencode("No hay turno abierto."));
    exit;
}

$turno_id = (int)$turno["id"];

$res = $conexion->query("SELECT COALESCE(MAX(id),0) AS max_id
                         FROM ventas
                         WHERE DATE(fecha)=CURDATE()");
$max_id = (int)$res->fetch_assoc()["max_id"];

$stmt = $conexion->prepare("UPDATE turnos SET historial_desde_venta_id=? WHERE id=?");
$stmt->bind_param("ii", $max_id, $turno_id);
$stmt->execute();

header("Location: ../vistas/ventas/venta.php?hist_ok=1");
exit;
