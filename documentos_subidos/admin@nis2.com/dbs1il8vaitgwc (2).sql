-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 28-07-2025 a las 08:13:53
-- Versión del servidor: 8.0.41-32
-- Versión de PHP: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dbs1il8vaitgwc`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos`
--

CREATE TABLE `archivos` (
  `id` int UNSIGNED NOT NULL,
  `usuario_id` int UNSIGNED NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_subidos`
--

CREATE TABLE `archivos_subidos` (
  `id` int NOT NULL,
  `proveedor_id` int DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `archivo_url` text NOT NULL,
  `nombre_archivo` text,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `revision_estado` enum('pendiente','revisado','aprobado','rechazado') DEFAULT NULL,
  `plantilla_uuid` char(36) DEFAULT NULL,
  `plantilla_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `archivos_subidos`
--

INSERT INTO `archivos_subidos` (`id`, `proveedor_id`, `usuario_id`, `archivo_url`, `nombre_archivo`, `fecha_subida`, `revision_estado`, `plantilla_uuid`, `plantilla_id`) VALUES
(56, NULL, 1, 'documentos_subidos/6881eb7c79322_pdf_texto_simple_prueba.pdf', 'pdf_texto_simple_prueba.pdf', '2025-07-24 08:14:52', 'pendiente', NULL, 13),
(61, 4, 29, 'documentos_subidos/6882013f1edbc_pdf_texto_simple_prueba_2.pdf', 'pdf_texto_simple_prueba_2.pdf', '2025-07-24 09:47:43', 'pendiente', NULL, 13);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultores`
--

CREATE TABLE `consultores` (
  `id` int NOT NULL,
  `usuario_id` int UNSIGNED NOT NULL,
  `nombre` text,
  `password` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish2_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `consultores`
--

INSERT INTO `consultores` (`id`, `usuario_id`, `nombre`, `password`) VALUES
(15, 86, NULL, NULL),
(19, 98, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plantillas`
--

CREATE TABLE `plantillas` (
  `id` int NOT NULL,
  `nombre` text NOT NULL,
  `descripcion` text,
  `consultor_id` int DEFAULT NULL,
  `archivo_url` text,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `plantillas`
--

INSERT INTO `plantillas` (`id`, `nombre`, `descripcion`, `consultor_id`, `archivo_url`, `fecha_subida`) VALUES
(7, 'dummy.pdf', NULL, 15, NULL, '2025-07-21 11:06:04'),
(12, 'ejemplo_documento.pdf', NULL, NULL, NULL, '2025-07-23 08:00:48'),
(13, 'pdf_texto_simple_prueba.pdf', NULL, NULL, NULL, '2025-07-23 09:46:10'),
(14, 'pdf_texto_simple_prueba.pdf', NULL, 15, NULL, '2025-07-23 09:59:22'),
(15, 'pdf_texto_simple_prueba.pdf', NULL, NULL, NULL, '2025-07-23 10:05:56'),
(16, 'pdf_texto_simple_prueba.pdf', NULL, NULL, NULL, '2025-07-23 10:15:50'),
(17, 'dummy (1).pdf', NULL, NULL, NULL, '2025-07-23 10:16:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int NOT NULL,
  `usuario_id` int UNSIGNED NOT NULL,
  `nombre_empresa` text NOT NULL,
  `normativa` text,
  `otros_datos` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `usuario_id`, `nombre_empresa`, `normativa`, `otros_datos`) VALUES
(1, 26, 'Hola', NULL, NULL),
(4, 29, 'Proveedor', NULL, NULL),
(29, 103, 'prova123', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_usuario`
--

CREATE TABLE `tipo_usuario` (
  `id_tipo_usuario` int NOT NULL,
  `nombre` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `tipo_usuario`
--

INSERT INTO `tipo_usuario` (`id_tipo_usuario`, `nombre`) VALUES
(1, 'ADMINISTRADOR'),
(2, 'PROVEEDOR'),
(3, 'CONSULTOR');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuarios` int UNSIGNED NOT NULL,
  `correo` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `tipo_usuario_id` int DEFAULT NULL,
  `verificado` tinyint(1) DEFAULT '0',
  `token_verificacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuarios`, `correo`, `password`, `tipo_usuario_id`, `verificado`, `token_verificacion`) VALUES
(1, 'admin@nis2.com', '$2y$10$/O6aq479C/5kCt2Mt9hB5..dXutfX/8x/TyMSx6Ga.FVI1KVQkzO2', 1, 0, NULL),
(12, 'jortegag@atgroup.es', '$2y$10$bG0aC28k2sVmFlfSecN0d.tx7kO97ylHP6zNmaFeo4fVM.l6Ds2zu', 2, 0, NULL),
(13, 'mariand@cmks.com', '$2y$10$C07XPt8TRKnDe90EPKQwqOngXBYEzJYx9YZWB35GKVoAzCx.NFUsu', 2, 0, NULL),
(14, 'man#jsd@mg', '$2y$10$kvIBqKHlM8EfSRE34s5L9erxCFOx.pQ1W38GGgfMhTrElwFHP.wNG', 2, 0, NULL),
(15, 'adr@atg.com', '$2y$10$cTr9DO1oC1SdXxGE1NEl0.7mGmzjfvZcPuh/bRKwa9c0kxhzmlocW', 2, 0, NULL),
(23, 'prueba@prueba.com', '$2y$10$LZ9ujp7Y7xAGbnNdDD.ZBeqbHDFoX693ySSGCh.sULV4rHx08/TGC', 2, 0, NULL),
(26, 'hola@hola.com', '$2y$10$br2VJykIUj5LMiA1PavGbOkSRb.GG9gfrVvBttj7gA10TVqJSjw/S', 2, 0, NULL),
(29, 'prueba@proveedor.com', '$2y$10$qP1x91iPf8SKto4E6FAD5eD2d2pKkgr0IJsjiR6vwueFR.eCYjWkq', 2, 0, NULL),
(86, 'desi@consultor.com', '$2y$10$IadOvycCnabSwCUA8u1aEOVjqxfMnGP.C/IQrGnW5UKXFQW9Hk.a2', 3, 0, NULL),
(87, 'prueba@admin4.com', '$2y$10$ahvXEdPISnxrnBCq.1zIS.CrnVHB3i.6GcuE3i8YTnZkSDDVrUA..', 1, 0, NULL),
(95, 'prueba@admin12.com', '$2y$10$uA8yeg5oOXiI9DYK6s/VHO75nnqUOx4yPyBslTBi3wiCTE2VQkVhy', 1, 0, NULL),
(98, 'prueba@consultor.com', '$2y$10$YaV39WTEDQ4e/takv0.kG.YuSWSgUKFLUQOxbhlmTS7NlSLsb.p/W', 3, 0, NULL),
(103, 'prova@gmail.com', '$2y$10$KuDK1fEudW7bYrAzyLSLjO.oR2HZfp83ZIfp4oZ6npxZubQETF9My', 2, 0, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `archivos`
--
ALTER TABLE `archivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`) USING BTREE;

--
-- Indices de la tabla `archivos_subidos`
--
ALTER TABLE `archivos_subidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proveedor_id` (`proveedor_id`),
  ADD KEY `fk_archivos_subidos_plantilla_uuid` (`plantilla_uuid`),
  ADD KEY `archivos_subidos_ibfk_1` (`plantilla_id`);

--
-- Indices de la tabla `consultores`
--
ALTER TABLE `consultores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `plantillas`
--
ALTER TABLE `plantillas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consultor_id` (`consultor_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  ADD PRIMARY KEY (`id_tipo_usuario`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuarios`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `fk_tipo_usuario` (`tipo_usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `archivos`
--
ALTER TABLE `archivos`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `archivos_subidos`
--
ALTER TABLE `archivos_subidos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT de la tabla `consultores`
--
ALTER TABLE `consultores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `plantillas`
--
ALTER TABLE `plantillas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  MODIFY `id_tipo_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuarios` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `archivos`
--
ALTER TABLE `archivos`
  ADD CONSTRAINT `fk_archivos-usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuarios`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `archivos_subidos`
--
ALTER TABLE `archivos_subidos`
  ADD CONSTRAINT `archivos_subidos_ibfk_1` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `archivos_subidos_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `consultores`
--
ALTER TABLE `consultores`
  ADD CONSTRAINT `fk_usrs_id` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuarios`) ON DELETE CASCADE;

--
-- Filtros para la tabla `plantillas`
--
ALTER TABLE `plantillas`
  ADD CONSTRAINT `plantillas_ibfk_1` FOREIGN KEY (`consultor_id`) REFERENCES `consultores` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD CONSTRAINT `proveedores_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuarios`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_tipo_usuario` FOREIGN KEY (`tipo_usuario_id`) REFERENCES `tipo_usuario` (`id_tipo_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
