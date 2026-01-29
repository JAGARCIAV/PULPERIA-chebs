<?php

function turnoActual() {
    // Ajusta la hora de corte si quieres (ej: 14:00)
    return (date("H") < 14) ? "mañana" : "tarde";
}

function totalTurnoHoy($conexion, $turno) {
    $sql = "SELECT COALESCE(SUM(total),0) AS total
            FROM ventas
            WHERE DATE(fecha)=CURDATE()
              AND turno = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $turno);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (float)$row["total"];
}

function existeCierreTurnoHoy($conexion, $turno) {
    $sql = "SELECT id FROM cierres_caja
            WHERE fecha = CURDATE() AND turno = ?
            LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $turno);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res && $res->num_rows > 0;
}

function cerrarCajaTurnoHoy($conexion, $turno, $observacion="") {
    if ($turno !== "mañana" && $turno !== "tarde") $turno = turnoActual();

    if (existeCierreTurnoHoy($conexion, $turno)) {
        return ["ok"=>false, "msg"=>"Ese turno ya fue cerrado hoy."];
    }

    $total = totalTurnoHoy($conexion, $turno);

    $sql = "INSERT INTO cierres_caja (fecha, turno, total_ventas, observacion)
            VALUES (CURDATE(), ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sds", $turno, $total, $observacion);

    if (!$stmt->execute()) {
        return ["ok"=>false, "msg"=>"Error al cerrar caja: ".$stmt->error];
    }

    return ["ok"=>true, "msg"=>"Caja cerrada", "cierre_id"=>$conexion->insert_id];
}

function totalDiaDesdeCierres($conexion) {
    $sql = "SELECT COALESCE(SUM(total_ventas),0) AS total
            FROM cierres_caja
            WHERE fecha = CURDATE()";
    $row = $conexion->query($sql)->fetch_assoc();
    return (float)$row["total"];
}


function obtenerCierresHoy($conexion) {
    $sql = "SELECT * FROM cierres_caja
            WHERE fecha = CURDATE()
            ORDER BY turno ASC";
    return $conexion->query($sql);
}
