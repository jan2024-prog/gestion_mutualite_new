<?php
session_start();
require_once("../connexion/connexion.php");

/* ==========================
   SUPPRESSION D'UN CREDIT
   ========================== */
if (isset($_GET['delete_id'])) {
    $id_credit = (int) $_GET['delete_id'];

    try {
        $del = $pdo->prepare("DELETE FROM credit WHERE id_credit = ?");
        $del->execute([$id_credit]);

        $_SESSION['message'] = "Crédit supprimé avec succès.";
    } catch (PDOException $e) {
        $_SESSION['message'] = "Erreur SQL : " . $e->getMessage();
    }

    header("Location: ../credit.php");
    exit;
}

/* ==========================
   INSERTION / MODIFICATION
   ========================== */
if (isset($_POST['save']) || isset($_POST['update'])) {

    $id_credit      = isset($_POST['id_credit']) ? (int) $_POST['id_credit'] : null;
    $id_compte      = (int) $_POST['id_compte'];
    $id_type_credit = (int) $_POST['id_type_credit'];
    $date_credit    = $_POST['date_credit'];
    $montant        = (float) $_POST['montant_credit'];
    $statut         = $_POST['statut'];
    $libele         = trim($_POST['libele']);
    $devise =htmlspecialchars($_POST['devise']);

    if (
        empty($id_compte) ||
        empty($id_type_credit) ||
        empty($date_credit) ||
        empty($montant) ||
        empty($statut)   ||
        empty($devise)
    ) {
        $_SESSION['message'] = "Tous les champs obligatoires doivent être remplis.";
        header("Location: ../credit.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        /* ==========================
           VERIFIER CREDIT EN COURS
           ========================== */
        if (!isset($_POST['update'])) {
            $verif = $pdo->prepare("
                SELECT id_credit 
                FROM credit
                WHERE id_compte = ?
                AND statut = 'En cours'
            ");
            $verif->execute([$id_compte]);

            if ($verif->fetch()) {
                throw new Exception("Ce compte a déjà un crédit en cours.");
            }
        }

        /* ==========================
           INSERTION
           ========================== */
        if (!isset($_POST['update'])) {

            $ins = $pdo->prepare("
                INSERT INTO credit
                (id_compte, id_type_credit, montant_credit, devise, date_credit, statut, libele)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([
                $id_compte,
                $id_type_credit,
                $montant,
                $devise,
                $date_credit,
                $statut,
                $libele
            ]);

            $_SESSION['message'] = "Crédit enregistré avec succès.";

        } else {
            /* ==========================
               MODIFICATION
               ========================== */
            $upd = $pdo->prepare("
                UPDATE credit
                SET 
                    id_compte = ?,
                    id_type_credit = ?,
                    montant_credit = ?,
                    devise =?,
                    date_credit = ?,
                    statut = ?,
                    libele = ?
                WHERE id_credit = ?
            ");
            $upd->execute([
                $id_compte,
                $id_type_credit,
                $montant,
                $devise,
                $date_credit,
                $statut,
                $libele,
                $id_credit
            ]);

            $_SESSION['message'] = "Crédit modifié avec succès.";
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['message'] = "Erreur : " . $e->getMessage();
    }

    header("Location: ../credit.php");
    exit;
}
