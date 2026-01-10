CREATE DATABASE gestion_mutualite 
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE gestion_mutualite;

-- =======================
-- ENTREPRISE
-- =======================
CREATE TABLE entreprise (
    id_entreprise INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    adresse VARCHAR(150),
    telephone VARCHAR(20),
    email VARCHAR(100),
    date_creation DATE,
    logo VARCHAR(200)
);

-- =======================
-- UTILISATEUR
-- =======================
CREATE TABLE utilisateur (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    id_entreprise INT,
    noms VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('Admin','Caissier','Secretaire','Gestionnaire') DEFAULT 'Secretaire',
    telephone VARCHAR(20),
    email VARCHAR(100),
    photo VARCHAR(100),
    statut ENUM('Actif','Inactif') DEFAULT 'Actif',
    date_creation DATE,

    FOREIGN KEY (id_entreprise) REFERENCES entreprise(id_entreprise)
        ON DELETE SET NULL
);

-- =======================
-- MEMBRE
-- =======================
CREATE TABLE membre (
    id_membre INT AUTO_INCREMENT PRIMARY KEY,
    id_entreprise INT,
    noms VARCHAR(50) NOT NULL,
    sexe VARCHAR(20),
    etat_civil VARCHAR(50),
    nomPartainaire VARCHAR(200),
    email VARCHAR(100),
    telephone VARCHAR(20),
    adresse VARCHAR(150),
    date_adhesion DATE,
    photo VARCHAR(100),
    statut ENUM('Actif','Suspendu') DEFAULT 'Actif',

    FOREIGN KEY (id_entreprise) REFERENCES entreprise(id_entreprise)
        ON DELETE SET NULL
);

-- =======================
-- COMPTE
-- =======================
CREATE TABLE compte (
    id_compte INT AUTO_INCREMENT PRIMARY KEY,
    id_membre INT UNIQUE,
    numero_compte VARCHAR(50),
    solde DECIMAL(12,2) DEFAULT 0,
    date_ouverture DATE,

    FOREIGN KEY (id_membre) REFERENCES membre(id_membre)
        ON DELETE CASCADE
);

-- =======================
-- COTISATION
-- =======================
CREATE TABLE cotisation (
    id_cotisation INT AUTO_INCREMENT PRIMARY KEY,
    id_compte INT,
    libele VARCHAR(100),
    montant DECIMAL(12,2) NOT NULL,
    date_cotisation DATE,
    semaine VARCHAR(20),
    annee INT,

    FOREIGN KEY (id_compte) REFERENCES compte(id_compte)
        ON DELETE CASCADE
);

-- =======================
-- TYPE CREDIT
-- =======================
CREATE TABLE type_credit (
    id_type_credit INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL,
    taux_interet DECIMAL(5,2),
    duree_max INT COMMENT 'en semaine'
);

-- =======================
-- CREDIT
-- =======================
CREATE TABLE credit (
    id_credit INT AUTO_INCREMENT PRIMARY KEY,
    id_compte INT,
    id_type_credit INT,
    montant_credit DECIMAL(12,2) NOT NULL,
    date_credit DATE,
    date_echeance DATE,
    statut ENUM('En cours','Soldé') DEFAULT 'En cours',
    libele VARCHAR(200),
    penalite_active TINYINT(1) DEFAULT 1,
    taux_interet DECIMAL(5,2) DEFAULT 10,
    last_penalite_date DATE NULL,

    FOREIGN KEY (id_compte) REFERENCES compte(id_compte)
        ON DELETE CASCADE,

    FOREIGN KEY (id_type_credit) REFERENCES type_credit(id_type_credit)
        ON DELETE RESTRICT
);

-- =======================
-- REMBOURSEMENT
-- =======================
CREATE TABLE remboursement (
    id_remboursement INT AUTO_INCREMENT PRIMARY KEY,
    id_credit INT,
    montant_rembourse DECIMAL(12,2) NOT NULL,
    date_remboursement DATE,
    libele VARCHAR(100),
    reste DECIMAL(12,2),

    FOREIGN KEY (id_credit) REFERENCES credit(id_credit)
        ON DELETE CASCADE
);

-- =======================
-- RETRAIT
-- =======================
CREATE TABLE retrait (
    id_retrait INT AUTO_INCREMENT PRIMARY KEY,
    id_compte INT,
    montant DECIMAL(12,2) NOT NULL,
    date_retrait DATE,
    libele VARCHAR(100),

    FOREIGN KEY (id_compte) REFERENCES compte(id_compte)
        ON DELETE CASCADE
);

