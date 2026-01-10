<?php
session_start();
require_once("connexion/connexion.php");

/* ==========================
   CHARGEMENT DU MEMBRE (MODIF)
========================== */
$membre = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM membre WHERE id_membre = ?");
    $stmt->execute([$_GET['id']]);
    $membre = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des membres</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
</head>
<body>

<main id="main" class="main">

<!-- ==========================
     TITRE
========================== -->
<div class="pagetitle">
    <h1>Gestion des membres</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Accueil</a></li>
            <li class="breadcrumb-item active">Membres</li>
        </ol>
    </nav>
</div>

<section class="section">
<div class="row">
<div class="card">
<div class="card-body">

<h5 class="card-title text-center">
    <?= $membre ? "Modification du membre" : "Enregistrement d’un membre" ?>
</h5>

<!-- ==========================
     MESSAGES
========================== -->
<?php if (!empty($_SESSION['message'])): ?>
    <div class="alert alert-success"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['messageError'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['messageError']; unset($_SESSION['messageError']); ?></div>
<?php endif; ?>

<!-- ==========================
     FORMULAIRE
========================== -->
<form method="post" action="traitement/traitementMembre.php" enctype="multipart/form-data">
<input type="hidden" name="id_membre" value="<?= $membre['id_membre'] ?? '' ?>">
<input type="hidden" name="action" value="<?= $membre ? 'modifier' : 'save' ?>">

<table class="table table-bordered">
<tr>
    <td>Noms </td>
    <td><input type="text" name="noms" class="form-control" required
               value="<?= $membre['noms'] ?? '' ?>"></td>
</tr>

<tr>
    <td>Sexe </td>
    <td>
        <select name="sexe" class="form-control" required>
            <option value="">-- Sélectionner --</option>
            <option value="M" <?= (isset($membre) && $membre['sexe']=='M')?'selected':'' ?>>Masculin</option>
            <option value="F" <?= (isset($membre) && $membre['sexe']=='F')?'selected':'' ?>>Féminin</option>
        </select>
    </td>
</tr>

<tr>
    <td>Etat Civil</td>
    <td>
        <select name="etat_civil" id="etat_civil" class="form-control" required>
            <option value="">-- Sélectionner --</option>
            <option value="Célibataire" <?= (isset($membre) && $membre['etat_civil']=='Célibataire')?'selected':'' ?>>Célibataire</option>
            <option value="Marié" <?= (isset($membre) && $membre['etat_civil']=='Marié')?'selected':'' ?>>Marié</option>
        </select>
    </td>
</tr>

<tr id="partenaire_row" style="display: <?= (isset($membre) && $membre['etat_civil']=='Marié')?'table-row':'none' ?>;">
    <td>Nom du Partenaire</td>
    <td><input type="text" name="nomPartainaire" id="nomPartainaire" class="form-control"
               value="<?= $membre['nomPartainaire'] ?? '' ?>"></td>
</tr>

<tr>
    <td>Email</td>
    <td><input type="email" name="email" class="form-control"
               value="<?= $membre['email'] ?? '' ?>"></td>
</tr>

<tr>
    <td>Téléphone</td>
    <td><input type="text" name="telephone" class="form-control"
               value="<?= $membre['telephone'] ?? '' ?>"></td>
</tr>

<tr>
    <td>Adresse</td>
    <td><input type="text" name="adresse" class="form-control"
               value="<?= $membre['adresse'] ?? '' ?>"></td>
</tr>

<tr>
    <td>Date d'adhésion</td>
    <td><input type="date" name="date_adhesion" class="form-control"
               value="<?= $membre['date_adhesion'] ?? date('Y-m-d') ?>"></td>
</tr>

<tr>
    <td>Photo</td>
    <td><input type="file" name="photo" class="form-control"></td>
</tr>

<tr>
    <td>Statut</td>
    <td>
        <select name="statut" class="form-control">
            <option value="Actif" <?= (isset($membre) && $membre['statut']=='Actif')?'selected':'' ?>>Actif</option>
            <option value="Suspendu" <?= (isset($membre) && $membre['statut']=='Suspendu')?'selected':'' ?>>Suspendu</option>
        </select>
    </td>
</tr>

<tr>
<td colspan="2" class="text-center">
    <button type="submit" class="btn btn-success btn-sm px-4">
        <?= $membre ? "Modifier" : "Enregistrer" ?>
    </button>
</td>
</tr>
</table>
</form>

</div>
</div>
</div>
</section>

<!-- ==========================
     LISTE DES MEMBRES
========================== -->
<?php
$stmt = $pdo->query("SELECT * FROM membre ORDER BY id_membre DESC");
$membres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
<div class="card">
<div class="card-body">

<h5 class="card-title">Liste des membres</h5>

<table class="table datatable table-striped">
<thead>
<tr>
    <th>#</th>
    <th>Noms</th>
    <th>Sexe</th>
    <th>Etat Civil</th>
    <th>Partenaire</th>
    <th>Email</th>
    <th>Téléphone</th>
    <th>Adresse</th>
    <th>Statut</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>
<?php if ($membres): $i=1; foreach ($membres as $m): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($m['noms']) ?></td>
    <td><?= $m['sexe'] ?></td>
    <td><?= $m['etat_civil'] ?></td>
    <td><?= $m['etat_civil'] == 'Marié' ? htmlspecialchars($m['nomPartainaire']) : '-' ?></td>
    <td><?= htmlspecialchars($m['email']) ?></td>
    <td><?= htmlspecialchars($m['telephone']) ?></td>
    <td><?= htmlspecialchars($m['adresse']) ?></td>
    <td><?= $m['statut'] ?></td>
    <td>
        <a href="?id=<?= $m['id_membre'] ?>" class="btn btn-primary btn-sm px-2">Modifier</a>
        <a href="traitement/traitementMembre.php?action=supprimer&id=<?= $m['id_membre'] ?>"
           onclick="return confirm('Supprimer ce membre ?')"
           class="btn btn-danger btn-sm px-2">Supprimer</a>
    </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="10" class="text-center">Aucun membre</td></tr>
<?php endif; ?>
</tbody>
</table>

</div>
</div>
</section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const etatCivil = document.getElementById('etat_civil');
    const partenaireRow = document.getElementById('partenaire_row');

    etatCivil.addEventListener('change', function() {
        if (etatCivil.value === 'Marié') {
            partenaireRow.style.display = 'table-row';
        } else {
            partenaireRow.style.display = 'none';
            document.getElementById('nomPartainaire').value = '';
        }
    });
});
</script>

<?php include "menu/lien.php"; ?>
</body>
</html>
