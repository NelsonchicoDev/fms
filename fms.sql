-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-08-2024 a las 23:35:09
-- Versión del servidor: 10.4.21-MariaDB
-- Versión de PHP: 8.0.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `fms`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admins`
--

CREATE TABLE `admins` (
  `id_admin` int(11) NOT NULL,
  `email_admin` text DEFAULT NULL,
  `password_admin` text DEFAULT NULL,
  `token_admin` text DEFAULT NULL,
  `token_exp_admin` text DEFAULT NULL,
  `date_created_admin` date DEFAULT NULL,
  `date_updated_admin` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `admins`
--

INSERT INTO `admins` (`id_admin`, `email_admin`, `password_admin`, `token_admin`, `token_exp_admin`, `date_created_admin`, `date_updated_admin`) VALUES
(1, 'admin@fms.com', '$2a$07$azybxcags23425sdg23sdeanQZqjaf6Birm2NvcYTNtJw24CsO5uq', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3MjQyNjgyMTgsImV4cCI6MTcyNDM1NDYxOCwiZGF0YSI6eyJpZCI6IjEiLCJlbWFpbCI6ImFkbWluQGZtcy5jb20ifX0.zxhQlZgx3or6KxaD8-fIUWOngE_kMp8Kxf_VVOuYf7A', '1724354618', '2024-08-13', '2024-08-21 19:23:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `files`
--

CREATE TABLE `files` (
  `id_file` int(11) NOT NULL,
  `id_folder_file` int(11) NOT NULL DEFAULT 0,
  `name_file` text DEFAULT NULL,
  `extension_file` text DEFAULT NULL,
  `type_file` text DEFAULT NULL,
  `size_file` double NOT NULL DEFAULT 0,
  `link_file` text DEFAULT NULL,
  `thumbnail_vimeo_file` text DEFAULT NULL,
  `id_mailchimp_file` text DEFAULT NULL,
  `date_created_file` date DEFAULT NULL,
  `date_updated_file` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `files`
--

INSERT INTO `files` (`id_file`, `id_folder_file`, `name_file`, `extension_file`, `type_file`, `size_file`, `link_file`, `thumbnail_vimeo_file`, `id_mailchimp_file`, `date_created_file`, `date_updated_file`) VALUES
(1, 1, 'aerial-over-the-water-suites', 'jpg', 'image/jpeg', 363045, 'https://fms.tutorialesatualcance.com/views/assets/files/66be7cf3f033f59.jpg', NULL, NULL, '2024-08-16', '2024-08-15 22:11:00'),
(2, 1, 'bosque verde', 'jpg', 'image/jpeg', 263381, 'https://fms.tutorialesatualcance.com/views/assets/files/66be7cf3f2b6759.jpg', NULL, NULL, '2024-08-16', '2024-08-16 17:59:43'),
(3, 1, 'aerial-entire-royal-caribbean-resort', 'jpg', 'image/jpeg', 275531, 'https://fms.tutorialesatualcance.com/views/assets/files/66be7cf3f0c6259.jpg', NULL, NULL, '2024-08-16', '2024-08-15 22:11:00'),
(4, 1, '1d480485f41be200497799e7def13fc6-700', 'jpg', 'image/jpeg', 254130, 'https://fms.tutorialesatualcance.com/views/assets/files/66be7cf3f0c6259.jpg', NULL, NULL, '2024-08-16', '2024-08-15 22:11:00'),
(5, 1, '8091eP_4', 'jpg', 'image/jpeg', 1158434, 'https://fms.tutorialesatualcance.com/views/assets/files/66be7cf3f152c59.jpg', NULL, NULL, '2024-08-16', '2024-08-15 22:11:00'),
(6, 1, '4fb36ea826a8cc7fc6144fc8423eb823', 'jpg', 'image/jpeg', 263940, 'https://fms.tutorialesatualcance.com/views/assets/files/66be7cf3f330d59.jpg', NULL, NULL, '2024-08-16', '2024-08-15 22:11:00'),
(9, 1, 'over-the-water-bungalows-ocean', 'jpg', 'image/jpeg', 337625, 'https://fms.tutorialesatualcance.com/views/assets/files/66bf89c5a5e0b57.jpg', NULL, NULL, '2024-08-16', '2024-08-16 17:17:57'),
(10, 1, 'DEISIGN_STUDIO_Animation_Mentor_TRIBE_1440p_02', 'jpg', 'image/jpeg', 415283, 'https://fms.tutorialesatualcance.com/views/assets/files/66bf8c4b069f543.jpg', NULL, NULL, '2024-08-16', '2024-08-16 17:28:43'),
(11, 1, 'muñeco', 'jpg', 'image/jpeg', 496061, 'https://fms.tutorialesatualcance.com/views/assets/files/66bf8d15bf2505.jpg', NULL, NULL, '2024-08-16', '2024-08-16 17:32:10'),
(12, 1, 'desarrollo', 'zip', 'application/zip', 321366, 'https://fms.tutorialesatualcance.com/views/assets/files/66bfb1eeac02f18.zip', NULL, NULL, '2024-08-16', '2024-08-21 04:52:08'),
(13, 1, 'screencapture-fms-tutorialesatualcance-2024-08-09-16_47_48', 'png', 'image/png', 2079898, 'https://fms.tutorialesatualcance.com/views/assets/files/66bfb1eeb52ac18.png', NULL, NULL, '2024-08-16', '2024-08-21 04:52:08'),
(14, 1, '363462827_1954504461586591_6429694061970198404_n-ezgif.com-optimize', 'gif', 'image/gif', 8493234, 'https://fms.tutorialesatualcance.com/views/assets/files/66bfb1eef001818.gif', NULL, NULL, '2024-08-16', '2024-08-21 04:52:08'),
(15, 1, '04-video', 'mp4', 'video/mp4', 13250957, 'https://fms.tutorialesatualcance.com/views/assets/files/66bfb1eeefa8718.mp4', NULL, NULL, '2024-08-16', '2024-08-21 04:52:08'),
(16, 1, '05-video', 'mp4', 'video/mp4', 14619280, 'https://fms.tutorialesatualcance.com/views/assets/files/66bfb1eef01e518.mp4', NULL, NULL, '2024-08-16', '2024-08-21 04:52:08'),
(17, 1, '06-video', 'mp4', 'video/mp4', 29619055, 'https://fms.tutorialesatualcance.com/views/assets/files/66bfb1eef01d218.mp4', NULL, NULL, '2024-08-16', '2024-08-21 04:52:08'),
(18, 1, '431013312_723908896545853_3884741710440907295_n-ezgif.com-crop', 'gif', 'image/gif', 14265143, 'https://fms.tutorialesatualcance.com/views/assets/files/66bfb1eef020218.gif', NULL, NULL, '2024-08-16', '2024-08-21 04:52:08'),
(19, 1, 'DOC-04~1', 'PDF', 'application/pdf', 68452, 'https://fms.tutorialesatualcance.com/views/assets/files/66bfb350ac83012.PDF', NULL, NULL, '2024-08-16', '2024-08-16 20:15:12'),
(20, 1, 'profesorado', 'jpg', 'image/jpeg', 38782, 'https://fms.tutorialesatualcance.com/views/assets/files/66bfc7375ac377.jpg', NULL, NULL, '2024-08-16', '2024-08-16 21:40:07'),
(21, 1, 'personal', 'jpg', 'image/jpeg', 40905, 'https://fms.tutorialesatualcance.com/views/assets/files/66bfc8cd12e5b53.jpg', NULL, NULL, '2024-08-16', '2024-08-16 21:46:53'),
(22, 1, 'oficina', 'jpg', 'image/jpeg', 64579, 'https://fms.tutorialesatualcance.com/views/assets/files/66bfc92053e8e16.jpg', NULL, NULL, '2024-08-16', '2024-08-16 21:48:16'),
(23, 1, 'musica', 'jpg', 'image/jpeg', 47992, 'https://fms.tutorialesatualcance.com/views/assets/files/66bfcfb92688e25.jpg', NULL, NULL, '2024-08-17', '2024-08-16 23:04:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `folders`
--

CREATE TABLE `folders` (
  `id_folder` int(11) NOT NULL,
  `name_folder` text DEFAULT NULL,
  `size_folder` text DEFAULT NULL,
  `total_folder` double NOT NULL DEFAULT 0,
  `max_upload_folder` text DEFAULT NULL,
  `url_folder` text DEFAULT NULL,
  `keys_folder` text DEFAULT NULL,
  `date_created_folder` date DEFAULT NULL,
  `date_updated_folder` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `folders`
--

INSERT INTO `folders` (`id_folder`, `name_folder`, `size_folder`, `total_folder`, `max_upload_folder`, `url_folder`, `keys_folder`, `date_created_folder`, `date_updated_folder`) VALUES
(1, 'Server', '200000000000', 3509021, '500000000', 'https://fms.tutorialesatualcance.com', NULL, '2024-08-14', '2024-08-21 21:19:21'),
(2, 'AWS S3', '200000000000', 0, '500000000', NULL, NULL, '2024-08-14', '2024-08-21 19:28:03'),
(3, 'Cloudinary', '20000000000', 0, '100000000', NULL, NULL, '2024-08-14', '2024-08-21 21:31:27'),
(4, 'Vimeo', '1000000000', 0, '500000000', NULL, NULL, '2024-08-14', '2024-08-14 17:19:29'),
(5, 'Mailchimp', '200000000000', 0, '1000000', NULL, NULL, '2024-08-14', '2024-08-14 17:19:29');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id_admin`);

--
-- Indices de la tabla `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id_file`);

--
-- Indices de la tabla `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`id_folder`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `admins`
--
ALTER TABLE `admins`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `files`
--
ALTER TABLE `files`
  MODIFY `id_file` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `folders`
--
ALTER TABLE `folders`
  MODIFY `id_folder` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
