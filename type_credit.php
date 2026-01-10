<?php
session_start();
require_once("connexion/connexion.php");

/* ==========================
   CHARGEMENT POUR MODIFICATION
========================== */
$type = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM Ttype_credit WHERE id_type_credit = ?");
    $stmt->execute([$_GET['id']]);
    $type = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Type de crédit</title>
</head>

<body>

<main id="main" class="main">

<!-- ==========================
     TITRE
========================== -->
<div class="pagetitle">
    <h1 class="text-center">Gestion des types de crédit</h1>
</div>

<!-- ==========================
     FORMULAIRE
========================== -->
<div class="card">
<div class="card-body">

<h5 class="card-title text-center">
    <?= $type ? "Modification du type de crédit" : "Ajout d’un type de crédit" ?>
</h5>

<form class="row g-3" action="traitement/traitementType_credit.php" method="POST">
    <input type="hidden" name="id_type_credit" value="<?= $type['id_type_credit'] ?? '' ?>">
    <input type="hidden" name="action" value="<?= $type ? 'modifier_type_credit' : 'ajouter_type_credit' ?>">

    <div class="col-md-4">
        <label class="form-label">Libellé</label>
        <input type="text" class="form-control" name="libelle"
               value="<?= $type['libelle'] ?? '' ?>" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Taux d’intérêt (%)</label>
        <input type="number" class="form-control" name="taux_interet"
               step="0.01" value="<?= $type['taux_interet'] ?? '' ?>" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Durée (mois)</label>
        <input type="number" class="form-control" name="duree_mois"
               value="<?= $type['duree_mois'] ?? '' ?>" required>
    </div>

    <div class="col-12 text-center">
        <button type="submit" class="btn btn-success btn-sm px-4">
            <?= $type ? "Modifier" : "Ajouter" ?>
        </button>
    </div>
</form>

</div>
</div>

<!-- ==========================
     LISTE DES TYPES DE CRÉDIT
========================== -->
<section class="section mt-4">
<div class="card">
<div class="card-body">

<h5 class="card-title">Liste des types de crédit</h5>
<p>Types de crédit disponibles</p>

<?php
$stmt = $pdo->query("SELECT * FROM Ttype_credit ORDER BY id_type_credit DESC");
?>

<table class="table datatable table-striped">
<thead>
<tr>
    <th>#</th>
    <th>Libellé</th>
    <th>Taux</th>
    <th>Durée</th>
    <th class="text-center">Actions</th>
</tr>
</thead>

<tbody>
<?php $i=1; while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($row['libelle']) ?></td>
    <td><?= number_format($row['taux_interet'], 2) ?> %</td>
    <td><?= $row['duree_mois'] ?> mois</td>
    <td class="text-center">
        <a href="?id=<?= $row['id_type_credit'] ?>" class="btn btn-primary btn-sm px-2">
            Modifier
        </a>
        <a href="traitement/traitementType_credit.php?action=supprimer_type_credit&id=<?= $row['id_type_credit'] ?>"
           onclick="return confirm('Supprimer ce type de crédit ?')"
           class="btn btn-danger btn-sm px-2">
           Supprimer
        </a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</div>
</div>
</section>

</main>

<?php include "menu/lien.php"; ?>
</body>
</html>
