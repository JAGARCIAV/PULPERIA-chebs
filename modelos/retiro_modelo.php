<?php
// modelos/retiro_modelo.php

function registrarRetiroCaja($conexion, $turno_id, $usuario_admin_id, $monto, $motivo = null) {
    $turno_id = (int)$turno_id;
    $usuario_admin_id = (int)$usuario_admin_id;

    $monto = (float)$monto;
    if ($monto <= 0) {
        return ["ok"=>false, "msg"=>"Monto inválido"];
    }

    $motivo = trim((string)$motivo);
    if ($motivo === "") $motivo = null;

    // validar que turno exista y esté abierto
    $t = $conexion->prepare("SELECT id FROM turnos WHERE id=? AND estado='abierto' LIMIT 1");
    $t->bind_param("i", $turno_id);
    $t->execute();
    $ex = $t->get_result()->fetch_assoc();
    if (!$ex) {
        return ["ok"=>false, "msg"=>"No hay turno abierto para retirar."];
    }

    $sql = "INSERT INTO retiros_caja (turno_id, usuario_admin_id, monto, motivo)
            VALUES (?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return ["ok"=>false, "msg"=>"Error prepare: ".$conexion->error];
    }

    $stmt->bind_param("iids", $turno_id, $usuario_admin_id, $monto, $motivo);
    $ok = $stmt->execute();

    if (!$ok) {
        return ["ok"=>false, "msg"=>"Error al guardar retiro: ".$stmt->error];
    }

    return ["ok"=>true, "msg"=>"Retiro registrado", "retiro_id"=>(int)$conexion->insert_id];
}
