<?php
require_once __DIR__ . "/../../config/conexion.php";

class Producto {

    public static function obtenerTodos() {
        $con = Conexion::conectar();
        $sql = "SELECT * FROM productos WHERE activo = 1";
        $stmt = $con->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerPorId($id) {
        $con = Conexion::conectar();
        $sql = "SELECT * FROM productos WHERE id = ?";
        $stmt = $con->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}