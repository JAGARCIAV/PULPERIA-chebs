<?php
require_once __DIR__ . "/../../config/conexion.php";

class Venta {

    // Crear venta (cabecera)
    public static function crearVenta() {
        $con = Conexion::conectar();

        $sql = "INSERT INTO ventas (fecha, total)
                VALUES (NOW(), 0)";
        $stmt = $con->prepare($sql);
        $stmt->execute();

        return $con->lastInsertId(); // id de la venta
    }

    // Actualizar total de la venta
    public static function actualizarTotal($venta_id) {
        $con = Conexion::conectar();

        $sql = "UPDATE ventas
                SET total = (
                    SELECT SUM(subtotal)
                    FROM detalle_venta
                    WHERE venta_id = ?
                )
                WHERE id = ?";
        $stmt = $con->prepare($sql);
        $stmt->execute([$venta_id, $venta_id]);
    }
}