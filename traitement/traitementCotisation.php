<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("../connexion/connexion.php");

/* ==========================
   ENREGISTREMENT COTISATION
========================== */
if (isset($_POST['action']) && $_POST['action'] === 'save') {

    $id_compte = intval($_POST['id_compte']);
    $montant   = floatval($_POST['montant']);

    $semaine = date('W');
    $annee   = date('Y');
    $date    = date('Y-m-d');

    /* ==========================
       ANTI-FRAUDE
       (1 cotisation / semaine)
    ========================== */
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM cotisation
        WHERE id_compte = ?
        AND semaine = ?
        AND annee = ?
    ");
    $check->execute([$id_compte, $semaine, $annee]);

    if ($check->fetchColumn() > 0) {
        $_SESSION['messageError'] = "❌ Cotisation déjà payée pour cette semaine.";
        header("Location: ../cotisation.php");
        exit;
    }

    /* ==========================
       INSERTION COTISATION
    ========================== */
    $stmt = $pdo->prepare("
        INSERT INTO cotisation
        (id_compte, libele, montant, date_cotisation, semaine, annee)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $id_compte,
        "Cotisation semaine $semaine",
        $montant,
        $date,
        $semaine,
        $annee
    ]);

    if ($result) {
        $_SESSION['message'] = "✅ Cotisation enregistrée avec succès.";
    } else {
        $_SESSION['messageError'] = "❌ Erreur lors de l'enregistrement.";
    }

    header("Location: ../cotisation.php");
    exit;
}

/* ==========================
   SUPPRESSION COTISATION
========================== */
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {

    $id_cotisation = intval($_GET['id']);

    try {
        $pdo->beginTransaction();

        $pdo->prepare("
            DELETE FROM cotisation
            WHERE id_cotisation = ?
        ")->execute([$id_cotisation]);

        $pdo->commit();

        $_SESSION['message'] = "Cotisation supprimée avec succès.";

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['messageError'] = "Suppression impossible.";
    }

    header("Location: ../cotisation.php");
    exit;
}

/* ==========================
   MODIFICATION COTISATION
========================== */
if (isset($_POST['action']) && $_POST['action'] === 'modifier') {

    $id_cotisation = intval($_POST['id_cotisation']);
    $montant       = floatval($_POST['montant']);
    $libele        = trim($_POST['libele']);

    /* ==========================
       VÉRIFICATION
    ========================== */
    if ($montant <= 0) {
        ?>
        <script>
            alert("❌ Le montant doit être supérieur à zéro.");
            window.history.back();
        </script>
        <?php
        exit;
    }

    $sql = "
        UPDATE cotisation SET
            montant = ?,
            libele = ?
        WHERE id_cotisation = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$montant, $libele, $id_cotisation]);

    ?>
    <script>
        alert("✅ Cotisation modifiée avec succès !");
        window.location.href = "../cotisation.php";
    </script>
    <?php
    exit;
}

/* ==========================
   GÉNÉRATION AUTOMATIQUE
   CRÉDIT ARRIÉRÉ
========================== */
if (isset($_GET['action']) && $_GET['action'] === 'verifier_arrieres') {

    $semaine = date('W');
    $annee   = date('Y');
    $date    = date('Y-m-d');

    $comptes = $pdo->query("SELECT id_compte FROM compte")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($comptes as $c) {

        $check = $pdo->prepare("
            SELECT COUNT(*) FROM cotisation
            WHERE id_compte = ?
            AND semaine = ?
            AND annee = ?
        ");
        $check->execute([$c['id_compte'], $semaine, $annee]);

        if ($check->fetchColumn() == 0) {

            $type = $pdo->query("
                SELECT id_type_credit
                FROM type_credit
                WHERE libelle = 'Arriéré'
                LIMIT 1
            ")->fetch();

            if ($type) {
                $pdo->prepare("
                    INSERT INTO credit
                    (id_compte, id_type_credit, montant_credit, date_credit, date_echeance, libele)
                    VALUES (?, ?, ?, ?, ?, ?)
                ")->execute([
                    $c['id_compte'],
                    $type['id_type_credit'],
                    0,
                    $date,
                    date('Y-m-d', strtotime('+7 days')),
                    'Arriéré de cotisation'
                ]);
            }
        }
    }

    $_SESSION['message'] = "Arriérés vérifiés avec succès.";
    header("Location: ../cotisation.php");
    exit;
}
