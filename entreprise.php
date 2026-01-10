<?php
session_start();
require_once("connexion/connexion.php");

/* ==========================
   CHARGEMENT DE L’ENTREPRISE (MODIF)
========================== */
$entreprise = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM entreprise WHERE id_entreprise = ?");
    $stmt->execute([$_GET['id']]);
    $entreprise = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des entreprises</title>
</head>

<body>

<main id="main" class="main">

<!-- ==========================
     TITRE
========================== -->
<div class="pagetitle">
    <h1>Gestion des entreprises</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Accueil</a></li>
            <li class="breadcrumb-item active">Entreprises</li>
        </ol>
    </nav>
</div>

<section class="section">
<div class="row">
<div class="card">
<div class="card-body">

<h5 class="card-title text-center">
    <?= $entreprise ? "Modification de l’entreprise" : "Enregistrement d’une entreprise" ?>
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
<form method="post" action="traitement/traitementEntreprise.php" enctype="multipart/form-data">
<input type="hidden" name="id_entreprise" value="<?= $entreprise['id_entreprise'] ?? '' ?>">
<input type="hidden" name="action" value="<?= $entreprise ? 'modifier' : 'save' ?>">

<table class="table table-bordered">

<tr>
    <td>Nom de l’entreprise</td>
    <td>
        <input type="text" name="nom" class="form-control" required
               value="<?= $entreprise['nom'] ?? '' ?>">
    </td>
</tr>

<tr>
    <td>Adresse</td>
    <td>
        <input type="text" name="adresse" class="form-control"
               value="<?= $entreprise['adresse'] ?? '' ?>">
    </td>
</tr>

<tr>
    <td>Téléphone</td>
    <td>
        <input type="text" name="telephone" class="form-control"
               value="<?= $entreprise['telephone'] ?? '' ?>">
    </td>
</tr>

<tr>
    <td>Email</td>
    <td>
        <input type="email" name="email" class="form-control"
               value="<?= $entreprise['email'] ?? '' ?>">
    </td>
</tr>

<tr>
    <td>Date de création</td>
    <td>
        <input type="date" name="date_creation" class="form-control"
               value="<?= $entreprise['date_creation'] ?? '' ?>">
    </td>
</tr>

<tr>
    <td>Logo</td>
    <td>
        <input type="file" name="logo" class="form-control">
        <?php if (!empty($entreprise['logo'])): ?>
            <small class="text-muted">Logo actuel : <?= $entreprise['logo'] ?></small>
        <?php endif; ?>
    </td>
</tr>

<tr>
<td colspan="2" class="text-center">
    <button type="submit" class="btn btn-success btn-sm px-4">
        <?= $entreprise ? "Modifier" : "Enregistrer" ?>
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
     LISTE DES ENTREPRISES
========================== -->
<?php
$stmt = $pdo->query("SELECT * FROM entreprise ORDER BY id_entreprise DESC");
$entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
<div class="card">
<div class="card-body">

<h5 class="card-title">Liste des entreprises</h5>

<table class="table datatable table-striped">
<thead>
<tr>
    <th>#</th>
    <th>Nom</th>
    <th>Adresse</th>
    <th>Téléphone</th>
    <th>Email</th>
    <th>Date création</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>
<?php if ($entreprises): $i=1; foreach ($entreprises as $e): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($e['nom']) ?></td>
    <td><?= $e['adresse'] ?></td>
    <td><?= $e['telephone'] ?></td>
    <td><?= $e['email'] ?></td>
    <td><?= $e['date_creation'] ?></td>
    <td>
        <a href="?id=<?= $e['id_entreprise'] ?>" class="btn btn-primary btn-sm">Modifier</a>
        <a href="traitement/traitementEntreprise.php?action=supprimer&id=<?= $e['id_entreprise'] ?>"
           onclick="return confirm('Supprimer cette entreprise ?')"
           class="btn btn-danger btn-sm">Supprimer</a>
    </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="7" class="text-center">Aucune entreprise</td></tr>
<?php endif; ?>
</tbody>
</table>

</div>
</div>
</section>

</main>

<?php include "menu/lien.php"; ?>
</body>
</html>
