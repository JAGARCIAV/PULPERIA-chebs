-- Diagnóstico de Salud de la Base de Datos (PULPERÍA CHEBS) - SEGURO
SET @has_schema_table = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() AND table_name = 'schema_version');

-- Obtenemos versión si existe la tabla, sino asumimos 1 (Pre-sistema)
SET @version_reg = IF(@has_schema_table > 0, (SELECT MAX(version) FROM schema_version), 1);

-- Conteo de columnas físicas
SET @cols_v2 = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'movimientos_inventario' AND column_name IN ('referencia_id', 'referencia_tipo'));

-- Conteo de índices físicos
SET @idx_v2 = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() AND table_name = 'movimientos_inventario' AND index_name = 'idx_mov_referencia');

-- DIAGNÓSTICO FINAL
SELECT 
    @version_reg AS version_en_registro,
    @cols_v2 AS columnas_v2_fisicas,
    @idx_v2 AS indices_v2_fisicos,
    CASE 
        WHEN @version_reg >= 2 AND @cols_v2 = 2 AND @idx_v2 = 1 THEN 'ESTADO: OK - LISTO V2'
        WHEN @has_schema_table = 0 THEN 'ESTADO: PENDIENTE (SISTEMA DE MIGRACIONES NO INICIALIZADO)'
        ELSE 'ESTADO: ERROR - ACTUALIZACIÓN INCOMPLETA'
    END AS diagnostico;
