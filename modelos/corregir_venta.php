<?php
require_once __DIR__ . '/../config/conexion.php';

/* Función para obtener el detalle de una línea específica antes de modificarla.
   Sirve para saber qué producto y qué cantidad había originalmente.
*/
function obtenerDetalleEspecifico($conexion, $detalle_id) {
    $sql = "SELECT * FROM detalle_venta WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $detalle_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/*
   Función para obtener los lotes de un producto ordenados por fecha de vencimiento (ASC).
   Sirve para descontar inventario (FIFO/FEFO).
*/
function obtenerLotesPorVencimiento($conexion, $producto_id) {
    // Buscamos lotes activos y con stock positivo, ordenados por fecha de vencimiento (el más viejo primero)
    $sql = "SELECT id, cantidad_unidades, fecha_vencimiento 
            FROM lotes 
            WHERE producto_id = ? AND cantidad_unidades > 0 AND activo = 1 
            ORDER BY fecha_vencimiento ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    return $stmt->get_result();
}

/*
   Función para obtener un lote donde devolver productos (Ajuste).
   Busca el lote activo con fecha de vencimiento más próxima (para venderlo pronto de nuevo).
*/
function obtenerLoteParaDevolucion($conexion, $producto_id) {
    $sql = "SELECT id, cantidad_unidades 
            FROM lotes 
            WHERE producto_id = ? AND activo = 1 
            ORDER BY fecha_vencimiento ASC LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/*
   Actualiza la línea del detalle de venta con nuevos valores.
*/
function actualizarLineaDetalle($conexion, $id_detalle, $producto_id, $cantidad, $precio, $subtotal) {
    $sql = "UPDATE detalle_venta 
            SET producto_id = ?, cantidad = ?, precio_unitario = ?, subtotal = ? 
            WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    
    // CORRECCIÓN: "iiddi" (5 letras para 5 variables)
    // i = int, d = double
    $stmt->bind_param("iiddi", $producto_id, $cantidad, $precio, $subtotal, $id_detalle);
    
    return $stmt->execute();
}

/*
   Elimina una línea del detalle.
*/
function eliminarLineaDetalle($conexion, $id_detalle) {
    $sql = "DELETE FROM detalle_venta WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_detalle);
    return $stmt->execute();
}

/*
   Obtiene el precio actual de un producto (para cuando cambiamos de producto).
*/
function obtenerPrecioProducto($conexion, $producto_id) {
    $sql = "SELECT precio_unidad FROM productos WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res ? $res['precio_unidad'] : 0;
}
?>