<?php
require_once("connexion/connexion.php");
// -------------------------
// Récupération du retrait à modifier si edit_id est présent
// -------------------------
$editRetrait = null;
if (isset($_GET['edit_id'])) {
    $id_edit = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("
        SELECT r.*, c.numero_compte 
        FROM retrait r
        JOIN compte c ON c.id_compte = r.id_compte
        WHERE r.id_retrait = ?
    ");
    $stmt->execute([$id_edit]);
    $editRetrait = $stmt->fetch(PDO::FETCH_ASSOC);
}
// -------------------------
// AJAX pour récupérer le nom du membre via id_compte
// -------------------------
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
   
</head>
<body class="theme-blush">

<main id="main" class="main">

    <div class="pagetitle">
        <h1><center>Completer les informations de retrait compte bloquer</center></h1>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title"><?= $editRetrait ? "Modifier le retrait" : "Nouveau retrait" ?></h5>

            <form class="row g-3" action="traitement/retrait.php" method="POST">
                <input type="hidden" name="id_retrait" value="<?= $editRetrait['id_retrait'] ?? '' ?>">

                <div class="col-lg-4 col-md-6">
                    <label>Compte</label>
                    <select name="compte" id="compte" class="form-control" required>
                        <option value="">-- Choisir le compte --</option>
                        <?php
                        $q = $pdo->query("SELECT id_compte, numero_compte FROM compte");
                        while ($c = $q->fetch()) {
                            $selected = ($editRetrait && $editRetrait['id_compte'] == $c['id_compte']) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($c['id_compte']) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($c['numero_compte']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label>Nom du bénéficiaire</label>
                    <input type="text" name="nom" id="nom_membre" class="form-control" readonly required
                    value="<?= $editRetrait['nom'] ?? '' ?>">
                </div>

                <div class="col-md-4">
                    <label>Montant</label>
                    <input type="number" name="montant" class="form-control" required
                           value="<?= $editRetrait['montant'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                <label>Devise</label>
                <select name="devise" class="form-select">
                    <option value="Franc">Franc congolais</option>
                    <option value="Dollar">Dollars</option>
                </select>
                </div>

                <div class="col-lg-4 col-md-6">
                    <label>Date</label>
                    <input type="date" name="daterem" class="form-control" required
                           value="<?= $editRetrait['date_retrait'] ?? '' ?>">
                </div>

                <div class="col-md-4">
                    <label>Libellé</label>
                    <input type="text" name="motif" class="form-control" required
                           value="<?= $editRetrait['libelle'] ?? '' ?>">
                </div>

                <div class="col-md-4">
                    <input class="btn btn-primary" name="<?= $editRetrait ? 'update' : 'save' ?>" 
                           type="submit" value="<?= $editRetrait ? 'Mettre à jour' : 'Enregistrer' ?>">
                </div>
            </form>

            <div>
                <?php if (isset($_GET['message'])) { ?>
                    <div class="alert alert-info"><?= htmlspecialchars($_GET['message']) ?></div>
                <?php } ?>
            </div>

        </div>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Liste retrait</h5>

                        <table class="table table-hover align-middle">
                            <thead class="table-primary">
                            <tr>
                                <td>#</td>
                                <th>Date</th>
                                <th>Compte</th>
                                <th>Nom</th>
                                <th>Libelle</th>
                                <th>Montant</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $i = 1;
                            $sql = $pdo->query("
                                SELECT r.*, m.noms, c.numero_compte 
                                FROM retrait r
                                JOIN compte c ON c.id_compte = r.id_compte
                                JOIN membre m on c.id_membre = m.id_membre
                                ORDER BY r.date_retrait DESC
                            ");
                            while ($row = $sql->fetch()) {
                            ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($row['date_retrait']) ?></td>
                                    <td><?= htmlspecialchars($row['numero_compte']) ?></td>
                                    <td><?= htmlspecialchars($row['noms']) ?></td>
                                    <td><?= htmlspecialchars($row['libele']) ?></td>
                                    <td><?= htmlspecialchars($row['montant']) ?></td>
                                    <td>
                                        <a href="retrait.php?edit_id=<?= $row['id_retrait'] ?>"
                                        class="btn btn-sm btn-outline-success">Modifier</a>
                                        <a href="traitement/retrait.php?delete_id=<?= $row['id_retrait'] ?>"
                                        onclick="return confirm('Voulez-vous vraiment supprimer ce retrait ?');"
                                        class="btn btn-sm btn-outline-danger">Supprimer</a>
                                        <a href="bon_retrait.php?id_retrait=<?= $row['id_retrait'] ?>"
                                        target="_blank"
                                        class="btn btn-sm btn-outline-primary">Imprimer</a>
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

<script>
    // Récupération automatique du nom du membre lors du changement du compte
    document.getElementById('compte').addEventListener('change', function () {
        const idCompte = this.value;
        const champNom = document.getElementById('nom_membre');
        champNom.value = '';

        if (idCompte === '') return;

        fetch('?ajax=1&id_compte=' + idCompte)
            .then(res => res.json())
            .then(data => {
                if (data && data.nom_membre) {
                    champNom.value = data.nom_membre;
                }
            })
            .catch(err => console.error(err));
    });
</script>

<?php include "menu/lien.php"; ?>
</body>
</html>
