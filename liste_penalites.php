<?php
session_start();
require_once("connexion/connexion.php");

$sql = "
SELECT 
    c.id_credit,
    co.numero_compte,
    m.noms,
    c.montant_credit,
    c.date_echeance,
    c.penalite_active,
    DATEDIFF(CURDATE(), c.date_echeance) AS jours_retard
FROM credit c
JOIN compte co ON c.id_compte = co.id_compte
JOIN membre m ON co.id_membre = m.id_membre
WHERE 
    c.penalite_active = 1
    AND c.date_echeance < CURDATE()
    AND c.statut != 'Soldé'
ORDER BY jours_retard DESC 
";

$credits = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Crédits en retard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<main id="main" class="main">

<div class="pagetitle">
    <h1 class="text-center">Crédits en retard avec pénalités actives</h1>
</div>

<div class="card mt-4">
<div class="card-body">

<?php if(count($credits) == 0): ?>
    <div class="text-center" style="color:green;font-weight:bold">
        Aucun crédit avec pénalité active 🎉
    </div>
<?php else: ?>
<table class="table table-striped">
<thead>
<tr>
    <th>#</th>
    <th>Membre</th>
    <th>Compte</th>
    <th>Montant crédit</th>
    <th>Échéance</th>
    <th>Jours de retard</th>
    <th>Pénalité</th>
    <th class="text-center">Action</th>
</tr>
</thead>
<tbody>
<?php $i=1; foreach($credits as $c): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($c['noms']) ?></td>
    <td><?= $c['numero_compte'] ?></td>
    <td><?= number_format($c['montant_credit'],2) ?> FC</td>
    <td><?= $c['date_echeance'] ?></td>
    <td class="text-center text-danger fw-bold"><?= $c['jours_retard'] ?> jour(s)</td>
    <td class="text-center text-danger fw-bold">ACTIVE</td>
    <td class="text-center">
        <form method="post" action="traitement/toggle_penalite.php">
            <input type="hidden" name="id_credit" value="<?= $c['id_credit'] ?>">
            <input type="hidden" name="etat" value="<?= $c['penalite_active'] ?>">
            <button type="submit" class="btn btn-sm <?= $c['penalite_active'] ? 'btn-danger' : 'btn-success' ?>">
                <?= $c['penalite_active'] ? 'Désactiver' : 'Activer' ?>
            </button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

</div>
</div>

<?php include "menu/lien.php"; ?>
</main>

</body>
</html>
