-- ============================================================
-- MIGRACIÓN: Protección brute force en login
-- Ejecutar una vez antes de desplegar el nuevo login.php
-- ============================================================

CREATE TABLE IF NOT EXISTS `login_intentos` (
  `clave`           VARCHAR(100) NOT NULL,
  `intentos`        INT          NOT NULL DEFAULT 1,
  `bloqueado_hasta` DATETIME     DEFAULT NULL,
  `ultimo_intento`  TIMESTAMP    NOT NULL DEFAULT current_timestamp()
                                 ON UPDATE current_timestamp(),
  PRIMARY KEY (`clave`),
  KEY `idx_bloqueado` (`bloqueado_hasta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
