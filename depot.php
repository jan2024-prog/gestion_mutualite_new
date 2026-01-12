<?php
session_start();
require_once("connexion/connexion.php");

/* =========================
   CHARGEMENT POUR MODIFICATION
   ========================= */
$c = null;
if (isset($_GET['id_depot'])) {
    $id = (int) $_GET['id_depot'];

    $sql = "
        SELECT 
            d.id_depot,
            d.id_compte_bloque,
            d.montant,
            d.date_depot,
            d.mode_paiement,
            d.libele,
            c.numero_compte_bloque,
            m.noms
        FROM tdepot_compte_bloque d
        JOIN tcompte_bloque c ON d.id_compte_bloque = c.id_compte_bloque
        JOIN Tmembrebl m ON c.id_membre = m.id_membre
        WHERE d.id_depot = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Dépôt compte bloqué</title>
    </head>

    <body>

        <main id="main" class="main">

            <div class="pagetitle">
                <h1 class="text-center">Dépôt compte bloqué</h1>
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
                    <h5 class="card-title">Ajouter / Modifier un dépôt</h5>
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
                    <!-- MESSAGES -->
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

                        <form class="row g-3" method="post" action="traitement/depotcompbl.php">
                            <?php if ($c): ?>
                                <input type="hidden" name="action" value="modifier_depot">
                                <input type="hidden" name="id_depot" value="<?= $c['id_depot'] ?>">
                            <?php endif; ?>
                            <!-- NUMERO DE COMPTE -->
                            <div class="col-md-4">
                                <label class="form-label">Numéro de compte</label>
                                <select name="numero_compte" id="numero_compte"
                                        class="form-select"
                                        onchange="afficherNom()" required>

                                    <option value="">-- Choisir --</option>
                                    <?php
                                    $membres = $pdo->query("
                                        SELECT 
                                            m.noms,
                                            c.id_compte_bloque,
                                            c.numero_compte_bloque
                                        FROM Tmembrebl m
                                        JOIN tcompte_bloque c ON m.id_membre = c.id_membre
                                        ORDER BY m.noms
                                    ");

                                    while ($m = $membres->fetch()) {
                                        $selected = ($c && $m['id_compte_bloque'] == $c['id_compte_bloque']) ? "selected" : "";
                                        echo "
                                            <option value='{$m['id_compte_bloque']}'
                                                    data-noms='{$m['noms']}'
                                                    $selected>
                                                {$m['numero_compte_bloque']}
                                            </option>
                                        ";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- NOM -->
                            <div class="col-md-4">
                                <label class="form-label">Noms</label>
                                <input type="text" id="noms" class="form-control" readonly
                                    value="<?= $c['noms'] ?? '' ?>">
                            </div>
                            <!-- MONTANT -->
                            <div class="col-md-2">
                                <label class="form-label">Montant</label>
                                <input type="number" name="montant" class="form-control" required
                                    value="<?= $c['montant'] ?? '' ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Devise</label>
                                <select name="devise" class="form-select">
                                    <option value="Franc">Franc congolais</option>
                                    <option value="Dollar">Dollars</option>
                                </select>
                            </div>
                            <!-- LIBELLE -->
                            <div class="col-md-4">
                                <label class="form-label">Libellé</label>
                                <input type="text" name="libelle" class="form-control" required
                                    value="<?= $c['libele'] ?? 'depot compte bloque' ?>">
                            </div>

                            <!-- MODE PAIEMENT -->
                            <div class="col-md-2">
                                <label class="form-label">Mode paiement</label>
                                <select name="modepaie" class="form-select" required>
                                    <option value="">-- choisir --</option>
                                    <option value="cash" <?= ($c && $c['mode_paiement']=='cash')?'selected':'' ?>>cash</option>
                                    <option value="banque" <?= ($c && $c['mode_paiement']=='banque')?'selected':'' ?>>banque</option>
                                    <option value="airtel money" <?= ($c && $c['mode_paiement']=='airtel money')?'selected':'' ?>>airtel money</option>
                                    <option value="mpesa" <?= ($c && $c['mode_paiement']=='mpesa')?'selected':'' ?>>mpesa</option>
                                    <option value="orange money" <?= ($c && $c['mode_paiement']=='orange money')?'selected':'' ?>>orange money</option>
                                </select>
                            </div>

                            <!-- DATE -->
                            <div class="col-md-2">
                                <label class="form-label">Date</label>
                                <input type="datetime-local" name="dateCotise" class="form-control" required
                                    value="<?= $c ? date('Y-m-d\TH:i', strtotime($c['date_depot'])) : date('Y-m-d\TH:i') ?>">
                            </div>

                            <!-- BOUTON -->
                            <div class="col-4 text-center">
                                <input type="submit" name="save"
                                    class="btn btn-primary btn-sm px-4"
                                    value="<?= $c ? 'Modifier' : 'Enregistrer' ?>">
                            </div>

                        </form>
                    </div>
                    </div>

                    <!-- LISTE DES DEPOTS -->
                    <section class="section mt-4">
                    <div class="card">
                    <div class="card-body">

                    <h5 class="card-title">Liste des dépôts</h5>

                    <?php
                    $liste = $pdo->query("
                        SELECT 
                            d.id_depot,
                            d.montant,
                            d.date_depot,
                            d.libele,
                            c.numero_compte_bloque,
                            m.noms
                        FROM tdepot_compte_bloque d
                        JOIN tcompte_bloque c ON d.id_compte_bloque = c.id_compte_bloque
                        JOIN Tmembrebl m ON c.id_membre = m.id_membre
                        ORDER BY d.date_depot DESC
                    ");
                    ?>

                    <table class="table table-striped datatable">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Compte</th>
                        <th>Nom</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Libellé</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i=1; while ($row = $liste->fetch(PDO::FETCH_ASSOC)) { ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['numero_compte_bloque']) ?></td>
                        <td><?= htmlspecialchars($row['noms']) ?></td>
                        <td><?= htmlspecialchars($row['date_depot']) ?></td>
                        <td><?= number_format($row['montant'], 0, ',', ' ') ?></td>
                        <td><?= htmlspecialchars($row['libele']) ?></td>
                        <td>
                            <a href="depot.php?id_depot=<?= $row['id_depot'] ?>" class="btn btn-primary btn-sm">
                                Modifier
                            </a>
                            <a href="traitement/depotcompbl.php?action=supprimer&id_depot=<?= $row['id_depot'] ?>"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Voulez-vous supprimer ce dépôt ?');">
                            Supprimer
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                    </tbody>
                    </table>

                </div>
            </div>
            </section>

        </main>

        <script>
        function afficherNom() {
            let select = document.getElementById("numero_compte");
            let nom = select.options[select.selectedIndex]?.getAttribute("data-noms");
            document.getElementById("noms").value = nom ? nom : "";
        }

        <?php if ($c): ?>
        document.addEventListener("DOMContentLoaded", afficherNom);
        <?php endif; ?>
        </script>

        <?php include "menu/lien.php"; ?>
    </body>
</html>
