<?php
// ✅ Producción: ocultar errores al usuario, registrar internamente
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$_chebs_log_dir = __DIR__ . '/../logs';
if (!is_dir($_chebs_log_dir)) {
    @mkdir($_chebs_log_dir, 0755, true);
}
ini_set('error_log', $_chebs_log_dir . '/php_errors.log');
unset($_chebs_log_dir);

// ✅ Zona horaria PHP (Bolivia)
date_default_timezone_set('America/La_Paz');

// ✅ Credenciales desde archivo externo (no hardcodeadas)
$_db_cfg = __DIR__ . '/db_config.php';
if (!file_exists($_db_cfg)) {
    error_log("CHEBS: db_config.php no encontrado en " . __DIR__);
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(503);
        echo json_encode(["ok" => false, "msg" => "Error de configuración del servidor."]);
    } else {
        http_response_code(503);
        echo "<h2>Configuración incompleta</h2><p>Copia <code>config/db_config.example.php</code> como <code>config/db_config.php</code> y completa tus credenciales.</p>";
    }
    exit;
}
require_once $_db_cfg;
unset($_db_cfg);

$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conexion->connect_error) {
    error_log("CHEBS: Error de conexión MySQLi — " . $conexion->connect_error);
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(503);
        echo json_encode(["ok" => false, "msg" => "No se pudo conectar a la base de datos."]);
    } else {
        http_response_code(503);
        echo "<h2>No se pudo conectar a la base de datos</h2><p>Verifica que MySQL esté activo y que las credenciales en <code>config/db_config.php</code> sean correctas.</p>";
    }
    exit;
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