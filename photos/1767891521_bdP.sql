/* =========================================================
   SCRIPT FINAL – APPLICATION DE GESTION D’UNE MUTUALITÉ
   Compatible MySQL / phpMyAdmin
========================================================= */

/* =========================
   BASE DE DONNÉES
========================= */
DROP DATABASE IF EXISTS mutualite;
CREATE DATABASE mutualite;
USE mutualite;

/* =========================
   ENTREPRISE
========================= */
CREATE TABLE Tentreprise (
    id_entreprise INT AUTO_INCREMENT PRIMARY KEY,
    nom_entreprise VARCHAR(150),
    adresse VARCHAR(200),
    telephone VARCHAR(20),
    email VARCHAR(100),
    date_creation DATE
);

/* =========================
   MEMBRE
========================= */
CREATE TABLE Tmembre (
    id_membre INT AUTO_INCREMENT PRIMARY KEY,
    id_entreprise INT,
    noms VARCHAR(150) NOT NULL,
    sexe VARCHAR(10),
    telephone VARCHAR(20),
    datenaissance DATE,
    date_adhesion DATE,
    email VARCHAR(100),
    photo VARCHAR(100),
    statut ENUM('Actif','Suspendu') DEFAULT 'Actif',
    FOREIGN KEY (id_entreprise) REFERENCES Tentreprise(id_entreprise)
);

/* =========================
   COMPTE
========================= */
CREATE TABLE Tcompte (
    id_compte INT AUTO_INCREMENT PRIMARY KEY,
    numero_compte VARCHAR(30) UNIQUE,
    id_membre INT UNIQUE,
    nom_membre VARCHAR(150),
    solde DECIMAL(12,2) DEFAULT 0,
    est_bloque BOOLEAN DEFAULT FALSE,
    date_deblocage DATE,
    date_creation DATE,
    FOREIGN KEY (id_membre) REFERENCES Tmembre(id_membre)
);

/* =========================
   COTISATION (HEBDOMADAIRE)
========================= */
CREATE TABLE Tcotisation (
    id_cotisation INT AUTO_INCREMENT PRIMARY KEY,
    numero_compte VARCHAR UNIQUE,
    montant DECIMAL(12,2),
    semaine INT,
    annee INT,
    libelle VARCHAR(200),
    date_cotisation DATE,
    UNIQUE (numero_compte, semaine, annee),
    FOREIGN KEY (numero_compte) REFERENCES Tcompte(numero_compte)
);

/* =========================
   TYPE DE CRÉDIT
========================= */
CREATE TABLE Ttype_credit (
    id_type_credit INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50),
    taux_interet DECIMAL(5,2),
    duree_mois INT
);

/* =========================
   CRÉDIT
========================= */
CREATE TABLE Tcredit (
    id_credit INT AUTO_INCREMENT PRIMARY KEY,
    id_membre INT,
    nom VARCHAR(100),
    id_type_credit INT,
    montant_credit DECIMAL(12,2),
    date_credit DATE,
    statut ENUM('En cours','Soldé') DEFAULT 'En cours',
    libele VARCHAR(100),
    FOREIGN KEY (id_membre) REFERENCES Tmembre(id_membre),
    FOREIGN KEY (id_type_credit) REFERENCES Ttype_credit(id_type_credit)
);

/* =========================
   REMBOURSEMENT
========================= */
CREATE TABLE Tremboursement (
    id_remboursement INT AUTO_INCREMENT PRIMARY KEY,
    id_credit INT,
    montant_rembourse DECIMAL(12,2),
    date_remboursement DATE,
    libele VARCHAR(100),
    FOREIGN KEY (id_credit) REFERENCES Tcredit(id_credit)
);

