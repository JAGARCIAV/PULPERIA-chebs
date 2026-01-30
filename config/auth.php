<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (empty($_SESSION['user'])) {
        header("Location: /PULPERIA-CHEBS/vistas/login.php");
        exit;
    }
}

function require_role(array $roles = []) {
    require_login();
    $rol = $_SESSION['user']['rol'] ?? null;

    if (!$rol || (!empty($roles) && !in_array($rol, $roles, true))) {
        http_response_code(403);
        echo "403 - No autorizado";
        exit;
    }
}
