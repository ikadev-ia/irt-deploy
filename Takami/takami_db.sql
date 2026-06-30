-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 29 juin 2026 à 21:20
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `takami_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp(),
  `derniere_connexion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `email`, `password`, `numero`, `date_inscription`, `derniere_connexion`) VALUES
(1, 'Fatoumata Diane', 'dianefatoushka@gmail.com', '$2y$10$sXGBkqavE/BrOL9SXoePBO89tCpyEHaWKTbPcCGjo.48L3m3ZWwhG', '90003100', '2026-06-26 14:48:55', '2026-06-29 11:12:30'),
(2, 'Djenebou Sacko', 'kanykim@gmail.com', '$2y$10$JAYq0jlwY13z/nHtm/QXt.lBzDkqWHBCYsPYXityzVws9SFw.Mfca', '76120974', '2026-06-29 11:08:42', '2026-06-29 14:50:43'),
(3, 'Fatoumata Diane', 'f@gmail.com', '$2y$10$0dSLkaqL8H6NXfj1zlo3N.DW3vmc3HhA/UThZmtC0CLopTBdRl7zC', '76269049', '2026-06-29 11:39:59', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `nom_client` varchar(100) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `paiement` varchar(50) DEFAULT NULL,
  `date_commande` timestamp NOT NULL DEFAULT current_timestamp(),
  `details_produits` text DEFAULT NULL,
  `montant_total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `nom_client`, `adresse`, `paiement`, `date_commande`, `details_produits`, `montant_total`) VALUES
(1, '', NULL, NULL, '2026-06-26 14:49:40', 'Grain Jigin (x1), Dembaya (x1), Nienaje (x1), ', 8650.00),
(2, 'Administrateur', NULL, NULL, '2026-06-26 15:04:34', 'Dembaya (x1), ', 3000.00),
(3, 'Administrateur', NULL, NULL, '2026-06-26 15:11:30', 'Grain Jigin (x1), ', 150.00),
(4, 'Fatoumata Diane', NULL, NULL, '2026-06-26 15:19:49', 'Grain Jigin (x1), Dembaya (x1), Nienaje (x1), ', 8650.00),
(5, 'Administrateur', NULL, NULL, '2026-06-26 15:22:38', 'Grain Jigin (x1), ', 150.00),
(6, 'Fatoumata Diane', NULL, NULL, '2026-06-26 15:25:25', 'Grain Jigin (x1), Dembaya (x1), Nienaje (x1), ', 8650.00),
(7, 'Fatoumata Diane', NULL, NULL, '2026-06-26 15:31:08', 'Dembaya (x38), ', 114000.00),
(8, 'Fatoumata Diane', NULL, NULL, '2026-06-26 15:33:12', 'Dembaya (x9), ', 27000.00),
(9, 'Administrateur', NULL, NULL, '2026-06-26 15:34:41', 'Dembaya (x1), ', 3000.00),
(10, 'Fatoumata Diane', NULL, NULL, '2026-06-26 15:41:08', 'Dembaya (x1), ', 3000.00),
(11, 'Fatoumata Diane', NULL, NULL, '2026-06-26 19:13:22', 'Dembaya (x2), ', 6000.00),
(12, 'Fatoumata Diane', NULL, NULL, '2026-06-27 01:57:03', 'Dembaya (x1), ', 3000.00),
(13, '', NULL, NULL, '2026-06-29 11:10:04', 'Nienaje (x1), ', 5500.00),
(14, '', NULL, NULL, '2026-06-29 11:42:00', 'Nienaje (x1), ', 5500.00),
(15, 'Administrateur', NULL, NULL, '2026-06-29 14:25:40', 'Dembaya (x1), ', 3000.00),
(16, 'Djenebou Sacko', NULL, NULL, '2026-06-29 14:51:04', 'Dembaya (x1), ', 3000.00);

-- --------------------------------------------------------

--
-- Structure de la table `details_commande`
--

CREATE TABLE `details_commande` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) DEFAULT NULL,
  `produit_id` int(11) DEFAULT NULL,
  `quantite` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `details_commande`
--

INSERT INTO `details_commande` (`id`, `commande_id`, `produit_id`, `quantite`) VALUES
(1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prix` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `Quantité` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id`, `nom`, `prix`, `image`, `Quantité`) VALUES
(1, 'Grain Jigin', 150, '250g.jpeg', 97),
(2, 'Dembaya', 3000, '50kg.jpeg', 3),
(3, 'Nienaje', 5500, '100kg.jpeg', 296);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `details_commande`
--
ALTER TABLE `details_commande`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `details_commande`
--
ALTER TABLE `details_commande`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
