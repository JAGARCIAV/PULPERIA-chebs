<?php
function obtenerUsuarioPorUsername($conexion, $usuario) {
    $sql = "SELECT * FROM usuarios WHERE usuario = ? AND activo = 1 LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