-- =======================
-- CAISSE
-- =======================
CREATE TABLE caisse (
    id_caisse INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT,
    libele VARCHAR(100) DEFAULT 'solde',
    solde DECIMAL(14,2) DEFAULT 0,
    date_update DATE,

    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur)
        ON DELETE SET NULL
);

CREATE TABLE historique_penalite (
    id_penalite INT AUTO_INCREMENT PRIMARY KEY,
    id_credit INT NOT NULL,
    montant_penalite DECIMAL(12,2) NOT NULL,
    date_penalite DATE NOT NULL,

    FOREIGN KEY (id_credit) REFERENCES credit(id_credit)
        ON DELETE CASCADE
);

-- =======================
-- MOUVEMENT CAISSE
-- =======================
CREATE TABLE mouvement_caisse (
    id_mouvement INT AUTO_INCREMENT PRIMARY KEY,
    id_caisse INT,
    id_utilisateur INT,
    type_mouvement ENUM('Entrée','Sortie') NOT NULL,
    origine ENUM('Cotisation','Retrait','Credit','Remboursement','Correction') NOT NULL,
    montant DECIMAL(14,2) NOT NULL,
    libele VARCHAR(150),
    date_mouvement DATE,

    FOREIGN KEY (id_caisse) REFERENCES caisse(id_caisse)
        ON DELETE CASCADE,

    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur)
        ON DELETE SET NULL
);

CREATE INDEX idx_cotisation_compte ON cotisation(id_compte);
CREATE INDEX idx_retrait_compte ON retrait(id_compte);
CREATE INDEX idx_credit_compte ON credit(id_compte);
CREATE INDEX idx_remboursement_credit ON remboursement(id_credit);
CREATE INDEX idx_penalite_credit ON historique_penalite(id_credit);


        -- TRIGGERS
        -- Trigger création automatique de compte après ajout d’un membre

DELIMITER $$

CREATE TRIGGER trg_creation_compte_membre
AFTER INSERT ON membre
FOR EACH ROW
BEGIN
    DECLARE num_compte VARCHAR(30);

    -- Génération du numéro de compte si tu veux le garder
    SET num_compte = CONCAT('MUT-', YEAR(CURDATE()), '-', LPAD(NEW.id_membre,4,'0'));

    INSERT INTO compte (id_membre, numero_compte, solde, date_ouverture)
    VALUES (NEW.id_membre, num_compte, 0, CURDATE());
END $$

DELIMITER ;

-- Trigger : augmentation du compte après cotisation
DELIMITER $$

CREATE TRIGGER trg_cotisation_update
AFTER INSERT ON cotisation
FOR EACH ROW
BEGIN
    DECLARE id_caisse_actuelle INT;

    -- 1️⃣ Mettre à jour le solde du compte
    UPDATE compte
    SET solde = solde + NEW.montant
    WHERE id_compte = NEW.id_compte;

    -- 2️⃣ Identifier la caisse active (ici on prend la première caisse)
    SELECT id_caisse INTO id_caisse_actuelle
    FROM caisse
    LIMIT 1;

    -- 3️⃣ Mettre à jour le solde de la caisse
    UPDATE caisse
    SET solde = solde + NEW.montant,
        date_update = CURDATE()
    WHERE id_caisse = id_caisse_actuelle;

    -- 4️⃣ Enregistrer le mouvement dans mouvement_caisse
    INSERT INTO mouvement_caisse (
        id_caisse,
        id_utilisateur,
        type_mouvement,
        origine,
        montant,
        libele,
        date_mouvement
    )
    VALUES (
        id_caisse_actuelle,
        NULL,                      -- id_utilisateur peut être rempli depuis la session PHP si besoin
        'Entrée',
        'Cotisation',
        NEW.montant,
        CONCAT('Cotisation ID ', NEW.id_cotisation),
        CURDATE()
    );

END $$

DELIMITER ;

-- Trigger après modification d’une cotisation
DELIMITER $$

