<?php
require_once __DIR__ . "/../../config/conexion.php";

class DetalleVenta {

    public static function agregarDetalle(
        $venta_id,
        $producto_id,
        $tipo_venta,
        $cantidad,
        $precio_unitario,
        $subtotal
    ) {
        $con = Conexion::conectar();

        $sql = "INSERT INTO detalle_venta (
                    venta_id,
                    producto_id,
                    tipo_venta,
                    cantidad,
                    precio_unitario,
                    subtotal
                ) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $con->prepare($sql);
        return $stmt->execute([
            $venta_id,
            $producto_id,
            $tipo_venta,
            $cantidad,
            $precio_unitario,
            $subtotal
        ]);
    }
}