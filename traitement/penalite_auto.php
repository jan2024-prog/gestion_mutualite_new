<?php
if (!isset($pdo)) {
    require_once(__DIR__ . "/../connexion/connexion.php");
}

$today = date('Y-m-d');

$sql = "
SELECT 
    c.id_credit,
    c.montant_credit,
    c.date_echeance,
    c.last_penalite_date,
    tc.taux_interet,
    IFNULL(SUM(r.montant_rembourse),0) AS total_rembourse
FROM credit c
JOIN type_credit tc ON c.id_type_credit = tc.id_type_credit
LEFT JOIN remboursement r ON r.id_credit = c.id_credit
WHERE 
    c.statut = 'En cours'
    AND c.penalite_active = 1
    AND c.date_echeance < :today

GROUP BY c.id_credit
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['today' => $today]);
$credits = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($credits as $credit) {

    // ⚠️ Bloque le recalcul le même jour
    if ($credit['last_penalite_date'] === $today) {
        continue;
    }

    $reste = $credit['montant_credit'] - $credit['total_rembourse'];
    if ($reste <= 0) {
        continue;
    }

    $penalite = $reste * ($credit['taux_interet'] / 100);

    try {
        $pdo->beginTransaction();

        // Historique
        $stmt = $pdo->prepare("
            INSERT INTO historique_penalite
            (id_credit, montant_penalite, date_penalite)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $credit['id_credit'],
            $penalite,
            $today
        ]);

        // Mise à jour du crédit
        $stmt = $pdo->prepare("
            UPDATE credit
            SET montant_credit = montant_credit + ?,
                last_penalite_date = ?
            WHERE id_credit = ?
        ");
        $stmt->execute([
            $penalite,
            $today,
            $credit['id_credit']
        ]);

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
    }
}
