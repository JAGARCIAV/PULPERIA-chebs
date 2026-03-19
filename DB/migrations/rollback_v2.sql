-- =============================================================================
-- SCRIPT DE REVERSIÓN: REGRESAR A VERSIÓN 1 (QUIRÚRGICO)
-- =============================================================================

-- 1. Eliminar índice si existe
SET @has_idx = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() AND table_name = 'movimientos_inventario' AND index_name = 'idx_mov_referencia');

SET @stmt_drop_idx = IF(@has_idx > 0, "DROP INDEX idx_mov_referencia ON movimientos_inventario", "SELECT 'INFO: No hay índice que borrar' AS log");
PREPARE exec_drop_idx FROM @stmt_drop_idx;
EXECUTE exec_drop_idx;
DEALLOCATE PREPARE exec_drop_idx;

-- 2. Eliminar referencia_id si existe
SET @has_ref_id = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'movimientos_inventario' AND column_name = 'referencia_id');

SET @stmt_drop_id = IF(@has_ref_id > 0, "ALTER TABLE movimientos_inventario DROP COLUMN referencia_id", "SELECT 'INFO: Columna referencia_id no existe' AS log");
PREPARE exec_drop_id FROM @stmt_drop_id;
EXECUTE exec_drop_id;
DEALLOCATE PREPARE exec_drop_id;

-- 3. Eliminar referencia_tipo si existe
SET @has_ref_tipo = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'movimientos_inventario' AND column_name = 'referencia_tipo');

SET @stmt_drop_tipo = IF(@has_ref_tipo > 0, "ALTER TABLE movimientos_inventario DROP COLUMN referencia_tipo", "SELECT 'INFO: Columna referencia_tipo no existe' AS log");
PREPARE exec_drop_tipo FROM @stmt_drop_tipo;
EXECUTE exec_drop_tipo;
DEALLOCATE PREPARE exec_drop_tipo;

-- 4. Limpiar historial de versiones
DELETE FROM schema_version WHERE version = 2;

-- 5. REPORTE
SELECT 'ROLLBACK FINALIZADO' AS status, COALESCE(MAX(version), 1) AS version_actual FROM schema_version;
