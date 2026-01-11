<?php
require_once("../connexion/connexion.php");

/* =====================================================
   AJAX : récupérer nom du membre et solde selon devise
   ===================================================== */
if (isset($_GET['ajax'], $_GET['id_compte_bloque'])) {
    header('Content-Type: application/json; charset=utf-8');

    $id = (int) $_GET['id_compte_bloque'];
    $sql = $pdo->prepare("
        SELECT m.noms AS nom_membre, solde_franc, solde_dollar
        FROM Tcompte_bloque cb
        INNER JOIN TmembreBl m ON m.id_membre = cb.id_membre
        WHERE cb.id_compte_bloque = :id
          AND cb.statut IN ('Débloqué','Urgent')
        LIMIT 1
    ");
    $sql->execute([':id' => $id]);
    echo json_encode($sql->fetch(PDO::FETCH_ASSOC) ?: []);
    exit;
}

/* =====================================================
   Suppression d'un retrait
   ===================================================== */
if (isset($_GET['delete_id'])) {
    $id_delete = (int) $_GET['delete_id'];

    try {
        $pdo->beginTransaction();

        $check = $pdo->prepare("SELECT * FROM Tretrait_compte_bloque WHERE id_retrait = ?");
        $check->execute([$id_delete]);
        $retrait = $check->fetch(PDO::FETCH_ASSOC);

        if (!$retrait) throw new Exception("Retrait inexistant.");

        $delete = $pdo->prepare("DELETE FROM Tretrait_compte_bloque WHERE id_retrait = ?");
        $delete->execute([$id_delete]);

        $pdo->commit();
        $message = "Retrait supprimé avec succès.";

    } catch (Throwable $e) {
        $pdo->rollBack();
        $message = "Erreur lors de la suppression : " . $e->getMessage();
    }

    header("Location: ../retraitcomptebl.php?message=" . urlencode($message));
    exit();
}

/* =====================================================
   Traitement du formulaire retrait (insertion ou modification)
   ===================================================== */
if (isset($_POST['save']) || isset($_POST['update'])) {

    $id_retrait       = isset($_POST['id_retrait']) ? (int) $_POST['id_retrait'] : null;
    $id_compte_bloque = (int) $_POST['id_compte_bloque'];
    $montant          = (float) $_POST['montant'];
    $date_retrait     = $_POST['date_retrait'];
    $devise           = $_POST['devise'];
    $libelle          = trim($_POST['libelle']);

    if ($id_compte_bloque && $montant > 0 && $date_retrait && $devise && $libelle) {
        try {
            $pdo->beginTransaction();

            // Vérifier statut et solde du compte
            $check = $pdo->prepare("
                SELECT statut, solde_franc, solde_dollar
                FROM Tcompte_bloque
                WHERE id_compte_bloque = ?
                  AND statut IN ('Débloqué','Urgent')
                FOR UPDATE
            ");
            $check->execute([$id_compte_bloque]);
            $compte = $check->fetch(PDO::FETCH_ASSOC);

            if (!$compte) throw new Exception("Compte non autorisé pour retrait.");

            if ($devise === 'Franc' && $compte['solde_franc'] < $montant) {
                throw new Exception("Solde insuffisant en Franc.");
            } elseif ($devise === 'Dollar' && $compte['solde_dollar'] < $montant) {
                throw new Exception("Solde insuffisant en Dollar.");
            }

            // Vérification doublon (hors update sur le même id)
            $verif = $pdo->prepare("
                SELECT 1
                FROM Tretrait_compte_bloque
                WHERE id_compte_bloque = ?
                  AND montant = ?
                  AND date_retrait = ?
                  AND libelle = ?
                  AND devise = ?
                  " . (isset($_POST['update']) ? "AND id_retrait != ?" : "") . "
            ");
            $params = [$id_compte_bloque, $montant, $date_retrait, $libelle, $devise];
            if (isset($_POST['update'])) $params[] = $id_retrait;
            $verif->execute($params);
            if ($verif->fetch()) throw new Exception("Cet enregistrement existe déjà.");

            if (isset($_POST['save'])) {
                // Insertion
                $insert = $pdo->prepare("
                    INSERT INTO Tretrait_compte_bloque
                    (id_compte_bloque, montant, date_retrait, libelle, devise)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $insert->execute([$id_compte_bloque, $montant, $date_retrait, $libelle, $devise]);
                $message = "Retrait effectué avec succès.";
            } else {
                // Modification
                $update = $pdo->prepare("
                    UPDATE Tretrait_compte_bloque
                    SET id_compte_bloque = ?, montant = ?, date_retrait = ?, libelle = ?, devise = ?
                    WHERE id_retrait = ?
                ");
                $update->execute([$id_compte_bloque, $montant, $date_retrait, $libelle, $devise, $id_retrait]);
                $message = "Retrait mis à jour avec succès.";
            }

            $pdo->commit();

        } catch (Throwable $e) {
            $pdo->rollBack();
            $message = "Erreur : " . $e->getMessage();
        }

        header("Location: ../retraitcomptebl.php?message=" . urlencode($message));
        exit();
    } else {
        header("Location: ../retraitcomptebl.php?message=" . urlencode("Champs obligatoires manquants."));
        exit();
    }
}
?>
