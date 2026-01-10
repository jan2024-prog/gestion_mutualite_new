<?php
session_start();
require_once("connexion/connexion.php");

// Semaine & année
$semaine = date('W');
$annee   = date('Y');

/* ==========================
   CHARGEMENT POUR MODIFICATION
========================== */
$cotisation = null;

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT * FROM cotisation
        WHERE id_cotisation = ?
    ");
    $stmt->execute([$_GET['id']]);
    $cotisation = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des cotisations</title>
</head>
<body>

<main id="main" class="main">

<!-- ==========================
     TITRE
========================== -->
<div class="pagetitle">
    <h1 class="text-center">Gestion des cotisations</h1>
</div>

<!-- ==========================
     FORMULAIRE
========================== -->
<section class="section">
<div class="row">
<div class="card">
<div class="card-body">

<h5 class="card-title text-center">
    <?= $cotisation ? "Modification de la cotisation" : "Enregistrement des cotisations hebdomadaires" ?>
</h5>

<!-- MESSAGES -->
<?php
if (!empty($_SESSION['message'])) {
    echo "<div class='alert alert-success'>".$_SESSION['message']."</div>";
    unset($_SESSION['message']);
}
if (!empty($_SESSION['messageError'])) {
    echo "<div class='alert alert-danger'>".$_SESSION['messageError']."</div>";
    unset($_SESSION['messageError']);
}
?>

<form method="POST" action="traitement/traitementCotisation.php">

    <!-- ACTION -->
    <input type="hidden" name="action" value="<?= $cotisation ? 'modifier' : 'save' ?>">

    <?php if ($cotisation): ?>
        <input type="hidden" name="id_cotisation" value="<?= $cotisation['id_cotisation'] ?>">
    <?php endif; ?>

    <div class="row g-3">

        <!-- MEMBRE -->
        <div class="col-md-6">
            <label class="form-label">Membre</label>
            <select name="id_compte" class="form-control" <?= $cotisation ? 'disabled' : 'required' ?>>
                <option value="">-- Choisir un compte --</option>
                <?php
                $stmt = $pdo->query("
                    SELECT c.id_compte, m.noms, c.numero_compte
                    FROM compte c
                    JOIN membre m ON m.id_membre = c.id_membre
                    WHERE m.statut='Actif'
                ");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $selected = ($cotisation && $cotisation['id_compte'] == $row['id_compte']) ? 'selected' : '';
                    echo "<option value='{$row['id_compte']}' $selected>
                        {$row['noms']} ({$row['numero_compte']})
                    </option>";
                }
                ?>
            </select>
        </div>

        <!-- SEMAINE -->
        <div class="col-md-3">
            <label class="form-label">Semaine</label>
            <input type="text" class="form-control"
                   value="<?= $cotisation ? $cotisation['semaine'] : $semaine ?>" readonly>
        </div>

        <!-- ANNÉE -->
        <div class="col-md-3">
            <label class="form-label">Année</label>
            <input type="text" class="form-control"
                   value="<?= $cotisation ? $cotisation['annee'] : $annee ?>" readonly>
        </div>

        <!-- MONTANT -->
        <div class="col-md-4">
            <label class="form-label">Montant</label>
            <input type="number" name="montant"
                   value="<?= $cotisation['montant'] ?? '' ?>"
                   class="form-control" required>
        </div>

        <!-- LIBELÉ -->
        <div class="col-md-8">
            <label class="form-label">Libellé</label>
            <input type="text" name="libele"
                   value="<?= $cotisation['libele'] ?? 'Cotisation hebdomadaire' ?>"
                   class="form-control">
        </div>

        <!-- BOUTON -->
        <div class="col-12 text-center mt-3">
            <button type="submit" class="btn btn-<?= $cotisation ? 'primary' : 'success' ?> btn-sm px-4">
                <?= $cotisation ? 'Modifier cotisation' : 'Payer cotisation' ?>
            </button>
        </div>

    </div>
</form>

</div>
</div>
</div>
</section>

<!-- ==========================
     HISTORIQUE
========================== -->
<section class="section mt-4">
<div class="row">
<div class="card">
<div class="card-body">

<h5 class="card-title">Historique des cotisations</h5>

<table class="table datatable table-striped">
<thead>
<tr>
    <th>#</th>
    <th>Membre</th>
    <th>Compte</th>
    <th>Montant</th>
    <th>Semaine</th>
    <th>Année</th>
    <th>Date</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php
$stmt = $pdo->query("
    SELECT ct.*, m.noms, c.numero_compte
    FROM cotisation ct
    JOIN compte c ON c.id_compte = ct.id_compte
    JOIN membre m ON m.id_membre = c.id_membre
    ORDER BY ct.date_cotisation DESC
");
$i = 1;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= $row['noms'] ?></td>
    <td><?= $row['numero_compte'] ?></td>
    <td><?= number_format($row['montant'],2,',',' ') ?> FC</td>
    <td><?= $row['semaine'] ?></td>
    <td><?= $row['annee'] ?></td>
    <td><?= date('d/m/Y', strtotime($row['date_cotisation'])) ?></td>
    <td>
        <a href="?id=<?= $row['id_cotisation'] ?>" class="btn btn-primary btn-sm">Modifier</a>
        <a href="traitement/traitementCotisation.php?action=supprimer&id=<?= $row['id_cotisation'] ?>"
           onclick="return confirm('Supprimer cette cotisation ?')"
           class="btn btn-danger btn-sm">Supprimer</a>
    </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

</div>
</div>
</div>
</section>

</main>

<?php include "menu/lien.php"; ?>
</body>
</html>
