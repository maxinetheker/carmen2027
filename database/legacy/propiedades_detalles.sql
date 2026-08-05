-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 05, 2026 at 03:11 PM
-- Server version: 11.4.12-MariaDB
-- PHP Version: 8.4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `carmenm3_main`
--

-- --------------------------------------------------------

--
-- Table structure for table `propiedades_detalles`
--

CREATE TABLE `propiedades_detalles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `area_m2` int(11) NOT NULL,
  `salon_de_juegos` tinyint(1) NOT NULL,
  `cocina` tinyint(1) NOT NULL,
  `salas` tinyint(1) NOT NULL,
  `terraza` tinyint(1) NOT NULL,
  `club_house` tinyint(1) NOT NULL,
  `cocheras` int(11) NOT NULL,
  `area_de_parrillas` tinyint(1) NOT NULL,
  `dormitorio_de_servicio` tinyint(1) NOT NULL,
  `gimnasio` tinyint(1) NOT NULL,
  `area_deportiva` tinyint(1) NOT NULL,
  `area_de_trabajo` tinyint(1) NOT NULL DEFAULT 0,
  `inscrip_en_registros_publicos` tinyint(1) NOT NULL,
  `banos` int(11) NOT NULL,
  `bano_de_visita` tinyint(1) NOT NULL DEFAULT 0,
  `comedor_de_diario` tinyint(1) NOT NULL,
  `sala_de_estar` tinyint(1) NOT NULL,
  `sala_de_reuniones` tinyint(1) NOT NULL DEFAULT 0,
  `patio` tinyint(1) NOT NULL,
  `patio_trasero` tinyint(1) NOT NULL DEFAULT 0,
  `guardania` tinyint(1) NOT NULL,
  `ascensor` tinyint(1) NOT NULL,
  `porton_electrico` tinyint(1) NOT NULL DEFAULT 0,
  `cisterna` tinyint(1) NOT NULL DEFAULT 0,
  `tanque_elevado` tinyint(1) NOT NULL DEFAULT 0,
  `pozo_a_tierra` tinyint(1) NOT NULL DEFAULT 0,
  `libre_de_cargas_gravamenes` tinyint(1) NOT NULL,
  `comedor` tinyint(1) NOT NULL,
  `lavanderia` tinyint(1) NOT NULL,
  `cuarto_de_planchado` tinyint(1) NOT NULL DEFAULT 0,
  `hall_ingreso` tinyint(1) NOT NULL,
  `walk_in_closet` tinyint(1) NOT NULL,
  `permite_mascotas` tinyint(1) NOT NULL,
  `zonificacion` varchar(255) NOT NULL,
  `servicios` varchar(255) DEFAULT '',
  `listos_para_ser_financiado` tinyint(1) NOT NULL,
  `piscina` tinyint(1) NOT NULL,
  `pisos` int(11) NOT NULL,
  `frente_metros` int(11) NOT NULL,
  `fondo_metros` int(11) NOT NULL,
  `oficina` tinyint(1) NOT NULL,
  `escritorio` tinyint(1) NOT NULL DEFAULT 0,
  `estudio` tinyint(1) NOT NULL DEFAULT 0,
  `jacuzzi` tinyint(1) NOT NULL,
  `jardin_interior` tinyint(1) NOT NULL,
  `jardin` tinyint(1) NOT NULL,
  `chimenea` tinyint(1) NOT NULL,
  `calefaccion` tinyint(1) NOT NULL DEFAULT 0,
  `aire_acondicionado` tinyint(1) NOT NULL DEFAULT 0,
  `almacen_de_alimentos` tinyint(1) NOT NULL,
  `deposito` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `dormitorio` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `propiedades_detalles`
--

INSERT INTO `propiedades_detalles` (`id`, `area_m2`, `salon_de_juegos`, `cocina`, `salas`, `terraza`, `club_house`, `cocheras`, `area_de_parrillas`, `dormitorio_de_servicio`, `gimnasio`, `area_deportiva`, `area_de_trabajo`, `inscrip_en_registros_publicos`, `banos`, `bano_de_visita`, `comedor_de_diario`, `sala_de_estar`, `sala_de_reuniones`, `patio`, `patio_trasero`, `guardania`, `ascensor`, `porton_electrico`, `cisterna`, `tanque_elevado`, `pozo_a_tierra`, `libre_de_cargas_gravamenes`, `comedor`, `lavanderia`, `cuarto_de_planchado`, `hall_ingreso`, `walk_in_closet`, `permite_mascotas`, `zonificacion`, `servicios`, `listos_para_ser_financiado`, `piscina`, `pisos`, `frente_metros`, `fondo_metros`, `oficina`, `escritorio`, `estudio`, `jacuzzi`, `jardin_interior`, `jardin`, `chimenea`, `calefaccion`, `aire_acondicionado`, `almacen_de_alimentos`, `deposito`, `created_at`, `updated_at`, `dormitorio`) VALUES
(8, 30000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'I4', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-03-17 01:44:59', '2026-01-27 09:11:47', 0),
(9, 525, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 'CV', '', 1, 0, 0, 15, 35, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-03-17 02:38:50', '2026-04-04 06:06:41', 2),
(16, 177, 0, 1, 0, 1, 0, 2, 0, 0, 0, 0, 0, 0, 3, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'No definido', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-04-04 06:31:57', '2026-04-04 06:41:48', 1),
(17, 160, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'No definido', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-04-04 07:10:30', '2026-04-04 07:10:30', 1),
(18, 1917, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'No definido', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-04-04 07:17:29', '2026-04-04 07:17:29', 0),
(19, 330, 0, 1, 0, 0, 0, 2, 0, 1, 0, 0, 0, 0, 3, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'No definido', '', 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, '2026-04-04 08:47:37', '2026-04-04 08:47:37', 4),
(20, 167, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 2, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 'No definido', '', 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-04-04 08:53:57', '2026-04-04 08:53:57', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `propiedades_detalles`
--
ALTER TABLE `propiedades_detalles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `propiedades_detalles`
--
ALTER TABLE `propiedades_detalles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
