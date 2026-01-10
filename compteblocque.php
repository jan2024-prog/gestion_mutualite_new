<?php
session_start();
require_once("connexion/connexion.php");

/* =========================
   CHARGEMENT POUR MODIFICATION
   ========================= */
$editMembre = null;
if (isset($_GET['id_membre'])) {
    $id = (int) $_GET['id_membre'];
    $stmt = $pdo->prepare("SELECT * FROM tmembrebl WHERE id_membre = ?");
    $stmt->execute([$id]);
    $editMembre = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
    <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Gestion des Membres</title>
        </head>
        <body>

            <main id="main" class="main">
                <div class="pagetitle">
                    <h1 class="text-center">Nouvelle compte bloquer</h1>
                </div>

                <!-- MESSAGES -->
                <?php
                if (isset($_SESSION['message'])) {
                    echo "<div class='alert alert-success'>".$_SESSION['message']."</div>";
                    unset($_SESSION['message']);
                }
                if (isset($_SESSION['messageError'])) {
                    echo "<div class='alert alert-danger'>".$_SESSION['messageError']."</div>";
                    unset($_SESSION['messageError']);
                }
                ?>

                <div class="card">
                <div class="card-body">
                <h5 class="card-title">Ajouter / Modifier un membre</h5>

                <form class="row g-3" method="post" action="traitement/creercomptebl.php">

                    <input type="hidden" name="id_membre" value="<?= $editMembre['id_membre'] ?? '' ?>">

                    <!-- NOM -->
                    <div class="col-md-8">
                        <label class="form-label">Noms</label>
                        <input type="text" name="noms" class="form-control" placeholder="Ex kasereka maru salima"
                            value="<?= htmlspecialchars($editMembre['noms'] ?? '') ?>">
                    </div>

                    <!-- GENRE -->
                    <div class="col-md-4">
                        <label class="form-label">Genre</label>
                        <select name="genre" class="form-select">
                            <option value="">-- Choisir votre genre --</option>
                            <option value="masculin" <?= (isset($editMembre['sexe']) && $editMembre['sexe'] === 'masculin') ? 'selected' : '' ?>>Masculin</option>
                            <option value="feminin" <?= (isset($editMembre['sexe']) && $editMembre['sexe'] === 'feminin') ? 'selected' : '' ?>>Feminin</option>
                        </select>
                    </div>

                    <!-- DATE DE NAISSANCE -->
                    <div class="col-md-4">
                        <label class="form-label">Date naissance</label>
                        <input type="date" name="datenaiss" class="form-control"
                            value="<?= isset($editMembre['datenaissance']) ? date('Y-m-d', strtotime($editMembre['datenaissance'])) : '' ?>">
                    </div>

                    <!-- TELEPHONE -->
                    <div class="col-md-4">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="telephone" class="form-control"
                            value="<?= htmlspecialchars($editMembre['telephone'] ?? '') ?>">
                    </div>

                    <!-- ETAT CIVILE -->
                    <div class="col-md-4">
                        <label class="form-label">Etat civile</label>
                        <select name="Etatcivil" class="form-select">
                            <option value="">-- Choisir votre Etat civile --</option>
                            <option value="marié" <?= (isset($editMembre['etatcivil']) && $editMembre['etatcivil'] === 'marié') ? 'selected' : '' ?>>Marié</option>
                            <option value="cellibataire" <?= (isset($editMembre['etatcivil']) && $editMembre['etatcivil'] === 'cellibataire') ? 'selected' : '' ?>>Célibataire</option>
                            <option value="divorsé" <?= (isset($editMembre['etatcivil']) && $editMembre['etatcivil'] === 'divorsé') ? 'selected' : '' ?>>Divorcé</option>
                            <option value="veuve" <?= (isset($editMembre['etatcivil']) && $editMembre['etatcivil'] === 'veuve') ? 'selected' : '' ?>>Veuve</option>
                        </select>
                    </div>

                    <!-- ADRESSE -->
                    <div class="col-md-8">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="adresse" class="form-control" placeholder="Ex Butembo, comm. mususa, q. bwinongo .."
                            value="<?= htmlspecialchars($editMembre['adresse'] ?? '') ?>">
                    </div>

                    <!-- STATUT -->
                    <div class="col-md-4">
                        <label class="form-label">Date debloqué</label>
                        <input type="date" name="datedeblo" class="form-control">
                    </div>

                    <!-- BOUTON -->
                    <div class="col-md-4 mt-3">
                        <input type="submit" name="save" class="btn btn-primary btn-sm p-2" value="Enregistrer">
                    </div>

                </form>
                </div>
                </div>

                <!-- LISTE DES MEMBRES -->
                <section class="section mt-4">
                    <div class="card">
                    <div class="card-body">

                    <h5 class="card-title">Liste des membres</h5>

                    <?php
                    $sql = "SELECT * FROM tmembrebl WHERE statut <> 'Supprimé' ORDER BY id_membre DESC";
                    $stmt = $pdo->query($sql);
                    ?>

                    <table class="table table-striped datatable">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Noms</th>
                        <th>Num</th>
                        <th>Genre</th>
                        <th>Date naissance</th>
                        <th>Téléphone</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['noms']) ?></td>
                        <td><?= htmlspecialchars($row['numero_compte_bloque']) ?></td>
                        <td><?= htmlspecialchars($row['sexe']) ?></td>
                        <td><?= isset($row['datenaissance']) ? date('d-m-Y', strtotime($row['datenaissance'])) : '' ?></td>
                        <td><?= htmlspecialchars($row['telephone']) ?></td>
                        <td><?= htmlspecialchars($row['statut']) ?></td>
                        <td>
                            <a href="compteblocque.php?id_membre=<?= $row['id_membre'] ?>" class="btn btn-primary btn-sm">Modifier</a>

                            <a href="traitement/creercomptebl.php?action=supprimer&id_membre=<?= $row['id_membre'] ?>"
                            onclick="return confirm('Voulez-vous supprimer ce membre ?');"
                            class="btn btn-danger btn-sm">Supprimer</a>
                        </td>
                    </tr>
                    <?php } ?>
                    </tbody>
                    </table>

                    </div>
                    </div>
                </section>

          </main>
         <?php include "menu/lien.php"; ?>
    </body>
</html>