CREATE TRIGGER trg_cotisation_update_solde
AFTER UPDATE ON cotisation
FOR EACH ROW
BEGIN
    DECLARE id_caisse_actuelle INT;

    -- Identifier la caisse active
    SELECT id_caisse INTO id_caisse_actuelle
    FROM caisse
    LIMIT 1;

    -- Ajuster le solde du compte : enlever l'ancien montant et ajouter le nouveau
    UPDATE compte
    SET solde = solde - OLD.montant + NEW.montant
    WHERE id_compte = NEW.id_compte;

    -- Ajuster le solde de la caisse
    UPDATE caisse
    SET solde = solde - OLD.montant + NEW.montant,
        date_update = CURDATE()
    WHERE id_caisse = id_caisse_actuelle;

    -- Ajouter un mouvement pour l'historique
    INSERT INTO mouvement_caisse (
        id_caisse,
        id_utilisateur,
        type_mouvement,
        origine,
        montant,
        libele,
        date_mouvement
    )
    VALUES (
        id_caisse_actuelle,
        NULL,  -- remplacer par l'utilisateur connecté si disponible
        'Entrée',
        'Cotisation',
        NEW.montant - OLD.montant,
        CONCAT('Modification Cotisation ID ', NEW.id_cotisation),
        CURDATE()
    );

END $$

DELIMITER ;


-- Trigger après suppression d’une cotisation
DELIMITER $$

CREATE TRIGGER trg_cotisation_delete
AFTER DELETE ON cotisation
FOR EACH ROW
BEGIN
    DECLARE id_caisse_actuelle INT;

    -- Identifier la caisse active
    SELECT id_caisse INTO id_caisse_actuelle
    FROM caisse
    LIMIT 1;

    -- Diminuer le solde du compte
    UPDATE compte
    SET solde = solde - OLD.montant
    WHERE id_compte = OLD.id_compte;

    -- Diminuer le solde de la caisse
    UPDATE caisse
    SET solde = solde - OLD.montant,
        date_update = CURDATE()
    WHERE id_caisse = id_caisse_actuelle;

    -- Ajouter un mouvement pour l'historique
    INSERT INTO mouvement_caisse (
        id_caisse,
        id_utilisateur,
        type_mouvement,
        origine,
        montant,
        libele,
        date_mouvement
    )
    VALUES (
        id_caisse_actuelle,
        NULL,  -- remplacer par l'utilisateur connecté si disponible
        'Sortie',
        'Cotisation',
        OLD.montant,
        CONCAT('Suppression Cotisation ID ', OLD.id_cotisation),
        CURDATE()
    );

END $$

DELIMITER ;

-- Trigger : après remboursement → mise à jour du crédit

DELIMITER $$

CREATE TRIGGER trg_remboursement_complet
AFTER INSERT ON remboursement
FOR EACH ROW
BEGIN
    DECLARE id_caisse_actuelle INT;
    DECLARE nouveau_reste DECIMAL(12,2);
    DECLARE id_compte_credit INT;


    -- 3️⃣ Identifier la caisse active (ici, la première caisse)
    SELECT id_caisse INTO id_caisse_actuelle
    FROM caisse
    LIMIT 1;

    -- 4️⃣ Mettre à jour le solde de la caisse
    UPDATE caisse
    SET solde = solde + NEW.montant_rembourse,
        date_update = CURDATE()
    WHERE id_caisse = id_caisse_actuelle;

    -- 5️⃣ Enregistrer le mouvement dans mouvement_caisse
    INSERT INTO mouvement_caisse (
        id_caisse,
        id_utilisateur,
        type_mouvement,
        origine,
        montant,
        libele,
        date_mouvement
    )
    VALUES (
        id_caisse_actuelle,
        NULL,  -- tu peux remplacer par l'id de l'utilisateur connecté depuis PHP
        'Entrée',
        'Remboursement',
        NEW.montant_rembourse,
        CONCAT('Remboursement Crédit ID ', NEW.id_credit),
        CURDATE()
    );

    -- 6️⃣ Calculer le reste du crédit
    SET nouveau_reste = (SELECT montant_credit FROM credit WHERE id_credit = NEW.id_credit)
                        - (SELECT IFNULL(SUM(montant_rembourse),0)
                           FROM remboursement
                           WHERE id_credit = NEW.id_credit);

    -- 7️⃣ Mettre à jour le crédit : reste et statut
    UPDATE credit
    SET last_penalite_date = CURDATE(),
        statut = CASE 
                    WHEN nouveau_reste <= 0 THEN 'Soldé'
                    ELSE 'En cours'
                 END
    WHERE id_credit = NEW.id_credit;

    -- 8️⃣ Mettre à jour la colonne reste du remboursement

