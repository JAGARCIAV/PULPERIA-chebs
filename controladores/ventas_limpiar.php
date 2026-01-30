<?php
require_once __DIR__ . "/../config/auth.php";
require_role(['admin','empleado']);
?>


<?php
require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Método no permitido");
}

// Mostrar errores SQL si falla algo
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion->begin_transaction();

    // 1) Borrar detalles de ventas de HOY
    $sql1 = "DELETE d
             FROM detalle_venta d
             INNER JOIN ventas v ON v.id = d.venta_id
             WHERE DATE(v.fecha) = CURDATE()";
    $conexion->query($sql1);

    // 2) Borrar ventas de HOY
    $sql2 = "DELETE FROM ventas
             WHERE DATE(fecha) = CURDATE()";
    $conexion->query($sql2);

    $conexion->commit();

    header("Location: ../vistas/ventas/venta.php?limpio=1");
    exit;

} catch (Exception $e) {
    $conexion->rollback();
    die("Error limpiando historial: " . $e->getMessage());
}