/* =========================
   RETRAIT
========================= */
CREATE TABLE Tretrait (
    id_retrait INT AUTO_INCREMENT PRIMARY KEY,
    id_compte INT,
    nom VARCHAR(100),
    montant DECIMAL(12,2),
    date_retrait DATE,
    libelle VARCHAR(200),
    FOREIGN KEY (id_compte) REFERENCES Tcompte(id_compte)
);

/* =========================
   UTILISATEUR
========================= */
CREATE TABLE Tutilisateur (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    noms VARCHAR(100),
    username VARCHAR(50),
    mot_passe VARCHAR(255),
    role ENUM('Admin','Secretaire','Tresorier')
);

/* =========================
   TRIGGERS
========================= */
DELIMITER $$

/* Création automatique du compte lors de l’ajout d’un membre */
CREATE TRIGGER trg_creation_compte_membre
AFTER INSERT ON Tmembre
FOR EACH ROW
BEGIN
    DECLARE num_compte VARCHAR(30);
    DECLARE noms_complet VARCHAR(150);

    SET noms_complet = NEW.noms;
    SET num_compte = CONCAT('MUT-', YEAR(CURDATE()), '-', LPAD(NEW.id_membre,4,'0'));

    INSERT INTO Tcompte (numero_compte, id_membre, nom_membre, solde, date_creation)
    VALUES (num_compte, NEW.id_membre, noms_complet, 0, CURDATE());
END$$

/* Cotisation → augmentation du solde */
CREATE TRIGGER trg_cotisation_compte
AFTER INSERT ON Tcotisation
FOR EACH ROW
BEGIN
    UPDATE Tcompte
    SET solde = solde + NEW.montant
    WHERE id_membre = NEW.id_membre;
END$$

/* Crédit → diminution du solde */
CREATE TRIGGER trg_credit_compte
AFTER INSERT ON Tcredit
FOR EACH ROW
BEGIN
    UPDATE Tcompte
    SET solde = solde - NEW.montant_credit
    WHERE id_membre = NEW.id_membre;
END$$

/* Remboursement → augmentation du solde */
CREATE TRIGGER trg_remboursement_compte
AFTER INSERT ON Tremboursement
FOR EACH ROW
BEGIN
    UPDATE Tcompte
    SET solde = solde + NEW.montant_rembourse
    WHERE id_membre = (
        SELECT id_membre FROM Tcredit WHERE id_credit = NEW.id_credit
    );
END$$

/* Crédit soldé automatiquement */
CREATE TRIGGER trg_credit_solde
AFTER INSERT ON Tremboursement
FOR EACH ROW
BEGIN
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
END$$

/* Vérification compte bloqué avant retrait */
CREATE TRIGGER trg_verif_compte_bloque
BEFORE INSERT ON Tretrait
FOR EACH ROW
BEGIN
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
END$$

/* Retrait → diminution du solde */
CREATE TRIGGER trg_retrait_compte
AFTER INSERT ON Tretrait
FOR EACH ROW
BEGIN
    UPDATE Tcompte
    SET solde = solde - NEW.montant
    WHERE id_compte = NEW.id_compte;
END$$

DELIMITER ;

/* =========================
   DONNÉES DE TEST
========================= */
INSERT INTO Tentreprise VALUES
(NULL,'Mutualité Espoir','Butembo','0990000000','contact@espoir.cd',CURDATE());

INSERT INTO Tmembre (id_entreprise,noms,sexe,telephone,date_adhesion)
VALUES
(1,'Jean Kasereka','M','0991111111',CURDATE()),
(1,'Marie Mukamana','F','0992222222',CURDATE());

INSERT INTO Ttype_credit (libelle,taux_interet,duree_mois)
VALUES
('Crédit social',5,6),
('Crédit urgence',3,3);

INSERT INTO Tcotisation (id_membre,montant,semaine,annee,libelle,date_cotisation)
VALUES
(1,5000,1,2025,'Cotisation semaine 1',CURDATE()),
(2,5000,1,2025,'Cotisation semaine 1',CURDATE());

