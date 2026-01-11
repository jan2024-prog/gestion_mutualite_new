<?php
require_once("connexion/connexion.php");

/* =====================================================
   RÉCUPÉRATION DU RETRAIT À MODIFIER
   ===================================================== */
$editRetrait = null;

if (isset($_GET['edit_id'])) {
    $id_edit = (int) $_GET['edit_id'];

    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            cb.numero_compte_bloque,
            cb.statut,
            cb.solde_franc,
            cb.solde_dollar,
            m.noms
        FROM Tretrait_compte_bloque r
        INNER JOIN Tcompte_bloque cb 
            ON cb.id_compte_bloque = r.id_compte_bloque
        INNER JOIN TmembreBl m 
            ON m.id_membre = cb.id_membre
        WHERE r.id_retrait = :id
          AND cb.statut IN ('Débloqué','Urgent')
        LIMIT 1
    ");

    $stmt->execute([':id' => $id_edit]);
    $editRetrait = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editRetrait) {
        die("Compte non débloqué ou retrait inexistant.");
    }
}

/* =====================================================
   AJAX : NOM DU MEMBRE + SOLDE VIA COMPTE BLOQUÉ
   ===================================================== */
if (isset($_GET['ajax'], $_GET['id_compte_bloque'])) {
    header('Content-Type: application/json; charset=utf-8');

    $id = (int) $_GET['id_compte_bloque'];

    $sql = $pdo->prepare("
        SELECT 
            m.noms AS nom_membre,
            cb.solde_franc,
            cb.solde_dollar
        FROM Tcompte_bloque cb
        INNER JOIN TmembreBl m ON m.id_membre = cb.id_membre
        WHERE cb.id_compte_bloque = :id
          AND cb.statut IN ('Débloqué','Urgent')
        LIMIT 1
    ");
    $sql->execute([':id' => $id]);

    echo json_encode($sql->fetch(PDO::FETCH_ASSOC) ?: []);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Retrait Compte Bloqué</title>
</head>

<body class="theme-blush">
<main id="main" class="main">

<div class="pagetitle">
    <h1><center>Compléter les informations de retrait (compte bloqué)</center></h1>
</div>

<div class="card">
<div class="card-body">

<h5 class="card-title">
    <?= $editRetrait ? "Modifier le retrait" : "Nouveau retrait" ?>
</h5>

<form class="row g-3" action="traitement/retraitcomptebl.php" method="POST">

<input type="hidden" name="id_retrait" value="<?= $editRetrait['id_retrait'] ?? '' ?>">

<!-- Compte bloqué -->
<div class="col-lg-4 col-md-6">
    <label>Compte bloqué</label>
    <select name="id_compte_bloque" id="compte_bloque" class="form-control" required>
        <option value="">-- Choisir le compte --</option>
        <?php
        $q = $pdo->query("
            SELECT id_compte_bloque, numero_compte_bloque
            FROM Tcompte_bloque
            WHERE statut IN ('Débloqué','Urgent')
        ");
        while ($c = $q->fetch()) {
            $selected = ($editRetrait && $editRetrait['id_compte_bloque'] == $c['id_compte_bloque']) ? 'selected' : '';
        ?>
            <option value="<?= $c['id_compte_bloque'] ?>" <?= $selected ?>>
                <?= htmlspecialchars($c['numero_compte_bloque']) ?>
            </option>
        <?php } ?>
    </select>
</div>

<!-- Nom du bénéficiaire -->
<div class="col-md-4">
    <label>Nom du bénéficiaire</label>
    <input type="text" id="nom_membre" class="form-control" readonly
           value="<?= $editRetrait['noms'] ?? '' ?>">
</div>

<!-- Solde disponible -->
<div class="col-md-4">
    <label>Solde disponible</label>
    <input type="text" id="solde_disponible" class="form-control" readonly>
</div>

<!-- Montant -->
<div class="col-md-4">
    <label>Montant</label>
    <input type="number" name="montant" class="form-control" required
           value="<?= $editRetrait['montant'] ?? '' ?>">
</div>

<!-- Date -->
<div class="col-md-2">
    <label>Date</label>
    <input type="date" name="date_retrait" class="form-control" required
           value="<?= isset($editRetrait['date_retrait']) ? date('Y-m-d', strtotime($editRetrait['date_retrait'])) : '' ?>">
</div>

<!-- Devise -->
<div class="col-md-2">
<label>Devise</label>
<select name="devise" id="devise" class="form-select">
    <option value="Franc">Franc congolais</option>
    <option value="Dollar">Dollar</option>
</select>
</div>

<!-- Libellé -->
<div class="col-md-4">
    <label>Libellé</label>
    <input type="text" name="libelle" class="form-control" required
           value="<?= $editRetrait['libelle'] ?? '' ?>">
</div>

<!-- Bouton -->
<div class="col-md-4">
    <input class="btn btn-primary"
           name="<?= $editRetrait ? 'update' : 'save' ?>"
           type="submit"
           value="<?= $editRetrait ? 'Mettre à jour' : 'Enregistrer' ?>">
</div>

</form>

</div>
</div>

<!-- ========================= LISTE DES RETRAITS ========================= -->
<section class="section">
<div class="card">
<div class="card-body">

<h5 class="card-title">Liste des retraits</h5>

<table class="table table-hover">
<thead class="table-primary">
<tr>
    <th>#</th>
    <th>Date</th>
    <th>Compte</th>
    <th>Nom</th>
    <th>Libellé</th>
    <th>Montant</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php
$i = 1;
$sql = $pdo->query("
    SELECT 
        r.*,
        cb.numero_compte_bloque,
        m.noms
    FROM Tretrait_compte_bloque r
    INNER JOIN Tcompte_bloque cb ON cb.id_compte_bloque = r.id_compte_bloque
    INNER JOIN TmembreBl m ON m.id_membre = cb.id_membre
    ORDER BY r.date_retrait DESC
");
while ($row = $sql->fetch()) {
?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= $row['date_retrait'] ?></td>
    <td><?= $row['numero_compte_bloque'] ?></td>
    <td><?= $row['noms'] ?></td>
    <td><?= $row['libelle'] ?></td>
    <td><?= $row['montant'] ?></td>
    <td>
        <a href="?edit_id=<?= $row['id_retrait'] ?>" class="btn btn-sm btn-success">Modifier</a>
        <a href="traitement/retraitcomptebl.php?delete_id=<?= $row['id_retrait'] ?>"
           onclick="return confirm('Supprimer ce retrait ?');"
           class="btn btn-sm btn-danger">Supprimer</a>
    </td>
</tr>
<?php } ?>

</tbody>
</table>

</div>
</div>
</section>

</main>

<!-- ========================= JS ========================= -->
<script>
const compteSelect = document.getElementById('compte_bloque');
const nomMembre = document.getElementById('nom_membre');
const soldeInput = document.getElementById('solde_disponible');
const deviseSelect = document.getElementById('devise');

function updateSolde() {
    const idCompte = compteSelect.value;
    const devise = deviseSelect.value;

    if (!idCompte) {
        nomMembre.value = '';
        soldeInput.value = '';
        return;
    }

    fetch('?ajax=1&id_compte_bloque=' + idCompte)
        .then(res => res.json())
        .then(data => {
            if (data.nom_membre) nomMembre.value = data.nom_membre;

            if (devise === 'Franc') soldeInput.value = data.solde_franc ?? 0;
            else soldeInput.value = data.solde_dollar ?? 0;
        })
        .catch(err => console.error(err));
}

// Mettre à jour solde au changement du compte ou de la devise
compteSelect.addEventListener('change', updateSolde);
deviseSelect.addEventListener('change', updateSolde);

// Si édition, afficher le solde au chargement
if (compteSelect.value) updateSolde();
</script>

<?php include "menu/lien.php"; ?>
</body>
</html>
