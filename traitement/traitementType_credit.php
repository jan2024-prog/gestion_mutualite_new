<?php
require_once("../connexion/connexion.php");

$action = $_POST['action'] ?? $_GET['action'] ?? null;

/* ======================
   AJOUT
====================== */
if ($action === 'ajouter') {

    $stmt = $pdo->prepare("
        INSERT INTO type_credit (libelle, taux_interet, duree_max)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $_POST['libelle'],
        $_POST['taux_interet'],
        $_POST['duree_max']
    ]);

    header("Location: ../type_credit.php?success=1");
    exit;
}

/* ======================
   MODIFICATION
====================== */
if ($action === 'modifier') {

    $stmt = $pdo->prepare("
        UPDATE type_credit
        SET libelle = ?, taux_interet = ?, duree_max = ?
        WHERE id_type_credit = ?
    ");
    $stmt->execute([
        $_POST['libelle'],
        $_POST['taux_interet'],
        $_POST['duree_max'],
        $_POST['id_type_credit']
    ]);

    header("Location: ../type_credit.php?success=2");
    exit;
}

/* ======================
   SUPPRESSION
====================== */
if ($action === 'supprimer') {

    $stmt = $pdo->prepare("
        DELETE FROM type_credit WHERE id_type_credit = ?
    ");
    $stmt->execute([$_GET['id']]);

    header("Location: ../type_credit.php?success=3");
    exit;
}
