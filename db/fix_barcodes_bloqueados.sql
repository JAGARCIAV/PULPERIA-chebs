-- ============================================================
-- FIX: Liberar barcodes de productos desactivados
-- Problema: productos con activo=0 retienen su barcode,
--           impidiendo registrar nuevos productos con ese codigo
-- Ejecutar en: phpMyAdmin → base tienda → pestaña SQL
-- ============================================================

-- Ver cuáles productos están bloqueando barcodes (solo para revisar)
SELECT id, nombre, barcode, activo
FROM productos
WHERE activo = 0 AND barcode IS NOT NULL AND barcode != '';

-- Liberar barcodes de todos los productos desactivados
UPDATE productos
SET barcode = NULL
WHERE activo = 0 AND barcode IS NOT NULL AND barcode != '';

-- Verificar que quedó limpio (debe devolver 0 filas)
SELECT id, nombre, barcode
FROM productos
WHERE activo = 0 AND barcode IS NOT NULL AND barcode != '';
