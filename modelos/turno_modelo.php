<?php

function obtenerTurnoAbiertoHoy($conexion) {
    $sql = "SELECT *
            FROM turnos
            WHERE fecha = CURDATE() AND estado = 'abierto'
            ORDER BY id DESC
            LIMIT 1";
    $res = $conexion->query($sql);

    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc();
    }
    return null;
}

function abrirTurno($conexion, $responsable, $monto_inicial, $desde_venta_id) {

    // ✅ No permitir 2 turnos abiertos
    $existe = obtenerTurnoAbiertoHoy($conexion);
    if ($existe) {
        return ["ok"=>false, "msg"=>"Ya existe un turno abierto. Ciérrelo primero."];
    }

    $responsable = trim($responsable);
    if ($responsable === "") $responsable = "SIN_USUARIO";

    $monto_inicial = (float)$monto_inicial;
    if ($monto_inicial < 0) $monto_inicial = 0;

    $desde_venta_id = (int)$desde_venta_id;

    $sql = "INSERT INTO turnos
            (fecha, abierto_en, estado, responsable, monto_inicial, historial_desde_venta_id)
            VALUES (CURDATE(), NOW(), 'abierto', ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sdi", $responsable, $monto_inicial, $desde_venta_id);
    $stmt->execute();

    return ["ok"=>true, "msg"=>"Turno abierto", "turno_id"=>$conexion->insert_id];
}

function cerrarTurno($conexion, $turno_id) {
    $turno_id = (int)$turno_id;

    $sql = "UPDATE turnos
            SET estado='cerrado', cerrado_en=NOW()
            WHERE id = ? AND estado='abierto'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $turno_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        return ["ok"=>false, "msg"=>"Ese turno no está abierto o no existe."];
    }

    return ["ok"=>true, "msg"=>"Turno cerrado"];
}

function totalVentasTurno($conexion, $turno_id) {
    $turno_id = (int)$turno_id;

    $sql = "SELECT COALESCE(SUM(v.total),0) total
            FROM ventas v
            WHERE v.turno_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $turno_id);
    $stmt->execute();
    return (float)$stmt->get_result()->fetch_assoc()["total"];
}

function obtenerUltimosTurnos($conexion, $limite = 5) {
    $limite = (int)$limite;

    $sql = "SELECT *
            FROM turnos
            ORDER BY id DESC
            LIMIT ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    return $stmt->get_result();
}
