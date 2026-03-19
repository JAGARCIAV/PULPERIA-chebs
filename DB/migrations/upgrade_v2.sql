-- =============================================================================
-- SCRIPT DE ACTUALIZACIÓN: VERSIÓN 2 (PULPERÍA CHEBS) - UNIVERSAL HARDENED
-- =============================================================================

-- 1. Garantizar tabla de control
CREATE TABLE IF NOT EXISTS schema_version (
    version INT PRIMARY KEY,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- 2. Registrar V1 si la tabla está virgen
INSERT IGNORE INTO schema_version (version, description) 
VALUES (1, 'Base inicial del sistema (Pre-V2)');

-- 3. APLICAR COLUMNA: referencia_id (Solo si no existe)
SET @has_ref_id = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'movimientos_inventario' AND column_name = 'referencia_id');

SET @stmt = IF(@has_ref_id = 0, 
    "ALTER TABLE movimientos_inventario ADD COLUMN referencia_id INT UNSIGNED NULL AFTER motivo", 
    "SELECT 'INFO: Columna referencia_id ya existe' AS status");
PREPARE exec_stmt FROM @stmt;
EXECUTE exec_stmt;
DEALLOCATE PREPARE exec_stmt;

-- 4. APLICAR COLUMNA: referencia_tipo (Solo si no existe)
SET @has_ref_tipo = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'movimientos_inventario' AND column_name = 'referencia_tipo');

SET @stmt = IF(@has_ref_tipo = 0, 
    "ALTER TABLE movimientos_inventario ADD COLUMN referencia_tipo VARCHAR(20) NULL AFTER referencia_id", 
    "SELECT 'INFO: Columna referencia_tipo ya existe' AS status");
PREPARE exec_stmt FROM @stmt;
EXECUTE exec_stmt;
DEALLOCATE PREPARE exec_stmt;

-- 5. MIGRACIÓN DE DATOS (Idempotente)
UPDATE movimientos_inventario 
SET 
    referencia_tipo = 'venta',
    referencia_id = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(motivo, 'Venta ID ', -1), ' ', 1) AS UNSIGNED)
WHERE 
    referencia_id IS NULL
    AND motivo REGEXP '^(Venta ID|Corrección Venta ID|Devolución Venta ID) [0-9]+';

-- 6. CREAR ÍNDICE (Solo si no existe)
SET @has_idx = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() AND table_name = 'movimientos_inventario' AND index_name = 'idx_mov_referencia');

SET @stmt_idx = IF(@has_idx = 0,
    "CREATE INDEX idx_mov_referencia ON movimientos_inventario (referencia_tipo, referencia_id)",
    "SELECT 'INFO: Indice idx_mov_referencia ya existe' AS status");
PREPARE exec_idx FROM @stmt_idx;
EXECUTE exec_idx;
DEALLOCATE PREPARE exec_idx;

-- 7. REGISTRO FINAL DE VERSIÓN
INSERT IGNORE INTO schema_version (version, description) 
VALUES (2, 'Actualización V2: Trazabilidad relacional de movimientos e inventario');

-- 8. REPORTE FINAL
SELECT 'PROCESO FINALIZADO' AS status, MAX(version) AS version_actual FROM schema_version;
