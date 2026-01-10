<?php
require_once __DIR__ . '/../connexion/connexion.php';


$today = date('Y-m-d');

$sql = $pdo->prepare("
    SELECT *
    FROM Tcredit
    WHERE statut = 'En cours'
      AND penalite_active = 1
      AND date_echeance < ?
      AND (last_penalite_date IS NULL OR last_penalite_date < ?)
");
$sql->execute([$today, $today]);

$credits = $sql->fetchAll(PDO::FETCH_ASSOC);

foreach ($credits as $credit) {

    $penalite = ($credit['montant_credit'] * $credit['taux_penalite']) / 100;

    // Mise à jour du crédit
    $update = $pdo->prepare("
        UPDATE Tcredit
        SET montant_credit = montant_credit + ?,
            last_penalite_date = ?
        WHERE id_credit = ?
    ");
    $update->execute([$penalite, $today, $credit['id_credit']]);

    // Historique
    $insert = $pdo->prepare("
        INSERT INTO Thistorique_penalite (id_credit, montant_penalite, date_penalite)
        VALUES (?, ?, ?)
    ");
    $insert->execute([$credit['id_credit'], $penalite, $today]);
}
