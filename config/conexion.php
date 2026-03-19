<?php
// ✅ Zona horaria PHP (Bolivia)
date_default_timezone_set('America/La_Paz');

$conexion = new mysqli("localhost", "root", "", "tienda", 3306);
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// ✅ charset seguro
$conexion->set_charset("utf8mb4");

// ✅ Zona horaria MySQL (Bolivia -04:00)
// Esto arregla CURDATE(), NOW(), DATE(fecha) y comparaciones por día.
$conexion->query("SET time_zone = '-04:00'");

// --- Red de seguridad V2 (Pre-flight check) ---
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['db_v2_ready'])) {
    // Buscamos ambas columnas en una sola consulta
    $check = $conexion->query("SHOW COLUMNS FROM movimientos_inventario WHERE Field IN ('referencia_id', 'referencia_tipo')");
    
    if ($check && $check->num_rows === 2) {
        $_SESSION['db_v2_ready'] = true; // Cacheamos el éxito en la sesión
    } else {
        // Detectar si es una petición AJAX o una página normal
        $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
                   || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
                   || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($is_ajax) {
            header('Content-Type: application/json');
            http_response_code(503);
            echo json_encode(["ok" => false, "msg" => "Actualización de base de datos requerida (V2)."]);
        } else {
            // Mostrar vista de error limpia
            include __DIR__ . '/../vistas/layout/error_db.php';
        }
        exit;
    }
}
?>