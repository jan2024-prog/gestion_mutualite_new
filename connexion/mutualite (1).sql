-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 04 jan. 2026 à 18:02
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
-- Base de données : `mutualite`
--

-- --------------------------------------------------------

--
-- Structure de la table `tcompte`
--

CREATE TABLE `tcompte` (
  `id_compte` int(11) NOT NULL,
  `numero_compte` varchar(30) DEFAULT NULL,
  `id_membre` int(11) DEFAULT NULL,
  `nom_membre` varchar(150) DEFAULT NULL,
  `solde` decimal(12,2) DEFAULT 0.00,
  `est_bloque` tinyint(1) DEFAULT 0,
  `date_deblocage` date DEFAULT NULL,
  `date_creation` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tcompte`
--

INSERT INTO `tcompte` (`id_compte`, `numero_compte`, `id_membre`, `nom_membre`, `solde`, `est_bloque`, `date_deblocage`, `date_creation`) VALUES
(1, 'MUT-2025-0001', 1, 'Jean Kasereka', 185.00, 0, NULL, '2025-12-17'),
(2, 'MUT-2025-0002', 2, 'Marie Mukamana', 3000.00, 0, NULL, '2025-12-17'),
(3, 'MUT-2025-0003', 3, 'Kitoko', 0.00, 0, NULL, '2025-12-17'),
(7, 'MUT-2025-0007', 7, '', 0.00, 0, NULL, '2025-12-20'),
(8, 'MUT-2025-0008', 8, '', 0.00, 0, NULL, '2025-12-20'),
(9, 'MUT-2025-0009', 9, '', 0.00, 0, NULL, '2025-12-20'),
(10, 'MUT-2025-0010', 10, 'Mois', 26.00, 0, NULL, '2025-12-20'),
(11, 'MUT-2025-0011', 11, '', 0.00, 0, NULL, '2025-12-20'),
(12, 'MUT-2025-0012', 12, '', 0.00, 0, NULL, '2025-12-20'),
(13, 'MUT-2025-0013', 13, 'Jean Kasereka', 0.00, 0, NULL, '2025-12-21'),
(14, 'MUT-2025-0014', 14, 'Kitoko', 0.00, 0, NULL, '2025-12-22'),
(15, 'MUT-2025-0015', 15, 'KAMBALE MUPIKA PATRICK', 6000.00, 0, NULL, '2025-12-24'),
(0, 'MUT-2026-0000', 0, 'Es', 0.00, 0, NULL, '2026-01-04');

-- --------------------------------------------------------

--
-- Structure de la table `tcompte_bloque`
--

CREATE TABLE `tcompte_bloque` (
  `id_compte_bloque` int(11) NOT NULL,
  `id_membre` int(11) NOT NULL,
  `numero_compte_bloque` varchar(30) DEFAULT NULL,
  `solde` decimal(12,2) DEFAULT 0.00,
  `date_creation` date DEFAULT curdate(),
  `date_deblocage` date DEFAULT NULL,
  `statut` enum('Bloque','Urgent','Debloque') DEFAULT 'Bloque'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déclencheurs `tcompte_bloque`
--
DELIMITER $$
CREATE TRIGGER `trg_init_statut_compte` BEFORE INSERT ON `tcompte_bloque` FOR EACH ROW BEGIN
    IF NEW.date_deblocage IS NOT NULL 
       AND NEW.date_deblocage <= CURDATE() THEN
        SET NEW.statut = 'Debloque';
    ELSE
        SET NEW.statut = 'Bloque';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_update_statut_compte` BEFORE UPDATE ON `tcompte_bloque` FOR EACH ROW BEGIN
    IF NEW.date_deblocage <= CURDATE() THEN
        SET NEW.statut = 'Debloque';
    ELSEIF DATEDIFF(NEW.date_deblocage, CURDATE()) BETWEEN 1 AND 15 THEN
        SET NEW.statut = 'Urgent';
    ELSE
        SET NEW.statut = 'Bloque';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `tcotisation`
--

CREATE TABLE `tcotisation` (
  `id_cotisation` int(11) NOT NULL,
  `numero_compte` varchar(50) NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `semaine` int(11) NOT NULL,
  `annee` int(11) NOT NULL,
  `libelle` varchar(200) DEFAULT NULL,
  `date_cotisation` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tcotisation`
--

INSERT INTO `tcotisation` (`id_cotisation`, `numero_compte`, `montant`, `semaine`, `annee`, `libelle`, `date_cotisation`) VALUES
(5, 'MUT-2025-0010', 14.00, 41, 2025, 'Cotisation hebdomadaire', '2025-12-22'),
(6, 'MUT-2025-0001', 200.00, 2, 2025, 'Cotisation hebdomadaire', '2025-12-20'),
(7, 'MUT-2025-0015', 6000.00, 3, 2025, 'Cotisation hebdomadaire', '2025-12-24');

--
-- Déclencheurs `tcotisation`
--
DELIMITER $$
CREATE TRIGGER `trg_cotisation_compte` AFTER INSERT ON `tcotisation` FOR EACH ROW BEGIN
    UPDATE Tcompte
    SET solde = solde + NEW.montant
    WHERE numero_compte = NEW.numero_compte;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `tcredit`
--

CREATE TABLE `tcredit` (
  `id_credit` int(11) NOT NULL,
  `id_membre` int(11) DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `id_type_credit` int(11) DEFAULT NULL,
  `montant_credit` decimal(12,2) DEFAULT NULL,
  `date_credit` date DEFAULT NULL,
  `date_echeance` date DEFAULT NULL,
  `statut` enum('En cours','Soldé') DEFAULT 'En cours',
  `libele` varchar(200) NOT NULL,
  `penalite_active` tinyint(1) DEFAULT 1,
  `taux_penalite` decimal(5,2) DEFAULT 10.00,
  `last_penalite_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tcredit`
--

INSERT INTO `tcredit` (`id_credit`, `id_membre`, `nom`, `id_type_credit`, `montant_credit`, `date_credit`, `date_echeance`, `statut`, `libele`, `penalite_active`, `taux_penalite`, `last_penalite_date`) VALUES
(1, 1, '', 1, 20000.00, '2025-12-17', NULL, 'En cours', '', 1, 10.00, NULL),
(2, 2, '', 1, 2000.00, '2026-01-03', '2026-01-04', 'En cours', '', 1, 10.00, NULL);

--
-- Déclencheurs `tcredit`
--
DELIMITER $$
CREATE TRIGGER `trg_credit_compte` AFTER INSERT ON `tcredit` FOR EACH ROW BEGIN
    UPDATE Tcompte
    SET solde = solde - NEW.montant_credit
    WHERE id_membre = NEW.id_membre;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `tdepot_compte_bloque`
--

CREATE TABLE `tdepot_compte_bloque` (
  `id_depot` int(11) NOT NULL,
  `id_compte_bloque` int(11) NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `date_depot` datetime DEFAULT current_timestamp(),
  `mode_paiement` varchar(50) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déclencheurs `tdepot_compte_bloque`
--
DELIMITER $$
CREATE TRIGGER `trg_check_montant_depot` BEFORE INSERT ON `tdepot_compte_bloque` FOR EACH ROW BEGIN
    IF NEW.montant <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Montant invalide';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_depot_delete` AFTER DELETE ON `tdepot_compte_bloque` FOR EACH ROW BEGIN
    UPDATE Tcompte_bloque
    SET solde = solde - OLD.montant
    WHERE id_compte_bloque = OLD.id_compte_bloque;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_depot_insert` AFTER INSERT ON `tdepot_compte_bloque` FOR EACH ROW BEGIN
    UPDATE Tcompte_bloque
    SET solde = solde + NEW.montant
    WHERE id_compte_bloque = NEW.id_compte_bloque;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_depot_update` AFTER UPDATE ON `tdepot_compte_bloque` FOR EACH ROW BEGIN
    UPDATE Tcompte_bloque
    SET solde = solde - OLD.montant + NEW.montant
    WHERE id_compte_bloque = NEW.id_compte_bloque;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `tentreprise`
--

CREATE TABLE `tentreprise` (
  `id_entreprise` int(11) NOT NULL,
  `nom_entreprise` varchar(150) DEFAULT NULL,
  `adresse` varchar(200) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `date_creation` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tentreprise`
--

INSERT INTO `tentreprise` (`id_entreprise`, `nom_entreprise`, `adresse`, `telephone`, `email`, `date_creation`) VALUES
(1, 'Mutualité Espoir', 'Butembo', '0990000000', 'contact@espoir.cd', '2025-12-17');

-- --------------------------------------------------------

--
-- Structure de la table `thistorique_penalite`
--

CREATE TABLE `thistorique_penalite` (
  `id_penalite` int(11) NOT NULL,
  `id_credit` int(11) NOT NULL,
  `montant_penalite` decimal(12,2) NOT NULL,
  `date_penalite` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tmembre`
--

CREATE TABLE `tmembre` (
  `id_membre` int(11) NOT NULL,
  `id_entreprise` int(11) DEFAULT NULL,
  `noms` varchar(150) NOT NULL,
  `sexe` varchar(10) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `datenaissance` date DEFAULT NULL,
  `date_adhesion` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `photos` varchar(100) DEFAULT NULL,
  `statut` enum('Actif','Suspendu') DEFAULT 'Actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tmembre`
--

INSERT INTO `tmembre` (`id_membre`, `id_entreprise`, `noms`, `sexe`, `telephone`, `datenaissance`, `date_adhesion`, `email`, `photos`, `statut`) VALUES
(1, 1, 'Jean Kasereka', 'masculin', '0991111111', '0000-00-00', '2025-12-17', 'gracianesosa@gmail.com', 'uwike_depliant_verso.jpg', 'Actif'),
(2, 1, 'Marie Mukamana', 'F', '0992222222', NULL, '2025-12-17', NULL, NULL, 'Actif'),
(3, NULL, 'Kitoko', 'Feminin', '0979429931', '2025-12-04', NULL, 'gracianesosa@gmail.com', 'uwike_depliant.jpg', 'Actif'),
(7, NULL, '', 'masculin', '', '0000-00-00', '2025-12-20', '', '', 'Actif'),
(8, NULL, '', 'masculin', '', '0000-00-00', '2025-12-20', '', '', 'Actif'),
(9, NULL, '', 'masculin', '', '0000-00-00', '2025-12-20', '', '', 'Actif'),
(10, NULL, 'Mois', 'feminin', '0991111111', '2025-12-14', '2025-12-20', 'gracianesosa@gmail.com', 'julet_verso_uwike-2.jpg', 'Suspendu'),
(11, NULL, '', 'masculin', '', '0000-00-00', '2025-12-20', '', '', 'Actif'),
(12, NULL, '', 'masculin', '', '0000-00-00', '2025-12-20', '', '', 'Actif'),
(13, NULL, 'Jean Kasereka', 'masculin', '0991111111', '0000-00-00', '2025-12-21', 'gracianesosa@gmail.com', '', 'Actif'),
(14, NULL, 'Kitoto', 'F', '0991111111', '2025-12-13', '2025-12-22', 'gracianesosa@gmail.com', 'julet_verso_uwike.jpg', 'Actif'),
(15, NULL, 'KAMBALE MUPIKA PATRICKE', 'M', '0991681931', '1992-06-24', '2025-12-24', 'gracianesosa@gmail.com', 'julet_verso_uwike.jpg', 'Actif'),
(0, NULL, 'Es', 'F', '0991111111', '2026-01-20', '2026-01-04', 'gracianesosa@gmail.com', 'CIVISME 1.pdf', 'Actif');

--
-- Déclencheurs `tmembre`
--
DELIMITER $$
CREATE TRIGGER `trg_creation_compte_membre` AFTER INSERT ON `tmembre` FOR EACH ROW BEGIN
    DECLARE num_compte VARCHAR(30);
    DECLARE noms_complet VARCHAR(150);

    SET noms_complet = NEW.noms;
    SET num_compte = CONCAT('MUT-', YEAR(CURDATE()), '-', LPAD(NEW.id_membre,4,'0'));

    INSERT INTO Tcompte (numero_compte, id_membre, nom_membre, solde, date_creation)
    VALUES (num_compte, NEW.id_membre, noms_complet, 0, CURDATE());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `tmembrebl`
--

CREATE TABLE `tmembrebl` (
  `id_membre` int(11) NOT NULL,
  `noms` varchar(150) NOT NULL,
  `sexe` varchar(10) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `datenaissance` date DEFAULT NULL,
  `date_adhesion` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `photo` varchar(100) DEFAULT NULL,
  `statut` enum('Actif','Suspendu') DEFAULT 'Actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tremboursement`
--

CREATE TABLE `tremboursement` (
  `id_remboursement` int(11) NOT NULL,
  `id_credit` int(11) DEFAULT NULL,
  `montant_rembourse` decimal(12,2) DEFAULT NULL,
  `date_remboursement` date DEFAULT NULL,
  `libele` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tremboursement`
--

INSERT INTO `tremboursement` (`id_remboursement`, `id_credit`, `montant_rembourse`, `date_remboursement`, `libele`) VALUES
(1, 1, 5000.00, '2025-12-17', ''),
(2, 1, 12.00, '2025-12-24', 'YHGDFIHJKDGF');

--
-- Déclencheurs `tremboursement`
--
DELIMITER $$
CREATE TRIGGER `trg_credit_solde` AFTER INSERT ON `tremboursement` FOR EACH ROW BEGIN
    DECLARE total DECIMAL(12,2);
    DECLARE montant DECIMAL(12,2);

    SELECT SUM(montant_rembourse)
    INTO total
    FROM Tremboursement
    WHERE id_credit = NEW.id_credit;

    SELECT montant_credit
    INTO montant
    FROM Tcredit
    WHERE id_credit = NEW.id_credit;

    IF total >= montant THEN
        UPDATE Tcredit
        SET statut = 'Soldé'
        WHERE id_credit = NEW.id_credit;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_remboursement_compte` AFTER INSERT ON `tremboursement` FOR EACH ROW BEGIN
    UPDATE Tcompte
    SET solde = solde + NEW.montant_rembourse
    WHERE id_membre = (
        SELECT id_membre FROM Tcredit WHERE id_credit = NEW.id_credit
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `tretrait`
--

CREATE TABLE `tretrait` (
  `id_retrait` int(11) NOT NULL,
  `id_compte` int(11) DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `montant` decimal(12,2) DEFAULT NULL,
  `date_retrait` date DEFAULT NULL,
  `libelle` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tretrait`
--

INSERT INTO `tretrait` (`id_retrait`, `id_compte`, `nom`, `montant`, `date_retrait`, `libelle`) VALUES
(1, 1, 'Jean Kasereka', 12.00, '2025-12-11', 'tykuyli');

--
-- Déclencheurs `tretrait`
--
DELIMITER $$
CREATE TRIGGER `trg_retrait_compte` AFTER INSERT ON `tretrait` FOR EACH ROW BEGIN
    UPDATE Tcompte
    SET solde = solde - NEW.montant
    WHERE id_compte = NEW.id_compte;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_verif_compte_bloque` BEFORE INSERT ON `tretrait` FOR EACH ROW BEGIN
    DECLARE bloque BOOLEAN;
    DECLARE fin DATE;

    SELECT est_bloque, date_deblocage
    INTO bloque, fin
    FROM Tcompte
    WHERE id_compte = NEW.id_compte;

    IF bloque = TRUE AND CURDATE() < fin THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Compte bloqué – retrait interdit';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `ttype_credit`
--

CREATE TABLE `ttype_credit` (
  `id_type_credit` int(11) NOT NULL,
  `libelle` varchar(50) DEFAULT NULL,
  `taux_interet` decimal(5,2) DEFAULT NULL,
  `duree_mois` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ttype_credit`
--

INSERT INTO `ttype_credit` (`id_type_credit`, `libelle`, `taux_interet`, `duree_mois`) VALUES
(1, 'Normal', 5.00, 5);

-- --------------------------------------------------------

--
-- Structure de la table `tutilisateur`
--

CREATE TABLE `tutilisateur` (
  `id_user` int(11) NOT NULL,
  `noms` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `mot_passe` varchar(255) DEFAULT NULL,
  `role` enum('Admin','Secretaire','Tresorier') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_comptes_bloques`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_comptes_bloques` (
`id_compte_bloque` int(11)
,`id_membre` int(11)
,`noms` varchar(150)
,`numero_compte_bloque` varchar(30)
,`solde` decimal(12,2)
,`statut` enum('Bloque','Urgent','Debloque')
,`date_creation` date
,`date_deblocage` date
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_comptes_debloques`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_comptes_debloques` (
`id_compte_bloque` int(11)
,`id_membre` int(11)
,`noms` varchar(150)
,`numero_compte_bloque` varchar(30)
,`solde` decimal(12,2)
,`statut` enum('Bloque','Urgent','Debloque')
,`date_creation` date
,`date_deblocage` date
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_comptes_urgents`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_comptes_urgents` (
`id_compte_bloque` int(11)
,`id_membre` int(11)
,`noms` varchar(150)
,`numero_compte_bloque` varchar(30)
,`solde` decimal(12,2)
,`statut` enum('Bloque','Urgent','Debloque')
,`date_creation` date
,`date_deblocage` date
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_compte_bloque_global`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_compte_bloque_global` (
`id_compte_bloque` int(11)
,`id_membre` int(11)
,`noms` varchar(150)
,`numero_compte_bloque` varchar(30)
,`solde` decimal(12,2)
,`statut` enum('Bloque','Urgent','Debloque')
,`date_creation` date
,`date_deblocage` date
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_credits_en_cours`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_credits_en_cours` (
`id_credit` int(11)
,`id_membre` int(11)
,`noms` varchar(150)
,`type_credit` varchar(50)
,`montant_credit` decimal(12,2)
,`date_credit` date
,`statut` enum('En cours','Soldé')
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_dashboard_compte_bloque`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_dashboard_compte_bloque` (
`nb_bloques` decimal(23,0)
,`nb_urgents` decimal(23,0)
,`nb_debloques` decimal(23,0)
,`total_comptes` bigint(21)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_historique_retraits`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_historique_retraits` (
`id_retrait` int(11)
,`numero_compte` varchar(30)
,`nom_membre` varchar(150)
,`montant` decimal(12,2)
,`date_retrait` date
,`libelle` varchar(200)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_membres_comptes`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_membres_comptes` (
`id_membre` int(11)
,`noms` varchar(150)
,`sexe` varchar(10)
,`telephone` varchar(20)
,`statut` enum('Actif','Suspendu')
,`id_compte` int(11)
,`numero_compte` varchar(30)
,`solde` decimal(12,2)
,`est_bloque` tinyint(1)
,`date_deblocage` date
,`date_creation` date
);

-- --------------------------------------------------------

--
-- Structure de la table `v_remboursements_credit`
--

CREATE TABLE `v_remboursements_credit` (
  `id_credit` int(11) DEFAULT NULL,
  `noms` varchar(150) DEFAULT NULL,
  `montant_credit` decimal(12,2) DEFAULT NULL,
  `total_rembourse` decimal(34,2) DEFAULT NULL,
  `reste_a_payer` decimal(35,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `v_situation_financiere`
--

CREATE TABLE `v_situation_financiere` (
  `total_soldes` decimal(34,2) DEFAULT NULL,
  `total_credits_en_cours` decimal(34,2) DEFAULT NULL,
  `total_cotisations` decimal(34,2) DEFAULT NULL,
  `total_remboursements` decimal(34,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la vue `v_comptes_bloques`
--
DROP TABLE IF EXISTS `v_comptes_bloques`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_comptes_bloques`  AS SELECT `v_compte_bloque_global`.`id_compte_bloque` AS `id_compte_bloque`, `v_compte_bloque_global`.`id_membre` AS `id_membre`, `v_compte_bloque_global`.`noms` AS `noms`, `v_compte_bloque_global`.`numero_compte_bloque` AS `numero_compte_bloque`, `v_compte_bloque_global`.`solde` AS `solde`, `v_compte_bloque_global`.`statut` AS `statut`, `v_compte_bloque_global`.`date_creation` AS `date_creation`, `v_compte_bloque_global`.`date_deblocage` AS `date_deblocage` FROM `v_compte_bloque_global` WHERE `v_compte_bloque_global`.`statut` = 'Bloque' ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_comptes_debloques`
--
DROP TABLE IF EXISTS `v_comptes_debloques`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_comptes_debloques`  AS SELECT `v_compte_bloque_global`.`id_compte_bloque` AS `id_compte_bloque`, `v_compte_bloque_global`.`id_membre` AS `id_membre`, `v_compte_bloque_global`.`noms` AS `noms`, `v_compte_bloque_global`.`numero_compte_bloque` AS `numero_compte_bloque`, `v_compte_bloque_global`.`solde` AS `solde`, `v_compte_bloque_global`.`statut` AS `statut`, `v_compte_bloque_global`.`date_creation` AS `date_creation`, `v_compte_bloque_global`.`date_deblocage` AS `date_deblocage` FROM `v_compte_bloque_global` WHERE `v_compte_bloque_global`.`statut` = 'Debloque' ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_comptes_urgents`
--
DROP TABLE IF EXISTS `v_comptes_urgents`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_comptes_urgents`  AS SELECT `v_compte_bloque_global`.`id_compte_bloque` AS `id_compte_bloque`, `v_compte_bloque_global`.`id_membre` AS `id_membre`, `v_compte_bloque_global`.`noms` AS `noms`, `v_compte_bloque_global`.`numero_compte_bloque` AS `numero_compte_bloque`, `v_compte_bloque_global`.`solde` AS `solde`, `v_compte_bloque_global`.`statut` AS `statut`, `v_compte_bloque_global`.`date_creation` AS `date_creation`, `v_compte_bloque_global`.`date_deblocage` AS `date_deblocage` FROM `v_compte_bloque_global` WHERE `v_compte_bloque_global`.`statut` = 'Urgent' ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_compte_bloque_global`
--
DROP TABLE IF EXISTS `v_compte_bloque_global`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_compte_bloque_global`  AS SELECT `cb`.`id_compte_bloque` AS `id_compte_bloque`, `m`.`id_membre` AS `id_membre`, `m`.`noms` AS `noms`, `cb`.`numero_compte_bloque` AS `numero_compte_bloque`, `cb`.`solde` AS `solde`, `cb`.`statut` AS `statut`, `cb`.`date_creation` AS `date_creation`, `cb`.`date_deblocage` AS `date_deblocage` FROM (`tcompte_bloque` `cb` join `tmembrebl` `m` on(`cb`.`id_membre` = `m`.`id_membre`)) ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_credits_en_cours`
--
DROP TABLE IF EXISTS `v_credits_en_cours`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_credits_en_cours`  AS SELECT `cr`.`id_credit` AS `id_credit`, `m`.`id_membre` AS `id_membre`, `m`.`noms` AS `noms`, `tc`.`libelle` AS `type_credit`, `cr`.`montant_credit` AS `montant_credit`, `cr`.`date_credit` AS `date_credit`, `cr`.`statut` AS `statut` FROM ((`tcredit` `cr` join `tmembre` `m` on(`cr`.`id_membre` = `m`.`id_membre`)) join `ttype_credit` `tc` on(`cr`.`id_type_credit` = `tc`.`id_type_credit`)) WHERE `cr`.`statut` = 'En cours' ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_dashboard_compte_bloque`
--
DROP TABLE IF EXISTS `v_dashboard_compte_bloque`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_dashboard_compte_bloque`  AS SELECT sum(`tcompte_bloque`.`statut` = 'Bloque') AS `nb_bloques`, sum(`tcompte_bloque`.`statut` = 'Urgent') AS `nb_urgents`, sum(`tcompte_bloque`.`statut` = 'Debloque') AS `nb_debloques`, count(0) AS `total_comptes` FROM `tcompte_bloque` ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_historique_retraits`
--
DROP TABLE IF EXISTS `v_historique_retraits`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_historique_retraits`  AS SELECT `r`.`id_retrait` AS `id_retrait`, `c`.`numero_compte` AS `numero_compte`, `c`.`nom_membre` AS `nom_membre`, `r`.`montant` AS `montant`, `r`.`date_retrait` AS `date_retrait`, `r`.`libelle` AS `libelle` FROM (`tretrait` `r` join `tcompte` `c` on(`r`.`id_compte` = `c`.`id_compte`)) ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_membres_comptes`
--
DROP TABLE IF EXISTS `v_membres_comptes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_membres_comptes`  AS SELECT `m`.`id_membre` AS `id_membre`, `m`.`noms` AS `noms`, `m`.`sexe` AS `sexe`, `m`.`telephone` AS `telephone`, `m`.`statut` AS `statut`, `c`.`id_compte` AS `id_compte`, `c`.`numero_compte` AS `numero_compte`, `c`.`solde` AS `solde`, `c`.`est_bloque` AS `est_bloque`, `c`.`date_deblocage` AS `date_deblocage`, `c`.`date_creation` AS `date_creation` FROM (`tmembre` `m` join `tcompte` `c` on(`m`.`id_membre` = `c`.`id_membre`)) ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `tcompte_bloque`
--
ALTER TABLE `tcompte_bloque`
  ADD PRIMARY KEY (`id_compte_bloque`),
  ADD UNIQUE KEY `numero_compte_bloque` (`numero_compte_bloque`),
  ADD KEY `fk_membre_compte` (`id_membre`);

--
-- Index pour la table `tdepot_compte_bloque`
--
ALTER TABLE `tdepot_compte_bloque`
  ADD PRIMARY KEY (`id_depot`),
  ADD KEY `fk_compte_depot` (`id_compte_bloque`);

--
-- Index pour la table `tmembrebl`
--
ALTER TABLE `tmembrebl`
  ADD PRIMARY KEY (`id_membre`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `tcompte_bloque`
--
ALTER TABLE `tcompte_bloque`
  MODIFY `id_compte_bloque` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tdepot_compte_bloque`
--
ALTER TABLE `tdepot_compte_bloque`
  MODIFY `id_depot` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tmembrebl`
--
ALTER TABLE `tmembrebl`
  MODIFY `id_membre` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `tcompte_bloque`
--
ALTER TABLE `tcompte_bloque`
  ADD CONSTRAINT `fk_membre_compte` FOREIGN KEY (`id_membre`) REFERENCES `tmembrebl` (`id_membre`) ON DELETE CASCADE;

--
-- Contraintes pour la table `tdepot_compte_bloque`
--
ALTER TABLE `tdepot_compte_bloque`
  ADD CONSTRAINT `fk_compte_depot` FOREIGN KEY (`id_compte_bloque`) REFERENCES `tcompte_bloque` (`id_compte_bloque`) ON DELETE CASCADE;

DELIMITER $$
--
-- Évènements
--
CREATE DEFINER=`root`@`localhost` EVENT `evt_maj_statut_compte` ON SCHEDULE EVERY 1 DAY STARTS '2026-01-04 18:58:44' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE Tcompte_bloque
SET statut =
    CASE
        WHEN date_deblocage <= CURDATE() THEN 'Debloque'
        WHEN DATEDIFF(date_deblocage, CURDATE()) BETWEEN 1 AND 15 THEN 'Urgent'
        ELSE 'Bloque'
    END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
