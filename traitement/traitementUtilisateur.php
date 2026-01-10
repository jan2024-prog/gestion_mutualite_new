<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("../connexion/connexion.php");

/* ==========================
   ENREGISTREMENT UTILISATEUR
========================== */
if (isset($_POST['action']) && $_POST['action'] === 'save') {

    $id_entreprise = !empty($_POST['id_entreprise']) ? $_POST['id_entreprise'] : null;
    $noms     = trim($_POST['noms']);
    $username = trim($_POST['username']);
    $password = $_POST['mot_de_passe'];
    $role     = $_POST['role'];
    $telephone= trim($_POST['telephone']);
    $email    = trim($_POST['email']);
    $statut   = $_POST['statut'] ?? 'Actif';
    $date_creation = date('Y-m-d');

    /* ==========================
       VÉRIFICATIONS
    ========================== */
    if (empty($noms) || empty($username) || empty($password)) {
        $_SESSION['messageError'] = "Veuillez remplir tous les champs obligatoires";
        header("Location: ../utilisateur.php");
        exit;
    }

    // Hash mot de passe
    $mot_de_passe = password_hash($password, PASSWORD_DEFAULT);

    /* ==========================
       PHOTO
    ========================== */
    $photo = null;
    if (!empty($_FILES['photo']['name'])) {
        $photo = time() . "_" . $_FILES['photo']['name'];
        move_uploaded_file($_FILES['photo']['tmp_name'], "../photos/".$photo);
    }

    /* ==========================
       INSERTION
    ========================== */
    try {
        $stmt = $pdo->prepare("
            INSERT INTO utilisateur
            (id_entreprise, noms, username, mot_de_passe, role, telephone, email, photo, statut, date_creation)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $id_entreprise,
            $noms,
            $username,
            $mot_de_passe,
            $role,
            $telephone,
            $email,
            $photo,
            $statut,
            $date_creation
        ]);

        $_SESSION['message'] = "Utilisateur enregistré avec succès";
    } catch (PDOException $e) {
        $_SESSION['messageError'] = "Erreur : nom d'utilisateur déjà utilisé";
    }

    header("Location: ../utilisateur.php");
    exit;
}

/* ==========================
   SUPPRESSION UTILISATEUR
========================== */
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {

    $id = intval($_GET['id']);

    try {
        $pdo->prepare("DELETE FROM utilisateur WHERE id_utilisateur=?")
            ->execute([$id]);

        $_SESSION['message'] = "Utilisateur supprimé avec succès";
    } catch (Exception $e) {
        $_SESSION['messageError'] = "Suppression impossible";
    }

    header("Location: ../utilisateur.php");
    exit;
}

/* ==========================
   MODIFICATION UTILISATEUR
========================== */
if (isset($_POST['action']) && $_POST['action'] === 'modifier') {

    $id_utilisateur = intval($_POST['id_utilisateur']);
    $id_entreprise  = !empty($_POST['id_entreprise']) ? $_POST['id_entreprise'] : null;
    $noms     = trim($_POST['noms']);
    $username = trim($_POST['username']);
    $role     = $_POST['role'];
    $telephone= trim($_POST['telephone']);
    $email    = trim($_POST['email']);
    $statut   = $_POST['statut'];

    if (empty($noms) || empty($username)) {
        ?>
        <script>
            alert("❌ Champs obligatoires manquants");
            window.history.back();
        </script>
        <?php
        exit;
    }

    /* ==========================
       PHOTO
    ========================== */
    $sqlPhoto = "";
    $paramsPhoto = [];

    if (!empty($_FILES['photo']['name'])) {
        $photo = time() . "_" . $_FILES['photo']['name'];
        move_uploaded_file($_FILES['photo']['tmp_name'], "../photos/".$photo);
        $sqlPhoto = ", photo=?";
        $paramsPhoto[] = $photo;
    }

    /* ==========================
       MOT DE PASSE
    ========================== */
    $sqlPass = "";
    $paramsPass = [];

    if (!empty($_POST['mot_de_passe'])) {
        $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
        $sqlPass = ", mot_de_passe=?";
        $paramsPass[] = $mot_de_passe;
    }

    /* ==========================
       UPDATE
    ========================== */
    $sql = "
        UPDATE utilisateur SET
            id_entreprise=?,
            noms=?,
            username=?,
            role=?,
            telephone=?,
            email=?,
            statut=?
            $sqlPass
            $sqlPhoto
        WHERE id_utilisateur=?
    ";

    $params = array_merge([
        $id_entreprise,
        $noms,
        $username,
        $role,
        $telephone,
        $email,
        $statut
    ], $paramsPass, $paramsPhoto, [$id_utilisateur]);

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        ?>
        <script>
            alert("✅ Utilisateur modifié avec succès");
            window.location.href = "../utilisateur.php";
        </script>
        <?php
        exit;

    } catch (PDOException $e) {
        ?>
        <script>
            alert("❌ Erreur lors de la modification");
            window.history.back();
        </script>
        <?php
        exit;
    }
}
