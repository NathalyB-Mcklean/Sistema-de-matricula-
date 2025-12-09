-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 08, 2025 at 10:53 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `matricula`
--

-- --------------------------------------------------------

--
-- Table structure for table `auditoria`
--

DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id_auditoria` int NOT NULL AUTO_INCREMENT,
  `usuario` varchar(100) DEFAULT NULL,
  `accion` varchar(255) DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_auditoria`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `auditoria`
--

INSERT INTO `auditoria` (`id_auditoria`, `usuario`, `accion`, `fecha`) VALUES
(1, 'Samuel De Luque', 'Creó estudiantes de prueba', '2025-12-06 10:00:00'),
(2, 'Samuel De Luque', 'Creó horarios y grupos', '2025-12-06 10:30:00'),
(3, 'Samuel De Luque', 'Registró matrículas de prueba', '2025-12-06 11:00:00'),
(4, 'Samuel De Luque', 'Activó período 2025-2', '2025-12-06 11:30:00'),
(5, 'Samuel De Luque', 'Eliminó estudiante ID: 1', '2025-12-08 12:32:18'),
(6, 'Samuel De Luque', 'Eliminó estudiante ID: 1', '2025-12-08 12:32:23');

-- --------------------------------------------------------

--
-- Table structure for table `carreras`
--

