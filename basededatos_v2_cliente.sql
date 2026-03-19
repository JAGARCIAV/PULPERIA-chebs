-- FASE 2C.0: Soporte relacional en movimientos
ALTER TABLE movimientos_inventario 
ADD COLUMN referencia_id INT UNSIGNED NULL AFTER motivo,
ADD COLUMN referencia_tipo VARCHAR(20) NULL AFTER referencia_id;

CREATE INDEX idx_mov_referencia ON movimientos_inventario (referencia_tipo, referencia_id);
