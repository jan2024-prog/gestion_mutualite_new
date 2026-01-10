<?php
session_start();
require_once __DIR__ . "/connexion/connexion.php"; // connexion à la DB
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des pénalités - Tontine</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
</head>

<body>

<div class="container-fluid mt-4">
    <div class="row">

        <!-- MENU GAUCHE -->
        <div class="col-lg-3 col-md-4">
            
        </div>

        <!-- CONTENU DROIT -->
        <div class="col-lg-9 col-md-8">
            <div class="container col-lg-10">

                <!-- TITRE -->
                <div class="row mb-3">
                    <div class="col">
                        <h4 class="fw-bold text-primary">
                            Historique des pénalités appliquées
                        </h4>
                    </div>
                </div>

                <!-- MESSAGE SESSION -->
                <?php if (!empty($_SESSION['message'])): ?>
                    <div class="alert alert-success">
                        <?= $_SESSION['message']; unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>

                <!-- CARD -->
                <div class="card shadow-sm">

                    <div class="card-header bg-primary text-white">
                        Liste des pénalités
                    </div>

                    <div class="card-body">

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm align-middle">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>#</th>
                                        <th>Membre</th>
                                        <th>Compte</th>
                                        <th>Montant pénalité</th>
                                        <th>Date pénalité</th>
                                    </tr>
                                </thead>

                                <tbody>
                                <?php
                                $sql = "
                                    SELECT 
                                        m.noms,
                                        cp.numero_compte,
                                        p.montant_penalite,
                                        p.date_penalite
                                    FROM Thistorique_penalite p
                                    JOIN Tcredit cr ON p.id_credit = cr.id_credit
                                    JOIN Tcompte cp ON cr.id_membre = cp.id_membre
                                    JOIN Tmembre m ON cp.id_membre = m.id_membre
                                    ORDER BY p.date_penalite DESC
                                ";

                                $stmt = $pdo->query($sql);
                                $i = 1;

                                if ($stmt->rowCount() > 0):
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($row['noms']) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($row['numero_compte']) ?></td>
                                        <td class="text-end text-danger fw-bold">
                                            <?= number_format($row['montant_penalite'], 0, ',', ' ') ?> FC
                                        </td>
                                        <td class="text-center">
                                            <?= date('d/m/Y', strtotime($row['date_penalite'])) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            Aucun historique de pénalité disponible
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>

                    <div class="card-footer text-center text-muted">
                        Système de gestion des crédits – Historique des pénalités automatiques
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
<?php include "menu/lien.php"; ?>
</html>