END $$

DELIMITER ;

-- Trigger après modification d’un remboursement
DELIMITER $$

CREATE TRIGGER trg_remboursement_update
AFTER UPDATE ON remboursement
FOR EACH ROW
BEGIN
    DECLARE id_caisse_actuelle INT;
    DECLARE id_compte_credit INT;
    DECLARE nouveau_reste DECIMAL(12,2);

    -- 3️⃣ Identifier la caisse active
    SELECT id_caisse INTO id_caisse_actuelle
    FROM caisse
    LIMIT 1;

    -- 4️⃣ Ajuster le solde de la caisse
    UPDATE caisse
    SET solde = solde - OLD.montant_rembourse + NEW.montant_rembourse,
        date_update = CURDATE()
    WHERE id_caisse = id_caisse_actuelle;

    -- 5️⃣ Ajouter un mouvement pour l’historique
    INSERT INTO mouvement_caisse (
        id_caisse,
        id_utilisateur,
        type_mouvement,
        origine,
        montant,
        libele,
        date_mouvement
    )
    VALUES (
        id_caisse_actuelle,
        NULL, -- remplacer par l'utilisateur connecté
        'Entrée',
        'Modification Remboursement',
        NEW.montant_rembourse - OLD.montant_rembourse,
        CONCAT('Modification Remboursement Crédit ID ', NEW.id_credit),
        CURDATE()
    );

    -- 6️⃣ Recalculer le reste du crédit
    SET nouveau_reste = (SELECT montant_credit FROM credit WHERE id_credit = NEW.id_credit)
                        - (SELECT IFNULL(SUM(montant_rembourse),0)
                           FROM remboursement
                           WHERE id_credit = NEW.id_credit);

    -- 7️⃣ Mettre à jour le crédit
    UPDATE credit
    SET statut = CASE
                    WHEN nouveau_reste <= 0 THEN 'Soldé'
                    ELSE 'En cours'
                 END,
        last_penalite_date = CURDATE()
    WHERE id_credit = NEW.id_credit;

   

END $$

DELIMITER ;

--  Trigger après suppression d’un remboursement
DELIMITER $$

CREATE TRIGGER trg_remboursement_delete
AFTER DELETE ON remboursement
FOR EACH ROW
BEGIN
    DECLARE id_caisse_actuelle INT;
    DECLARE id_compte_credit INT;
    DECLARE nouveau_reste DECIMAL(12,2);

    -- 3️⃣ Identifier la caisse active
    SELECT id_caisse INTO id_caisse_actuelle
    FROM caisse
    LIMIT 1;

    -- 4️⃣ Diminuer le solde de la caisse
    UPDATE caisse
    SET solde = solde - OLD.montant_rembourse,
        date_update = CURDATE()
    WHERE id_caisse = id_caisse_actuelle;

    -- 5️⃣ Ajouter un mouvement pour l’historique
    INSERT INTO mouvement_caisse (
        id_caisse,
        id_utilisateur,
        type_mouvement,
        origine,
        montant,
        libele,
        date_mouvement
    )
    VALUES (
        id_caisse_actuelle,
        NULL, -- remplacer par l'utilisateur connecté
        'Sortie',
        'Suppression Remboursement',
        OLD.montant_rembourse,
        CONCAT('Suppression Remboursement Crédit ID ', OLD.id_credit),
        CURDATE()
    );

    -- 6️⃣ Recalculer le reste du crédit
    SET nouveau_reste = (SELECT montant_credit FROM credit WHERE id_credit = OLD.id_credit)
                        - (SELECT IFNULL(SUM(montant_rembourse),0)
                           FROM remboursement 
                           WHERE id_credit = OLD.id_credit);

    -- 7️⃣ Mettre à jour le crédit
    UPDATE credit
    SET statut = CASE
                    WHEN nouveau_reste <= 0 THEN 'Soldé'
                    ELSE 'En cours'
                 END,
        last_penalite_date = CURDATE()
    WHERE id_credit = OLD.id_credit;

END $$

DELIMITER ;

--  Trigger : retrait → compte + caisse + mouvement_caisse
DELIMITER $$

