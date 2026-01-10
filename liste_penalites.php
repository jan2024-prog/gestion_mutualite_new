<?php
session_start();
require_once("config/connexion.php"); // connexion à la DB
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des pénalités - Tontine</title>
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
                        <h4 class="fw-bold text-primary text-center">
                            Liste complète des pénalités appliquées
                        </h4>
                    </div>
                </div>

                <!-- CARD -->
                <div class="card shadow-sm">

                    <div class="card-header bg-primary text-white">
                        Historique des pénalités
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
                                        <th>Crédit</th>
                                    </tr>
                                </thead>

                                <tbody>
                                <?php
                                $sql = "
                                    SELECT 
                                        m.noms,
                                        c.numero_compte,
                                        p.montant_penalite,
                                        p.date_penalite,
                                        cr.montant_credit AS montant_credit
                                    FROM Thistorique_penalite p
                                    JOIN Tcredit cr ON p.id_credit = cr.id_credit
                                    JOIN Tcompte c ON cr.id_membre = c.id_membre
                                    JOIN Tmembre m ON c.id_membre = m.id_membre
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
                                        <td class="text-end">
                                            <?= number_format($row['montant_credit'], 0, ',', ' ') ?> FC
                                        </td>
                                    </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            Aucun historique de pénalités disponible
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>

                    <!-- FOOTER -->
                    <div class="card-footer text-muted text-center">
                        Système de gestion des crédits – Liste des pénalités
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
