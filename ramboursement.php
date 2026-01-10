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
            r.libele
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
        c.id_credit,
        m.noms
    FROM Tcredit c
    INNER JOIN Tmembre m ON m.id_membre = c.id_membre
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
        m.noms
    FROM Tremboursement r
    INNER JOIN Tcredit c ON c.id_credit = r.id_credit
    INNER JOIN Tmembre m ON m.id_membre = c.id_membre
    ORDER BY r.date_remboursement DESC
");
$queryaffichage->execute();
?>

  <body>

  <main id="main" class="main">

    <div class="pagetitle">
        <h1 class="text-center">Gestion des remboursements</h1>
    </div>

    <div class="card">
      <div class="card-body">
      <h5 class="card-title">
          <?= $edit ? "Modifier un remboursement" : "Nouveau remboursement" ?>
      </h5>

        <form action="traitement/remboursementt.php" method="POST" class="row g-3">

          <input type="hidden" name="id_remboursement"
                value="<?= $edit['id_remboursement'] ?? '' ?>">

          <div class="col-md-4">
            <label>Crédit</label>
            <select name="credit" class="form-control" required>
            <option value="">-- Choisir le crédit --</option>
            <?php
            $query_sel->execute();
            while ($c = $query_sel->fetch()) {
                $selected = ($edit && $edit['id_credit'] == $c['id_credit']) ? 'selected' : '';
            ?>
            <option value="<?= $c['id_credit'] ?>" <?= $selected ?>>
                <?= htmlspecialchars($c['noms']) ?>
            </option>
            <?php } ?>
            </select>
          </div>

          <div class="col-md-4">
            <label>Montant</label>
            <input type="number" step="0.01" name="montant" class="form-control"
            value="<?= $edit['montant_rembourse'] ?? '' ?>" required>
          </div>

          <div class="col-md-4">
            <label>Date</label>
            <input type="date" name="daterem" class="form-control"
            value="<?= $edit['date_remboursement'] ?? '' ?>" required>  
          </div>

          <div class="col-md-6">
            <label>Libellé</label>
            <input type="text" name="libele" class="form-control"
            value="<?= $edit['libele'] ?? '' ?>" required>
          </div>

          <div class="col-md-4 d-flex align-items-end">
            <input type="submit"name="<?= $edit ? 'update' : 'save' ?>"class="btn btn-primary"
            value="<?= $edit ? 'Mettre à jour' : 'Enregistrer' ?>">
          </div>

        </form>
      </div>
    </div>

    <?php if (isset($_GET['message'])) { ?>
    <div class="alert alert-info mt-2">
    <?= htmlspecialchars($_GET['message']) ?>
    </div>
    <?php } ?>

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
    <td><?= htmlspecialchars($row['noms']) ?></td>
    <td><?= number_format($row['montant_rembourse'], 2) ?></td>
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

  </body>
</html>
