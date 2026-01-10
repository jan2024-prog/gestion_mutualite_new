<?php
session_start();
require_once("../connexion/connexion.php");

$id_entreprise = 1; // si tu veux gérer par entreprise connectée, sinon laisse 1 par défaut

// ==========================
// ENREGISTREMENT
// ==========================
if (isset($_POST['action']) && $_POST['action'] === 'save') {

    $noms = htmlspecialchars($_POST['noms']);
    $sexe = $_POST['sexe'];
    $etat_civil = $_POST['etat_civil'];
    $nomPartainaire = ($etat_civil === 'Marié') ? htmlspecialchars($_POST['nomPartainaire']) : null;
    $email = htmlspecialchars($_POST['email']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $adresse = htmlspecialchars($_POST['adresse']);
    $date_adhesion = $_POST['date_adhesion'] ?? date('Y-m-d');
    $statut = $_POST['statut'];

    $photo = "";
    if (!empty($_FILES['photo']['name'])) {
        $photo = $_FILES['photo']['name'];
        move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/".$photo);
    }

    $stmt = $pdo->prepare("INSERT INTO membre 
        (id_entreprise, noms, sexe, etat_civil, nomPartainaire, email, telephone, adresse, date_adhesion, photo, statut)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $res = $stmt->execute([$id_entreprise, $noms, $sexe, $etat_civil, $nomPartainaire, $email, $telephone, $adresse, $date_adhesion, $photo, $statut]);

    if ($res) {
        $_SESSION['message'] = "Membre enregistré avec succès !";
    } else {
        $_SESSION['messageError'] = "Erreur lors de l'enregistrement !";
    }
    header("Location: ../membre.php");
    exit;
}

// ==========================
// MODIFICATION
// ==========================
if (isset($_POST['action']) && $_POST['action'] === 'modifier') {

    $id_membre = intval($_POST['id_membre']);
    $noms = htmlspecialchars($_POST['noms']);
    $sexe = $_POST['sexe'];
    $etat_civil = $_POST['etat_civil'];
    $nomPartainaire = ($etat_civil === 'Marié') ? htmlspecialchars($_POST['nomPartainaire']) : null;
    $email = htmlspecialchars($_POST['email']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $adresse = htmlspecialchars($_POST['adresse']);
    $statut = $_POST['statut'];

    if (!empty($_FILES['photo']['name'])) {
        $photo = $_FILES['photo']['name'];
        move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/".$photo);

        $sql = "UPDATE membre SET id_entreprise=?, noms=?, sexe=?, etat_civil=?, nomPartainaire=?, email=?, telephone=?, adresse=?, statut=?, photo=? WHERE id_membre=?";
        $params = [$id_entreprise, $noms, $sexe, $etat_civil, $nomPartainaire, $email, $telephone, $adresse, $statut, $photo, $id_membre];
    } else {
        $sql = "UPDATE membre SET id_entreprise=?, noms=?, sexe=?, etat_civil=?, nomPartainaire=?, email=?, telephone=?, adresse=?, statut=? WHERE id_membre=?";
        $params = [$id_entreprise, $noms, $sexe, $etat_civil, $nomPartainaire, $email, $telephone, $adresse, $statut, $id_membre];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $_SESSION['message'] = "Modification effectuée avec succès !";
    header("Location: ../membre.php");
    exit;
}

// ==========================
// SUPPRESSION
// ==========================
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {

    $id_membre = intval($_GET['id']);

    $stmt = $pdo->prepare("
        UPDATE membre
        SET statut = 'Suspendu'
        WHERE id_membre = ?
    ");
    $stmt->execute([$id_membre]);

    $_SESSION['message'] = "Membre désactivé avec succès !";

    header("Location: ../membre.php");
    exit;
}

?>
