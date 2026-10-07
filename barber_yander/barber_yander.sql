-- =========================================================
-- BASE DE DATOS: Barbería Yander
-- Compatible con MySQL / MariaDB (XAMPP/phpMyAdmin / MySQL 8)
-- =========================================================

CREATE DATABASE IF NOT EXISTS `barber_yander` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `barber_yander`;

-- =========================================================
-- 1. TABLA: usuarios
-- =========================================================
DROP TABLE IF EXISTS `password_reset_codes`;
DROP TABLE IF EXISTS `citas`;
DROP TABLE IF EXISTS `usuarios`;

CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `firebase_uid` VARCHAR(128) NULL UNIQUE COMMENT 'UID único del usuario en Firebase Auth si aplica',
  `nombre` VARCHAR(100) NOT NULL,
  `correo` VARCHAR(100) NOT NULL UNIQUE,
  `telefono` VARCHAR(20) NULL,
  `tipo_doc` VARCHAR(10) NULL COMMENT 'cc, ti, ce, etc.',
  `num_doc` VARCHAR(30) NULL,
  `contrasena` VARCHAR(255) NOT NULL,
  `rol` ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
  `estado` ENUM('activo', 'bloqueado') NOT NULL DEFAULT 'activo',
  `bloqueado_desde` DATETIME NULL,
  `bloqueado_hasta` DATETIME NULL,
  `motivo_bloqueo` VARCHAR(255) NULL,
  `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_usuarios_correo` (`correo`),
  INDEX `idx_usuarios_firebase` (`firebase_uid`),
  INDEX `idx_usuarios_rol` (`rol`),
  INDEX `idx_usuarios_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 2. TABLA: servicios
-- Catálogo de servicios con precios y emojis
-- =========================================================
DROP TABLE IF EXISTS `servicios`;

CREATE TABLE `servicios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `firebase_id` VARCHAR(128) NULL UNIQUE COMMENT 'ID del documento en Firestore',
  `nombre` VARCHAR(100) NOT NULL UNIQUE,
  `precio` DECIMAL(10, 2) NOT NULL,
  `emoji` VARCHAR(20) NULL,
  `imagen` VARCHAR(255) NULL,
  `descripcion` TEXT NULL,
  `duracion_minutos` INT NOT NULL DEFAULT 50,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 3. TABLA: citas
-- Gestión y reservas de citas
-- =========================================================
CREATE TABLE `citas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `firebase_id` VARCHAR(128) NULL UNIQUE COMMENT 'ID del documento en Firestore',
  `id_usuario` INT NOT NULL,
  `id_servicio` INT NOT NULL,
  `fecha_cita` DATE NOT NULL,
  `hora_cita` TIME NOT NULL,
  `estado` ENUM('pendiente', 'confirmada', 'completada', 'cancelada') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_citas_usuario` 
    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_citas_servicio` 
    FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id`) 
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX `idx_citas_fecha_hora` (`fecha_cita`, `hora_cita`),
  INDEX `idx_citas_estado` (`estado`),
  INDEX `idx_citas_usuario` (`id_usuario`),
  INDEX `idx_citas_servicio` (`id_servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 4. TABLA: password_reset_codes
-- Gestión de códigos de verificación temporal de 6 dígitos
-- =========================================================
CREATE TABLE `password_reset_codes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `codigo_hash` VARCHAR(255) NOT NULL,
  `intentos` INT NOT NULL DEFAULT 0,
  `usado` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_expiracion` DATETIME NOT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_reset_usuario` 
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_reset_usuario` (`usuario_id`),
  INDEX `idx_reset_usado` (`usado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- DATOS INICIALES (SEED DATA)
-- =========================================================

-- Inserción de Usuarios de Prueba con contraseñas encriptadas Bcrypt
-- Admin: admin@barberyander.com / admin123
-- Cliente: cliente@gmail.com / cliente123
INSERT INTO `usuarios` (`nombre`, `correo`, `telefono`, `tipo_doc`, `num_doc`, `contrasena`, `rol`) VALUES
('Administrador Yander', 'admin@barberyander.com', '3225236255', 'cc', '1000000001', '$2y$10$33DquBhTD4XHuvdGcsVdoeRH56mrrfLXCyZ6BWFHevWeAQP20ph9m', 'admin'),
('Carlos Perez', 'cliente@gmail.com', '3001234567', 'cc', '1000000002', '$2y$10$K3k4Q.W17pSVYwOHkr4UfuTcK0YWKhY1xhe3qNGpO2GP5k2TGQqO2', 'cliente');

-- Inserción de los Servicios que se muestran en el sitio web
INSERT INTO `servicios` (`nombre`, `precio`, `emoji`, `imagen`, `descripcion`, `duracion_minutos`) VALUES
('Corte de Cabello', 15000.00, '💈', 'img/trabajo1.jpg', 'Corte clásico o moderno con perfilado profesional.', 50),
('Arreglo de Barba', 10000.00, '🧔', 'img/trabajo2.jpg', 'Delineado, afeitado tradicional y toalla caliente.', 30),
('Corte + Barba', 25000.00, '💇‍♂️🧔', 'img/trabajo3.jpg', 'Combo completo de corte de cabello y diseño de barba.', 60),
('Tinte de Cabello', 90000.00, '🎨', 'img/trabajo4.jpg', 'Coloración, decoloración y aplicación de tintes profesionales.', 90),
('Arreglo de Cejas', 5000.00, '✂️', 'img/trabajo5.jpg', 'Limpieza y perfilado de cejas con navaja.', 20),
('Servicio Premium', 80000.00, '⭐', 'img/trabajo6.jpg', 'Experiencia completa: Corte, barba, mascarilla facial y exfoliación.', 80);

-- Cita de ejemplo inicial
INSERT INTO `citas` (`id_usuario`, `id_servicio`, `fecha_cita`, `hora_cita`, `estado`) VALUES
(2, 1, CURDATE(), '10:00:00', 'confirmada');
