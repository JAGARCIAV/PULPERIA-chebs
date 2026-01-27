<?php
require_once __DIR__ . "/../../config/conexion.php";

class Lote {

    // 🔹 Obtener lotes disponibles de un producto (FIFO)
    public static function obtenerLotesDisponibles($producto_id) {
        $con = Conexion::conectar();

        $sql = "SELECT * FROM lotes
                WHERE producto_id = ?
                AND cantidad_unidades > 0
                ORDER BY fecha_ingreso ASC";

        $stmt = $con->prepare($sql);
        $stmt->execute([$producto_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 Descontar unidades de los lotes
    public static function descontarStock($producto_id, $unidades_a_descontar) {
        $con = Conexion::conectar();
        $lotes = self::obtenerLotesDisponibles($producto_id);

        foreach ($lotes as $lote) {
            if ($unidades_a_descontar <= 0) {
                break;
            }

            $restar = min($lote['cantidad_unidades'], $unidades_a_descontar);

            // actualizar lote
            $sql = "UPDATE lotes
                    SET cantidad_unidades = cantidad_unidades - ?
                    WHERE id = ?";
            $stmt = $con->prepare($sql);
            $stmt->execute([$restar, $lote['id']]);

            $unidades_a_descontar -= $restar;
        }

        // si aún faltan unidades → no había stock suficiente
        if ($unidades_a_descontar > 0) {
            return false;
        }

        return true;
    }

    // 🔹 Obtener stock total disponible de un producto
    public static function obtenerStockDisponible($producto_id)
    {
        $con = Conexion::conectar();

        $sql = "SELECT SUM(cantidad_unidades) AS total
                FROM lotes
                WHERE producto_id = ?
                AND cantidad_unidades > 0";

        $stmt = $con->prepare($sql);
        $stmt->execute([$producto_id]);

        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($res['total'] ?? 0);
    }

}