INSERT INTO Tcredit (id_membre,id_type_credit,montant_credit,date_credit)
VALUES
(1,1,20000,CURDATE());

INSERT INTO Tremboursement (id_credit,montant_rembourse,date_remboursement)
VALUES
(1,5000,CURDATE());

/* =========================================================
   FICHIER : views_mutualite.sql
   OBJET  : VUES POUR LA GESTION DE LA MUTUALITÉ
   SGBD   : MySQL
========================================================= */

USE mutualite;

/* =========================================================
   1. MEMBRES + COMPTES
========================================================= */
DROP VIEW IF EXISTS v_membres_comptes;
CREATE VIEW v_membres_comptes AS
SELECT 
    m.id_membre,
    m.noms,
    m.sexe,
    m.telephone,
    m.statut,
    c.id_compte,
    c.numero_compte,
    c.solde,
    c.est_bloque,
    c.date_deblocage,
    c.date_creation
FROM Tmembre m
JOIN Tcompte c ON m.id_membre = c.id_membre;

/* =========================================================
   2. COTISATIONS HEBDOMADAIRES
========================================================= */
DROP VIEW IF EXISTS v_cotisations_membres;
CREATE VIEW v_cotisations_membres AS
SELECT
    m.id_membre,
    m.noms,
    c.id_cotisation,
    c.semaine,
    c.annee,
    c.montant,
    c.libelle,
    c.date_cotisation
FROM Tcotisation c
JOIN Tmembre m ON c.id_membre = m.id_membre;

/* =========================================================
   3. TOTAL DES COTISATIONS PAR MEMBRE
========================================================= */
DROP VIEW IF EXISTS v_total_cotisations_membre;
CREATE VIEW v_total_cotisations_membre AS
SELECT
    m.id_membre,
    m.noms,
    IFNULL(SUM(c.montant),0) AS total_cotise
FROM Tmembre m
LEFT JOIN Tcotisation c ON m.id_membre = c.id_membre
GROUP BY m.id_membre;

/* =========================================================
   4. CRÉDITS EN COURS
========================================================= */
DROP VIEW IF EXISTS v_credits_en_cours;
CREATE VIEW v_credits_en_cours AS
SELECT
    cr.id_credit,
    m.id_membre,
    m.noms,
    tc.libelle AS type_credit,
    cr.montant_credit,
    cr.date_credit,
    cr.statut
FROM Tcredit cr
JOIN Tmembre m ON cr.id_membre = m.id_membre
JOIN Ttype_credit tc ON cr.id_type_credit = tc.id_type_credit
WHERE cr.statut = 'En cours';

/* =========================================================
   5. SITUATION DES REMBOURSEMENTS
========================================================= */
DROP VIEW IF EXISTS v_remboursements_credit;
CREATE VIEW v_remboursements_credit AS
SELECT
    cr.id_credit,
    m.noms,
    cr.montant_credit,
    IFNULL(SUM(r.montant_rembourse),0) AS total_rembourse,
    (cr.montant_credit - IFNULL(SUM(r.montant_rembourse),0)) AS reste_a_payer
FROM Tcredit cr
JOIN Tmembre m ON cr.id_membre = m.id_membre
LEFT JOIN Tremboursement r ON cr.id_credit = r.id_credit
GROUP BY cr.id_credit;

/* =========================================================
   6. SITUATION FINANCIÈRE GLOBALE
========================================================= */
DROP VIEW IF EXISTS v_situation_financiere;
CREATE VIEW v_situation_financiere AS
SELECT
    (SELECT IFNULL(SUM(solde),0) FROM Tcompte) AS total_soldes,
    (SELECT IFNULL(SUM(montant_credit),0) FROM Tcredit WHERE statut='En cours') AS total_credits_en_cours,
    (SELECT IFNULL(SUM(montant),0) FROM Tcotisation) AS total_cotisations,
    (SELECT IFNULL(SUM(montant_rembourse),0) FROM Tremboursement) AS total_remboursements;

