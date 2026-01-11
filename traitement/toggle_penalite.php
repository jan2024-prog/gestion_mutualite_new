<?php
require_once("../connexion/connexion.php");

if (!isset($_POST['id_credit'], $_POST['etat'])) {
    exit;
}

$id_credit = (int)$_POST['id_credit'];
$nouvel_etat = ($_POST['etat'] == 1) ? 0 : 1;

$stmt = $pdo->prepare("
    UPDATE credit
    SET penalite_active = ?
    WHERE id_credit = ?
");
$stmt->execute([$nouvel_etat, $id_credit]);

header("Location: ../liste_penalites.php");
exit;
