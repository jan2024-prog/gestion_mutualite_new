
<?php 

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("../connexion/connexion.php");

/* ==========================
   AJOUT TYPE DE CRÉDIT
========================== */
if (isset($_POST['action']) && $_POST['action'] === 'ajouter_type_credit') {

    $stmt = $pdo->prepare("
        INSERT INTO Ttype_credit (libelle, taux_interet, duree_mois)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $_POST['libelle'],
        $_POST['taux_interet'],
        $_POST['duree_mois']
    ]);

    $_SESSION['message'] = "Type de crédit ajouté avec succès";
    header("Location: ../type_credit.php");
    exit;
}

/* ==========================
   MODIFICATION TYPE DE CRÉDIT
========================== */
if (isset($_POST['action']) && $_POST['action'] === 'modifier_type_credit') {

    $stmt = $pdo->prepare("
        UPDATE Ttype_credit
        SET libelle = ?, taux_interet = ?, duree_mois = ?
        WHERE id_type_credit = ?
    ");
    $stmt->execute([
        $_POST['libelle'],
        $_POST['taux_interet'],
        $_POST['duree_mois'],
        $_POST['id_type_credit']
    ]);

    $_SESSION['message'] = "Type de crédit modifié avec succès";
    header("Location: ../type_credit.php");
    exit;
}


/* ============================
   SUPPRESSION TYPE DE CRÉDIT
============================ */
if (isset($_GET['action']) && $_GET['action'] === 'supprimer_type_credit') {

    $id_type_credit = intval($_GET['id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM Ttype_credit WHERE id_type_credit = ?");
        $stmt->execute([$id_type_credit]);

        $_SESSION['message'] = "Type de crédit supprimé avec succès";

    } catch (PDOException $e) {
        $_SESSION['messageError'] = "Erreur lors de la suppression : " . $e->getMessage();
    }

    header("Location: ../type_credit.php");
    exit;
}