/* =========================================================
   7. COMPTES BLOQUÉS
========================================================= */
DROP VIEW IF EXISTS v_comptes_bloques;
CREATE VIEW v_comptes_bloques AS
SELECT
    numero_compte,
    nom_membre,
    solde,
    date_deblocage
FROM Tcompte
WHERE est_bloque = TRUE;

/* =========================================================
   8. HISTORIQUE DES RETRAITS
========================================================= */
DROP VIEW IF EXISTS v_historique_retraits;
CREATE VIEW v_historique_retraits AS
SELECT
    r.id_retrait,
    c.numero_compte,
    c.nom_membre,
    r.montant,
    r.date_retrait,
    r.libelle
FROM Tretrait r
JOIN Tcompte c ON r.id_compte = c.id_compte;


    -- triggers de dimunition dans le compte apres suppression de la cotisation

DELIMITER $$

/* Suppression cotisation → diminution du solde du compte */
CREATE TRIGGER trg_cotisation_delete_compte
AFTER DELETE ON Tcotisation
FOR EACH ROW
BEGIN
    UPDATE Tcompte
    SET solde = solde - OLD.montant
    WHERE numero_compte = OLD.numero_compte;
END$$

DELIMITER ;

--  Apres modification de la cotisation
DELIMITER $$

CREATE TRIGGER trg_cotisation_update_compte
AFTER UPDATE ON Tcotisation
FOR EACH ROW
BEGIN
    /* Cas 1 : même compte, montant modifié */
    IF OLD.numero_compte = NEW.numero_compte THEN
        UPDATE Tcompte
        SET solde = solde + (NEW.montant - OLD.montant)
        WHERE numero_compte = NEW.numero_compte;
    ELSE
        /* Cas 2 : compte changé */
        UPDATE Tcompte
        SET solde = solde - OLD.montant
        WHERE numero_compte = OLD.numero_compte;

        UPDATE Tcompte
        SET solde = solde + NEW.montant
        WHERE numero_compte = NEW.numero_compte;
    END IF;
END$$

DELIMITER ;

-- Apres suppression dans la table retrait il faut que ca remet le montant dans la table compte
DELIMITER $$

CREATE TRIGGER trg_retrait_delete_compte
AFTER DELETE ON Tretrait
FOR EACH ROW
BEGIN
    UPDATE Tcompte
    SET solde = solde + OLD.montant
    WHERE id_compte = OLD.id_compte;
END$$

DELIMITER ;

--  Apres modification
DELIMITER $$

CREATE TRIGGER trg_retrait_update_compte
AFTER UPDATE ON Tretrait
FOR EACH ROW
BEGIN
    DECLARE difference DECIMAL(12,2);

    -- Calcul de la différence
    SET difference = NEW.montant - OLD.montant;

    -- Ajustement du solde
    UPDATE Tcompte
    SET solde = solde - difference
    WHERE id_compte = NEW.id_compte;
END$$

DELIMITER ;

-- TRIGGER : DIMINUTION DU MONTANT DU CRÉDIT APRÈS REMBOURSEMENT
DELIMITER $$

CREATE TRIGGER trg_diminution_credit_apres_remboursement
AFTER INSERT ON Tremboursement
FOR EACH ROW
BEGIN
    UPDATE Tcredit
    SET montant_credit = montant_credit - NEW.montant_rembourse
    WHERE id_credit = NEW.id_credit;
END$$

DELIMITER ;


-- PASSAGE AUTOMATIQUE À « SOLDÉ » SI LE CRÉDIT = 0

DELIMITER $$

CREATE TRIGGER trg_credit_solde_si_zero
AFTER UPDATE ON Tcredit
FOR EACH ROW
BEGIN
    IF NEW.montant_credit <= 0 THEN
        UPDATE Tcredit
        SET statut = 'Soldé',
            montant_credit = 0
        WHERE id_credit = NEW.id_credit;
    END IF;
