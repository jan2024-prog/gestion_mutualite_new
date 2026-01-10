<?php
$message = "";
require_once ("../connexion/connexion.php");

// ----------------------------
// Suppression d'un retrait
// ----------------------------
if (isset($_GET['delete_id'])) {
    $id_retrait = (int) $_GET['delete_id'];

    try {
        $delete = $pdo->prepare("DELETE FROM retrait WHERE id_retrait = ?");
        $delete->execute([$id_retrait]);

        $message = "Retrait supprimé avec succès.";
    } catch (PDOException $e) {
        $message = "Erreur SQL lors de la suppression : " . $e->getMessage();
    }

    header("Location: ../retrait.php?message=" . urlencode($message));
    exit();
}

// ----------------------------
// Modification d'un retrait
// ----------------------------
if (isset($_POST['update'])) {

    $id_retrait = htmlspecialchars($_POST['id_retrait'] ?? '');
    $compte     = htmlspecialchars($_POST['compte'] ?? '');
    $montant    = htmlspecialchars($_POST['montant'] ?? '');
    $dateret    = htmlspecialchars($_POST['daterem'] ?? '');
    $motif      = htmlspecialchars($_POST['motif'] ?? '');
    $devise     = htmlspecialchars($_POST['devise'] ?? '');

    if (!empty($id_retrait) && !empty($compte) && !empty($montant)&& !empty($devise) && !empty($dateret) && !empty($motif)) {

        try {
            // Vérifier solde et crédit
            $soldeCheck = $pdo->prepare("SELECT solde, nom_membre FROM compte WHERE id_compte = ?");
            $soldeCheck->execute([$compte]);
            $compteData = $soldeCheck->fetch(PDO::FETCH_ASSOC);

            if (!$compteData || $compteData['solde'] <= 0) {
                $message = "Impossible de modifier le retrait : solde insuffisant ou compte vide.";
            } elseif ($compteData['solde'] < $montant) {
                $message = "Impossible de modifier le retrait : montant supérieur au solde disponible.";
            } else {
                // Nom automatique si vide
                if (empty($nom)) {
                    $nom = $compteData['nom_membre'] ?? '';
                }

                // Mise à jour du retrait
                $update = $pdo->prepare("
                    UPDATE retrait 
                    SET id_compte = ?, montant = ?, devise=?, date_retrait = ?, libele = ?
                    WHERE id_retrait = ?
                ");
                $update->execute([$compte, $montant, $devise, $dateret, $motif, $id_retrait]);

                $message = "Retrait modifié avec succès.";
            }

        } catch (PDOException $e) {
            $message = "Erreur SQL lors de la modification : " . $e->getMessage();
        }

    } else {
        $message = "Tous les champs sont obligatoires pour la modification.";
    }

    header("Location: ../retrait.php?message=" . urlencode($message));
    exit();
}

// ----------------------------
// Insertion d'un retrait
// ----------------------------
if (isset($_POST['save'])) {

    $compte  = htmlspecialchars($_POST['compte'] ?? '');
    $montant = htmlspecialchars($_POST['montant'] ?? '');
    $dateret = htmlspecialchars($_POST['daterem'] ?? '');
    $motif   = htmlspecialchars($_POST['motif'] ?? '');
    $devise     = htmlspecialchars($_POST['devise'] ?? '');

    if (!empty($compte) && !empty($montant) && !empty($dateret) && !empty($motif) && !empty($devise)) {

        try {
            // Vérifier solde et crédit
            $soldeCheck = $pdo->prepare("SELECT solde, numero_compte FROM compte WHERE id_compte = ?");
            $soldeCheck->execute([$compte]);
            $compteData = $soldeCheck->fetch(PDO::FETCH_ASSOC);

            if (!$compteData || $compteData['solde'] <= 0) {
                $message = "Impossible d'effectuer le retrait : solde insuffisant ou compte vide.";
            } elseif ($compteData['solde'] < $montant) {
                $message = "Impossible d'effectuer le retrait : montant supérieur au solde disponible.";
            } else {
                // Nom automatique si vide
                if (empty($nom)) {
                    $nom = $compteData['numero_compte'] ?? '';
                }

                // Vérification de doublon
                $queryverifier = $pdo->prepare(
                    "SELECT * FROM retrait 
                     WHERE id_compte = ? 
                     AND montant = ? 
                     AND devise =?
                     AND date_retrait = ? 
                     AND libele = ?"
                );
                $queryverifier->execute([$compte, $montant, $devise, $dateret, $motif]);

                if ($queryverifier->fetch()) {
                    $message = "Cet enregistrement existe déjà.";
                } else {
                    // Insertion
                    $inserer = $pdo->prepare(
                        "INSERT INTO retrait (id_compte,  montant, devise, date_retrait, libele)
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    $inserer->execute([$compte, $montant, $devise, $dateret, $motif]);

                    // Débiter le compte
                    $pdo->prepare("UPDATE compte SET solde = solde - ? WHERE id_compte = ?")
                        ->execute([$montant, $compte]);

                    $message = "Enregistrement effectué avec succès.";
                }
            }

        } catch (PDOException $e) {
            $message = "Erreur SQL : " . $e->getMessage();
        }

    } else {
        $message = "Tous les champs sont obligatoires.";
    }

    header("Location: ../retrait.php?message=" . urlencode($message));
    exit();
}
?>
