<?php
class Conexion {
    public static function conectar() {
        $host = "localhost";
        $db   = "tienda";   // el nombre de tu BD
        $user = "root";
        $pass = "";

        try {
            $con = new PDO(
                "mysql:host=$host;dbname=$db;charset=utf8",
                $user,
                $pass
            );
            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $con;
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
}