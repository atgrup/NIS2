-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 10-07-2025 a las 08:10:09
-- Versión del servidor: 8.0.41-32
-- Versión de PHP: 8.2.28

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
CREATE DATABASE IF NOT EXISTS `dbs1il8vaitgwc` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `dbs1il8vaitgwc`;

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
  `plantilla_id` int DEFAULT NULL,
  `proveedor_id` int NOT NULL,
  `archivo_url` text NOT NULL,
  `nombre_archivo` text,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `revision_estado` enum('pendiente','revisado','aprobado','rechazado') DEFAULT NULL,
  `consultor_id` int DEFAULT NULL,
  `comentarios` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `archivos_subidos`
--

INSERT INTO `archivos_subidos` (`id`, `plantilla_id`, `proveedor_id`, `archivo_url`, `nombre_archivo`, `fecha_subida`, `revision_estado`, `consultor_id`, `comentarios`) VALUES
(25, NULL, 1, 'hola@hola.com/1751884913_Important Document.pdf', 'Important Document.pdf', '2025-07-07 10:41:53', 'pendiente', NULL, NULL),
(26, NULL, 1, 'hola@hola.com/1751885165_Important Document.pdf', 'Important Document.pdf', '2025-07-07 10:46:06', 'pendiente', NULL, NULL),
(27, NULL, 1, 'hola@hola.com/1751885170_Important Document.pdf', 'Important Document.pdf', '2025-07-07 10:46:10', 'pendiente', NULL, NULL),
(41, NULL, 1, 'documentos_subidos/hola@hola.com/1751961332_Important Document.pdf', 'Important Document.pdf', '2025-07-08 07:55:33', 'pendiente', NULL, NULL);

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plantillas_asignadas`
--

CREATE TABLE `plantillas_asignadas` (
  `id` int NOT NULL,
  `proveedor_id` int NOT NULL,
  `plantilla_id` int NOT NULL,
  `fecha_asignacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('pendiente','enviado','revisado','rechazado','aprobado') DEFAULT NULL,
  `comentarios` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(1, 26, 'hola', NULL, NULL),
(2, 27, 'Adria', NULL, NULL),
(3, 28, 'desi', NULL, NULL);

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
(4, 'adria@atgroup.com', '$2y$10$R5V56s36xNGP9.C.O6dFeuE/XwUm3wEA3T4unPur7yqMbpA2KOgkG', 2, 0, NULL),
(5, 'pipicaca@pipi.cac', '$2y$10$CeD2wufYnlllgddPomjeeuJvE0zO2YxxObQuovXa/L73GNUgjOjyS', 2, 0, NULL),
(12, 'jortegag@atgroup.es', '$2y$10$bG0aC28k2sVmFlfSecN0d.tx7kO97ylHP6zNmaFeo4fVM.l6Ds2zu', 2, 0, NULL),
(13, 'mariand@cmks.com', '$2y$10$C07XPt8TRKnDe90EPKQwqOngXBYEzJYx9YZWB35GKVoAzCx.NFUsu', 2, 0, NULL),
(14, 'man#jsd@mg', '$2y$10$kvIBqKHlM8EfSRE34s5L9erxCFOx.pQ1W38GGgfMhTrElwFHP.wNG', 2, 0, NULL),
(15, 'adr@atg.com', '$2y$10$cTr9DO1oC1SdXxGE1NEl0.7mGmzjfvZcPuh/bRKwa9c0kxhzmlocW', 2, 0, NULL),
(20, 'hola3@hola.com', '$2y$10$Vq5fukqblaYGkeK/Aj2iLuEl5A/sHYDb9dpBYQLrvauKcSN6KRPCC', 2, 0, NULL),
(21, 'hola4@hola.com', '$2y$10$85MC0Fzn9qdTdbwwwnJeAup6agyCyOKQx0RO1kXHidAusmt7UiwPq', 2, 0, NULL),
(22, 'hola5@hola.com', '$2y$10$ImhAMkvJKyrQjo30cFJHreQU4JPoaRDuB2891Tbtvf30os3T2fUO2', 2, 0, NULL),
(23, 'prueba@prueba.com', '$2y$10$Bf3M7f.8/9PdKltuAqpQheGMENQlY1btLhz3KrZqGTWwDyRu.ckzq', 2, 0, NULL),
(25, 'prueba1@prueba.com', '$2y$10$UX4qgk4L7TheA7BFHemM8uqHUxLXPymkLTOR5QeDlD1uC1scVVZei', 2, 0, NULL),
(26, 'hola@hola.com', '$2y$10$M8I1Cv.sMV6S1h9IBb3lDOK.SrTM0.ZRkpB7A5Ohoq5gdmzi/y2Tm', 2, 0, NULL),
(27, 'asa@atp.com', '$2y$10$L1BL9jCYq4GIr/jwXD5gceQc/q296ZSvVsbpEFd2eMbdvUzg1lxIi', 2, 0, NULL),
(28, 'desi@desi.com', '$2y$10$8KuuLD3ak4DS.7rG9oVJw.oSR96CVpYRa.OdTPRNlkBj4mCnt3jua', 2, 0, NULL);

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
  ADD KEY `plantilla_id` (`plantilla_id`),
  ADD KEY `proveedor_id` (`proveedor_id`),
  ADD KEY `consultor_id` (`consultor_id`);

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
-- Indices de la tabla `plantillas_asignadas`
--
ALTER TABLE `plantillas_asignadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proveedor_id` (`proveedor_id`),
  ADD KEY `plantilla_id` (`plantilla_id`);

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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `archivos_subidos`
--
ALTER TABLE `archivos_subidos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `consultores`
--
ALTER TABLE `consultores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `plantillas`
--
ALTER TABLE `plantillas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `plantillas_asignadas`
--
ALTER TABLE `plantillas_asignadas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  MODIFY `id_tipo_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuarios` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

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
  ADD CONSTRAINT `archivos_subidos_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `archivos_subidos_ibfk_3` FOREIGN KEY (`consultor_id`) REFERENCES `consultores` (`id`) ON DELETE SET NULL;

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
-- Filtros para la tabla `plantillas_asignadas`
--
ALTER TABLE `plantillas_asignadas`
  ADD CONSTRAINT `plantillas_asignadas_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plantillas_asignadas_ibfk_2` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas` (`id`) ON DELETE CASCADE;

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
