<?php
require_once "../config/conexion.php";
require_once "../modelos/caja_turno_modelo.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Método no permitido");
}

$turno = $_POST["turno"] ?? turnoActual();
$obs = $_POST["observacion"] ?? "";

$r = cerrarCajaTurnoHoy($conexion, $turno, $obs);

if (!$r["ok"]) {
    header("Location: ../vistas/ventas/venta.php?turno_err=" . urlencode($r["msg"]));
    exit;
}

header("Location: ../vistas/ventas/venta.php?turno_ok=1");
exit;
