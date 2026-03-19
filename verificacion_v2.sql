-- Verificar que las nuevas ventas se registran con ID numérico
SELECT id, motivo, referencia_id, referencia_tipo 
FROM movimientos_inventario 
WHERE referencia_tipo = 'venta' 
ORDER BY id DESC LIMIT 10;

-- Verificar existencia de columnas críticas (Red de Seguridad)
SHOW COLUMNS FROM movimientos_inventario WHERE Field IN ('referencia_id', 'referencia_tipo');