END$$

DELIMITER ;

-- Empêcher un remboursement supérieur au reste du crédit
DELIMITER $$

CREATE TRIGGER trg_verif_remboursement_credit
BEFORE INSERT ON Tremboursement
FOR EACH ROW
BEGIN
    DECLARE reste DECIMAL(12,2);

    SELECT montant_credit
    INTO reste
    FROM Tcredit
    WHERE id_credit = NEW.id_credit;

    IF NEW.montant_rembourse > reste THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Le montant dépasse le reste du crédit';
    END IF;
END$$

DELIMITER ;

-- TRIGGER : AJUSTEMENT DU CRÉDIT APRÈS MODIFICATION
DELIMITER $$

CREATE TRIGGER trg_update_credit_apres_modif_remboursement
AFTER UPDATE ON Tremboursement
FOR EACH ROW
BEGIN
    DECLARE difference DECIMAL(12,2);

    SET difference = NEW.montant_rembourse - OLD.montant_rembourse;

    UPDATE Tcredit
    SET montant_credit = montant_credit - difference
    WHERE id_credit = NEW.id_credit;
END$$

DELIMITER ;

-- TRIGGER : CRÉDIT SOLDÉ AUTOMATIQUEMENT (RÉUTILISABLE)
DELIMITER $$

CREATE TRIGGER trg_credit_solde_auto
AFTER UPDATE ON Tcredit
FOR EACH ROW
BEGIN
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
END$$

DELIMITER ;

--  SÉCURITÉ : EMPÊCHER UNE MODIFICATION ILLÉGALE 
-- Empêcher qu’un remboursement modifié dépasse le reste du crédit


DELIMITER $$

CREATE TRIGGER trg_verif_update_remboursement
BEFORE UPDATE ON Tremboursement
FOR EACH ROW
BEGIN
    DECLARE reste DECIMAL(12,2);

    SELECT montant_credit + OLD.montant_rembourse
    INTO reste
    FROM Tcredit
    WHERE id_credit = NEW.id_credit;

    IF NEW.montant_rembourse > reste THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Montant de remboursement invalide';
    END IF;
END$$

DELIMITER ;




-- COMPTE BLOCKER
-- ============================================
-- ACTIVER LES EVENTS MYSQL
-- ============================================
SET GLOBAL event_scheduler = ON;

-- ============================================
-- TABLE MEMBRE (si pas encore créée)
-- ============================================
CREATE TABLE IF NOT EXISTS TmembreBl (
    id_membre INT AUTO_INCREMENT PRIMARY KEY,
    noms VARCHAR(150) NOT NULL,
    numero_compte_bloque
    sexe VARCHAR(10),
    telephone VARCHAR(20),
    datenaissance DATE,
    date_adhesion CURRENT DATE,
    etatcivil VARCHAR(200),
    email VARCHAR(100),
    photo VARCHAR(100),
    date_deblocage date,
    statut ENUM('Actif','Suspendu') DEFAULT 'Actif'
);

-- ============================================
-- TABLE DEPOT COMPTE BLOQUÉ
-- ============================================
CREATE TABLE IF NOT EXISTS Tdepot_compte_bloque (
    id_depot INT PRIMARY KEY AUTO_INCREMENT,
    id_compte_bloque INT NOT NULL,
    montant DECIMAL(12,2) NOT NULL CHECK (montant > 0),
    date_depot DATETIME DEFAULT CURRENT_TIMESTAMP,
    mode_paiement VARCHAR(50),
    libele VARCHAR(100),
    FOREIGN KEY (id_compte_bloque) 
        REFERENCES Tcompte_bloque(id_compte_bloque)
        ON DELETE CASCADE
);

