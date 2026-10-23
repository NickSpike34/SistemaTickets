-- ==========================================================
-- SISTEMA DE SOPORTE TÉCNICO TIPO SEMÁFORO
-- Base de Datos: soporte_tickets
-- Compatible con MySQL 5.7+ / 8.0+ / MariaDB 10.3+
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `soporte_tickets` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `soporte_tickets`;

-- ----------------------------------------------------------
-- 1. Tabla: usuarios_soporte
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `comentarios`;
DROP TABLE IF EXISTS `tickets`;
DROP TABLE IF EXISTS `equipos`;
DROP TABLE IF EXISTS `usuarios_soporte`;

CREATE TABLE `usuarios_soporte` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nombre` VARCHAR(100) NOT NULL,
    `activo` TINYINT(1) DEFAULT 1,
    `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Tabla: equipos
-- ----------------------------------------------------------
CREATE TABLE `equipos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip` VARCHAR(45) NOT NULL UNIQUE,
    `hostname` VARCHAR(100) NOT NULL,
    `inventario` VARCHAR(50) NOT NULL,
    `serie` VARCHAR(100) DEFAULT NULL,
    `modelo` VARCHAR(100) DEFAULT NULL,
    `area` VARCHAR(100) NOT NULL,
    `ubicacion` VARCHAR(150) NOT NULL,
    `extension` VARCHAR(20) DEFAULT NULL,
    `responsable` VARCHAR(150) DEFAULT NULL,
    `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Tabla: tickets
-- ----------------------------------------------------------
CREATE TABLE `tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `folio` VARCHAR(20) NOT NULL UNIQUE,
    `ip` VARCHAR(45) NOT NULL,
    `hostname` VARCHAR(100) DEFAULT NULL,
    `inventario` VARCHAR(50) DEFAULT NULL,
    `area` VARCHAR(100) NOT NULL,
    `problema` VARCHAR(150) NOT NULL,
    `descripcion` TEXT NOT NULL,
    `prioridad` ENUM('Alta', 'Media', 'Baja') DEFAULT 'Media',
    `extension` VARCHAR(20) DEFAULT NULL,
    `estado` ENUM('PENDIENTE', 'EN PROCESO', 'EN ESPERA', 'ATENDIDO', 'CANCELADO') DEFAULT 'PENDIENTE',
    `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `fecha_cierre` DATETIME DEFAULT NULL,
    `usuario_soporte` VARCHAR(100) DEFAULT NULL,
    `comentarios` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. Tabla: comentarios
-- ----------------------------------------------------------
CREATE TABLE `comentarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `usuario_soporte_id` INT DEFAULT NULL,
    `texto` TEXT NOT NULL,
    `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `archivo` VARCHAR(255) DEFAULT NULL,
    CONSTRAINT `fk_comentarios_tickets` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comentarios_usuarios` FOREIGN KEY (`usuario_soporte_id`) REFERENCES `usuarios_soporte` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- DATOS DE PRUEBA
-- ----------------------------------------------------------

-- Usuarios de Soporte (Contraseña: S0p0rt3UMF8)
-- Generados con password_hash('S0p0rt3UMF8', PASSWORD_BCRYPT)
INSERT INTO `usuarios_soporte` (`id`, `usuario`, `password`, `nombre`, `activo`) VALUES
(1, 'Guizardonio', '$2y$10$9fSJamjrYaWCDlRIWJrn1eKBY6nnorRq4Y9Rr3EfFDJWBZpokXg1O', 'Guizar', 1),
(2, 'Checo', '$2y$10$9fSJamjrYaWCDlRIWJrn1eKBY6nnorRq4Y9Rr3EfFDJWBZpokXg1O', 'Sergio', 1);

-- 5 Equipos registrados (Incluye 127.0.0.1 y ::1 para pruebas locales inmediatas)
INSERT INTO `equipos` (`id`, `ip`, `hostname`, `inventario`, `serie`, `modelo`, `area`, `ubicacion`, `extension`, `responsable`) VALUES
(1, '127.0.0.1', 'PC-DESARROLLO-01', 'INV-2026-001', 'SN-DELL-88231', 'Dell OptiPlex 7090', 'Sistemas y TI', 'Edificio Central - Piso 2', '1001', 'Estación de Desarrollo'),
(2, '::1', 'PC-DESARROLLO-LOCAL', 'INV-2026-002', 'SN-HP-99320', 'HP EliteDesk 800 G6', 'Sistemas y TI', 'Edificio Central - Piso 2', '1002', 'Estación Localhost'),
(3, '192.168.1.45', 'PC-CONTAB-03', 'INV-2026-045', 'SN-LEN-45129', 'Lenovo ThinkCentre M70q', 'Contabilidad y Finanzas', 'Edificio A - Piso 1', '2405', 'C.P. Carlos Mendoza'),
(4, '192.168.1.60', 'PC-RH-02', 'INV-2026-060', 'SN-HP-11029', 'HP ProDesk 400 G7', 'Recursos Humanos', 'Edificio B - Planta Baja', '1102', 'Lic. Mariana Gómez'),
(5, '192.168.1.88', 'PC-LOGIST-05', 'INV-2026-088', 'SN-DELL-33211', 'Dell Vostro 3681', 'Logística y Almacén', 'Almacén General - Bahía 3', '3310', 'Ing. Roberto Garza');

-- 3 Tickets activos de ejemplo (Folios con formato ST-2026-XXXX)
INSERT INTO `tickets` (`id`, `folio`, `ip`, `hostname`, `inventario`, `area`, `problema`, `descripcion`, `prioridad`, `extension`, `estado`, `fecha_creacion`, `fecha_actualizacion`, `usuario_soporte`, `comentarios`) VALUES
(1, 'ST-2026-0001', '192.168.1.45', 'PC-CONTAB-03', 'INV-2026-045', 'Contabilidad y Finanzas', 'Falla de impresión en red', 'La impresora departamental no responde al enviar pólizas de cierre mensual.', 'Alta', '2405', 'PENDIENTE', DATE_SUB(NOW(), INTERVAL 45 MINUTE), DATE_SUB(NOW(), INTERVAL 45 MINUTE), NULL, NULL),
(2, 'ST-2026-0002', '192.168.1.60', 'PC-RH-02', 'INV-2026-060', 'Recursos Humanos', 'Lentitud extrema y pantalla congelada', 'El equipo se traba al abrir el sistema de nómina y muestra advertencia de memoria.', 'Media', '1102', 'EN PROCESO', DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 30 MINUTE), 'Sergio', 'Se inició diagnóstico de disco y liberación de memoria RAM.'),
(3, 'ST-2026-0003', '192.168.1.88', 'PC-LOGIST-05', 'INV-2026-088', 'Logística y Almacén', 'Reemplazo de fuente de poder', 'Equipo no enciende después de variación de voltaje en el almacén.', 'Alta', '3310', 'EN ESPERA', DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR), 'Guizar', 'Se solicitó reemplazo de fuente ATX 500W al departamento de compras.');

-- Comentarios de prueba
INSERT INTO `comentarios` (`id`, `ticket_id`, `usuario_soporte_id`, `texto`, `fecha_creacion`, `archivo`) VALUES
(1, 2, 2, 'Diagnóstico preliminar: El ventilador del CPU presentaba acumulación de polvo. Se limpió y se está probando estabilidad.', DATE_SUB(NOW(), INTERVAL 30 MINUTE), NULL),
(2, 3, 1, 'Revisión en sitio completada. Tarjeta madre intacta, fuente dañada. Orden de compra OC-882 generada.', DATE_SUB(NOW(), INTERVAL 1 HOUR), NULL);