CREATE TRIGGER trg_retrait_complet
AFTER INSERT ON retrait
FOR EACH ROW
BEGIN
    DECLARE id_caisse_actuelle INT;
    DECLARE id_compte_retrait INT;

    -- 1️⃣ Récupérer l'id du compte
    SET id_compte_retrait = NEW.id_compte;

    -- 2️⃣ Diminuer le solde du compte
    UPDATE compte
    SET solde = solde - NEW.montant
    WHERE id_compte = id_compte_retrait;

    -- 3️⃣ Identifier la caisse active (première caisse)
    SELECT id_caisse INTO id_caisse_actuelle
    FROM caisse
    LIMIT 1;

    -- 4️⃣ Diminuer le solde de la caisse
    UPDATE caisse
    SET solde = solde - NEW.montant,
        date_update = CURDATE()
    WHERE id_caisse = id_caisse_actuelle;

    -- 5️⃣ Enregistrer le mouvement dans mouvement_caisse
    INSERT INTO mouvement_caisse (
        id_caisse,
        id_utilisateur,
        type_mouvement,
        origine,
        montant,
        libele,
        date_mouvement
    )
    VALUES (
        id_caisse_actuelle,
        NULL,  -- remplacer par l'utilisateur connecté si disponible
        'Sortie',
        'Retrait',
        NEW.montant,
        CONCAT('Retrait Compte ID ', NEW.id_compte),
        CURDATE()
    );

END $$

DELIMITER ;

-- Trigger après modification d’un retrait
DELIMITER $$

CREATE TRIGGER trg_retrait_update
AFTER UPDATE ON retrait
FOR EACH ROW
BEGIN
    DECLARE id_caisse_actuelle INT;

    -- 1️⃣ Identifier la caisse active
    SELECT id_caisse INTO id_caisse_actuelle
    FROM caisse
    LIMIT 1;

    -- 2️⃣ Ajuster le solde du compte : enlever ancien montant, ajouter nouveau
    UPDATE compte
    SET solde = solde - OLD.montant + NEW.montant
    WHERE id_compte = NEW.id_compte;

    -- 3️⃣ Ajuster le solde de la caisse
    UPDATE caisse
    SET solde = solde - OLD.montant + NEW.montant,
        date_update = CURDATE()
    WHERE id_caisse = id_caisse_actuelle;

    -- 4️⃣ Enregistrer le mouvement dans mouvement_caisse
    INSERT INTO mouvement_caisse (
        id_caisse,
        id_utilisateur,
        type_mouvement,
        origine,
        montant,
        libele,
        date_mouvement
    )
    VALUES (
        id_caisse_actuelle,
        NULL,  -- remplacer par l'utilisateur connecté
        CASE 
            WHEN NEW.montant - OLD.montant >= 0 THEN 'Sortie'
            ELSE 'Entrée'
        END,
        'Modification Retrait',
        ABS(NEW.montant - OLD.montant),
        CONCAT('Modification Retrait Compte ID ', NEW.id_compte),
        CURDATE()
    );

END $$

DELIMITER ;


-- Trigger après suppression d’un retrait
DELIMITER $$

CREATE TRIGGER trg_retrait_delete
AFTER DELETE ON retrait
FOR EACH ROW
BEGIN
    DECLARE id_caisse_actuelle INT;

    -- 1️⃣ Identifier la caisse active
    SELECT id_caisse INTO id_caisse_actuelle
    FROM caisse
    LIMIT 1;

    -- 2️⃣ Diminuer le retrait du compte (en fait on "remet" le montant car le retrait est supprimé)
    UPDATE compte
    SET solde = solde + OLD.montant
    WHERE id_compte = OLD.id_compte;

    -- 3️⃣ Diminuer le retrait de la caisse (remettre le montant)
    UPDATE caisse
    SET solde = solde + OLD.montant,
        date_update = CURDATE()
    WHERE id_caisse = id_caisse_actuelle;

    -- 4️⃣ Enregistrer le mouvement dans mouvement_caisse
    INSERT INTO mouvement_caisse (
        id_caisse,
        id_utilisateur,
        type_mouvement,
        origine,
        montant,
        libele,
        date_mouvement
    )
    VALUES (
        id_caisse_actuelle,
        NULL,  -- remplacer par l'utilisateur connecté
        'Entrée',
        'Suppression Retrait',
        OLD.montant,
        CONCAT('Suppression Retrait Compte ID ', OLD.id_compte),
        CURDATE()
    );

END $$

DELIMITER ;

