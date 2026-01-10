<?php
session_start();
require_once("connexion/connexion.php");

/* ==========================
   CHARGEMENT UTILISATEUR (MODIF)
========================== */
$utilisateur = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$_GET['id']]);
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ==========================
   ENTREPRISES
========================== */
$entreprises = $pdo->query("SELECT id_entreprise, nom FROM entreprise ORDER BY nom")
                   ->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des utilisateurs</title>
</head>

<body>

<main id="main" class="main">

<!-- ==========================
     TITRE
========================== -->
<div class="pagetitle">
    <h1>Gestion des utilisateurs</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Accueil</a></li>
            <li class="breadcrumb-item active">Utilisateurs</li>
        </ol>
    </nav>
</div>

<section class="section">
<div class="row">
<div class="card">
<div class="card-body">

<h5 class="card-title text-center">
    <?= $utilisateur ? "Modification de l'utilisateur" : "Enregistrement d’un utilisateur" ?>
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
<form method="post" action="traitement/traitementUtilisateur.php" enctype="multipart/form-data">
<input type="hidden" name="id_utilisateur" value="<?= $utilisateur['id_utilisateur'] ?? '' ?>">
<input type="hidden" name="action" value="<?= $utilisateur ? 'modifier' : 'save' ?>">

<table class="table table-bordered">

<tr>
    <td>Entreprise</td>
    <td>
        <select name="id_entreprise" class="form-control">
            <option value="">-- Aucune --</option>
            <?php foreach ($entreprises as $e): ?>
                <option value="<?= $e['id_entreprise'] ?>"
                    <?= (isset($utilisateur) && $utilisateur['id_entreprise']==$e['id_entreprise'])?'selected':'' ?>>
                    <?= htmlspecialchars($e['nom']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>

<tr>
    <td>Noms</td>
    <td><input type="text" name="noms" class="form-control" required
        value="<?= $utilisateur['noms'] ?? '' ?>"></td>
</tr>

<tr>
    <td>Nom d'utilisateur</td>
    <td><input type="text" name="username" class="form-control" required
        value="<?= $utilisateur['username'] ?? '' ?>"></td>
</tr>

<tr>
    <td>Mot de passe</td>
    <td>
        <input type="password" name="mot_de_passe" class="form-control"
               <?= $utilisateur ? '' : 'required' ?>>
        <?php if ($utilisateur): ?>
            <small class="text-muted">Laisser vide pour ne pas modifier</small>
        <?php endif; ?>
    </td>
</tr>

<tr>
    <td>Rôle</td>
    <td>
        <select name="role" class="form-control">
            <?php
            $roles = ['Admin','Caissier','Secretaire','Gestionnaire'];
            foreach ($roles as $r):
            ?>
                <option value="<?= $r ?>"
                    <?= (isset($utilisateur) && $utilisateur['role']==$r)?'selected':'' ?>>
                    <?= $r ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>

<tr>
    <td>Téléphone</td>
    <td><input type="text" name="telephone" class="form-control"
        value="<?= $utilisateur['telephone'] ?? '' ?>"></td>
</tr>

<tr>
    <td>Email</td>
    <td><input type="email" name="email" class="form-control"
        value="<?= $utilisateur['email'] ?? '' ?>"></td>
</tr>

<tr>
    <td>Photo</td>
    <td><input type="file" name="photo" class="form-control"></td>
</tr>

<tr class="d-none">
    <td>Statut</td>
    <td>
        <select name="statut" class="form-control">
            <option value="Actif" <?= (isset($utilisateur) && $utilisateur['statut']=='Actif')?'selected':'' ?>>Actif</option>
            <option value="Inactif" <?= (isset($utilisateur) && $utilisateur['statut']=='Inactif')?'selected':'' ?>>Inactif</option>
        </select>
    </td>
</tr>

<tr>
<td colspan="2" class="text-center">
    <button type="submit" class="btn btn-success btn-sm px-4">
        <?= $utilisateur ? "Modifier" : "Enregistrer" ?>
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
     LISTE UTILISATEURS
========================== -->
<?php
$users = $pdo->query("
    SELECT u.*, e.nom AS entreprise
    FROM utilisateur u
    LEFT JOIN entreprise e ON e.id_entreprise = u.id_entreprise
    ORDER BY id_utilisateur DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
<div class="card">
<div class="card-body">

<h5 class="card-title">Liste des utilisateurs</h5>

<table class="table datatable table-striped">
<thead>
<tr>
    <th>#</th>
    <th>Noms</th>
    <th>Username</th>
    <th>Rôle</th>
    <th>Entreprise</th>
    <th>Téléphone</th>
    <th>Statut</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>
<?php if ($users): $i=1; foreach ($users as $u): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($u['noms']) ?></td>
    <td><?= $u['username'] ?></td>
    <td><?= $u['role'] ?></td>
    <td><?= $u['entreprise'] ?? '-' ?></td>
    <td><?= $u['telephone'] ?></td>
    <td><?= $u['statut'] ?></td>
    <td>
        <a href="?id=<?= $u['id_utilisateur'] ?>" class="btn btn-primary btn-sm px-2">Modifier</a>
        <a href="traitement/traitementUtilisateur.php?action=supprimer&id=<?= $u['id_utilisateur'] ?>"
           onclick="return confirm('Supprimer cet utilisateur ?')"
           class="btn btn-danger btn-sm px-2">Supprimer</a>
    </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="8" class="text-center">Aucun utilisateur</td></tr>
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
