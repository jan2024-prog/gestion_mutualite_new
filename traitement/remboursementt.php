<?php
require_once("../connexion/connexion.php");

/* ============================
   SUPPRESSION D'UN REMBOURSEMENT
   ============================ */
if (isset($_GET['delete_id'])) {

    $id_remboursement = (int) $_GET['delete_id'];

    try {
        $del = $pdo->prepare("DELETE FROM Tremboursement WHERE id_remboursement = ?");
        $del->execute([$id_remboursement]);

        $message = "Remboursement supprimé avec succès.";

    } catch (PDOException $e) {
        $message = "Erreur SQL : " . $e->getMessage();
    }

    header("Location: ../ramboursement.php?message=" . urlencode($message));
    exit();
}

/* ============================
   AJOUT D'UN REMBOURSEMENT
   ============================ */
if (isset($_POST['save'])) {

    $id_credit = (int) $_POST['credit'];
    $montant   = (float) $_POST['montant'];
    $devise    = $_POST['devise'];
    $daterem   = $_POST['daterem'];
    $libele    = $_POST['libele'];

    if ($id_credit && $montant > 0 && $devise && $daterem && $libele) {

        try {
            /* ============================
               1. RÉCUPÉRER LE CRÉDIT
               ============================ */
            $creditReq = $pdo->prepare("
                SELECT montant_credit, devise
                FROM credit
                WHERE id_credit = ?
            ");
            $creditReq->execute([$id_credit]);
            $credit = $creditReq->fetch(PDO::FETCH_ASSOC);

            if (!$credit) {
                header("Location: ../ramboursement.php?error=Crédit introuvable");
                exit();
            }

            /* ============================
               2. VÉRIFIER LA DEVISE
               ============================ */
            if ($credit['devise'] !== $devise) {
                header("Location: ../ramboursement.php?error=La devise du remboursement doit être " . $credit['devise']);
                exit();
            }

            /* ============================
               3. TOTAL DÉJÀ REMBOURSÉ
               ============================ */
            $totalReq = $pdo->prepare("
                SELECT IFNULL(SUM(montant_rembourse),0) AS total_rembourse
                FROM remboursement
                WHERE id_credit = ?
                  AND devise = ?
            ");
            $totalReq->execute([$id_credit, $devise]);
            $total = $totalReq->fetch(PDO::FETCH_ASSOC)['total_rembourse'];

            $reste = $credit['montant_credit'] - $total;

            /* ============================
               4. CONTRÔLES DE MONTANT
               ============================ */
            if ($montant > $reste) {
                header("Location: ../ramboursement.php?error=Montant supérieur au reste à rembourser (" . number_format($reste,2,',',' ') . " " . $devise . ")");
                exit();
            }

            /* ============================
               5. INSERTION
               ============================ */
            $insert = $pdo->prepare("
                INSERT INTO remboursement
                (id_credit, montant_rembourse, devise, date_remboursement, libele)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $id_credit,
                $montant,
                $devise,
                $daterem,
                $libele
            ]);

            /* ============================
               6. ALERTE SI CRÉDIT SOLDÉ
               ============================ */
            if ($montant == $reste) {
                $message = "Remboursement effectué. ⚠️ Crédit totalement remboursé.";
            } else {
                $message = "Remboursement enregistré avec succès. Reste à payer : " .
                    number_format($reste - $montant, 2, ',', ' ') . " " . $devise;
            }

        } catch (PDOException $e) {
            $message = "Erreur SQL : " . $e->getMessage();
        }

    } else {
        $message = "Tous les champs sont obligatoires.";
    }

    header("Location: ../ramboursement.php?message=" . urlencode($message));
    exit();
}

/* ============================
   MODIFICATION D'UN REMBOURSEMENT
   ============================ */
if (isset($_POST['update'])) {

    $id_remboursement = (int) $_POST['id_remboursement'];
    $id_credit = (int) $_POST['credit'];
    $montant   = (float) $_POST['montant'];
    $devise    = $_POST['devise'];
    $daterem   = $_POST['daterem'];
    $libele    = $_POST['libele'];

    try {
        /* récupération du remboursement existant */
        $oldReq = $pdo->prepare("
            SELECT montant_rembourse
            FROM remboursement
            WHERE id_remboursement = ?
        ");
        $oldReq->execute([$id_remboursement]);
        $old = $oldReq->fetch(PDO::FETCH_ASSOC);

        if (!$old) {
            header("Location: ../ramboursement.php?error=Remboursement introuvable");
            exit();
        }

        /* total sans l'ancien remboursement */
        $sumReq = $pdo->prepare("
            SELECT IFNULL(SUM(montant_rembourse),0) AS total
            FROM remboursement
            WHERE id_credit = ?
              AND devise = ?
              AND id_remboursement != ?
        ");
        $sumReq->execute([$id_credit, $devise, $id_remboursement]);
        $total = $sumReq->fetch(PDO::FETCH_ASSOC)['total'];

        $creditReq = $pdo->prepare("SELECT montant_credit FROM Tcredit WHERE id_credit = ?");
        $creditReq->execute([$id_credit]);
        $credit = $creditReq->fetch(PDO::FETCH_ASSOC);

        if (($total + $montant) > $credit['montant_credit']) {
            header("Location: ../ramboursement.php?error=Modification impossible : dépassement du montant du crédit");
            exit();
        }

        $update = $pdo->prepare("
            UPDATE remboursement
            SET montant_rembourse = ?, devise = ?, date_remboursement = ?, libele = ?
            WHERE id_remboursement = ?
        ");
        $update->execute([$montant, $devise, $daterem, $libele, $id_remboursement]);

        $message = "Remboursement modifié avec succès.";

    } catch (PDOException $e) {
        $message = "Erreur SQL : " . $e->getMessage();
    }

    header("Location: ../ramboursement.php?message=" . urlencode($message));
    exit();
}