-- ============================================
-- TABLE COMPTE BLOQUÉ
-- ============================================
CREATE TABLE IF NOT EXISTS Tcompte_bloque (
    id_compte_bloque INT AUTO_INCREMENT PRIMARY KEY,
    id_membre INT NOT NULL,
    numero_compte_bloque VARCHAR(30) UNIQUE,
    solde DECIMAL(12,2) DEFAULT 0,
    date_creation CURRENT DATE,
    date_deblocage DATE,
    statut ENUM('Bloqué','Urgent','Débloqué') DEFAULT 'Bloqué',
    FOREIGN KEY (id_membre) REFERENCES TmembreBl(id_membre)
);

-- ============================================
-- TRIGGER : STATUT À L'INSERTION
-- ============================================
DELIMITER $$

CREATE TRIGGER trg_init_statut_compte_bloque
BEFORE INSERT ON Tcompte_bloque
FOR EACH ROW
BEGIN
    IF NEW.date_deblocage <= CURDATE() THEN
        SET NEW.statut = 'Débloqué';
    ELSE
        SET NEW.statut = 'Bloqué';
    END IF;
END$$

DELIMITER ;

-- ============================================
-- TRIGGER : PASSAGE AUTOMATIQUE À "URGENT"
-- ============================================
DELIMITER $$

CREATE TRIGGER trg_update_urgent
BEFORE UPDATE ON Tcompte_bloque
FOR EACH ROW
BEGIN
    IF NEW.date_deblocage IS NOT NULL THEN
        IF DATEDIFF(NEW.date_deblocage, CURDATE()) <= 15
           AND DATEDIFF(NEW.date_deblocage, CURDATE()) > 0 THEN
            SET NEW.statut = 'Urgent';
        END IF;
    END IF;
END$$

DELIMITER ;

-- ============================================
-- TRIGGER : PASSAGE AUTOMATIQUE À "DÉBLOQUÉ"
-- ============================================
DELIMITER $$

CREATE TRIGGER trg_update_debloque
BEFORE UPDATE ON Tcompte_bloque
FOR EACH ROW
BEGIN
    IF NEW.date_deblocage <= CURDATE() THEN
        SET NEW.statut = 'Débloqué';
    END IF;
END$$

DELIMITER ;

-- ============================================
-- EVENT AUTOMATIQUE (MISE À JOUR CHAQUE JOUR)
-- ============================================
CREATE EVENT IF NOT EXISTS evt_maj_statut_compte_bloque
ON SCHEDULE EVERY 1 DAY
DO
UPDATE Tcompte_bloque
SET statut = 
    CASE
        WHEN date_deblocage <= CURDATE() THEN 'Débloqué'
        WHEN DATEDIFF(date_deblocage, CURDATE()) <= 15 THEN 'Urgent'
        ELSE 'Bloqué'
    END;


DROP VIEW IF EXISTS v_compte_bloque_global;
CREATE VIEW v_compte_bloque_global AS
SELECT 
    cb.id_compte_bloque,
    m.id_membre,
    m.noms,
    cb.numero_compte_bloque,
    cb.solde,
    cb.statut,
    cb.date_creation,
    cb.date_deblocage
FROM Tcompte_bloque cb
JOIN TmembreBl m ON cb.id_membre = m.id_membre;


DROP VIEW IF EXISTS v_comptes_bloques_actifs;
CREATE VIEW v_comptes_bloques_actifs AS
SELECT *
FROM v_compte_bloque_global
WHERE statut = 'Bloqué';


DROP VIEW IF EXISTS v_comptes_urgents;
CREATE VIEW v_comptes_urgents AS
SELECT *
FROM v_compte_bloque_global
WHERE statut = 'Urgent';


DROP VIEW IF EXISTS v_comptes_debloques;
CREATE VIEW v_comptes_debloques AS
SELECT *
FROM v_compte_bloque_global
WHERE statut = 'Débloqué';


