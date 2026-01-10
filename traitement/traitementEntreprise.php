<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("../connexion/connexion.php");

/* ==========================
   ENREGISTREMENT ENTREPRISE
========================== */
if (isset($_POST['action']) && $_POST['action'] === 'save') {

    $nom           = trim($_POST['nom']);
    $adresse       = trim($_POST['adresse']);
    $telephone     = trim($_POST['telephone']);
    $email         = trim($_POST['email']);
    $date_creation = $_POST['date_creation'];

    /* ==========================
       GESTION DU LOGO
    ========================== */
    $logo = "";
    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] == 0) {
        $logo = time() . "_" . $_FILES['logo']['name'];
        move_uploaded_file($_FILES['logo']['tmp_name'], "../logos/".$logo);
    }

    /* ==========================
       INSERTION
    ========================== */
    $stmt = $pdo->prepare("
        INSERT INTO entreprise (nom, adresse, telephone, email, date_creation, logo)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $nom,
        $adresse,
        $telephone,
        $email,
        $date_creation,
        $logo
    ]);

    if ($result) {
        $_SESSION['message'] = "Entreprise enregistrée avec succès !";
    } else {
        $_SESSION['messageError'] = "Erreur lors de l'enregistrement de l’entreprise.";
    }

    header("Location: ../entreprise.php");
    exit;
}

/* ==========================
   SUPPRESSION ENTREPRISE
========================== */
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {

    $id_entreprise = intval($_GET['id']);

    try {
        $pdo->beginTransaction();

        // 👉 Si plus tard tu ajoutes des dépendances (membres, comptes…)
        // tu pourras les supprimer ici avant

        $pdo->prepare("
            DELETE FROM entreprise WHERE id_entreprise = ?
        ")->execute([$id_entreprise]);

        $pdo->commit();

        $_SESSION['message'] = "Entreprise supprimée avec succès.";

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['messageError'] = "Suppression impossible (dépendances existantes).";
    }

    header("Location: ../entreprise.php");
    exit;
}

/* ==========================
   MODIFICATION ENTREPRISE
========================== */
if (isset($_POST['action']) && $_POST['action'] === 'modifier') {

    $id_entreprise = intval($_POST['id_entreprise']);
    $nom           = trim($_POST['nom']);
    $adresse       = trim($_POST['adresse']);
    $telephone     = trim($_POST['telephone']);
    $email         = trim($_POST['email']);
    $date_creation = $_POST['date_creation'];

    /* ==========================
       VÉRIFICATION
    ========================== */
    if (empty($nom)) {
        ?>
        <script>
            alert("❌ Le nom de l’entreprise est obligatoire.");
            window.history.back();
        </script>
        <?php
        exit;
    }

    /* ==========================
       GESTION DU LOGO
    ========================== */
    if (!empty($_FILES['logo']['name'])) {

        $logo = time() . "_" . $_FILES['logo']['name'];
        move_uploaded_file($_FILES['logo']['tmp_name'], "../logos/".$logo);

        $sql = "
            UPDATE entreprise SET
                nom = ?,
                adresse = ?,
                telephone = ?,
                email = ?,
                date_creation = ?,
                logo = ?
            WHERE id_entreprise = ?
        ";

        $params = [
            $nom,
            $adresse,
            $telephone,
            $email,
            $date_creation,
            $logo,
            $id_entreprise
        ];

    } else {

        $sql = "
            UPDATE entreprise SET
                nom = ?,
                adresse = ?,
                telephone = ?,
                email = ?,
                date_creation = ?
            WHERE id_entreprise = ?
        ";

        $params = [
            $nom,
            $adresse,
            $telephone,
            $email,
            $date_creation,
            $id_entreprise
        ];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    ?>
    <script>
        alert("✅ Modification effectuée avec succès !");
        window.location.href = "../entreprise.php";
    </script>
    <?php
    exit;
}
