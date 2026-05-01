-- ============================================================
-- FIX: MABELS WAFER VAINILLA 100G no aparece en productos ni ventas
-- Problema: producto quedó con activo=0 (desactivado)
-- Base de datos: tienda
-- Ejecutar en: phpMyAdmin → base tienda → pestaña SQL
-- ============================================================

UPDATE productos
SET activo = 1
WHERE nombre = 'MABELS WAFER VAINILLA 100G'
  AND activo = 0;

-- Verificar resultado (debe mostrar activo=1 y stock=4)
SELECT
    p.id,
    p.nombre,
    p.activo,
    p.barcode,
    SUM(l.cantidad_unidades) AS stock_disponible
FROM productos p
LEFT JOIN lotes l ON l.producto_id = p.id
    AND l.activo = 1
    AND l.cantidad_unidades > 0
    AND (l.fecha_vencimiento IS NULL OR l.fecha_vencimiento >= CURDATE())
WHERE p.nombre = 'MABELS WAFER VAINILLA 100G'
GROUP BY p.id;
