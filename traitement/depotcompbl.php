<?php
require_once("../connexion/connexion.php");

/* =========================
   FONCTION : TOTAL DÉPOSÉ
   ========================= */
function getTotalDepose(PDO $pdo, int $id_compte, string $devise): float
{
    $req = $pdo->prepare("
        SELECT IFNULL(SUM(montant),0) AS total
        FROM tdepot_compte_bloque
        WHERE id_compte_bloque = ?
          AND devise = ?
    ");
    $req->execute([$id_compte, $devise]);
    return (float) $req->fetchColumn();
}

/* =========================
   SUPPRESSION D'UN DÉPÔT
   ========================= */
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {

    $id_depot = (int) ($_GET['id_depot'] ?? 0);

    if ($id_depot > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM tdepot_compte_bloque WHERE id_depot = ?");
            $stmt->execute([$id_depot]);
            $message = "Dépôt supprimé avec succès";
        } catch (PDOException $e) {
            $message = "Erreur suppression : " . $e->getMessage();
        }
    } else {
        $message = "Identifiant invalide";
    }

    header("Location: ../depot.php?message=" . urlencode($message));
    exit();
}

/* =========================
   MODIFICATION D'UN DÉPÔT
   ========================= */
if (isset($_POST['action']) && $_POST['action'] === 'modifier_depot') {

    $id_depot      = (int) $_POST['id_depot'];
    $id_compte     = (int) $_POST['numero_compte'];
    $montant       = $_POST['montant'];
    $libelle       = $_POST['libelle'];
    $modepaie      = $_POST['modepaie'];
    $dateDepot     = $_POST['dateCotise'];
    $devise        = $_POST['devise'];

    if ($id_depot && $id_compte && $montant && $libelle && $modepaie && $dateDepot && $devise) {

        try {
            $stmt = $pdo->prepare("
                UPDATE tdepot_compte_bloque
                SET id_compte_bloque = ?,
                    montant = ?,
                    date_depot = ?,
                    mode_paiement = ?,
                    libele = ?,
                    devise = ?
                WHERE id_depot = ?
            ");
            $stmt->execute([
                $id_compte,
                $montant,
                $dateDepot,
                $modepaie,
                $libelle,
                $devise,
                $id_depot
            ]);

            $total = getTotalDepose($pdo, $id_compte, $devise);
            $message = "Dépôt modifié | Total déposé : "
                . number_format($total, 2, ',', ' ') . " " . $devise;

        } catch (PDOException $e) {
            $message = "Erreur SQL : " . $e->getMessage();
        }
    } else {
        $message = "Tous les champs sont obligatoires";
    }

    header("Location: ../depot.php?message=" . urlencode($message));
    exit();
}

/* =========================
   AJOUT D'UN DÉPÔT
   ========================= */
if (isset($_POST['save'])) {

    $id_compte  = (int) $_POST['numero_compte'];
    $montant    = $_POST['montant'];
    $libelle    = $_POST['libelle'];
    $modepaie   = $_POST['modepaie'];
    $dateDepot  = $_POST['dateCotise'];
    $devise     = $_POST['devise'];

    if ($id_compte && $montant && $libelle && $modepaie && $dateDepot && $devise) {

        try {
            $insert = $pdo->prepare("
                INSERT INTO tdepot_compte_bloque
                (id_compte_bloque, montant, date_depot, mode_paiement, libele, devise)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $id_compte,
                $montant,
                $dateDepot,
                $modepaie,
                $libelle,
                $devise
            ]);

            $total = getTotalDepose($pdo, $id_compte, $devise);

            $message = "Dépôt enregistré avec succès | Vous avez atteint un montant de : "
                . number_format($total, 2, ',', ' ') . " " . $devise. " courage cher membre et merci pour notre confiance";

        } catch (PDOException $e) {
            $message = "Erreur SQL : " . $e->getMessage();
        }
    } else {
        $message = "Veuillez remplir tous les champs";
    }

    header("Location: ../depot.php?message=" .$message);
    exit();
}
