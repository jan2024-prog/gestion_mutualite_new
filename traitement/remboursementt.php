<?php
require_once("../connexion/connexion.php");

/* ============================
   SUPPRESSION D'UN REMBOURSEMENT
   ============================ */
if (isset($_GET['delete_id'])) {

    $id_remboursement = (int) $_GET['delete_id'];

    try {
        $query_delete = $pdo->prepare(
            "DELETE FROM remboursement WHERE id_remboursement = ?"
        );
        $query_delete->execute([$id_remboursement]);

        $message = "Remboursement supprimé avec succès.";
    } catch (PDOException $e) {
        $message = "Erreur SQL lors de la suppression : " . $e->getMessage();
    }

    header("Location: ../ramboursement.php?message=" . urlencode($message));
    exit();
}

/* ============================
   MODIFICATION D'UN REMBOURSEMENT
   ============================ */
   if (isset($_POST['update'])) {

    $id_remboursement = (int) $_POST['id_remboursement'];
    $credit  = htmlspecialchars($_POST['credit']);
    $montant = htmlspecialchars($_POST['montant']);
    $daterem = htmlspecialchars($_POST['daterem']);
    $libele  = htmlspecialchars($_POST['libele']);
    $devise=htmlspecialchars($_POST['devise']);
    if (!empty($id_remboursement) && !empty($credit) && !empty($montant) && !empty($daterem) && !empty($libele) && !empty($devise)) {

        try {
            $query_update = $pdo->prepare("
                UPDATE Tremboursement
                SET 
                    id_credit = ?,
                    montant_rembourse = ?,
                    devise=?,
                    date_remboursement = ?,
                    libele = ?
                WHERE id_remboursement = ?
            ");

            $query_update->execute([
                $credit,
                $montant,
                $devise,
                $daterem,
                $libele,
                $id_remboursement
            ]);

            $message = "Remboursement modifié avec succès.";

        } catch (PDOException $e) {
            $message = "Erreur SQL : " . $e->getMessage();
        }

    } else {
        $message = "Tous les champs sont obligatoires.";
    }

    header("Location: ../ramboursement.php?message=".($message));
    exit();
}

/* ============================
   INSERTION D'UN REMBOURSEMENT
   ============================ */
if (isset($_POST['save'])) {

    $credit  = htmlspecialchars($_POST['credit']);
    $montant = htmlspecialchars($_POST['montant']);
    $daterem = htmlspecialchars($_POST['daterem']);
    $libele  = htmlspecialchars($_POST['libele']);
    $devise=htmlspecialchars($_POST['devise']);
    if (!empty($credit) && !empty($montant) && !empty($devise) && !empty($daterem) && !empty($libele)) {

        try {
            $query_verif = $pdo->prepare(
                "SELECT * FROM remboursement 
                 WHERE id_credit = ? 
                 AND montant_rembourse = ? 
                 AND devise=?
                 AND date_remboursement = ? 
                 AND libele = ?"
            );
            $query_verif->execute([$credit, $montant,$devise, $daterem, $libele]);

            if ($query_verif->fetch()) {
                $message = "Cet enregistrement existe déjà.";
            } else {
                $query_inserer = $pdo->prepare(
                    "INSERT INTO remboursement 
                    (id_credit, montant_rembourse, devise, date_remboursement, libele) 
                    VALUES (?, ?, ?, ?, ?)"
                );
                $query_inserer->execute([$credit, $montant, $devise, $daterem, $libele]);

                $message = "Enregistrement effectué avec succès.";
            }

        } catch (PDOException $e) {
            $message = "Erreur SQL : " . $e->getMessage();
        }

    } else {
        $message = "Tous les champs sont obligatoires.";
    }

    header("Location: ../ramboursement.php?message=" . urlencode($message));
    exit();
}
