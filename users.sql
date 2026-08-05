-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-08-2026 a las 00:11:28
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
-- Base de datos: `nexusbc`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `pin` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `email_verified_at`, `password`, `pin`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Benito', 'Benito', 'benito@marin-supplies.com', NULL, '$2y$12$TuZ.isT6qy0Bn6CLxzkRS.w.KBN4z/ube9kIPn.AHDy4592J0hUDe', '$2y$12$nkMiiY68GULlDZtv5RB88etAt.RxiIBdG4/GHRQMMrADfEXuMyCZG', 'par', NULL, '2026-08-05 20:36:30', '2026-08-05 20:36:30'),
(2, 'Angel', 'Angel', 'angel@marin-supplies.com', NULL, '$2y$12$46lxMAcUrUl9cZCllbTCB.gA7zRdCF.ddfAJ6dgsfr4T7wxrEeM0e', '$2y$12$jJH1GItVEP0Mxf52X1eSkuVZnyuCFT5DcWzwoMwFugiJIBSPmfxye', 'par', NULL, '2026-08-05 20:36:31', '2026-08-05 20:36:31'),
(3, 'Esmeralda', 'Esmeralda', 'bcentro@marin-supplies.com', NULL, '$2y$12$7LXRlePGsGQEcQaCI0fTL.2DTal2mrzf5hcSCOHBcG.M8ukXSR.gq', '$2y$12$.e.6Pp1J2uTd2farSQrDL.WgOQyGyAXj1.HXTRcCNkQrmwfjXcnGK', 'par', NULL, '2026-08-05 20:36:31', '2026-08-05 20:36:31'),
(4, 'Ari', 'Ari', 'ari@marin-supplies.com', NULL, '$2y$12$qthlLP1eJyold9AMVepJceUBFX5Fy9Ui9PYQdyADf7YdPVEeTZdOO', '$2y$12$/IG4K/SoQVuyN3GJM1..iee/8IDpcHYVXjUY64AEmTVJE1z9rk9XO', 'par', NULL, '2026-08-05 20:36:32', '2026-08-05 20:36:32'),
(5, 'Carlos', 'Carlos', 'carlos@marin-supplies.com', NULL, '$2y$12$FI54Otm7YJkHN.NJmEkMtejuUlnNHSwqr3q7zAAZU1Dzr3A0Ryv6K', '$2y$12$6V6L.fv5xanRKDNEB.DCA.Yglit28hv7LJsADSCzONsRrsmlIlUeu', 'par', NULL, '2026-08-05 20:36:32', '2026-08-05 20:36:32'),
(6, 'Fernando', 'Fernando', 'fmorales@marin-supplies.com', NULL, '$2y$12$yBYAuLjY9FTX043P/eJcDu8kS5piIShavHFDyVIFC4RajD/5RZKHS', '$2y$12$MIUh3/1cj6gxgIP.jfKHa.Jb4tKV5MHm8IuJGuij83EI1369Movlu', 'admin', NULL, '2026-08-05 20:52:41', '2026-08-05 20:52:41');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_username_unique` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
