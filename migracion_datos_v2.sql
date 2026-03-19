-- FASE 2C.0: Migración de historial basado en texto a relacional
UPDATE movimientos_inventario 
SET 
    referencia_tipo = 'venta',
    referencia_id = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(motivo, 'Venta ID ', -1), ' ', 1) AS UNSIGNED)
WHERE 
    motivo REGEXP '^(Venta ID|Corrección Venta ID|Devolución Venta ID) [0-9]+';
