<?php
require_once("../connexion/connexion.php");
/* =========================
   SUPPRESSION D'UN DEPOT
   ========================= */
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {

    if (!empty($_GET['id_depot'])) {
        $id_depot = (int) $_GET['id_depot'];

        try {
            $supprimer = $pdo->prepare("
                DELETE FROM tdepot_compte_bloque 
                WHERE id_depot = ?
            ");
            $supprimer->execute([$id_depot]);

            $message = "Dépôt supprimé avec succès";

        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression : " . $e->getMessage();
        }
    } else {
        $message = "Identifiant invalide";
    }

    header("Location: ../depot.php?message=" . urlencode($message));
    exit();
}
/* =========================
   MODIFICATION D'UN DEPOT
   ========================= */
if (isset($_POST['action']) && $_POST['action'] === 'modifier_depot') {

    $id_depot      = (int) $_POST['id_depot'];
    $numero_compte = $_POST['numero_compte'];
    $montant       = $_POST['montant'];
    $libelle       = $_POST['libelle'];
    $modepaie      = $_POST['modepaie'];
    $dateCotise    = $_POST['dateCotise'];
    $devise        =$_POST['devise'];
    if (
        !empty($id_depot) &&
        !empty($numero_compte) &&
        !empty($montant) &&
        !empty($libelle) &&
        !empty($modepaie) &&
        !empty($dateCotise) &&
        !empty($devise)
    ) {

        try {
            $update = $pdo->prepare("
                UPDATE tdepot_compte_bloque
                SET
                    id_compte_bloque = ?,
                    montant = ?,
                    date_depot = ?,
                    mode_paiement = ?,
                    libele = ?,
                    devise=?
                WHERE id_depot = ?
            ");

            $ok = $update->execute([
                $numero_compte,
                $montant,
                $dateCotise,
                $modepaie,
                $libelle,
                $devise,
                $id_depot
            ]);

            if ($ok) {
                $message = "Dépôt modifié avec succès";
            } else {
                $message = "Erreur lors de la modification";
            }

        } catch (PDOException $e) {
            $message = "Erreur SQL : " . $e->getMessage();
        }

    } else {
        $message = "Veuillez remplir tous les champs";
    }

    header("Location: ../depot.php?message=" . urlencode($message));
    exit();
}


/* =========================
   AJOUT D'UN DEPOT
   ========================= */
if (isset($_POST['save'])) {

    $numero_compte = $_POST['numero_compte'];
    $montant       = $_POST['montant'];
    $libelle       = $_POST['libelle'];
    $modepaie      = $_POST['modepaie'];
    $dateCotise    = $_POST['dateCotise'];
    $devise        =$_POST['devise'];
    if (
        !empty($numero_compte) &&
        !empty($montant) &&
        !empty($libelle) &&
        !empty($modepaie) &&
        !empty($dateCotise) &&
        !empty($devise)
    ) {

        try {
            $verif = $pdo->prepare("
                SELECT 1 
                FROM tdepot_compte_bloque 
                WHERE id_compte_bloque = ?
                  AND montant = ?
                  AND date_depot = ?
                  AND mode_paiement = ?
                  AND libele = ?
                  AND devise=?
            ");

            $verif->execute([
                $numero_compte,
                $montant,
                $dateCotise,
                $modepaie,
                $libelle,
                $devise
            ]);

            if ($verif->fetch()) {
                $message = "Cet enregistrement existe déjà";
            } else {

                $insert = $pdo->prepare("
                    INSERT INTO tdepot_compte_bloque
                    (id_compte_bloque, montant, date_depot, mode_paiement, libele, devise)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $ok = $insert->execute([
                    $numero_compte,
                    $montant,
                    $dateCotise,
                    $modepaie,
                    $libelle,
                    $devise
                ]);

                if ($ok) {
                    $message = "Enregistrement effectué avec succès";
                } else {
                    $message = "Erreur lors de l'enregistrement";
                }
            }

        } catch (PDOException $e) {
            $message = "Erreur SQL : " . $e->getMessage();
        }

    } else {
        $message = "Veuillez remplir tous les champs";
    }

    header("Location: ../depot.php?message=" . urlencode($message));
    exit();
}
