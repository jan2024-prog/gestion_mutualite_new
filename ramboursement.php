<!DOCTYPE html>
<html lang="fr">
<?php
require_once("connexion/connexion.php");

/* =============================
   MODE MODIFICATION
   ============================= */
$edit = null;

if (isset($_GET['edit_id'])) {
    $id_edit = (int) $_GET['edit_id'];

    $stmt = $pdo->prepare("
        SELECT 
            r.id_remboursement,
            r.id_credit,
            r.montant_rembourse,
            r.date_remboursement,
            r.libele,
            r.devise
        FROM Tremboursement r
        WHERE r.id_remboursement = ?
    ");
    $stmt->execute([$id_edit]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =============================
   LISTE DES CRÉDITS EN COURS
   ============================= */
$query_sel = $pdo->prepare("
   SELECT 
       m.*,
       co.numero_compte,
       c.id_credit,
       c.montant_credit,
       c.date_credit,
       c.devise,
       c.statut
   FROM membre m
   INNER JOIN compte co ON m.id_membre = co.id_membre
   INNER JOIN credit c ON co.id_compte = c.id_compte
   WHERE c.statut = 'En cours'
     AND c.montant_credit > 0
");
$query_sel->execute();

/* =============================
   LISTE DES REMBOURSEMENTS
   ============================= */
$queryaffichage = $pdo->prepare("
    SELECT
        r.id_remboursement,
        r.montant_rembourse,
        r.date_remboursement,
        r.libele,
        r.devise,
        m.noms AS nom_membre,
        co.numero_compte
    FROM remboursement r
    INNER JOIN credit c ON c.id_credit = r.id_credit
    INNER JOIN compte co ON co.id_compte = c.id_compte
    INNER JOIN membre m ON m.id_membre = co.id_membre
    ORDER BY r.date_remboursement DESC
");
$queryaffichage->execute();
?>

<body>

<main id="main" class="main">

    <div class="pagetitle">
        <h1 class="text-center">Gestion des remboursements</h1>
    </div>

    <!-- MESSAGES & ALERTES -->
    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
      <h5 class="card-title">
          <?= $edit ? "Modifier un remboursement" : "Nouveau remboursement" ?>
      </h5>

        <form action="traitement/remboursementt.php" method="POST" class="row g-3">

          <input type="hidden" name="id_remboursement" value="<?= $edit['id_remboursement'] ?? '' ?>">

          <!-- CREDIT -->
          <div class="col-md-4">
            <label>Crédit</label>
            <select name="credit" id="credit" class="form-control" required>
            <option value="">-- Choisir le crédit --</option>
            <?php
            $query_sel->execute();
            while ($c = $query_sel->fetch()) {
                $selected = ($edit && $edit['id_credit'] == $c['id_credit']) ? 'selected' : '';
            ?>
            <option value="<?= $c['id_credit'] ?>" data-devise="<?= $c['devise'] ?>" <?= $selected ?>>
                <?= htmlspecialchars($c['noms']) ?> (<?= htmlspecialchars($c['numero_compte']) ?>)
            </option>
            <?php } ?>
            </select>
          </div>

          <!-- Hidden pour récupérer la devise si modification -->
          <?php if ($edit): ?>
          <input type="hidden" id="credit_devise_init" value="<?= htmlspecialchars($edit['devise']) ?>">
          <?php endif; ?>

          <!-- MONTANT -->
          <div class="col-md-4">
            <label>Montant</label>
            <input type="number" step="0.01" name="montant" id="montant" class="form-control"
            value="<?= $edit['montant_rembourse'] ?? '' ?>" required>
          </div>

          <!-- RESTE À PAYER -->
          <div class="col-md-4">
            <label>Reste à payer</label>
            <input type="text" id="reste" class="form-control" readonly value="">
          </div>

          <!-- DATE -->
          <div class="col-md-4">
            <label>Date</label>
            <input type="date" name="daterem" class="form-control"
            value="<?= $edit['date_remboursement'] ?? '' ?>" required>  
          </div>

          <!-- DEVISE -->
          <div class="col-md-2">
          <label>Devise</label>
          <select name="devise" id="devise" class="form-select" required>
              <option value="Franc" <?= ($edit && $edit['devise']=='Franc')?'selected':'' ?>>Franc congolais</option>
              <option value="Dollar" <?= ($edit && $edit['devise']=='Dollar')?'selected':'' ?>>Dollars</option>
          </select>
          </div>

          <!-- LIBELLE -->
          <div class="col-md-6">
            <label>Libellé</label>
            <input type="text" name="libele" class="form-control"
            value="<?= $edit['libele'] ?? '' ?>" required>
          </div>

          <!-- BOUTON -->
          <div class="col-md-4 d-flex align-items-end">
            <input type="submit" name="<?= $edit ? 'update' : 'save' ?>" class="btn btn-primary"
            value="<?= $edit ? 'Mettre à jour' : 'Enregistrer' ?>">
          </div>

        </form>
      </div>
    </div>

    <!-- LISTE DES REMBOURSEMENTS -->
    <section class="section mt-4">
    <div class="card">
    <div class="card-body">

    <h5 class="card-title">Liste des remboursements</h5>

    <table class="table table-hover align-middle">
    <thead class="table-primary">
    <tr>
    <th>#</th>
    <th>Date</th>
    <th>Membre</th>
    <th>Montant</th>
    <th>Devise</th>
    <th>Libellé</th>
    <th>Action</th>
    </tr>
    </thead>

    <tbody>
    <?php
    $i = 0;
    while ($row = $queryaffichage->fetch()) {
        $i++;
    ?>
    <tr>
    <td><?= $i ?></td>
    <td><?= htmlspecialchars($row['date_remboursement']) ?></td>
    <td><?= htmlspecialchars($row['nom_membre']) ?></td>
    <td><?= number_format($row['montant_rembourse'], 2) ?></td>
    <td><?= htmlspecialchars($row['devise']) ?></td>
    <td><?= htmlspecialchars($row['libele']) ?></td>
    <td>
    <a href="ramboursement.php?edit_id=<?=$row['id_remboursement'] ?>"
      class="btn btn-sm btn-outline-success">Modifier</a>

    <a href="traitement/remboursementt.php?delete_id=<?=$row['id_remboursement'] ?>"
      onclick="return confirm('Confirmer la suppression ?');"
      class="btn btn-sm btn-outline-danger">Supprimer</a>
      <a href="" class="btn btn-sm btn-outline-primary">Imprimer</a>
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

<!-- SCRIPT POUR RESTE À PAYER & ALERTES -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const creditSelect = document.getElementById("credit");
    const deviseSelect = document.getElementById("devise");
    const resteInput = document.getElementById("reste");
    const montantInput = document.getElementById("montant");

    function updateReste() {
        const id_credit = creditSelect.value;
        if (!id_credit) {
            resteInput.value = '';
            return;
        }

        // Récupérer la devise initiale si en modification
        const initDevise = document.getElementById("credit_devise_init")?.value;
        if (initDevise) deviseSelect.value = initDevise;

        fetch('traitement/get_reste_credit.php?id_credit=' + id_credit + '&devise=' + deviseSelect.value)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    resteInput.value = '';
                    montantInput.value = '';
                } else {
                    resteInput.value = parseFloat(data.reste).toFixed(2) + ' ' + deviseSelect.value;
                    montantInput.max = data.reste;
                    if (parseFloat(data.reste) <= 0) {
                        alert('Ce crédit est déjà totalement remboursé !');
                        montantInput.value = '';
                    }
                }
            });
    }

    creditSelect.addEventListener("change", updateReste);
    deviseSelect.addEventListener("change", updateReste);

    // Mise à jour automatique si modification
    <?php if ($edit): ?>
        updateReste();
    <?php endif; ?>
});
</script>

</body>
</html>
