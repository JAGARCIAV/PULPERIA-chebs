<?php
// INCLUDES
include_once "../config/conexion.php";
include_once "../modelos/corregir_venta.php";
// Asegúrate de incluir donde tengas tus funciones existentes:
// actualizarTotalVenta, actualizarCantidadLote, registrarMovimiento
include_once "../modelos/venta_modelo.php"; 
include_once "../modelos/lote_modelo.php";
// include_once "../modelos/producto_modelo.php"; // Si ahí están las de lotes

// INICIAR TRANSACCIÓN (Crucial para inventarios)
$conexion->begin_transaction();

try {
    // 1. Recibir datos del formulario
    $venta_id = isset($_POST['venta_id']) ? (int)$_POST['venta_id'] : 0;
    $cantidades = isset($_POST['cantidad']) ? $_POST['cantidad'] : [];
    $nuevos_productos = isset($_POST['nuevo_producto']) ? $_POST['nuevo_producto'] : []; // Array
    $eliminar = isset($_POST['eliminar']) ? $_POST['eliminar'] : []; // Array de IDs a borrar

    if ($venta_id === 0) {
        throw new Exception("ID de venta inválido.");
    }

    // ==========================================
    // FUNCIONES AUXILIARES DE LÓGICA DE INVENTARIO
    // ==========================================

    // A. Función para devolver productos al inventario (AJUSTE/ENTRADA)
    function devolverAlInventario($conexion, $producto_id, $cantidad) {
        if ($cantidad <= 0) return;
        
        // Buscamos el mejor lote para devolver (el que vence más pronto para que salga rápido)
        $lote = obtenerLoteParaDevolucion($conexion, $producto_id);
        
        if ($lote) {
            $nueva_qty = $lote['cantidad_unidades'] + $cantidad;
            actualizarCantidadLote($conexion, $lote['id'], $nueva_qty);
            registrarMovimiento($conexion, $producto_id, $lote['id'], 'ajuste', $cantidad, 'Se corrigió una venta (Devolución)');
        } else {
            // Si no hay lotes activos, podrías crear uno o lanzar error. 
            // Aquí asumimos que siempre hay un lote base o lanzamos error.
             throw new Exception("No hay lote activo para devolver el producto ID: $producto_id");
        }
    }

    // B. Función para descontar productos del inventario (SALIDA)
    // Maneja múltiples lotes si uno no alcanza
    function descontarDelInventario($conexion, $producto_id, $cantidad_necesaria) {
        if ($cantidad_necesaria <= 0) return;

        $lotes = obtenerLotesPorVencimiento($conexion, $producto_id);
        $pendiente = $cantidad_necesaria;

        while ($lote = $lotes->fetch_assoc()) {
            if ($pendiente <= 0) break;

            $disponible = $lote['cantidad_unidades'];
            
            if ($disponible >= $pendiente) {
                // Este lote alcanza
                $nueva_qty = $disponible - $pendiente;
                actualizarCantidadLote($conexion, $lote['id'], $nueva_qty);
                registrarMovimiento($conexion, $producto_id, $lote['id'], 'salida', $pendiente, 'Se corrigió una venta (Salida)');
                $pendiente = 0;
            } else {
                // Este lote no alcanza, lo vaciamos y seguimos al siguiente
                actualizarCantidadLote($conexion, $lote['id'], 0);
                registrarMovimiento($conexion, $producto_id, $lote['id'], 'salida', $disponible, 'Se corrigió una venta (Salida parcial)');
                $pendiente -= $disponible;
            }
        }

        if ($pendiente > 0) {
            throw new Exception("Stock insuficiente para el producto ID: $producto_id. Faltan $pendiente unidades.");
        }
    }

    // ==========================================
    // PROCESAMIENTO
    // ==========================================

    // Iteramos sobre todos los detalles que venían en el formulario (usando el array de cantidades como base)
    foreach ($cantidades as $id_detalle => $nueva_cantidad) {
        $id_detalle = (int)$id_detalle;
        $nueva_cantidad = (int)$nueva_cantidad;

        // 1. Obtener datos originales ("La foto de antes")
        $detalle_original = obtenerDetalleEspecifico($conexion, $id_detalle);
        if (!$detalle_original) continue;

        $producto_id_original = (int)$detalle_original['producto_id'];
        $cantidad_original = (int)$detalle_original['cantidad'];

        // 2. Verificar si se marcó para ELIMINAR
        if (in_array($id_detalle, $eliminar)) {
            // Devolver todo el stock original
            devolverAlInventario($conexion, $producto_id_original, $cantidad_original);
            // Borrar fila
            eliminarLineaDetalle($conexion, $id_detalle);
            continue; // Saltar al siguiente ciclo
        }

        // 3. Verificar si hubo CAMBIO DE PRODUCTO
        // El input viene como "ID - Nombre" o vacío.
        $input_nuevo_prod = isset($nuevos_productos[$id_detalle]) ? trim($nuevos_productos[$id_detalle]) : '';
        $cambio_producto = false;
        $nuevo_producto_id = $producto_id_original; // Por defecto es el mismo

        if (!empty($input_nuevo_prod)) {
            // Extraer ID del string "15 - Coca Cola"
            $partes = explode('-', $input_nuevo_prod);
            $posible_id = (int)trim($partes[0]);
            
            // Si el ID es diferente al original, es un cambio
            if ($posible_id > 0 && $posible_id !== $producto_id_original) {
                $cambio_producto = true;
                $nuevo_producto_id = $posible_id;
            }
        }

        // 4. LÓGICA DE ACTUALIZACIÓN
        
        if ($cambio_producto) {
            // CASO: CAMBIO DE PRODUCTO
            // A. Devolver TODO el producto viejo
            devolverAlInventario($conexion, $producto_id_original, $cantidad_original);
            
            // B. Descontar TODO el producto nuevo (con la nueva cantidad)
            descontarDelInventario($conexion, $nuevo_producto_id, $nueva_cantidad);

            // C. Obtener precio nuevo y actualizar fila
            $nuevo_precio = obtenerPrecioProducto($conexion, $nuevo_producto_id);
            $nuevo_subtotal = $nuevo_precio * $nueva_cantidad;
            
            actualizarLineaDetalle($conexion, $id_detalle, $nuevo_producto_id, $nueva_cantidad, $nuevo_precio, $nuevo_subtotal);

        } else {
            // CASO: MISMO PRODUCTO, POSIBLE CAMBIO DE CANTIDAD
            if ($nueva_cantidad !== $cantidad_original) {
                $diferencia = $nueva_cantidad - $cantidad_original;

                if ($diferencia > 0) {
                    // Aumentó la cantidad (necesitamos sacar más del inventario)
                    // La diferencia es positiva, ej: 5 (antes) a 8 (ahora) = 3 a descontar
                    descontarDelInventario($conexion, $producto_id_original, $diferencia);
                } else {
                    // Disminuyó la cantidad (necesitamos devolver al inventario)
                    // La diferencia es negativa, ej: 5 (antes) a 2 (ahora) = -3. Usamos abs() -> 3 a devolver
                    devolverAlInventario($conexion, $producto_id_original, abs($diferencia));
                }

                // Actualizar fila (Precio se mantiene, solo cambia subtotal)
                $precio_actual = $detalle_original['precio_unitario'];
                $nuevo_subtotal = $precio_actual * $nueva_cantidad;
                
                actualizarLineaDetalle($conexion, $id_detalle, $producto_id_original, $nueva_cantidad, $precio_actual, $nuevo_subtotal);
            }
        }
    }

    // 5. ACTUALIZAR TOTAL GLOBAL DE LA VENTA
    actualizarTotalVenta($conexion, $venta_id);

    // 6. CONFIRMAR TRANSACCIÓN
    $conexion->commit();

    // Redireccionar con éxito
    header("Location: /PULPERIA-chebs/vistas/ventas/venta.php");
    exit;

} catch (Exception $e) {
    // SI ALGO FALLA, DESHACER TODO
    $conexion->rollback();
    // Puedes redirigir a una página de error o mostrarlo
    die("Error al corregir la venta: " . $e->getMessage() . " <br><a href='javascript:history.back()'>Volver</a>");
}
?>