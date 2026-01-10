<?php
require_once("connexion/connexion.php");

require_once("traitement/penalite_auto.php");

/* ==========================
   TRAITEMENT AJAX
   ========================== */
if (isset($_GET['ajax']) && isset($_GET['id_compte'])) {
    header('Content-Type: application/json');

    $id_compte = (int) $_GET['id_compte'];

    $sql = $pdo->prepare("
        SELECT m.noms AS nom_membre
        FROM Tcompte c
        INNER JOIN Tmembre m ON m.id_membre = c.id_membre
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
        SELECT c.id_credit, c.id_membre, c.nom, c.id_type_credit, c.montant_credit, c.date_credit, c.statut, c.libele, cp.id_compte
        FROM Tcredit c
        INNER JOIN Tcompte cp ON cp.id_membre = c.id_membre
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
       m.noms AS nom_membre,
       cp.numero_compte,
       tc.libelle AS type_credit,
       c.montant_credit,
       c.date_credit,
       c.statut
   FROM Tcredit c
   INNER JOIN Tmembre m ON m.id_membre = c.id_membre
   INNER JOIN Tcompte cp ON cp.id_membre = m.id_membre
   INNER JOIN Ttype_credit tc ON tc.id_type_credit = c.id_type_credit
   ORDER BY c.date_credit DESC
");
$query->execute();
?>

<!DOCTYPE html>
<html lang="en">
    <body>
        <main id="main" class="main">

            <div class="pagetitle">
                <h1 class="text-center">Compléter les informations concernant le crédit</h1>
            </div>
          <!-- LA SESSION MESSAGE DES PENALITES AUTOMATIQUES -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?= $edit ? "Modifier crédit" : "Nouveau crédit" ?></h5>

                <form class="row g-3" method="POST" action="traitement/credit.php">

                    <!-- HIDDEN ID POUR MODIFICATION -->
                    <?php if($edit): ?>
                    <input type="hidden" name="id_credit" value="<?= $edit['id_credit'] ?>">
                    <input type="hidden" name="update" value="1">
                    <?php endif; ?>

                    <!-- NUMERO MEMBRE -->
                    <div class="col-lg-4 col-md-6">
                    <label>Numéro membre</label>
                    <select name="compte" id="compte" class="form-control" required>
                        <option value="">-- Choisir le compte --</option>

                        <?php
                        $q = $pdo->prepare("
                            SELECT 
                                c.id_compte,
                                c.numero_compte,
                                c.nom_membre
                            FROM Tcompte c
                            WHERE c.solde > 0
                            AND c.est_bloque = FALSE
                            AND NOT EXISTS (
                                SELECT 1
                                FROM Tcredit cr
                                WHERE cr.id_membre = c.id_membre
                                    AND cr.statut = 'En cours'
                            )
                            ORDER BY c.numero_compte
                        ");
                        $q->execute();

                        while ($c = $q->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($edit && $edit['id_membre'] == $c['id_membre']) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($c['id_compte']) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($c['numero_compte']) ?>
                            </option>
                        <?php } ?>
                    </select>
                    </div>

                    <!-- NOM -->
                    <div class="col-md-4">
                    <label>Nom du bénéficiaire</label>
                    <input type="text" name="nom" id="nom_membre"
                        class="form-control" readonly required
                        value="<?= $edit ? htmlspecialchars($edit['nom']) : '' ?>">
                    </div>

                    <!-- TYPE CREDIT -->
                    <div class="col-md-4">
                    <label>Type crédit</label>
                    <select name="typecredit" class="form-control" required>
                        <option value="">-- choisir le type crédit --</option>
                        <?php
                        $type = $pdo->query("SELECT * FROM Ttype_credit");
                        while ($t = $type->fetch()) {
                            $selectedType = ($edit && $edit['id_type_credit'] == $t['id_type_credit']) ? 'selected' : '';
                        ?>
                            <option value="<?= $t['id_type_credit'] ?>" <?= $selectedType ?>>
                                <?= htmlspecialchars($t['libelle']) ?>
                            </option>
                        <?php } ?>
                    </select>
                    </div>

                    <!-- DATE -->
                    <div class="col-md-4">
                    <label>Date</label>
                    <input type="date" name="datecredit" class="form-control" required
                        value="<?= $edit ? $edit['date_credit'] : '' ?>">
                    </div>

                    <!-- MONTANT -->
                    <div class="col-md-4">
                    <label>Montant</label>
                    <input type="text" name="montantcredit"
                        class="form-control" placeholder="Ex: 6000 CDF" required
                        value="<?= $edit ? $edit['montant_credit'] : '' ?>">
                    </div>

                    <!-- STATUT -->
                    <div class="col-md-4">
                    <label>Statut</label>
                    <select name="statut" class="form-control">
                        <option value="En cours" <?= $edit && $edit['statut']=='En cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="Soldé" <?= $edit && $edit['statut']=='Soldé' ? 'selected' : '' ?>>Soldé</option>
                    </select>
                    </div>

                    <!-- LIBELE -->
                    <div class="col-md-6">
                    <label>Libele</label>
                    <input type="text" name="libele" class="form-control" required
                        value="<?= $edit ? htmlspecialchars($edit['libele']) : '' ?>">
                    </div>

                    <div class="col-md-4"><br>
                    <input type="submit" name="save" class="btn btn-primary" value="<?= $edit ? 'Modifier' : 'Enregistrer' ?>">
                    </div>

                    </form>
                </div>
                    </div>

                    <section class="section">
                    <div class="row">
                    <div class="col-lg-12">

                    <div class="card">
                    <div class="card-body">
                    <h5 class="card-title">Liste crédits</h5>
                    <table class="table table-hover">
                    <thead class="table-primary">
                    <tr>
                            <tr>
                                <th>#</th>
                                <th>Membre</th>
                                <th>Compte</th>
                                <th>Crédit</th>
                                <th>Montant</th>
                                <th>Date</th>
                                <th>Etat de penalité</th>
                                <th>Action</th>
                            </tr>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; while($row = $query->fetch(PDO::FETCH_ASSOC)) { ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['nom_membre']) ?></td>
                                <td><?= htmlspecialchars($row['numero_compte']) ?></td>
                                <td><?= htmlspecialchars($row['type_credit']) ?></td>
                                <td><?= number_format($row['montant_credit']) ?></td>
                                <td><?= $row['date_credit'] ?></td>
                                <td><?= $row['penalite_active'] ? 'Active' : 'Arrêtée' ?></td>

                                
                    <td>
                    <a href="credit.php?edit_id=<?= $row['id_credit'] ?>" class="btn btn-sm btn-outline-success">Modifier</a>
                    <a href="traitement/credit.php?delete_id=<?= $row['id_credit'] ?>" class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Voulez-vous vraiment supprimer ce crédit ?');">
                    supprimer
                    </a>
                    <a href="" class="btn btn-sm btn-outline-primary">Imprimer</a>
                    
                    <!-- BTN ACTIVATION ET DESACTIVATION CREDIT -->
                    <?php if ($row['statut'] == 'En cours'): ?>
                        <a href="traitement/desactiver_credit.php?id=<?= $row['id_credit'] ?>"
                        class="btn btn-warning btn-sm px-2"
                        onclick="return confirm('Désactiver ce crédit ?')">
                            Désactiver
                        </a>
                    <?php else: ?>
                        <a href="traitement/reactiver_credit.php?id=<?= $row['id_credit'] ?>"
                        class="btn btn-success btn-sm px-2"
                        onclick="return confirm('Réactiver ce crédit ?')">
                            Réactiver
                        </a>
                    <?php endif; ?>

                    </td>
                    </tr>
                    <?php } ?>
                    </tbody>
                    </table>

                    </div>
                    </div>

                    </div>
            </div>
        </section>

    </main>

    <!-- ==========================
        JAVASCRIPT AJAX
        ========================== -->
    <script>
    document.getElementById('compte').addEventListener('change', function () {
        const idCompte = this.value;
        const champNom = document.getElementById('nom_membre');
        champNom.value = '';

        if (!idCompte) return;

        fetch('?ajax=1&id_compte=' + idCompte)
            .then(response => response.json())
            .then(data => {
                if (data && data.nom_membre) {
                    champNom.value = data.nom_membre;
                }
            })
            .catch(error => console.error(error));
    });
    </script>

    <?php include "menu/lien.php"; ?>
    </body>
</html>
