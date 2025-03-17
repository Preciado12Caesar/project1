-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-03-2025 a las 21:22:35
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `maquinaria_catalogo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_pdf`
--

CREATE TABLE `archivos_pdf` (
  `id_archivo` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo_referencia` enum('tipo_maquinaria','marca','producto') NOT NULL,
  `id_referencia` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre`) VALUES
(1, 'PRODUCTOS'),
(2, 'SERVICIOS');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `marcas`
--

CREATE TABLE `marcas` (
  `id_marca` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `id_tipo_maquinaria` int(11) DEFAULT NULL,
  `pdf_ruta` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `marcas`
--

INSERT INTO `marcas` (`id_marca`, `nombre`, `id_tipo_maquinaria`, `pdf_ruta`) VALUES
(1, 'CAT', 1, NULL),
(2, 'KOMATSU', 1, NULL),
(3, 'VOLVO', 1, NULL),
(4, 'CAT', 2, NULL),
(5, 'KOMATSU', 2, NULL),
(6, 'VOLVO', 2, NULL),
(7, 'CAT', 3, NULL),
(8, 'KOMATSU', 3, NULL),
(9, 'JCB', 3, NULL),
(10, 'CAT', 4, NULL),
(11, 'KOMATSU', 4, NULL),
(12, 'CAT', 5, NULL),
(13, 'KOMATSU', 5, NULL),
(14, 'JOHN DEERE', 5, NULL),
(15, 'CAT', 6, NULL),
(16, 'KOMATSU', 6, NULL),
(17, 'JOHN DEERE', 6, NULL),
(18, 'CAT', 7, NULL),
(19, 'KOMATSU', 7, NULL),
(20, 'JOHN DEERE', 7, NULL),
(21, 'JCB', 7, NULL),
(22, 'CAT', 8, NULL),
(23, 'CASE', 8, 'uploads/pdf/1742065606_30062-SILABO.pdf'),
(24, 'MERLO', 8, NULL),
(25, 'MANITOU', 8, NULL),
(26, 'CAT', 9, 'uploads/pdf/1742065570_30064-SILABO.pdf'),
(27, 'KOMATSU', 9, NULL),
(28, 'CAT', 10, NULL),
(29, 'KOMATSU', 10, NULL),
(30, 'VOLVO', 10, NULL),
(31, 'DOOSAN', 10, NULL),
(32, 'JOHN DEERE', 10, NULL),
(33, 'SELLOS DE CADENAS', 11, NULL),
(34, 'PERNERIA', 11, NULL),
(35, 'BARRAS DE RECALCE', 11, NULL),
(36, 'RESORTE DE TEMPLADOR', 11, NULL),
(37, 'GUIADORES DE CADENA', 11, NULL),
(38, 'PINES Y BOCINAS DE CUCHARA', 11, NULL),
(39, 'GUIADORES DE MOTONIVELADORAS', 11, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `serie` varchar(255) NOT NULL,
  `id_marca` int(11) DEFAULT NULL,
  `pdf_ruta` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `serie`, `id_marca`, `pdf_ruta`) VALUES
(1, 'SERIE J', 1, NULL),
(2, 'SERIE K', 1, NULL),
(3, 'SERIE ADVANSYS', 1, NULL),
(4, 'SERIE KMAX', 2, NULL),
(5, 'SERIE KPRIME', 2, NULL),
(6, 'PIN VERTICAL', 3, NULL),
(7, 'PIN HORIZONTAL', 3, NULL),
(8, 'SERIE J', 4, NULL),
(9, 'SERIE K', 4, NULL),
(10, 'SERIE ADVANSYS', 4, 'uploads/pdf/1741901944_maquetacion_de_pagina_web.pdf'),
(11, 'PIN HORIZONTAL', 5, NULL),
(12, 'SERIE KMAX', 5, NULL),
(13, 'SERIE KPRIME', 5, NULL),
(14, 'SERIE ADVANSYS', 6, NULL),
(15, 'PIN HORIZONTAL', 6, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subcategorias`
--

CREATE TABLE `subcategorias` (
  `id_subcategoria` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `id_categoria` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `subcategorias`
--

INSERT INTO `subcategorias` (`id_subcategoria`, `nombre`, `id_categoria`) VALUES
(1, 'PUNTAS', 1),
(2, 'CUCHILLAS', 1),
(3, 'TREN DE RODAMIENTO', 1),
(4, 'TREN DE RODAMIENTO', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_maquinaria`
--

CREATE TABLE `tipo_maquinaria` (
  `id_tipo_maquinaria` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `id_subcategoria` int(11) DEFAULT NULL,
  `pdf_ruta` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_maquinaria`
--

INSERT INTO `tipo_maquinaria` (`id_tipo_maquinaria`, `nombre`, `id_subcategoria`, `pdf_ruta`) VALUES
(1, 'EXCAVADORAS', 1, NULL),
(2, 'CARGADORES FRONTALES', 1, NULL),
(3, 'RETROEXCAVADORAS', 1, NULL),
(4, 'MOTONIVELADORAS', 1, NULL),
(5, 'TRACTORES', 2, NULL),
(6, 'MOTONIVELADORAS', 2, NULL),
(7, 'RETROEXCAVADORAS', 2, NULL),
(8, 'MINICARGADORES', 2, NULL),
(9, 'TRACTORES', 3, NULL),
(10, 'EXCAVADORAS', 3, NULL),
(11, 'MISCELANEOS', 3, NULL),
(12, 'RECALCE DE ZAPATAS', 4, NULL),
(13, 'REPARACIÓN DE RODAMIENTOS', 4, NULL),
(14, 'REPARACIÓN DE CADENAS', 4, NULL),
(15, 'ENZAPATADO DE CADENAS', 4, NULL),
(16, 'CAMBIO DE PASOS MASTER', 4, NULL),
(17, 'REPARACIÓN DE TEMPLADOR DE CADENA', 4, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `ultimo_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `username`, `password`, `nombre`, `email`, `fecha_creacion`, `ultimo_login`) VALUES
(1, 'admin', '$2y$10$SGKeXpJ8morArAD3nqZSReayH39NzjzmgXxLNudDEWeMaBBqCZBrG', 'Admin', 'rg@email.com', '2025-03-13 15:57:44', '2025-03-17 11:01:02');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `archivos_pdf`
--
ALTER TABLE `archivos_pdf`
  ADD PRIMARY KEY (`id_archivo`),
  ADD KEY `idx_referencia` (`tipo_referencia`,`id_referencia`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `marcas`
--
ALTER TABLE `marcas`
  ADD PRIMARY KEY (`id_marca`),
  ADD KEY `id_tipo_maquinaria` (`id_tipo_maquinaria`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `id_marca` (`id_marca`);

--
-- Indices de la tabla `subcategorias`
--
ALTER TABLE `subcategorias`
  ADD PRIMARY KEY (`id_subcategoria`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `tipo_maquinaria`
--
ALTER TABLE `tipo_maquinaria`
  ADD PRIMARY KEY (`id_tipo_maquinaria`),
  ADD KEY `id_subcategoria` (`id_subcategoria`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `archivos_pdf`
--
ALTER TABLE `archivos_pdf`
  MODIFY `id_archivo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `marcas`
--
ALTER TABLE `marcas`
  MODIFY `id_marca` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `subcategorias`
--
ALTER TABLE `subcategorias`
  MODIFY `id_subcategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tipo_maquinaria`
--
ALTER TABLE `tipo_maquinaria`
  MODIFY `id_tipo_maquinaria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `marcas`
--
ALTER TABLE `marcas`
  ADD CONSTRAINT `marcas_ibfk_1` FOREIGN KEY (`id_tipo_maquinaria`) REFERENCES `tipo_maquinaria` (`id_tipo_maquinaria`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_marca`) REFERENCES `marcas` (`id_marca`);

--
-- Filtros para la tabla `subcategorias`
--
ALTER TABLE `subcategorias`
  ADD CONSTRAINT `subcategorias_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`);

--
-- Filtros para la tabla `tipo_maquinaria`
--
ALTER TABLE `tipo_maquinaria`
  ADD CONSTRAINT `tipo_maquinaria_ibfk_1` FOREIGN KEY (`id_subcategoria`) REFERENCES `subcategorias` (`id_subcategoria`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
