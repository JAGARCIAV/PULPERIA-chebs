-- ============================================================
-- MIGRACIÓN V3: Auditoría de anulaciones y correcciones
-- Compatible con MariaDB 10.0+ (XAMPP Windows)
-- Ejecutar UNA VEZ antes de actualizar el código PHP.
-- Es seguro ejecutar múltiples veces (IF NOT EXISTS).
-- ============================================================

ALTER TABLE ventas
  ADD COLUMN IF NOT EXISTS anulado_por   INT NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS anulado_en    DATETIME NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS corregido_por INT NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS corregido_en  DATETIME NULL DEFAULT NULL;

SELECT 'Migración V3 aplicada — auditoría de anulaciones y correcciones' AS status;
