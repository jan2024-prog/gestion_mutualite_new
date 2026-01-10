-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 05 jan. 2026 à 03:44
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
(1, 'MUT-2025-0001', 1, 'Jean Kasereka', -10000.00, 0, NULL, '2025-12-26'),
(2, 'MUT-2025-0002', 2, 'Marie Mukamana', 4990.00, 0, NULL, '2025-12-26');

-- --------------------------------------------------------

--
-- Structure de la table `tcompte_bloque`
--

CREATE TABLE `tcompte_bloque` (
  `id_compte_bloque` int(11) NOT NULL,
  `id_membre` int(11) NOT NULL,
  `noms` varchar(200) NOT NULL,
  `numero_compte_bloque` varchar(30) DEFAULT NULL,
  `solde` decimal(12,2) DEFAULT 0.00,
  `date_creation` date DEFAULT NULL,
  `date_deblocage` date DEFAULT NULL,
  `statut` enum('Bloqué','Urgent','Débloqué') DEFAULT 'Bloqué'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tcompte_bloque`
--

INSERT INTO `tcompte_bloque` (`id_compte_bloque`, `id_membre`, `noms`, `numero_compte_bloque`, `solde`, `date_creation`, `date_deblocage`, `statut`) VALUES
(3, 4, 'patricia sokoni ', 'MUT-2025-002', 0.00, NULL, NULL, 'Bloqué'),
(4, 5, 'kambale mutenyo janvier', 'MUT-2025-003', 90.00, NULL, NULL, 'Bloqué'),
(5, 6, 'Gracian Esosa', 'COMPTBLOC--2025', 0.00, NULL, NULL, 'Bloqué'),
(6, 7, 'Gracian Esosa ebilib', 'CBL-2025-001', 120.00, NULL, NULL, 'Bloqué');

--
-- Déclencheurs `tcompte_bloque`
--
DELIMITER $$
CREATE TRIGGER `trg_init_statut_compte_bloque` BEFORE INSERT ON `tcompte_bloque` FOR EACH ROW BEGIN
    IF NEW.date_deblocage <= CURDATE() THEN
        SET NEW.statut = 'Débloqué';
    ELSE
        SET NEW.statut = 'Bloqué';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_update_debloque` BEFORE UPDATE ON `tcompte_bloque` FOR EACH ROW BEGIN
    IF NEW.date_deblocage <= CURDATE() THEN
        SET NEW.statut = 'Débloqué';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_update_urgent` BEFORE UPDATE ON `tcompte_bloque` FOR EACH ROW BEGIN
    IF NEW.date_deblocage IS NOT NULL THEN
        IF DATEDIFF(NEW.date_deblocage, CURDATE()) <= 15
           AND DATEDIFF(NEW.date_deblocage, CURDATE()) > 0 THEN
            SET NEW.statut = 'Urgent';
        END IF;
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
  `id_membre` int(11) DEFAULT NULL,
  `montant` decimal(12,2) DEFAULT NULL,
  `semaine` int(11) DEFAULT NULL,
  `annee` int(11) DEFAULT NULL,
  `libelle` varchar(200) DEFAULT NULL,
  `date_cotisation` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tcotisation`
--

INSERT INTO `tcotisation` (`id_cotisation`, `id_membre`, `montant`, `semaine`, `annee`, `libelle`, `date_cotisation`) VALUES
(1, 1, 5000.00, 1, 2025, 'Cotisation semaine 1', '2025-12-26'),
(2, 2, 5000.00, 1, 2025, 'Cotisation semaine 1', '2025-12-26');

--
-- Déclencheurs `tcotisation`
--
DELIMITER $$
CREATE TRIGGER `trg_cotisation_compte` AFTER INSERT ON `tcotisation` FOR EACH ROW BEGIN
    UPDATE Tcompte
    SET solde = solde + NEW.montant
    WHERE id_membre = NEW.id_membre;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_cotisation_delete_compte` AFTER DELETE ON `tcotisation` FOR EACH ROW BEGIN
    UPDATE Tcompte
    SET solde = solde - OLD.montant
    WHERE id_membre = OLD.id_membre;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_cotisation_update_compte` AFTER UPDATE ON `tcotisation` FOR EACH ROW BEGIN
    /* Cas 1 : même compte, montant modifié */
    IF OLD.id_membre = NEW.id_membre THEN
        UPDATE Tcompte
        SET solde = solde + (NEW.montant - OLD.montant)
        WHERE id_membre = NEW.id_membre;
    ELSE
        /* Cas 2 : compte changé */
        UPDATE Tcompte
        SET solde = solde - OLD.montant
        WHERE id_membre = OLD.id_membre;

        UPDATE Tcompte
        SET solde = solde + NEW.montant
        WHERE id_membre = NEW.id_membre;
    END IF;
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
  `nom` varchar(200) NOT NULL,
  `id_type_credit` int(11) DEFAULT NULL,
  `montant_credit` decimal(12,2) DEFAULT NULL,
  `date_credit` date DEFAULT NULL,
  `date_echea` varchar(200) NOT NULL,
  `statut` enum('En cours','Soldé') DEFAULT 'En cours',
  `libele` varchar(200) NOT NULL,
  `penalite_active` tinyint(1) DEFAULT 1,
  `taux_penalite` decimal(5,2) DEFAULT 10.00,
  `last_penalite_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tcredit`
--

INSERT INTO `tcredit` (`id_credit`, `id_membre`, `nom`, `id_type_credit`, `montant_credit`, `date_credit`, `date_echea`, `statut`, `libele`, `penalite_active`, `taux_penalite`, `last_penalite_date`) VALUES
(1, 1, '', 1, 20000.00, '2025-12-26', '', 'En cours', '', 1, 10.00, NULL),
(2, 2, 'Marie Mukamana', 3, 10.00, '2026-01-04', '', 'En cours', 'credit giga', 1, 10.00, NULL);

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
DELIMITER $$
CREATE TRIGGER `trg_credit_solde_auto` AFTER UPDATE ON `tcredit` FOR EACH ROW BEGIN
    IF NEW.montant_credit <= 0 THEN
        UPDATE Tcredit
        SET montant_credit = 0,
            statut = 'Soldé'
        WHERE id_credit = NEW.id_credit;
    ELSE
        UPDATE Tcredit
        SET statut = 'En cours'
        WHERE id_credit = NEW.id_credit;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_credit_solde_si_zero` AFTER UPDATE ON `tcredit` FOR EACH ROW BEGIN
    IF NEW.montant_credit <= 0 THEN
        UPDATE Tcredit
        SET statut = 'Soldé',
            montant_credit = 0
        WHERE id_credit = NEW.id_credit;
    END IF;
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
  `montant` decimal(12,2) NOT NULL CHECK (`montant` > 0),
  `date_depot` datetime DEFAULT current_timestamp(),
  `mode_paiement` varchar(50) DEFAULT NULL,
  `libele` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tdepot_compte_bloque`
--

INSERT INTO `tdepot_compte_bloque` (`id_depot`, `id_compte_bloque`, `montant`, `date_depot`, `mode_paiement`, `libele`) VALUES
(4, 6, 120.00, '2026-01-04 05:03:00', 'cash', 'depot compte bloque'),
(5, 4, 90.00, '2026-01-03 00:00:00', 'cash', 'depot compte bloque');

--
-- Déclencheurs `tdepot_compte_bloque`
--
DELIMITER $$
CREATE TRIGGER `trg_delete_depot_compte_bloque` AFTER DELETE ON `tdepot_compte_bloque` FOR EACH ROW BEGIN
    UPDATE Tcompte_bloque
    SET solde = solde - OLD.montant
    WHERE id_compte_bloque = OLD.id_compte_bloque;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_depot_compte_bloque` AFTER INSERT ON `tdepot_compte_bloque` FOR EACH ROW BEGIN
    UPDATE Tcompte_bloque
    SET solde = solde + NEW.montant
    WHERE id_compte_bloque = NEW.id_compte_bloque;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_update_depot_compte_bloque` AFTER UPDATE ON `tdepot_compte_bloque` FOR EACH ROW BEGIN
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
(1, 'Mutualité Espoir', 'Butembo', '0990000000', 'contact@espoir.cd', '2025-12-26');

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
  `statut` enum('Actif','Suspendu') DEFAULT 'Actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tmembre`
--

INSERT INTO `tmembre` (`id_membre`, `id_entreprise`, `noms`, `sexe`, `telephone`, `datenaissance`, `date_adhesion`, `statut`) VALUES
(1, 1, 'Jean Kasereka', 'M', '0991111111', NULL, '2025-12-26', 'Actif'),
(2, 1, 'Marie Mukamana', 'F', '0992222222', NULL, '2025-12-26', 'Actif');

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
  `numero_compte_bloque` varchar(15) NOT NULL,
  `sexe` varchar(10) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `datenaissance` date DEFAULT NULL,
  `date_adhesion` date DEFAULT NULL,
  `etatcivil` varchar(100) DEFAULT NULL,
  `photo` varchar(100) DEFAULT NULL,
  `date_deblocage` date DEFAULT NULL,
  `statut` enum('Actif','Suspendu') DEFAULT 'Actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tmembrebl`
--

INSERT INTO `tmembrebl` (`id_membre`, `noms`, `numero_compte_bloque`, `sexe`, `telephone`, `datenaissance`, `date_adhesion`, `etatcivil`, `photo`, `date_deblocage`, `statut`) VALUES
(1, 'kambale mutenyo', 'MUT-2025-001', 'masculin', '+243994898765', '2024-11-08', NULL, 'marié', NULL, NULL, 'Actif'),
(4, 'patricia sokoni ', 'MUT-2025-002', 'feminin', '+243994', '2025-12-19', NULL, 'cellibataire', NULL, NULL, ''),
(5, 'kambale mutenyo janvier', 'MUT-2025-003', 'masculin', '+243994898765', '2025-12-09', NULL, 'marié', NULL, NULL, 'Actif'),
(6, 'Gracian Esosa', 'COMPTBLOC--2025', 'masculin', '+243994898765', '2025-12-31', NULL, 'divorsé', NULL, NULL, 'Actif'),
(7, 'Gracian Esosa ebilib', 'CBL-2025-001', 'feminin', '+243994898765', '2025-12-31', NULL, 'divorsé', NULL, NULL, 'Actif');

--
-- Déclencheurs `tmembrebl`
--
DELIMITER $$
CREATE TRIGGER `ajoute_compte` AFTER INSERT ON `tmembrebl` FOR EACH ROW BEGIN
    INSERT INTO tcompte_bloque(id_membre, noms, numero_compte_bloque, solde, date_deblocage, statut) 
    VALUES (New.id_membre, New.noms, New.numero_compte_bloque,'0', New.date_deblocage, New.statut);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `tremboursement`
--

CREATE TABLE `tremboursement` (
  `id_remboursement` int(11) NOT NULL,
  `id_credit` int(11) DEFAULT NULL,
  `montant_rembourse` decimal(12,2) DEFAULT NULL,
  `date_remboursement` date DEFAULT NULL,
  `libele` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tremboursement`
--

INSERT INTO `tremboursement` (`id_remboursement`, `id_credit`, `montant_rembourse`, `date_remboursement`, `libele`) VALUES
(1, 1, 5000.00, '2025-12-26', '');

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
CREATE TRIGGER `trg_diminution_credit_apres_remboursement` AFTER INSERT ON `tremboursement` FOR EACH ROW BEGIN
    UPDATE Tcredit
    SET montant_credit = montant_credit - NEW.montant_rembourse
    WHERE id_credit = NEW.id_credit;
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
DELIMITER $$
CREATE TRIGGER `trg_update_credit_apres_modif_remboursement` AFTER UPDATE ON `tremboursement` FOR EACH ROW BEGIN
    DECLARE difference DECIMAL(12,2);

    SET difference = NEW.montant_rembourse - OLD.montant_rembourse;

    UPDATE Tcredit
    SET montant_credit = montant_credit - difference
    WHERE id_credit = NEW.id_credit;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_verif_remboursement_credit` BEFORE INSERT ON `tremboursement` FOR EACH ROW BEGIN
    DECLARE reste DECIMAL(12,2);

    SELECT montant_credit
    INTO reste
    FROM Tcredit
    WHERE id_credit = NEW.id_credit;

    IF NEW.montant_rembourse > reste THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Le montant dépasse le reste du crédit';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_verif_update_remboursement` BEFORE UPDATE ON `tremboursement` FOR EACH ROW BEGIN
    DECLARE reste DECIMAL(12,2);

    SELECT montant_credit + OLD.montant_rembourse
    INTO reste
    FROM Tcredit
    WHERE id_credit = NEW.id_credit;

    IF NEW.montant_rembourse > reste THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Montant de remboursement invalide';
    END IF;
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
  `montant` decimal(12,2) DEFAULT NULL,
  `date_retrait` date DEFAULT NULL,
  `libele` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
CREATE TRIGGER `trg_retrait_delete_compte` AFTER DELETE ON `tretrait` FOR EACH ROW BEGIN
    UPDATE Tcompte
    SET solde = solde + OLD.montant
    WHERE id_compte = OLD.id_compte;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_retrait_update_compte` AFTER UPDATE ON `tretrait` FOR EACH ROW BEGIN
    DECLARE difference DECIMAL(12,2);

    -- Calcul de la différence
    SET difference = NEW.montant - OLD.montant;

    -- Ajustement du solde
    UPDATE Tcompte
    SET solde = solde - difference
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
(1, 'Crédit social', 5.00, 6),
(2, 'Crédit urgence', 3.00, 3),
(3, 'giga', 10.00, 3);

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
`numero_compte` varchar(30)
,`nom_membre` varchar(150)
,`solde` decimal(12,2)
,`date_deblocage` date
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_comptes_bloques_actifs`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_comptes_bloques_actifs` (
`id_compte_bloque` int(11)
,`id_membre` int(11)
,`noms` varchar(150)
,`numero_compte_bloque` varchar(30)
,`solde` decimal(12,2)
,`statut` enum('Bloqué','Urgent','Débloqué')
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
,`statut` enum('Bloqué','Urgent','Débloqué')
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
,`statut` enum('Bloqué','Urgent','Débloqué')
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
,`statut` enum('Bloqué','Urgent','Débloqué')
,`date_creation` date
,`date_deblocage` date
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_cotisations_membres`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_cotisations_membres` (
`id_membre` int(11)
,`noms` varchar(150)
,`id_cotisation` int(11)
,`semaine` int(11)
,`annee` int(11)
,`montant` decimal(12,2)
,`libelle` varchar(200)
,`date_cotisation` date
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
`nb_bloques` decimal(22,0)
,`nb_urgents` decimal(22,0)
,`nb_debloques` decimal(22,0)
,`total_comptes` bigint(21)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_historique_compte_bloque`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_historique_compte_bloque` (
`id_membre` int(11)
,`noms` varchar(150)
,`numero_compte_bloque` varchar(30)
,`solde` decimal(12,2)
,`statut` enum('Bloqué','Urgent','Débloqué')
,`date_creation` date
,`date_deblocage` date
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_historique_retraits`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_historique_retraits` (
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
-- Doublure de structure pour la vue `v_remboursements_credit`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_remboursements_credit` (
`id_credit` int(11)
,`noms` varchar(150)
,`montant_credit` decimal(12,2)
,`total_rembourse` decimal(34,2)
,`reste_a_payer` decimal(35,2)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_situation_financiere`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_situation_financiere` (
`total_soldes` decimal(34,2)
,`total_credits_en_cours` decimal(34,2)
,`total_cotisations` decimal(34,2)
,`total_remboursements` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_statistiques_compte_bloque`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_statistiques_compte_bloque` (
`total_comptes` bigint(21)
,`total_bloques` decimal(22,0)
,`total_urgents` decimal(22,0)
,`total_debloques` decimal(22,0)
,`solde_total` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_total_cotisations_membre`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_total_cotisations_membre` (
`id_membre` int(11)
,`noms` varchar(150)
,`total_cotise` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Structure de la vue `v_comptes_bloques`
--
DROP TABLE IF EXISTS `v_comptes_bloques`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_comptes_bloques`  AS SELECT `tcompte`.`numero_compte` AS `numero_compte`, `tcompte`.`nom_membre` AS `nom_membre`, `tcompte`.`solde` AS `solde`, `tcompte`.`date_deblocage` AS `date_deblocage` FROM `tcompte` WHERE `tcompte`.`est_bloque` = 1 ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_comptes_bloques_actifs`
--
DROP TABLE IF EXISTS `v_comptes_bloques_actifs`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_comptes_bloques_actifs`  AS SELECT `v_compte_bloque_global`.`id_compte_bloque` AS `id_compte_bloque`, `v_compte_bloque_global`.`id_membre` AS `id_membre`, `v_compte_bloque_global`.`noms` AS `noms`, `v_compte_bloque_global`.`numero_compte_bloque` AS `numero_compte_bloque`, `v_compte_bloque_global`.`solde` AS `solde`, `v_compte_bloque_global`.`statut` AS `statut`, `v_compte_bloque_global`.`date_creation` AS `date_creation`, `v_compte_bloque_global`.`date_deblocage` AS `date_deblocage` FROM `v_compte_bloque_global` WHERE `v_compte_bloque_global`.`statut` = 'Bloqué' ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_comptes_debloques`
--
DROP TABLE IF EXISTS `v_comptes_debloques`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_comptes_debloques`  AS SELECT `v_compte_bloque_global`.`id_compte_bloque` AS `id_compte_bloque`, `v_compte_bloque_global`.`id_membre` AS `id_membre`, `v_compte_bloque_global`.`noms` AS `noms`, `v_compte_bloque_global`.`numero_compte_bloque` AS `numero_compte_bloque`, `v_compte_bloque_global`.`solde` AS `solde`, `v_compte_bloque_global`.`statut` AS `statut`, `v_compte_bloque_global`.`date_creation` AS `date_creation`, `v_compte_bloque_global`.`date_deblocage` AS `date_deblocage` FROM `v_compte_bloque_global` WHERE `v_compte_bloque_global`.`statut` = 'Débloqué' ;

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
-- Structure de la vue `v_cotisations_membres`
--
DROP TABLE IF EXISTS `v_cotisations_membres`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_cotisations_membres`  AS SELECT `m`.`id_membre` AS `id_membre`, `m`.`noms` AS `noms`, `c`.`id_cotisation` AS `id_cotisation`, `c`.`semaine` AS `semaine`, `c`.`annee` AS `annee`, `c`.`montant` AS `montant`, `c`.`libelle` AS `libelle`, `c`.`date_cotisation` AS `date_cotisation` FROM (`tcotisation` `c` join `tmembre` `m` on(`c`.`id_membre` = `m`.`id_membre`)) ;

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

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_dashboard_compte_bloque`  AS SELECT sum(case when `tcompte_bloque`.`statut` = 'Bloqué' then 1 else 0 end) AS `nb_bloques`, sum(case when `tcompte_bloque`.`statut` = 'Urgent' then 1 else 0 end) AS `nb_urgents`, sum(case when `tcompte_bloque`.`statut` = 'Débloqué' then 1 else 0 end) AS `nb_debloques`, count(0) AS `total_comptes` FROM `tcompte_bloque` ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_historique_compte_bloque`
--
DROP TABLE IF EXISTS `v_historique_compte_bloque`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_historique_compte_bloque`  AS SELECT `m`.`id_membre` AS `id_membre`, `m`.`noms` AS `noms`, `cb`.`numero_compte_bloque` AS `numero_compte_bloque`, `cb`.`solde` AS `solde`, `cb`.`statut` AS `statut`, `cb`.`date_creation` AS `date_creation`, `cb`.`date_deblocage` AS `date_deblocage` FROM (`tmembrebl` `m` left join `tcompte_bloque` `cb` on(`m`.`id_membre` = `cb`.`id_membre`)) ORDER BY `m`.`noms` ASC ;

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

-- --------------------------------------------------------

--
-- Structure de la vue `v_remboursements_credit`
--
DROP TABLE IF EXISTS `v_remboursements_credit`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_remboursements_credit`  AS SELECT `cr`.`id_credit` AS `id_credit`, `m`.`noms` AS `noms`, `cr`.`montant_credit` AS `montant_credit`, ifnull(sum(`r`.`montant_rembourse`),0) AS `total_rembourse`, `cr`.`montant_credit`- ifnull(sum(`r`.`montant_rembourse`),0) AS `reste_a_payer` FROM ((`tcredit` `cr` join `tmembre` `m` on(`cr`.`id_membre` = `m`.`id_membre`)) left join `tremboursement` `r` on(`cr`.`id_credit` = `r`.`id_credit`)) GROUP BY `cr`.`id_credit` ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_situation_financiere`
--
DROP TABLE IF EXISTS `v_situation_financiere`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_situation_financiere`  AS SELECT (select ifnull(sum(`tcompte`.`solde`),0) from `tcompte`) AS `total_soldes`, (select ifnull(sum(`tcredit`.`montant_credit`),0) from `tcredit` where `tcredit`.`statut` = 'En cours') AS `total_credits_en_cours`, (select ifnull(sum(`tcotisation`.`montant`),0) from `tcotisation`) AS `total_cotisations`, (select ifnull(sum(`tremboursement`.`montant_rembourse`),0) from `tremboursement`) AS `total_remboursements` ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_statistiques_compte_bloque`
--
DROP TABLE IF EXISTS `v_statistiques_compte_bloque`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_statistiques_compte_bloque`  AS SELECT count(0) AS `total_comptes`, sum(case when `tcompte_bloque`.`statut` = 'Bloqué' then 1 else 0 end) AS `total_bloques`, sum(case when `tcompte_bloque`.`statut` = 'Urgent' then 1 else 0 end) AS `total_urgents`, sum(case when `tcompte_bloque`.`statut` = 'Débloqué' then 1 else 0 end) AS `total_debloques`, sum(`tcompte_bloque`.`solde`) AS `solde_total` FROM `tcompte_bloque` ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_total_cotisations_membre`
--
DROP TABLE IF EXISTS `v_total_cotisations_membre`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_total_cotisations_membre`  AS SELECT `m`.`id_membre` AS `id_membre`, `m`.`noms` AS `noms`, ifnull(sum(`c`.`montant`),0) AS `total_cotise` FROM (`tmembre` `m` left join `tcotisation` `c` on(`m`.`id_membre` = `c`.`id_membre`)) GROUP BY `m`.`id_membre` ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `tcompte`
--
ALTER TABLE `tcompte`
  ADD PRIMARY KEY (`id_compte`),
  ADD UNIQUE KEY `numero_compte` (`numero_compte`),
  ADD UNIQUE KEY `id_membre` (`id_membre`);

--
-- Index pour la table `tcompte_bloque`
--
ALTER TABLE `tcompte_bloque`
  ADD PRIMARY KEY (`id_compte_bloque`),
  ADD UNIQUE KEY `numero_compte_bloque` (`numero_compte_bloque`),
  ADD KEY `id_membre` (`id_membre`);

--
-- Index pour la table `tcotisation`
--
ALTER TABLE `tcotisation`
  ADD PRIMARY KEY (`id_cotisation`),
  ADD UNIQUE KEY `id_membre` (`id_membre`,`semaine`,`annee`);

--
-- Index pour la table `tcredit`
--
ALTER TABLE `tcredit`
  ADD PRIMARY KEY (`id_credit`),
  ADD KEY `id_membre` (`id_membre`),
  ADD KEY `id_type_credit` (`id_type_credit`);

--
-- Index pour la table `tdepot_compte_bloque`
--
ALTER TABLE `tdepot_compte_bloque`
  ADD PRIMARY KEY (`id_depot`),
  ADD KEY `id_compte_bloque` (`id_compte_bloque`);

--
-- Index pour la table `tentreprise`
--
ALTER TABLE `tentreprise`
  ADD PRIMARY KEY (`id_entreprise`);

--
-- Index pour la table `tmembre`
--
ALTER TABLE `tmembre`
  ADD PRIMARY KEY (`id_membre`),
  ADD KEY `id_entreprise` (`id_entreprise`);

--
-- Index pour la table `tmembrebl`
--
ALTER TABLE `tmembrebl`
  ADD PRIMARY KEY (`id_membre`),
  ADD UNIQUE KEY `numero_compte_bloque` (`numero_compte_bloque`);

--
-- Index pour la table `tremboursement`
--
ALTER TABLE `tremboursement`
  ADD PRIMARY KEY (`id_remboursement`),
  ADD KEY `id_credit` (`id_credit`);

--
-- Index pour la table `tretrait`
--
ALTER TABLE `tretrait`
  ADD PRIMARY KEY (`id_retrait`),
  ADD KEY `id_compte` (`id_compte`);

--
-- Index pour la table `ttype_credit`
--
ALTER TABLE `ttype_credit`
  ADD PRIMARY KEY (`id_type_credit`);

--
-- Index pour la table `tutilisateur`
--
ALTER TABLE `tutilisateur`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `tcompte`
--
ALTER TABLE `tcompte`
  MODIFY `id_compte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `tcompte_bloque`
--
ALTER TABLE `tcompte_bloque`
  MODIFY `id_compte_bloque` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `tcotisation`
--
ALTER TABLE `tcotisation`
  MODIFY `id_cotisation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `tcredit`
--
ALTER TABLE `tcredit`
  MODIFY `id_credit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `tdepot_compte_bloque`
--
ALTER TABLE `tdepot_compte_bloque`
  MODIFY `id_depot` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `tentreprise`
--
ALTER TABLE `tentreprise`
  MODIFY `id_entreprise` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `tmembre`
--
ALTER TABLE `tmembre`
  MODIFY `id_membre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `tmembrebl`
--
ALTER TABLE `tmembrebl`
  MODIFY `id_membre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `tremboursement`
--
ALTER TABLE `tremboursement`
  MODIFY `id_remboursement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `tretrait`
--
ALTER TABLE `tretrait`
  MODIFY `id_retrait` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ttype_credit`
--
ALTER TABLE `ttype_credit`
  MODIFY `id_type_credit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `tutilisateur`
--
ALTER TABLE `tutilisateur`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `tcompte`
--
ALTER TABLE `tcompte`
  ADD CONSTRAINT `tcompte_ibfk_1` FOREIGN KEY (`id_membre`) REFERENCES `tmembre` (`id_membre`);

--
-- Contraintes pour la table `tcompte_bloque`
--
ALTER TABLE `tcompte_bloque`
  ADD CONSTRAINT `tcompte_bloque_ibfk_1` FOREIGN KEY (`id_membre`) REFERENCES `tmembrebl` (`id_membre`);

--
-- Contraintes pour la table `tcotisation`
--
ALTER TABLE `tcotisation`
  ADD CONSTRAINT `tcotisation_ibfk_1` FOREIGN KEY (`id_membre`) REFERENCES `tmembre` (`id_membre`);

--
-- Contraintes pour la table `tcredit`
--
ALTER TABLE `tcredit`
  ADD CONSTRAINT `tcredit_ibfk_1` FOREIGN KEY (`id_membre`) REFERENCES `tmembre` (`id_membre`),
  ADD CONSTRAINT `tcredit_ibfk_2` FOREIGN KEY (`id_type_credit`) REFERENCES `ttype_credit` (`id_type_credit`);

--
-- Contraintes pour la table `tdepot_compte_bloque`
--
ALTER TABLE `tdepot_compte_bloque`
  ADD CONSTRAINT `tdepot_compte_bloque_ibfk_1` FOREIGN KEY (`id_compte_bloque`) REFERENCES `tcompte_bloque` (`id_compte_bloque`) ON DELETE CASCADE;

--
-- Contraintes pour la table `tmembre`
--
ALTER TABLE `tmembre`
  ADD CONSTRAINT `tmembre_ibfk_1` FOREIGN KEY (`id_entreprise`) REFERENCES `tentreprise` (`id_entreprise`);

--
-- Contraintes pour la table `tremboursement`
--
ALTER TABLE `tremboursement`
  ADD CONSTRAINT `tremboursement_ibfk_1` FOREIGN KEY (`id_credit`) REFERENCES `tcredit` (`id_credit`);

--
-- Contraintes pour la table `tretrait`
--
ALTER TABLE `tretrait`
  ADD CONSTRAINT `tretrait_ibfk_1` FOREIGN KEY (`id_compte`) REFERENCES `tcompte` (`id_compte`);

DELIMITER $$
--
-- Évènements
--
CREATE DEFINER=`root`@`localhost` EVENT `evt_maj_statut_compte_bloque` ON SCHEDULE EVERY 1 DAY STARTS '2025-12-27 06:54:23' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE Tcompte_bloque
SET statut = 
    CASE
        WHEN date_deblocage <= CURDATE() THEN 'Débloqué'
        WHEN DATEDIFF(date_deblocage, CURDATE()) <= 15 THEN 'Urgent'
        ELSE 'Bloqué'
    END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
