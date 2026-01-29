<?php
require_once "../config/conexion.php";
require_once "../modelos/turno_modelo.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Método no permitido");
}

$turno_id = isset($_POST["turno_id"]) ? (int)$_POST["turno_id"] : 0;

$result = cerrarTurno($conexion, $turno_id);

if (!$result["ok"]) {
    header("Location: ../vistas/ventas/venta.php?turno_err=" . urlencode($result["msg"]));
    exit;
}

header("Location: ../vistas/ventas/venta.php?turno_cerrado=1");
exit;