DROP VIEW IF EXISTS v_statistiques_compte_bloque;
CREATE VIEW v_statistiques_compte_bloque AS
SELECT
    COUNT(*) AS total_comptes,
    SUM(CASE WHEN statut = 'Bloqué' THEN 1 ELSE 0 END) AS total_bloques,
    SUM(CASE WHEN statut = 'Urgent' THEN 1 ELSE 0 END) AS total_urgents,
    SUM(CASE WHEN statut = 'Débloqué' THEN 1 ELSE 0 END) AS total_debloques,
    SUM(solde) AS solde_total
FROM Tcompte_bloque;


DROP VIEW IF EXISTS v_historique_compte_bloque;
CREATE VIEW v_historique_compte_bloque AS
SELECT
    m.id_membre,
    m.noms,
    cb.numero_compte_bloque,
    cb.solde,
    cb.statut,
    cb.date_creation,
    cb.date_deblocage
FROM TmembreBl m
LEFT JOIN Tcompte_bloque cb ON m.id_membre = cb.id_membre
ORDER BY m.noms;


DROP VIEW IF EXISTS v_dashboard_compte_bloque;
CREATE VIEW v_dashboard_compte_bloque AS
SELECT 
    SUM(CASE WHEN statut='Bloqué' THEN 1 ELSE 0 END) AS nb_bloques,
    SUM(CASE WHEN statut='Urgent' THEN 1 ELSE 0 END) AS nb_urgents,
    SUM(CASE WHEN statut='Débloqué' THEN 1 ELSE 0 END) AS nb_debloques,
    COUNT(*) AS total_comptes
FROM Tcompte_bloque;


-- Voir tous les comptes bloqués
SELECT * FROM v_comptes_bloques_actifs;

-- Voir les comptes urgents
SELECT * FROM v_comptes_urgents;

-- Statistiques globales
SELECT * FROM v_statistiques_compte_bloque;

-- Dashboard
SELECT * FROM v_dashboard_compte_bloque;
-- ============================================
-- TRIGGER : MISE À JOUR DU SOLDE APRÈS DÉPÔT
-- ============================================
DELIMITER $$
CREATE TRIGGER ajoute_compte AFTER INSERT ON 
TmembreBl FOR EACH ROW
BEGIN
    INSERT INTO tcompte_bloque(id_membre, noms, numero_compte_bloque, solde, date_deblocage, statut) 
    VALUES (New.id_membre, New.noms, New.numero_compte_bloque,'0', New.date_deblocage, New.statut);
END $$
DELIMITER ;

-- ============================================
-- TRIGGER : MISE À JOUR DU SOLDE APRÈS DÉPÔT
-- ============================================
DELIMITER $$

CREATE TRIGGER trg_depot_compte_bloque
AFTER INSERT ON Tdepot_compte_bloque
FOR EACH ROW
BEGIN
    UPDATE Tcompte_bloque
    SET solde = solde + NEW.montant
    WHERE id_compte_bloque = NEW.id_compte_bloque;
END$$

DELIMITER ;

-- ============================================
-- TRIGGER : DIMINUTION DU SOLDE APRÈS SUPPRESSION
-- ============================================
DELIMITER $$

CREATE TRIGGER trg_delete_depot_compte_bloque
AFTER DELETE ON Tdepot_compte_bloque
FOR EACH ROW
BEGIN
    UPDATE Tcompte_bloque
    SET solde = solde - OLD.montant
    WHERE id_compte_bloque = OLD.id_compte_bloque;
END$$

DELIMITER ;

-- ============================================
-- TRIGGER : AJUSTEMENT DU SOLDE APRÈS MODIFICATION
-- ============================================
DELIMITER $$

CREATE TRIGGER trg_update_depot_compte_bloque
AFTER UPDATE ON Tdepot_compte_bloque
FOR EACH ROW
BEGIN
    UPDATE Tcompte_bloque
    SET solde = solde - OLD.montant + NEW.montant
    WHERE id_compte_bloque = NEW.id_compte_bloque;
END$$

DELIMITER ;
