<?php
session_start();
require_once "../connexion/connexion.php";

if (!isset($_GET['id'])) {
    header("Location: ../credits.php");
    exit;
}

$id_credit = (int) $_GET['id'];

$stmt = $pdo->prepare("
    UPDATE Tcredit
    SET statut = 'Désactivé',
        penalite_active = 0
    WHERE id_credit = ?
");
$stmt->execute([$id_credit]);

$_SESSION['message'] = "Crédit désactivé avec succès.";
header("Location: ../credit.php");
exit;