-- COMPTE BLOCKER
CREATE TABLE IF NOT EXISTS TmembreBl (
    id_membre INT AUTO_INCREMENT PRIMARY KEY,
    noms VARCHAR(150) NOT NULL,
    numero_compte_bloque VARCHAR(30),  -- Ajout du type
    sexe VARCHAR(10),
    telephone VARCHAR(20),
    datenaissance DATE,
    date_adhesion DATE DEFAULT CURRENT_DATE, -- Correction syntaxe
    etatcivil VARCHAR(200),
    email VARCHAR(100),
    photo VARCHAR(100),
    date_deblocage DATE,
    statut ENUM('Actif','Suspendu') DEFAULT 'Actif',
    adresse varchar(200)
);

CREATE TABLE IF NOT EXISTS Tcompte_bloque (
    id_compte_bloque INT AUTO_INCREMENT PRIMARY KEY,
    id_membre INT NOT NULL,
    numero_compte_bloque VARCHAR(30) UNIQUE,
    solde DECIMAL(12,2) DEFAULT 0,
    date_creation DATE DEFAULT CURRENT_DATE,
    date_deblocage DATE,
    statut ENUM('Bloqué','Urgent','Débloqué') DEFAULT 'Bloqué',
    FOREIGN KEY (id_membre) REFERENCES TmembreBl(id_membre)
);