DROP TABLE IF EXISTS `carreras`;
CREATE TABLE IF NOT EXISTS `carreras` (
  `id_carrera` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `codigo` varchar(20) DEFAULT NULL,
  `duracion_semestres` int DEFAULT '10',
  `creditos_totales` int DEFAULT '180',
  `nivel_academico` enum('Técnico','Licenciatura','Ingeniería','Maestría','Doctorado') DEFAULT 'Ingeniería',
  `area_conocimiento` varchar(100) DEFAULT NULL,
  `perfil_egreso` text,
  `campo_laboral` text,
  `estado` enum('activa','inactiva') DEFAULT 'activa',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_carrera`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `carreras`
--

INSERT INTO `carreras` (`id_carrera`, `nombre`, `descripcion`, `codigo`, `duracion_semestres`, `creditos_totales`, `nivel_academico`, `area_conocimiento`, `perfil_egreso`, `campo_laboral`, `estado`, `fecha_creacion`) VALUES
(1, 'Ingeniería de Software', 'Formación de profesionales capaces de diseñar, desarrollar, implementar y mantener sistemas de software de calidad, aplicando principios de ingeniería y metodologías ágiles.', 'ISW-001', 8, 180, 'Ingeniería', 'Tecnologías de la Información', 'Profesional capaz de analizar, diseñar, desarrollar, implementar y mantener sistemas de software complejos, gestionar proyectos tecnológicos y liderar equipos de desarrollo.', 'Empresas de desarrollo de software, departamentos de TI, consultoría tecnológica, emprendimiento en tecnología.', 'activa', '2025-12-05 20:55:32'),
(2, 'Biomecánica', 'Formación interdisciplinaria que combina principios de ingeniería, biología y medicina para analizar sistemas biológicos y desarrollar soluciones tecnológicas en salud.', 'BIO-002', 8, 180, 'Ingeniería', 'Ingeniería Biomédica', 'Profesional capaz de aplicar principios mecánicos en sistemas biológicos, diseñar dispositivos médicos, analizar movimiento humano y contribuir a la investigación biomédica.', 'Hospitales, centros de rehabilitación, empresas de dispositivos médicos, investigación biomédica, ortopedia.', 'activa', '2025-12-05 20:55:32');

-- --------------------------------------------------------

--
-- Table structure for table `docentes`
--

DROP TABLE IF EXISTS `docentes`;
CREATE TABLE IF NOT EXISTS `docentes` (
  `id_docente` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(50) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `titulo_academico` varchar(100) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) NOT NULL,
  `años_experiencia` int DEFAULT '0',
  `estado` enum('activo','inactivo','licencia') DEFAULT 'activo',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_docente`),
  UNIQUE KEY `cedula` (`cedula`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `docentes`
--

INSERT INTO `docentes` (`id_docente`, `cedula`, `nombre`, `apellido`, `titulo_academico`, `especialidad`, `telefono`, `correo`, `años_experiencia`, `estado`, `fecha_creacion`) VALUES
(16, '8-123-456', 'Carlos', 'Mendoza', 'Doctor en Ciencias de la Computación', 'Inteligencia Artificial', '6001-1234', 'carlos.mendoza@utp.ac.pa', 10, 'activo', '2025-12-06 16:10:05'),
(17, '8-234-567', 'María', 'González', 'MSc. en Ingeniería de Software', 'Desarrollo Web', '6001-2345', 'maria.gonzalez@utp.ac.pa', 7, 'activo', '2025-12-06 16:10:05'),
(18, '8-345-678', 'Roberto', 'Sánchez', 'Ing. en Sistemas', 'Bases de Datos', '6001-3456', 'roberto.sanchez@utp.ac.pa', 5, 'activo', '2025-12-06 16:10:05'),
(19, '8-456-789', 'Ana', 'Fernández', 'Doctor en Biomecánica', 'Biomecánica Deportiva', '6001-4567', 'ana.fernandez@utp.ac.pa', 12, 'activo', '2025-12-06 16:10:05'),
(20, '8-567-890', 'Jorge', 'López', 'MSc. en Biomateriales', 'Biomateriales', '6001-5678', 'jorge.lopez@utp.ac.pa', 8, 'activo', '2025-12-06 16:10:05'),
(21, '8-678-901', 'Patricia', 'Ramírez', 'Ing. en Mecatrónica', 'Robótica', '6001-6789', 'patricia.ramirez@utp.ac.pa', 6, 'activo', '2025-12-06 16:10:05'),
(22, '8-789-012', 'Luis', 'Martínez', 'Lic. en Educación', 'Comunicación', '6001-7890', 'luis.martinez@utp.ac.pa', 15, 'activo', '2025-12-06 16:10:05'),
(23, '8-890-123', 'Sandra', 'Vega', 'MSc. en Redes', 'Redes y Seguridad', '6001-8901', 'sandra.vega@utp.ac.pa', 9, 'activo', '2025-12-06 16:10:05'),
(24, '8-901-234', 'Miguel', 'Castro', 'Doctor en Física', 'Física Aplicada', '6001-9012', 'miguel.castro@utp.ac.pa', 20, 'activo', '2025-12-06 16:10:05'),
(25, '8-012-345', 'Elena', 'Rodríguez', 'MSc. en Matemáticas', 'Matemáticas Discretas', '6001-0123', 'elena.rodriguez@utp.ac.pa', 10, 'activo', '2025-12-06 16:10:05'),
(26, '8-123-789', 'David', 'Herrera', 'Ing. en Software', 'Arquitectura de Software', '6002-1234', 'david.herrera@utp.ac.pa', 8, 'activo', '2025-12-06 16:10:05'),
(27, '8-234-890', 'Laura', 'Morales', 'MSc. en IA', 'Machine Learning', '6002-2345', 'laura.morales@utp.ac.pa', 6, 'activo', '2025-12-06 16:10:05'),
(28, '8-345-901', 'Juan', 'Pérez', 'Doctor en Medicina', 'Anatomía Humana', '6002-3456', 'juan.perez@utp.ac.pa', 18, 'activo', '2025-12-06 16:10:05'),
(29, '8-456-012', 'Carmen', 'Díaz', 'MSc. en Rehabilitación', 'Terapia Física', '6002-4567', 'carmen.diaz@utp.ac.pa', 9, 'activo', '2025-12-06 16:10:05'),
(30, '8-567-123', 'Ricardo', 'Torres', 'Ing. Electrónico', 'Instrumentación Médica', '6002-5678', 'ricardo.torres@utp.ac.pa', 11, 'activo', '2025-12-06 16:10:05');

-- --------------------------------------------------------

--
-- Table structure for table `encuestas`
--

DROP TABLE IF EXISTS `encuestas`;
CREATE TABLE IF NOT EXISTS `encuestas` (
  `id_encuesta` int NOT NULL AUTO_INCREMENT,
  `id_estudiante` int DEFAULT NULL,
  `satisfaccion` enum('Excelente','Conforme','Inconforme','No respondida') NOT NULL,
  `observaciones` text,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_encuesta`),
  KEY `id_estudiante` (`id_estudiante`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `encuestas`
--

INSERT INTO `encuestas` (`id_encuesta`, `id_estudiante`, `satisfaccion`, `observaciones`, `fecha`) VALUES
(1, 8, 'Inconforme', 'Espero que la próxima esté más bonita la página. ', '2025-12-08 17:17:57');

-- --------------------------------------------------------

--
-- Table structure for table `estudiantes`
--

DROP TABLE IF EXISTS `estudiantes`;
CREATE TABLE IF NOT EXISTS `estudiantes` (
  `id_estudiante` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(50) NOT NULL,
  `id_usuario` int NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `año_carrera` int DEFAULT '1',
  `semestre_actual` int DEFAULT '1',
  `fecha_ingreso` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `id_carrera` int DEFAULT NULL,
  PRIMARY KEY (`id_estudiante`),
  UNIQUE KEY `cedula` (`cedula`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_carrera` (`id_carrera`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `estudiantes`
--

INSERT INTO `estudiantes` (`id_estudiante`, `cedula`, `id_usuario`, `telefono`, `año_carrera`, `semestre_actual`, `fecha_ingreso`, `id_carrera`) VALUES
(1, '8-888-001', 19, '6000-1001', 2, 1, '2025-08-01 05:00:00', 1),
(2, '8-888-002', 20, '6000-1002', 1, 1, '2025-08-01 05:00:00', 1),
(3, '8-888-003', 21, '6000-1003', 2, 1, '2024-08-01 05:00:00', 1),
(4, '8-888-004', 22, '6000-1004', 1, 1, '2025-08-01 05:00:00', 2),
(5, '8-888-005', 23, '6000-1005', 2, 1, '2024-08-01 05:00:00', 2),
(6, '8-888-006', 24, '6000-1006', 1, 1, '2025-08-01 05:00:00', 2),
(7, '8-412-790', 26, '', 1, 1, '2025-12-08 19:33:11', NULL),
(8, '8-360-601', 27, '', 1, 1, '2025-12-08 19:56:52', 1);

-- --------------------------------------------------------

--
-- Table structure for table `grupos_horarios_materia`
--

DROP TABLE IF EXISTS `grupos_horarios_materia`;
CREATE TABLE IF NOT EXISTS `grupos_horarios_materia` (
  `id_ghm` int NOT NULL AUTO_INCREMENT,
  `id_materia` int NOT NULL,
  `id_horario` int NOT NULL,
  `aula` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_ghm`),
  KEY `id_materia` (`id_materia`),
  KEY `id_horario` (`id_horario`)
) ENGINE=MyISAM AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `grupos_horarios_materia`
--

INSERT INTO `grupos_horarios_materia` (`id_ghm`, `id_materia`, `id_horario`, `aula`) VALUES
(1, 1, 1, 'Aula 101'),
(2, 1, 6, 'Aula 102'),
(3, 2, 2, 'Aula 103'),
(4, 2, 7, 'Aula 104'),
(5, 3, 3, 'Lab. Computación 1'),
(6, 3, 8, 'Lab. Computación 2'),
(7, 4, 4, 'Aula 201'),
(8, 4, 9, 'Aula 202'),
(9, 5, 5, 'Aula 105'),
(10, 5, 10, 'Aula 106'),
(11, 6, 11, 'Lab. Computación 1'),
(12, 6, 16, 'Lab. Computación 2'),
(13, 7, 12, 'Aula 203'),
(14, 7, 17, 'Aula 204'),
(15, 8, 13, 'Aula 205'),
(16, 8, 18, 'Aula 206'),
(17, 9, 14, 'Lab. Física'),
(18, 9, 19, 'Lab. Física'),
(19, 10, 15, 'Aula 107'),
(20, 10, 20, 'Aula 108'),
(21, 11, 21, 'Lab. Computación 1'),
(22, 11, 1, 'Lab. Computación 3'),
(23, 12, 2, 'Aula 207'),
(24, 12, 7, 'Aula 208'),
(25, 13, 3, 'Aula 209'),
(26, 13, 8, 'Aula 210'),
(27, 14, 4, 'Lab. Bases de Datos'),
(28, 14, 9, 'Lab. Bases de Datos'),
(29, 15, 5, 'Aula 109'),
(30, 15, 10, 'Aula 110'),
(31, 16, 11, 'Aula 301'),
(32, 16, 16, 'Aula 302'),
(33, 17, 12, 'Lab. Programación 1'),
(34, 17, 17, 'Lab. Programación 2'),
(35, 18, 13, 'Lab. Bases de Datos'),
(36, 18, 18, 'Lab. Bases de Datos'),
(37, 19, 14, 'Aula 303'),
(38, 19, 19, 'Aula 304'),
(39, 20, 15, 'Aula 305'),
(40, 20, 20, 'Aula 306'),
(41, 41, 1, 'Aula 401'),
(42, 41, 11, 'Aula 402'),
(43, 42, 2, 'Lab. Anatomía'),
(44, 42, 12, 'Lab. Anatomía'),
(45, 43, 3, 'Aula 403'),
(46, 43, 13, 'Aula 404'),
(47, 44, 4, 'Lab. Física Biomédic'),
(48, 44, 14, 'Lab. Física Biomédic'),
(49, 45, 5, 'Lab. Química'),
(50, 45, 15, 'Lab. Química'),
(51, 46, 6, 'Lab. Anatomía'),
(52, 46, 16, 'Lab. Anatomía'),
(53, 47, 7, 'Aula 405'),
(54, 47, 17, 'Aula 406'),
(55, 48, 8, 'Aula 407'),
(56, 48, 18, 'Aula 408'),
(57, 49, 9, 'Lab. Mecánica'),
(58, 49, 19, 'Lab. Mecánica'),
(59, 50, 10, 'Lab. Bioquímica'),
(60, 50, 20, 'Lab. Bioquímica'),
(61, 51, 21, 'Lab. Biomecánica'),
(62, 51, 1, 'Lab. Biomecánica'),
(63, 52, 2, 'Aula 409'),
(64, 52, 12, 'Aula 410'),
(65, 53, 3, 'Lab. Mecánica'),
(66, 53, 13, 'Lab. Mecánica'),
(67, 54, 4, 'Aula 501'),
(68, 54, 14, 'Aula 502'),
(69, 55, 5, 'Lab. Electrónica'),
(70, 55, 15, 'Lab. Electrónica'),
(71, 56, 6, 'Lab. Biomecánica'),
(72, 56, 16, 'Lab. Biomecánica'),
(73, 57, 7, 'Aula 503'),
(74, 57, 17, 'Aula 504'),
(75, 58, 8, 'Lab. Fluidos'),
(76, 58, 18, 'Lab. Fluidos'),
(77, 59, 9, 'Lab. Instrumentación'),
(78, 59, 19, 'Lab. Instrumentación'),
(79, 60, 10, 'Lab. Computación 4'),
(80, 60, 20, 'Lab. Computación 4');

-- --------------------------------------------------------

--
-- Table structure for table `grupos_periodo`
--

DROP TABLE IF EXISTS `grupos_periodo`;
CREATE TABLE IF NOT EXISTS `grupos_periodo` (
  `id_grupo_periodo` int NOT NULL AUTO_INCREMENT,
  `id_ghm` int NOT NULL,
  `id_periodo` int NOT NULL,
  `cupo_maximo` int DEFAULT '30',
  `cupo_actual` int DEFAULT '0',
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id_grupo_periodo`),
  KEY `id_ghm` (`id_ghm`),
  KEY `id_periodo` (`id_periodo`)
) ENGINE=MyISAM AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `grupos_periodo`
--

INSERT INTO `grupos_periodo` (`id_grupo_periodo`, `id_ghm`, `id_periodo`, `cupo_maximo`, `cupo_actual`, `estado`) VALUES
(1, 1, 1, 30, 15, 'activo'),
(2, 2, 1, 30, 15, 'activo'),
(3, 3, 1, 30, 15, 'activo'),
(4, 4, 1, 30, 15, 'activo'),
(5, 5, 1, 30, 15, 'activo'),
(6, 6, 1, 30, 15, 'activo'),
(7, 7, 1, 30, 15, 'activo'),
(8, 8, 1, 30, 15, 'activo'),
(9, 9, 1, 30, 15, 'activo'),
(10, 10, 1, 30, 15, 'activo'),
(11, 11, 1, 30, 15, 'activo'),
(12, 12, 1, 30, 15, 'activo'),
(13, 13, 1, 30, 15, 'activo'),
(14, 14, 1, 30, 15, 'activo'),
(15, 15, 1, 30, 15, 'activo'),
(16, 16, 1, 30, 15, 'activo'),
(17, 17, 1, 30, 15, 'activo'),
(18, 18, 1, 30, 15, 'activo'),
(19, 19, 1, 30, 15, 'activo'),
(20, 20, 1, 30, 15, 'activo'),
(21, 21, 1, 30, 15, 'activo'),
(22, 22, 1, 30, 15, 'activo'),
(23, 23, 1, 30, 15, 'activo'),
(24, 24, 1, 30, 15, 'activo'),
(25, 25, 1, 30, 15, 'activo'),
(26, 26, 1, 30, 15, 'activo'),
(27, 27, 1, 30, 15, 'activo'),
(28, 28, 1, 30, 15, 'activo'),
(29, 29, 1, 30, 15, 'activo'),
(30, 30, 1, 30, 15, 'activo'),
(31, 31, 1, 30, 15, 'activo'),
(32, 32, 1, 30, 15, 'activo'),
(33, 33, 1, 30, 15, 'activo'),
(34, 34, 1, 30, 15, 'activo'),
(35, 35, 1, 30, 15, 'activo'),
(36, 36, 1, 30, 15, 'activo'),
(37, 37, 1, 30, 15, 'activo'),
(38, 38, 1, 30, 15, 'activo'),
(39, 39, 1, 30, 15, 'activo'),
(40, 40, 1, 30, 15, 'activo'),
(41, 41, 1, 30, 15, 'activo'),
(42, 42, 1, 30, 15, 'activo'),
(43, 43, 1, 30, 15, 'activo'),
(44, 44, 1, 30, 15, 'activo'),
(45, 45, 1, 30, 15, 'activo'),
(46, 46, 1, 30, 15, 'activo'),
(47, 47, 1, 30, 15, 'activo'),
(48, 48, 1, 30, 15, 'activo'),
(49, 49, 1, 30, 15, 'activo'),
(50, 50, 1, 30, 15, 'activo'),
(51, 51, 1, 30, 15, 'activo'),
(52, 52, 1, 30, 15, 'activo'),
(53, 53, 1, 30, 15, 'activo'),
(54, 54, 1, 30, 15, 'activo'),
(55, 55, 1, 30, 15, 'activo'),
(56, 56, 1, 30, 15, 'activo'),
(57, 57, 1, 30, 15, 'activo'),
(58, 58, 1, 30, 15, 'activo'),
(59, 59, 1, 30, 15, 'activo'),
(60, 60, 1, 30, 15, 'activo'),
(61, 61, 1, 30, 15, 'activo'),
(62, 62, 1, 30, 15, 'activo'),
(63, 63, 1, 30, 15, 'activo'),
(64, 64, 1, 30, 15, 'activo'),
(65, 65, 1, 30, 15, 'activo'),
(66, 66, 1, 30, 15, 'activo'),
(67, 67, 1, 30, 15, 'activo'),
(68, 68, 1, 30, 15, 'activo'),
(69, 69, 1, 30, 15, 'activo'),
(70, 70, 1, 30, 15, 'activo'),
(71, 71, 1, 30, 15, 'activo'),
(72, 72, 1, 30, 15, 'activo'),
(73, 73, 1, 30, 15, 'activo'),
(74, 74, 1, 30, 15, 'activo'),
(75, 75, 1, 30, 15, 'activo'),
(76, 76, 1, 30, 15, 'activo'),
(77, 77, 1, 30, 15, 'activo'),
(78, 78, 1, 30, 15, 'activo'),
(79, 79, 1, 30, 15, 'activo'),
(80, 80, 1, 30, 15, 'activo'),
(81, 1, 2, 30, 1, 'activo'),
(82, 2, 2, 30, 1, 'activo'),
(83, 3, 2, 30, 0, 'activo'),
(84, 4, 2, 30, 0, 'activo'),
(85, 5, 2, 30, 0, 'activo'),
(86, 6, 2, 30, 0, 'activo'),
(87, 7, 2, 30, 0, 'activo'),
(88, 8, 2, 30, 0, 'activo'),
(89, 9, 2, 30, 1, 'activo'),
(90, 10, 2, 30, 1, 'activo'),
(91, 11, 2, 30, 0, 'activo'),
(92, 12, 2, 30, 0, 'activo'),
(93, 13, 2, 30, 0, 'activo'),
(94, 14, 2, 30, 0, 'activo'),
(95, 15, 2, 30, 0, 'activo'),
(96, 16, 2, 30, 0, 'activo'),
(97, 17, 2, 30, 0, 'activo'),
(98, 18, 2, 30, 0, 'activo'),
(99, 19, 2, 30, 0, 'activo'),
(100, 20, 2, 30, 0, 'activo'),
(101, 21, 2, 30, 0, 'activo'),
(102, 22, 2, 30, 0, 'activo'),
(103, 23, 2, 30, 0, 'activo'),
(104, 24, 2, 30, 0, 'activo'),
(105, 25, 2, 30, 0, 'activo'),
(106, 26, 2, 30, 0, 'activo'),
(107, 27, 2, 30, 0, 'activo'),
(108, 28, 2, 30, 0, 'activo'),
(109, 29, 2, 30, 0, 'activo'),
(110, 30, 2, 30, 0, 'activo'),
(111, 31, 2, 30, 0, 'activo'),
(112, 32, 2, 30, 0, 'activo'),
(113, 33, 2, 30, 0, 'activo'),
(114, 34, 2, 30, 0, 'activo'),
(115, 35, 2, 30, 0, 'activo'),
(116, 36, 2, 30, 0, 'activo'),
(117, 37, 2, 30, 0, 'activo'),
(118, 38, 2, 30, 0, 'activo'),
(119, 39, 2, 30, 0, 'activo'),
(120, 40, 2, 30, 0, 'activo'),
(121, 41, 2, 30, 0, 'activo'),
(122, 42, 2, 30, 0, 'activo'),
(123, 43, 2, 30, 0, 'activo'),
(124, 44, 2, 30, 0, 'activo'),
(125, 45, 2, 30, 0, 'activo'),
(126, 46, 2, 30, 0, 'activo'),
(127, 47, 2, 30, 0, 'activo'),
(128, 48, 2, 30, 0, 'activo'),
(129, 49, 2, 30, 0, 'activo'),
(130, 50, 2, 30, 0, 'activo'),
(131, 51, 2, 30, 0, 'activo'),
(132, 52, 2, 30, 0, 'activo'),
(133, 53, 2, 30, 0, 'activo'),
(134, 54, 2, 30, 0, 'activo'),
(135, 55, 2, 30, 0, 'activo'),
(136, 56, 2, 30, 0, 'activo'),
(137, 57, 2, 30, 0, 'activo'),
(138, 58, 2, 30, 0, 'activo'),
(139, 59, 2, 30, 0, 'activo'),
(140, 60, 2, 30, 0, 'activo'),
(141, 61, 2, 30, 0, 'activo'),
(142, 62, 2, 30, 0, 'activo'),
(143, 63, 2, 30, 0, 'activo'),
(144, 64, 2, 30, 0, 'activo'),
(145, 65, 2, 30, 0, 'activo'),
(146, 66, 2, 30, 0, 'activo'),
(147, 67, 2, 30, 0, 'activo'),
(148, 68, 2, 30, 0, 'activo'),
(149, 69, 2, 30, 0, 'activo'),
(150, 70, 2, 30, 0, 'activo'),
(151, 71, 2, 30, 0, 'activo'),
(152, 72, 2, 30, 0, 'activo'),
(153, 73, 2, 30, 0, 'activo'),
(154, 74, 2, 30, 0, 'activo'),
(155, 75, 2, 30, 0, 'activo'),
(156, 76, 2, 30, 0, 'activo'),
(157, 77, 2, 30, 0, 'activo'),
(158, 78, 2, 30, 0, 'activo'),
(159, 79, 2, 30, 0, 'activo'),
(160, 80, 2, 30, 0, 'activo');

-- --------------------------------------------------------

--
-- Table structure for table `horarios`
--

DROP TABLE IF EXISTS `horarios`;
CREATE TABLE IF NOT EXISTS `horarios` (
  `id_horario` int NOT NULL AUTO_INCREMENT,
  `dia` varchar(20) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  PRIMARY KEY (`id_horario`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `horarios`
--

INSERT INTO `horarios` (`id_horario`, `dia`, `hora_inicio`, `hora_fin`) VALUES
(1, 'Lunes', '08:00:00', '10:00:00'),
(2, 'Lunes', '10:00:00', '12:00:00'),
(3, 'Lunes', '14:00:00', '16:00:00'),
(4, 'Lunes', '16:00:00', '18:00:00'),
(5, 'Lunes', '18:00:00', '20:00:00'),
(6, 'Martes', '08:00:00', '10:00:00'),
(7, 'Martes', '10:00:00', '12:00:00'),
(8, 'Martes', '14:00:00', '16:00:00'),
(9, 'Martes', '16:00:00', '18:00:00'),
(10, 'Martes', '18:00:00', '20:00:00'),
(11, 'Miércoles', '08:00:00', '10:00:00'),
(12, 'Miércoles', '10:00:00', '12:00:00'),
(13, 'Miércoles', '14:00:00', '16:00:00'),
(14, 'Miércoles', '16:00:00', '18:00:00'),
(15, 'Miércoles', '18:00:00', '20:00:00'),
(16, 'Jueves', '08:00:00', '10:00:00'),
(17, 'Jueves', '10:00:00', '12:00:00'),
(18, 'Jueves', '14:00:00', '16:00:00'),
(19, 'Jueves', '16:00:00', '18:00:00'),
(20, 'Jueves', '18:00:00', '20:00:00'),
(21, 'Viernes', '08:00:00', '10:00:00'),
(22, 'Viernes', '10:00:00', '12:00:00'),
(23, 'Viernes', '14:00:00', '16:00:00'),
(24, 'Viernes', '16:00:00', '18:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `materias`
--

DROP TABLE IF EXISTS `materias`;
CREATE TABLE IF NOT EXISTS `materias` (
  `id_materia` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `costo` decimal(10,2) DEFAULT '0.00',
  `id_carrera` int NOT NULL,
  `id_docente` int DEFAULT NULL,
  PRIMARY KEY (`id_materia`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `id_carrera` (`id_carrera`),
  KEY `fk_materias_docentes` (`id_docente`)
) ENGINE=MyISAM AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `materias`
--

INSERT INTO `materias` (`id_materia`, `codigo`, `nombre`, `descripcion`, `costo`, `id_carrera`, `id_docente`) VALUES
(1, 'ISW-001', 'Introducción a la Ingeniería de Software', 'Fundamentos de la ingeniería de software y su importancia en el desarrollo tecnológico', 150.00, 1, 16),
(2, 'ISW-002', 'Matemática Discreta', 'Lógica matemática, teoría de conjuntos, relaciones y funciones aplicadas a la computación', 150.00, 1, 25),
(3, 'ISW-003', 'Algoritmos y Programación I', 'Fundamentos de programación, estructuras de control y algoritmos básicos', 150.00, 1, 17),
(4, 'ISW-004', 'Fundamentos de Computación', 'Arquitectura de computadoras, sistemas operativos y redes básicas', 150.00, 1, 23),
(5, 'ISW-005', 'Comunicación Efectiva', 'Habilidades de comunicación oral y escrita para ingenieros', 150.00, 1, 22),
(6, 'ISW-006', 'Algoritmos y Programación II', 'Estructuras de datos, recursión y algoritmos avanzados', 150.00, 1, 17),
(7, 'ISW-007', 'Cálculo Diferencial', 'Límites, derivadas y aplicaciones en ingeniería', 150.00, 1, 25),
(8, 'ISW-008', 'Álgebra Lineal', 'Matrices, vectores y espacios vectoriales aplicados', 150.00, 1, 25),
(9, 'ISW-009', 'Física para Computación', 'Principios físicos aplicados a sistemas computacionales', 150.00, 1, 24),
(10, 'ISW-010', 'Inglés Técnico I', 'Vocabulario y comunicación técnica en inglés', 150.00, 1, 22),
(11, 'ISW-011', 'Estructuras de Datos', 'Implementación y uso de estructuras de datos complejas', 150.00, 1, 17),
(12, 'ISW-012', 'Cálculo Integral', 'Integrales y aplicaciones en ingeniería', 150.00, 1, 25),
(13, 'ISW-013', 'Probabilidad y Estadística', 'Análisis probabilístico y estadístico para ingeniería', 150.00, 1, 25),
(14, 'ISW-014', 'Bases de Datos I', 'Diseño e implementación de bases de datos relacionales', 150.00, 1, 18),
(15, 'ISW-015', 'Inglés Técnico II', 'Comunicación técnica avanzada en inglés', 150.00, 1, 22),
(16, 'ISW-016', 'Análisis y Diseño de Sistemas', 'Metodologías de análisis y diseño de sistemas de información', 150.00, 1, 16),
(17, 'ISW-017', 'Programación Orientada a Objetos', 'Principios de POO, clases, objetos y herencia', 150.00, 1, 17),
(18, 'ISW-018', 'Bases de Datos II', 'Bases de datos avanzadas, NoSQL y optimización', 150.00, 1, 18),
(19, 'ISW-019', 'Arquitectura de Software', 'Patrones de diseño y arquitecturas software', 150.00, 1, 26),
(20, 'ISW-020', 'Ingeniería de Requisitos', 'Técnicas para elicitar, analizar y gestionar requisitos', 150.00, 1, 16),
(21, 'ISW-021', 'Desarrollo Web Avanzado', 'Tecnologías web modernas y frameworks', 150.00, 1, 17),
(22, 'ISW-022', 'Desarrollo Móvil', 'Aplicaciones para dispositivos móviles', 150.00, 1, 17),
(23, 'ISW-023', 'Redes de Computadoras', 'Protocolos, arquitecturas y seguridad en redes', 150.00, 1, 23),
(24, 'ISW-024', 'Sistemas Operativos', 'Funcionamiento y administración de sistemas operativos', 150.00, 1, 23),
(25, 'ISW-025', 'Pruebas de Software', 'Metodologías y herramientas para testing', 150.00, 1, 16),
(26, 'ISW-026', 'Inteligencia Artificial', 'Algoritmos de IA y machine learning', 150.00, 1, 27),
(27, 'ISW-027', 'Seguridad Informática', 'Principios y técnicas de ciberseguridad', 150.00, 1, 23),
(28, 'ISW-028', 'Computación en la Nube', 'Arquitecturas cloud y servicios en la nube', 150.00, 1, 26),
(29, 'ISW-029', 'Gestión de Proyectos de Software', 'Metodologías ágiles y gestión de proyectos', 150.00, 1, 16),
(30, 'ISW-030', 'Diseño de Interfaces de Usuario', 'UX/UI y experiencia de usuario', 150.00, 1, 17),
(31, 'ISW-031', 'Proyecto de Software I', 'Desarrollo de proyecto software integral', 150.00, 1, 16),
(32, 'ISW-032', 'DevOps y CI/CD', 'Integración y despliegue continuo', 150.00, 1, 26),
(33, 'ISW-033', 'Big Data', 'Procesamiento y análisis de grandes volúmenes de datos', 150.00, 1, 18),
(34, 'ISW-034', 'Ética Profesional', 'Ética en la ingeniería de software', 150.00, 1, 22),
(35, 'ISW-035', 'Electiva I', 'Materia electiva según especialización', 150.00, 1, 27),
(36, 'ISW-036', 'Proyecto de Software II', 'Proyecto final de carrera', 150.00, 1, 16),
(37, 'ISW-037', 'Práctica Profesional', 'Experiencia laboral supervisada', 150.00, 1, 16),
(38, 'ISW-038', 'Seminario de Graduación', 'Preparación para la vida profesional', 150.00, 1, 22),
(39, 'ISW-039', 'Electiva II', 'Materia electiva según especialización', 150.00, 1, 23),
(40, 'ISW-040', 'Electiva III', 'Materia electiva según especialización', 150.00, 1, 17),
(41, 'BIO-041', 'Introducción a la Biomecánica', 'Fundamentos y aplicaciones de la biomecánica', 150.00, 2, 19),
(42, 'BIO-042', 'Anatomía Humana I', 'Estructura y función del cuerpo humano', 150.00, 2, 28),
(43, 'BIO-043', 'Matemática I', 'Cálculo y álgebra para ingeniería', 150.00, 2, 25),
(44, 'BIO-044', 'Física General', 'Principios fundamentales de física', 150.00, 2, 24),
(45, 'BIO-045', 'Química General', 'Principios químicos aplicados', 150.00, 2, 24),
(46, 'BIO-046', 'Anatomía Humana II', 'Sistemas muscular y esquelético', 150.00, 2, 28),
(47, 'BIO-047', 'Fisiología Humana I', 'Funcionamiento de sistemas corporales', 150.00, 2, 28),
(48, 'BIO-048', 'Matemática II', 'Cálculo avanzado para ingeniería', 150.00, 2, 25),
(49, 'BIO-049', 'Estática', 'Equilibrio de cuerpos rígidos', 150.00, 2, 24),
(50, 'BIO-050', 'Bioquímica', 'Principios químicos en sistemas biológicos', 150.00, 2, 24),
(51, 'BIO-051', 'Biomecánica del Movimiento', 'Análisis cinemático y cinético', 150.00, 2, 19),
(52, 'BIO-052', 'Fisiología Humana II', 'Sistemas cardiovascular y respiratorio', 150.00, 2, 28),
(53, 'BIO-053', 'Dinámica', 'Movimiento de partículas y cuerpos rígidos', 150.00, 2, 24),
(54, 'BIO-054', 'Resistencia de Materiales', 'Comportamiento mecánico de materiales', 150.00, 2, 24),
(55, 'BIO-055', 'Electrónica Básica', 'Circuitos y componentes electrónicos', 150.00, 2, 30),
(56, 'BIO-056', 'Biomecánica de Tejidos', 'Propiedades mecánicas de tejidos biológicos', 150.00, 2, 19),
(57, 'BIO-057', 'Bioestadística', 'Análisis estadístico en ciencias biológicas', 150.00, 2, 25),
(58, 'BIO-058', 'Mecánica de Fluidos', 'Comportamiento de fluidos en sistemas biológicos', 150.00, 2, 24),
(59, 'BIO-059', 'Instrumentación Biomédica I', 'Sensores y equipos médicos', 150.00, 2, 30),
(60, 'BIO-060', 'Programación para Biomecánica', 'Fundamentos de programación aplicada', 150.00, 2, 17),
(61, 'BIO-061', 'Diseño de Prótesis', 'Principios de diseño protésico', 150.00, 2, 19),
(62, 'BIO-062', 'Biomateriales', 'Materiales compatibles con sistemas biológicos', 150.00, 2, 20),
(63, 'BIO-063', 'Mecatrónica Aplicada', 'Sistemas mecatrónicos en biomecánica', 150.00, 2, 21),
(64, 'BIO-064', 'Instrumentación Biomédica II', 'Equipos avanzados de diagnóstico', 150.00, 2, 30),
(65, 'BIO-065', 'Termodinámica Biológica', 'Transferencia de calor en sistemas biológicos', 150.00, 2, 24),
(66, 'BIO-066', 'Diseño de Órtesis', 'Diseño de dispositivos de asistencia', 150.00, 2, 19),
(67, 'BIO-067', 'Rehabilitación Asistida', 'Tecnologías para rehabilitación', 150.00, 2, 29),
(68, 'BIO-068', 'Procesamiento de Señales Biomédicas', 'Análisis de señales fisiológicas', 150.00, 2, 30),
(69, 'BIO-069', 'Control de Sistemas', 'Sistemas de control aplicados', 150.00, 2, 21),
(70, 'BIO-070', 'Biomecánica Deportiva', 'Aplicaciones en ciencias del deporte', 150.00, 2, 19),
(71, 'BIO-071', 'Proyecto de Biomecánica I', 'Desarrollo de proyecto integral', 150.00, 2, 19),
(72, 'BIO-072', 'Regulación y Normativa', 'Regulaciones de dispositivos médicos', 150.00, 2, 22),
(73, 'BIO-073', 'Gestión de Proyectos Biomédicos', 'Gestión de proyectos en salud', 150.00, 2, 16),
(74, 'BIO-074', 'Electiva I', 'Materia electiva según especialización', 150.00, 2, 20),
(75, 'BIO-075', 'Electiva II', 'Materia electiva según especialización', 150.00, 2, 21),
(76, 'BIO-076', 'Proyecto de Biomecánica II', 'Proyecto final de carrera', 150.00, 2, 19),
(77, 'BIO-077', 'Práctica Profesional', 'Experiencia laboral supervisada', 150.00, 2, 19),
(78, 'BIO-078', 'Seminario de Investigación', 'Metodología de investigación', 150.00, 2, 22),
(79, 'BIO-079', 'Bioética', 'Ética en ingeniería biomédica', 150.00, 2, 22),
(80, 'BIO-080', 'Electiva III', 'Materia electiva según especialización', 150.00, 2, 30);

-- --------------------------------------------------------

--
-- Table structure for table `matriculas`
--

DROP TABLE IF EXISTS `matriculas`;
CREATE TABLE IF NOT EXISTS `matriculas` (
  `id_matricula` int NOT NULL AUTO_INCREMENT,
  `id_estudiante` int NOT NULL,
  `id_ghm` int NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_periodo` int DEFAULT NULL,
  PRIMARY KEY (`id_matricula`),
  KEY `id_estudiante` (`id_estudiante`),
  KEY `id_ghm` (`id_ghm`),
  KEY `id_periodo` (`id_periodo`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `matriculas`
--

INSERT INTO `matriculas` (`id_matricula`, `id_estudiante`, `id_ghm`, `fecha`, `id_periodo`) VALUES
(1, 1, 1, '2025-08-01 09:00:00', 2),
(2, 1, 3, '2025-08-01 10:00:00', 2),
(3, 1, 5, '2025-08-01 14:00:00', 2),
(4, 2, 2, '2025-08-02 08:00:00', 2),
(5, 2, 4, '2025-08-02 10:00:00', 2),
(6, 3, 7, '2025-08-02 14:00:00', 2),
(7, 3, 11, '2025-08-02 16:00:00', 2),
(8, 4, 41, '2025-08-03 08:00:00', 2),
(9, 4, 43, '2025-08-03 10:00:00', 2),
(10, 5, 51, '2025-08-03 14:00:00', 2),
(11, 5, 55, '2025-08-03 16:00:00', 2),
(12, 6, 61, '2025-08-04 18:00:00', 2),
(13, 6, 65, '2025-08-04 08:00:00', 2),
(14, 8, 1, '2025-12-08 16:46:03', 2),
(15, 8, 2, '2025-12-08 17:15:09', 2),
(16, 8, 9, '2025-12-08 17:16:39', 2),
(17, 8, 10, '2025-12-08 17:16:39', 2);

-- --------------------------------------------------------

--
-- Table structure for table `periodos_academicos`
--

DROP TABLE IF EXISTS `periodos_academicos`;
CREATE TABLE IF NOT EXISTS `periodos_academicos` (
  `id_periodo` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(20) NOT NULL,
  `año` int NOT NULL,
  `semestre` int NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('activo','inactivo','planificado') DEFAULT 'planificado',
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_periodo`),
  UNIQUE KEY `nombre` (`nombre`)
) ;

--
-- Dumping data for table `periodos_academicos`
--

INSERT INTO `periodos_academicos` (`id_periodo`, `nombre`, `año`, `semestre`, `fecha_inicio`, `fecha_fin`, `estado`, `fecha_registro`) VALUES
(1, '2025-1', 2025, 1, '2025-03-01', '2025-07-30', 'inactivo', '2025-12-05 20:12:18'),
(2, '2025-2', 2025, 2, '2025-08-01', '2025-12-20', 'activo', '2025-12-05 20:12:18'),
(3, '2024-2', 2024, 2, '2024-08-01', '2024-12-20', 'inactivo', '2025-12-05 20:12:18'),
(4, '2024-1', 2024, 1, '2024-03-01', '2024-07-30', 'inactivo', '2025-12-05 20:12:18');

-- --------------------------------------------------------

--
-- Table structure for table `plan_estudios`
--

DROP TABLE IF EXISTS `plan_estudios`;
CREATE TABLE IF NOT EXISTS `plan_estudios` (
  `id_plan` int NOT NULL AUTO_INCREMENT,
  `id_materia` int NOT NULL,
  `nivel` int NOT NULL,
  `semestre` int DEFAULT '1',
  `año` int DEFAULT '1',
  PRIMARY KEY (`id_plan`),
  KEY `id_materia` (`id_materia`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `plan_estudios`
--

INSERT INTO `plan_estudios` (`id_plan`, `id_materia`, `nivel`, `semestre`, `año`) VALUES
(1, 1, 1, 1, 1),
(2, 2, 1, 1, 1),
(3, 3, 1, 1, 1),
(4, 4, 1, 1, 1),
(5, 5, 1, 1, 1),
(6, 6, 2, 1, 1),
(7, 7, 2, 1, 1),
(8, 8, 2, 1, 1),
(9, 9, 2, 1, 1),
(10, 10, 2, 1, 1),
(11, 41, 1, 1, 1),
(12, 42, 1, 1, 1),
(13, 43, 1, 1, 1),
(14, 44, 1, 1, 1),
(15, 45, 1, 1, 1),
(16, 46, 2, 1, 1),
(17, 47, 2, 1, 1),
(18, 48, 2, 1, 1),
(19, 49, 2, 1, 1),
(20, 50, 2, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(20) NOT NULL DEFAULT 'estudiante',
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`)
) ;

--
-- Dumping data for table `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre`, `apellido`, `correo`, `password`, `rol`, `estado`) VALUES
(1, 'Samuel', 'De Luque', 'vegetta777@utp.ac.pa', '$2y$10$I8EglvY8z1GZ4M4u8eOb9.oF9Moj9m8oKMfP3TFRHr7vOXA0wTIP2', 'admin', 'activo'),
(25, 'Mariah', 'Carey', 'mariah.carey@utp.ac.pa', '$2y$10$wYasZFBSZv1MnitJLhgbl.HQxLy0vzzMjAwpULJL7ksqK1yPpVBmu', 'estudiante', 'activo'),
(4, 'Carlos', 'Mendoza', 'carlos.mendoza@utp.ac.pa', 'hashed_password1', 'docente', 'activo'),
(5, 'María', 'González', 'maria.gonzalez@utp.ac.pa', 'hashed_password2', 'docente', 'activo'),
(6, 'Roberto', 'Sánchez', 'roberto.sanchez@utp.ac.pa', 'hashed_password3', 'docente', 'activo'),
(7, 'Ana', 'Fernández', 'ana.fernandez@utp.ac.pa', 'hashed_password4', 'docente', 'activo'),
(8, 'Jorge', 'López', 'jorge.lopez@utp.ac.pa', 'hashed_password5', 'docente', 'activo'),
(9, 'Patricia', 'Ramírez', 'patricia.ramirez@utp.ac.pa', 'hashed_password6', 'docente', 'activo'),
(10, 'Luis', 'Martínez', 'luis.martinez@utp.ac.pa', 'hashed_password7', 'docente', 'activo'),
(11, 'Sandra', 'Vega', 'sandra.vega@utp.ac.pa', 'hashed_password8', 'docente', 'activo'),
(12, 'Miguel', 'Castro', 'miguel.castro@utp.ac.pa', 'hashed_password9', 'docente', 'activo'),
(13, 'Elena', 'Rodríguez', 'elena.rodriguez@utp.ac.pa', 'hashed_password10', 'docente', 'activo'),
(14, 'David', 'Herrera', 'david.herrera@utp.ac.pa', 'hashed_password11', 'docente', 'activo'),
(15, 'Laura', 'Morales', 'laura.morales@utp.ac.pa', 'hashed_password12', 'docente', 'activo'),
(16, 'Juan', 'Pérez', 'juan.perez@utp.ac.pa', 'hashed_password13', 'docente', 'activo'),
(17, 'Carmen', 'Díaz', 'carmen.diaz@utp.ac.pa', 'hashed_password14', 'docente', 'activo'),
(18, 'Ricardo', 'Torres', 'ricardo.torres@utp.ac.pa', 'hashed_password15', 'docente', 'activo'),
(19, 'Ana', 'García', 'ana.garcia@utp.ac.pa', '$2y$10$AbCdEfGhIjKlMnOpQrStUvWxYz1234567890', 'estudiante', 'inactivo'),
(20, 'Luis', 'Rodríguez', 'luis.rodriguez@utp.ac.pa', '$2y$10$BcDeFgHiJkLmNoPqRsTuVwXyZ1234567890', 'estudiante', 'activo'),
(21, 'María', 'Martínez', 'maria.martinez@utp.ac.pa', '$2y$10$CdEfGhIjKlMnOpQrStUvWxYz1234567890', 'estudiante', 'activo'),
(22, 'Carlos', 'Hernández', 'carlos.hernandez@utp.ac.pa', '$2y$10$DeFgHiJkLmNoPqRsTuVwXyZ1234567890', 'estudiante', 'activo'),
(23, 'Laura', 'López', 'laura.lopez@utp.ac.pa', '$2y$10$EfGhIjKlMnOpQrStUvWxYz1234567890', 'estudiante', 'activo'),
(24, 'Pedro', 'González', 'pedro.gonzalez@utp.ac.pa', '$2y$10$FgHiJkLmNoPqRsTuVwXyZ1234567890', 'estudiante', 'activo'),
(26, 'Estudiante', 'Prueba', 'estudiante@utp.ac.pa', '$2y$10$GU3.J7lh7gXXRjeXxAARoeNzkkteMtjMxkCuyZdYx8IYEObd7zkBi', 'estudiante', 'activo'),
(27, 'Nathaly', 'Bonilla', 'nathaly@utp.ac.pa', '$2y$10$402tVojcJvihP7F9jkaZyu90WYALleuKZytVm3Ag/WHEwuzpQuNRy', 'estudiante', 'activo');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
