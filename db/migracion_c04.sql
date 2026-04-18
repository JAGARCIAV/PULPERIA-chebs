-- =============================================================
-- MIGRACIÓN C-04: Integridad de tipos en BD
-- Ejecutar en orden. HACER BACKUP ANTES.
-- =============================================================

-- PASO 1: Verificación previa (debe devolver 0 en ambas)
-- Si alguno devuelve > 0, corregir antes de continuar.
SELECT 'stock_actual NULL' AS check_name, COUNT(*) AS total
  FROM productos WHERE stock_actual IS NULL
UNION ALL
SELECT 'cantidad_unidades negativa', COUNT(*)
  FROM lotes WHERE cantidad_unidades < 0;

-- PASO 2: Limpiar NULLs en stock_actual (si el check anterior lo requirió)
UPDATE productos SET stock_actual = 0 WHERE stock_actual IS NULL;

-- PASO 3: Aplicar restricciones
ALTER TABLE productos
  MODIFY COLUMN stock_actual INT NOT NULL DEFAULT 0;

ALTER TABLE lotes
  MODIFY COLUMN cantidad_unidades INT UNSIGNED NOT NULL DEFAULT 0;

-- PASO 4: Verificación final
DESCRIBE productos;
DESCRIBE lotes;