CREATE TABLE IF NOT EXISTS Tdepot_compte_bloque (
    id_depot INT AUTO_INCREMENT PRIMARY KEY,
    id_compte_bloque INT NOT NULL,
    montant DECIMAL(12,2) NOT NULL CHECK (montant > 0),
    date_depot DATETIME DEFAULT CURRENT_TIMESTAMP,
    mode_paiement VARCHAR(50),
    libele VARCHAR(100),
    FOREIGN KEY (id_compte_bloque) 
        REFERENCES Tcompte_bloque(id_compte_bloque)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Tretrait_compte_bloque (
    id_retrait INT AUTO_INCREMENT PRIMARY KEY,
    id_compte_bloque INT NOT NULL,
    montant DECIMAL(12,2) NOT NULL CHECK (montant > 0),
    date_retrait DATETIME DEFAULT CURRENT_TIMESTAMP,
    mode_paiement VARCHAR(50),
    libelle VARCHAR(100),
    FOREIGN KEY (id_compte_bloque)
        REFERENCES Tcompte_bloque(id_compte_bloque)
        ON DELETE CASCADE
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

CREATE TABLE IF NOT EXISTS historique_compte_bloque (
    id_historique INT AUTO_INCREMENT PRIMARY KEY,
    id_compte_bloque INT,
    type_operation ENUM('Depot','Retrait') NOT NULL,
    montant DECIMAL(12,2) NOT NULL,
    date_operation DATETIME DEFAULT CURRENT_TIMESTAMP,
    mode_paiement VARCHAR(50),
    libelle VARCHAR(100),
    solde_avant DECIMAL(12,2),
    solde_apres DECIMAL(12,2),
    FOREIGN KEY (id_compte_bloque)
        REFERENCES Tcompte_bloque(id_compte_bloque)
        ON DELETE CASCADE
);

    -- Trigger AFTER INSERT sur retrait
DELIMITER $$

CREATE TRIGGER trg_retrait_compte_bloque
AFTER INSERT ON Tretrait_compte_bloque
FOR EACH ROW
BEGIN
    DECLARE solde_av DECIMAL(12,2);
    DECLARE solde_ap DECIMAL(12,2);

    -- On récupère le solde actuel du compte
    SELECT solde INTO solde_av FROM Tcompte_bloque WHERE id_compte_bloque = NEW.id_compte_bloque;

    -- Calcul du nouveau solde après retrait
    SET solde_ap = solde_av - NEW.montant;

    -- Mise à jour du compte bloqué
    UPDATE Tcompte_bloque
    SET solde = solde_ap
    WHERE id_compte_bloque = NEW.id_compte_bloque;

    -- On peut aussi diminuer le solde du compte principal du membre si nécessaire
    -- UPDATE Tcompte
    -- SET solde = solde - NEW.montant
    -- WHERE id_membre = (SELECT id_membre FROM Tcompte_bloque WHERE id_compte_bloque = NEW.id_compte_bloque);

    -- Ajout à l'historique
    INSERT INTO historique_compte_bloque(
        id_compte_bloque,
        type_operation,
        montant,
        mode_paiement,
        libelle,
        solde_avant,
        solde_apres
    ) VALUES (
        NEW.id_compte_bloque,
        'Retrait',
        NEW.montant,
        NEW.mode_paiement,
        NEW.libelle,
        solde_av,
        solde_ap
    );

    -- Supprimer le compte bloqué si solde = 0 (optionnel)
    IF solde_ap <= 0 THEN
        DELETE FROM Tcompte_bloque WHERE id_compte_bloque = NEW.id_compte_bloque;
        -- Et si tu veux supprimer le membre aussi
        -- DELETE FROM TmembreBl WHERE id_membre = (SELECT id_membre FROM Tcompte_bloque WHERE id_compte_bloque = NEW.id_compte_bloque);
    END IF;

END$$

DELIMITER ;
-- declacher pou inserer automatiquement le compté dans la table compte
DELIMITER $$
CREATE TRIGGER creer_compt_bl_aut 
    AFTER INSERT ON TmembreBl FOR
     EACH ROW
        BEGIN
            INSERT INTO tcompte_bloque(id_membre, numero_compte_bloque, solde, date_deblocage, statut) 
            VALUES (new.id_membre, new.numero_compte_bloque, '0', new.date_deblocage, 'Bloqué');
        END$$
DELIMITER ;
-- Après ajout d’une cotisation → AUGMENTER le solde
DELIMITER $$

CREATE TRIGGER trg_depot_after_insert
AFTER INSERT ON Tdepot_compte_bloque
FOR EACH ROW
BEGIN
    UPDATE Tcompte_bloque
    SET solde = solde + NEW.montant
    WHERE id_compte_bloque = NEW.id_compte_bloque;
END$$

DELIMITER ;
-- Après suppression d’une cotisation → DIMINUER le solde
DELIMITER $$

CREATE TRIGGER trg_depot_after_delete
AFTER DELETE ON Tdepot_compte_bloque
FOR EACH ROW
BEGIN
    UPDATE Tcompte_bloque
    SET solde = solde - OLD.montant
    WHERE id_compte_bloque = OLD.id_compte_bloque;
END$$

DELIMITER ;
-- Après modification d’une cotisation → AJUSTEMENT AUTOMATIQUE
DELIMITER $$

CREATE TRIGGER trg_depot_after_update
AFTER UPDATE ON Tdepot_compte_bloque
FOR EACH ROW
BEGIN
    UPDATE Tcompte_bloque
    SET solde = solde + (NEW.montant - OLD.montant)
    WHERE id_compte_bloque = NEW.id_compte_bloque;
END$$

DELIMITER ;
-- APRÈS AJOUT D’UN RETRAIT → DIMINUER LE SOLDE



-- APRÈS SUPPRESSION D’UN RETRAIT → RESTITUER LE SOLDE


-- APRÈS MODIFICATION D’UN RETRAIT → AJUSTEMENT AUTOMATIQUE


-- SÉCURITÉ COMPTABLE OBLIGATOIRE (ANTI-SOLDE NÉGATIF AVANT RETRAIT)
DELIMITER $$

CREATE TRIGGER trg_retrait_before_insert
BEFORE INSERT ON Tretrait_compte_bloque
FOR EACH ROW
BEGIN
    DECLARE solde_actuel DECIMAL(12,2);

    SELECT solde 
    INTO solde_actuel
    FROM Tcompte_bloque
    WHERE id_compte_bloque = NEW.id_compte_bloque
    FOR UPDATE;

    IF solde_actuel < NEW.montant THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Retrait refusé : solde insuffisant';
    END IF;
END$$

DELIMITER ;
-- (OPTIONNEL MAIS RECOMMANDÉ) — CONTRÔLE AVANT MODIFICATION DE RETRAIT
DELIMITER $$

CREATE TRIGGER trg_retrait_before_update
BEFORE UPDATE ON Tretrait_compte_bloque
FOR EACH ROW
BEGIN
    DECLARE solde_disponible DECIMAL(12,2);

    SELECT solde + OLD.montant
    INTO solde_disponible
    FROM Tcompte_bloque
    WHERE id_compte_bloque = NEW.id_compte_bloque
    FOR UPDATE;

    IF solde_disponible < NEW.montant THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Modification refusée : solde insuffisant';
    END IF;
END$$

DELIMITER ;


--  2ieme Apports des francs