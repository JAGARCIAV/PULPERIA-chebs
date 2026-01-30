<?php
// modelos/turno_modelo.php

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

// ✅ total retiros del turno (tabla retiros_caja)
function totalRetirosTurno($conexion, $turno_id) {
    $turno_id = (int)$turno_id;

    $stmt = $conexion->prepare("SELECT COALESCE(SUM(monto),0) AS total FROM retiros_caja WHERE turno_id=?");
    $stmt->bind_param("i", $turno_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (float)($row['total'] ?? 0);
}

function totalVentasTurno($conexion, $turno_id) {
    $turno_id = (int)$turno_id;

    $stmt = $conexion->prepare("SELECT COALESCE(SUM(total),0) AS total FROM ventas WHERE turno_id=?");
    $stmt->bind_param("i", $turno_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (float)($row["total"] ?? 0);
}

/**
 * ✅ ABRIR TURNO (usa efectivo_inicial_contado)
 * - Deja todo en 0 y calcula efectivo_esperado = efectivo inicial
 */
function abrirTurno($conexion, $responsable, $usuario_id, $efectivo_inicial_contado, $desde_venta_id = 0) {

    // ❌ No permitir 2 turnos abiertos GLOBAL (como ya lo tenías)
    $existe = obtenerTurnoAbiertoHoy($conexion);
    if ($existe) {
        return ["ok"=>false, "msg"=>"Ya existe un turno abierto. Ciérrelo primero."];
    }

    $responsable = trim($responsable);
    if ($responsable === "") $responsable = "SIN_USUARIO";

    $usuario_id = (int)$usuario_id;

    $efectivo_inicial_contado = (float)$efectivo_inicial_contado;
    if ($efectivo_inicial_contado < 0) $efectivo_inicial_contado = 0;

    $desde_venta_id = (int)$desde_venta_id;

    // Guardamos también monto_inicial por compatibilidad (igual al contado)
    $monto_inicial    = $efectivo_inicial_contado;
    $total_ventas     = 0.00;
    $total_retiros    = 0.00;
    $efectivo_esperado= $efectivo_inicial_contado; // inicial + ventas - retiros (por ahora)
    $diferencia       = 0.00;

    $sql = "INSERT INTO turnos
            (fecha, abierto_en, estado, responsable, usuario_id,
             monto_inicial, historial_desde_venta_id,
             efectivo_inicial_contado, total_ventas, total_retiros, efectivo_esperado, diferencia)
            VALUES
            (CURDATE(), NOW(), 'abierto', ?, ?,
             ?, ?,
             ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return ["ok"=>false, "msg"=>"Error prepare: ".$conexion->error];
    }

    // ✅ OJO: SIN ESPACIOS. Son 9 variables => 9 tipos
    // s (responsable)
    // i (usuario_id)
    // d (monto_inicial)
    // i (desde_venta_id)
    // d (efectivo_inicial_contado)
    // d d d d d (totales/esperado/diferencia)
    $stmt->bind_param(
        "sididdddd",
        $responsable,
        $usuario_id,
        $monto_inicial,
        $desde_venta_id,
        $efectivo_inicial_contado,
        $total_ventas,
        $total_retiros,
        $efectivo_esperado,
        $diferencia
    );

    $ok = $stmt->execute();
    if (!$ok) {
        return ["ok"=>false, "msg"=>"Error al abrir turno: ".$stmt->error];
    }

    return ["ok"=>true, "msg"=>"Turno abierto", "turno_id"=>(int)$conexion->insert_id];
}

/**
 * ✅ CERRAR TURNO
 * - Calcula: total_ventas, total_retiros, efectivo_esperado
 * - Si llega $efectivo_cierre_contado, calcula diferencia
 */
function cerrarTurno($conexion, $turno_id, $efectivo_cierre_contado = null) {
    $turno_id = (int)$turno_id;

    // Traer el turno abierto
    $stmt = $conexion->prepare("SELECT id, efectivo_inicial_contado FROM turnos WHERE id=? AND estado='abierto' LIMIT 1");
    $stmt->bind_param("i", $turno_id);
    $stmt->execute();
    $turno = $stmt->get_result()->fetch_assoc();

    if (!$turno) {
        return ["ok"=>false, "msg"=>"Ese turno no está abierto o no existe."];
    }

    $efectivoInicial = (float)($turno['efectivo_inicial_contado'] ?? 0);

    $totalVentas  = totalVentasTurno($conexion, $turno_id);
    $totalRetiros = totalRetirosTurno($conexion, $turno_id);

    $efectivoEsperado = $efectivoInicial + $totalVentas - $totalRetiros;

    $cierre = null;
    if ($efectivo_cierre_contado !== null) {
        $cierre = (float)$efectivo_cierre_contado;
        if ($cierre < 0) $cierre = 0;
    }

    $diferencia = 0.00;
    if ($cierre !== null) {
        $diferencia = $cierre - $efectivoEsperado;
    }

    // Actualizar turno
    $sqlUp = "UPDATE turnos
              SET estado='cerrado',
                  cerrado_en=NOW(),
                  efectivo_cierre_contado=?,
                  total_ventas=?,
                  total_retiros=?,
                  efectivo_esperado=?,
                  diferencia=?
              WHERE id=? AND estado='abierto'";

    $up = $conexion->prepare($sqlUp);
    if (!$up) {
        return ["ok"=>false, "msg"=>"Error prepare cierre: ".$conexion->error];
    }

    // Si $cierre es null, guardamos NULL en la BD
    // bind_param no acepta null bien con "d" en algunos casos, así que hacemos esto:
    if ($cierre === null) {
        // ponemos 0 pero luego lo dejamos NULL con query aparte (más simple y estable)
        $cierreTemp = 0.00;
        $up->bind_param("dddddi", $cierreTemp, $totalVentas, $totalRetiros, $efectivoEsperado, $diferencia, $turno_id);
    } else {
        $up->bind_param("dddddi", $cierre, $totalVentas, $totalRetiros, $efectivoEsperado, $diferencia, $turno_id);
    }

    $up->execute();

    if ($up->affected_rows === 0) {
        return ["ok"=>false, "msg"=>"No se pudo cerrar el turno (quizá ya fue cerrado)."];
    }

    if ($cierre === null) {
        $fix = $conexion->prepare("UPDATE turnos SET efectivo_cierre_contado=NULL WHERE id=?");
        $fix->bind_param("i", $turno_id);
        $fix->execute();
    }

    $resumen = [
        'turno_id'          => $turno_id,
        'monto_inicial'     => $efectivoInicial,
        'total_ventas'      => $totalVentas,
        'total_retiros'     => $totalRetiros,
        'efectivo_esperado' => $efectivoEsperado,
        'efectivo_cierre'   => $cierre,
        'diferencia'        => $diferencia,
    ];

    return ["ok"=>true, "msg"=>"Turno cerrado", "resumen"=>$resumen];
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

