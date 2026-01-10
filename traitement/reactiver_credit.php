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
    SET statut = 'En cours',
        penalite_active = 1,
        last_penalite_date = NULL
    WHERE id_credit = ?
");
$stmt->execute([$id_credit]);

$_SESSION['message'] = "Crédit réactivé avec succès.";
header("Location: ../credit.php");
exit;
