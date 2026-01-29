<?php
require_once "../config/conexion.php";
require_once "../modelos/turno_modelo.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Método no permitido");
}

$responsable = $_POST["responsable"] ?? "SIN_USUARIO";
$monto = isset($_POST["monto_inicial"]) ? (float)$_POST["monto_inicial"] : 0;

// ✅ última venta (para marcar desde dónde empieza el historial)
$res = $conexion->query("SELECT COALESCE(MAX(id),0) id FROM ventas");
$desde = (int)$res->fetch_assoc()["id"];

$result = abrirTurno($conexion, $responsable, $monto, $desde);

if (!$result["ok"]) {
    header("Location: ../vistas/ventas/venta.php?turno_err=" . urlencode($result["msg"]));
    exit;
}

header("Location: ../vistas/ventas/venta.php?turno_ok=1");
exit;
