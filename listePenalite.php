<?php
require_once("connexion/connexion.php");
$query = $pdo->prepare("
    SELECT 
        c.id_credit,
        m.noms AS nom_membre,
        cp.numero_compte,
        c.montant_credit,
        IFNULL(SUM(r.montant_rembourse), 0) AS total_rembourse,
        (c.montant_credit - IFNULL(SUM(r.montant_rembourse), 0)) AS reste,
        ((c.montant_credit - IFNULL(SUM(r.montant_rembourse), 0)) * 0.10) AS penalite,
        ((c.montant_credit - IFNULL(SUM(r.montant_rembourse), 0)) * 1.10) AS total_a_payer,
        c.date_credit
    FROM Tcredit c
    LEFT JOIN Tremboursement r ON r.id_credit = c.id_credit
    INNER JOIN Tmembre m ON m.id_membre = c.id_membre
    INNER JOIN Tcompte cp ON cp.id_membre = m.id_membre
    WHERE c.statut = 'En cours'
    GROUP BY c.id_credit
    HAVING reste > 0
");
$query->execute();
?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Liste des crédits avec pénalités</h5>

        <table class="table table-bordered table-hover">
            <thead class="table-danger">
                <tr>
                    <th>#</th>
                    <th>Membre</th>
                    <th>Compte</th>
                    <th>Crédit</th>
                    <th>Remboursé</th>
                    <th>Reste</th>
                    <th>Pénalité (10%)</th>
                    <th>Total à payer</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $query->fetch(PDO::FETCH_ASSOC)) { ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['nom_membre']) ?></td>
                    <td><?= htmlspecialchars($row['numero_compte']) ?></td>
                    <td><?= number_format($row['montant_credit'], 2) ?></td>
                    <td><?= number_format($row['total_rembourse'], 2) ?></td>
                    <td><?= number_format($row['reste'], 2) ?></td>
                    <td class="text-danger"><?= number_format($row['penalite'], 2) ?></td>
                    <td class="fw-bold text-primary"><?= number_format($row['total_a_payer'], 2) ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
