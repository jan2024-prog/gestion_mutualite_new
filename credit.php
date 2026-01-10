<?php
session_start();
require_once("connexion/connexion.php");
require_once("traitement/penalite_auto.php");

/* ==========================
   TRAITEMENT AJAX : NOM MEMBRE
   ========================== */
if (isset($_GET['ajax']) && isset($_GET['id_compte'])) {
    header('Content-Type: application/json');

    $id_compte = (int) $_GET['id_compte'];

    $sql = $pdo->prepare("
        SELECT m.noms AS nom_membre
        FROM compte c
        INNER JOIN membre m ON m.id_membre = c.id_membre
        WHERE c.id_compte = ?
    ");
    $sql->execute([$id_compte]);

    echo json_encode($sql->fetch(PDO::FETCH_ASSOC));
    exit;
}

/* ==========================
   MODIFICATION : RECUP CREDIT
   ========================== */
$edit = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];

    $stmt = $pdo->prepare("
        SELECT 
            c.id_credit,
            c.id_compte,
            m.noms AS nom,
            c.id_type_credit,
            c.montant_credit,
            c.date_credit,
            c.statut,
            c.libele
        FROM credit c
        INNER JOIN compte cp ON cp.id_compte = c.id_compte
        INNER JOIN membre m ON m.id_membre = cp.id_membre
        WHERE c.id_credit = ?
    ");
    $stmt->execute([$edit_id]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ==========================
   AFFICHAGE DES CREDITS
   ========================== */
$query = $pdo->prepare("
    SELECT 
        c.id_credit,
        c.penalite_active,
        m.noms,
        ct.numero_compte,
        tc.libelle AS type_credit,
        c.montant_credit,
        c.date_credit,
        c.statut
    FROM credit c
    INNER JOIN compte ct ON ct.id_compte = c.id_compte
    INNER JOIN membre m ON m.id_membre = ct.id_membre
    INNER JOIN type_credit tc ON tc.id_type_credit = c.id_type_credit
    ORDER BY c.date_credit DESC
");
$query->execute();
?>

<!DOCTYPE html>
<html lang="fr">
<body>

<main id="main" class="main">

<div class="pagetitle">
    <h1 class="text-center">Compléter les informations concernant le crédit</h1>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?= $_SESSION['message']; unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<div class="card">
<div class="card-body">
<h5 class="card-title"><?= $edit ? "Modifier crédit" : "Nouveau crédit" ?></h5>

<form class="row g-3" method="POST" action="traitement/credit.php">

<?php if ($edit): ?>
    <input type="hidden" name="id_credit" value="<?= $edit['id_credit'] ?>">
    <input type="hidden" name="update" value="1">
<?php endif; ?>

<!-- COMPTE -->
<div class="col-md-4">
<label>Numéro membre</label>
<select name="id_compte" id="compte" class="form-control" required>
    <option value="">-- Choisir le compte --</option>
    <?php
    $q = $pdo->prepare("
        SELECT 
            c.id_compte,
            c.numero_compte,
            m.noms,
            m.id_membre
        FROM compte c
        INNER JOIN membre m ON m.id_membre = c.id_membre
        WHERE c.solde > 0
        AND NOT EXISTS (
            SELECT 1 FROM credit cr
            WHERE cr.id_compte = c.id_compte
            AND cr.statut = 'En cours'
        )
        ORDER BY c.numero_compte
    ");
    $q->execute();

    while ($c = $q->fetch(PDO::FETCH_ASSOC)) {
        $selected = ($edit && $edit['id_compte'] == $c['id_compte']) ? 'selected' : '';
        echo "<option value='{$c['id_compte']}' $selected>{$c['numero_compte']}</option>";
    }
    ?>
</select>
</div>

<!-- NOM -->
<div class="col-md-4">
<label>Nom du bénéficiaire</label>
<input type="text" id="nom_membre" class="form-control" readonly
       value="<?= $edit ? htmlspecialchars($edit['nom']) : '' ?>">
</div>

<!-- TYPE CREDIT -->
<div class="col-md-4">
<label>Type crédit</label>
<select name="id_type_credit" class="form-control" required>
<option value="">-- Choisir --</option>
<?php
$type = $pdo->query("SELECT * FROM type_credit");
while ($t = $type->fetch()) {
    $selected = ($edit && $edit['id_type_credit'] == $t['id_type_credit']) ? 'selected' : '';
    echo "<option value='{$t['id_type_credit']}' $selected>{$t['libelle']}</option>";
}
?>
</select>
</div>

<!-- DATE -->
<div class="col-md-4">
<label>Date crédit</label>
<input type="date" name="date_credit" class="form-control" required
       value="<?= $edit ? $edit['date_credit'] : '' ?>">
</div>

<!-- MONTANT -->
<div class="col-md-4">
<label>Montant</label>
<input type="number" name="montant_credit" class="form-control" required
       value="<?= $edit ? $edit['montant_credit'] : '' ?>">
</div>

<!-- STATUT -->
<div class="col-md-4">
<label>Statut</label>
<select name="statut" class="form-control">
    <option value="En cours" <?= $edit && $edit['statut']=='En cours'?'selected':'' ?>>En cours</option>
    <option value="Soldé" <?= $edit && $edit['statut']=='Soldé'?'selected':'' ?>>Soldé</option>
</select>
</div>

<!-- LIBELE -->
<div class="col-md-2">
<label>Devise</label>
<select name="devise" class="form-select">
    <option value="Franc">Franc congolais</option>
    <option value="Dollar">Dollars</option>
</select>
</div>
<!-- devise -->
<div class="col-md-4">
<label>Libellé</label>
<input type="text" name="libele" class="form-control" required
       value="<?= $edit ? htmlspecialchars($edit['libele']) : '' ?>">
</div>
<div class="col-md-2">
<label>Date echeance</label>
<input type="date" name="dateecheance" class="form-control" required
       value="<?= $edit ? htmlspecialchars($edit['date_echeance']) : '' ?>">
</div>
<div class="col-md-2"><br>
<input type="submit" name="save" class="btn btn-primary"
       value="<?= $edit ? 'Modifier' : 'Enregistrer' ?>">
</div>

</form>
</div>
</div>

<!-- LISTE DES CREDITS -->
<div class="card mt-4">
<div class="card-body">
<h5 class="card-title">Liste des crédits</h5>

<table class="table table-hover">
<thead class="table-primary">
<tr>
<th>#</th>
<th>Membre</th>
<th>Compte</th>
<th>Type</th>
<th>Montant</th>
<th>Date</th>
<th>statut</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($row = $query->fetch(PDO::FETCH_ASSOC)): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= htmlspecialchars($row['noms']) ?></td>
<td><?= htmlspecialchars($row['numero_compte']) ?></td>
<td><?= htmlspecialchars($row['type_credit']) ?></td>
<td><?= number_format($row['montant_credit'],2) ?></td>
<td><?= $row['date_credit'] ?></td>
<td><?= htmlspecialchars($row['statut']) ?></td>
<td>
<a href="credit.php?edit_id=<?= $row['id_credit'] ?>" class="btn btn-sm btn-success">Modifier</a>
<a href="traitement/credit.php?delete_id=<?= $row['id_credit'] ?>"
   class="btn btn-sm btn-danger"
   onclick="return confirm('Supprimer ce crédit ?');">Supprimer</a>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

</main>

<!-- AJAX -->
<script>
document.getElementById('compte').addEventListener('change', function () {
    const idCompte = this.value;
    const nom = document.getElementById('nom_membre');
    nom.value = '';

    if (!idCompte) return;

    fetch('?ajax=1&id_compte=' + idCompte)
        .then(r => r.json())
        .then(d => { if (d && d.nom_membre) nom.value = d.nom_membre; });
});
</script>

<?php include "menu/lien.php"; ?>
</body>
</html>
