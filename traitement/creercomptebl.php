<?php
require_once("../connexion/connexion.php");

/* ==========================
   SUPPRESSION LOGIQUE D’UN MEMBRE
   ========================== */
if (isset($_GET['action'], $_GET['id_membre']) && $_GET['action'] === 'supprimer') {

    try {
        $pdo->beginTransaction();

        $id = (int) $_GET['id_membre'];

        $verif = $pdo->prepare("SELECT statut FROM tmembrebl WHERE id_membre=?");
        $verif->execute([$id]);
        $membre = $verif->fetch(PDO::FETCH_ASSOC);

        if (!$membre) {
            throw new Exception("Membre introuvable");
        }

        if ($membre['statut'] === 'Supprimé') {
            throw new Exception("Le membre est déjà supprimé");
        }

        // Suppression des dépôts liés
        $deleteDepots = $pdo->prepare("
            DELETE d FROM tdepot_compte_bloque d
            INNER JOIN tcompte_bloque c ON d.id_compte_bloque = c.id_compte_bloque
            WHERE c.id_membre=?
        ");
        $deleteDepots->execute([$id]);

        // Suppression logique
        $update = $pdo->prepare("UPDATE tmembrebl SET statut='Supprimé' WHERE id_membre=?");
        $update->execute([$id]);

        $pdo->commit();
        $message = "Membre supprimé logiquement avec succès";

    } catch (Throwable $e) {
        $pdo->rollBack();
        $message = "Erreur : " . $e->getMessage();
    }

    header("Location: ../compteblocque.php?message=" . urlencode($message));
    exit();
}

/* ==========================
   CHARGEMENT POUR MODIFICATION
   ========================== */
$editMembre = null;
if (isset($_GET['id_membre']) && !isset($_GET['action'])) {
    $stmt = $pdo->prepare("SELECT * FROM tmembrebl WHERE id_membre=?");
    $stmt->execute([(int) $_GET['id_membre']]);
    $editMembre = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ==========================
   AJOUT / MODIFICATION
   ========================== */
if (isset($_POST['save'])) {

    try {
        $id_membre  = $_POST['id_membre'] ?? null;
        $nom        = trim($_POST['noms']);
        $genre      = trim($_POST['genre']);
        $datenaiss  = $_POST['datenaiss'];
        $telephone  = trim($_POST['telephone']);
        $etatcivil  = trim($_POST['Etatcivil']);
        $adresse    = trim($_POST['adresse']);
        $datedeblo  = $_POST['datedeblo'];

        if (!$nom || !$genre || !$datenaiss || !$telephone || !$etatcivil) {
            throw new Exception("Tous les champs obligatoires doivent être remplis");
        }

        /* ========= GENERATION NUMERO ========= */
        if (!$id_membre) {
            $annee = date('Y');
            $prefix = "CBL-$annee-";

            $req = $pdo->prepare("
                SELECT numero_compte_bloque 
                FROM tmembrebl 
                WHERE numero_compte_bloque LIKE ? 
                ORDER BY numero_compte_bloque DESC LIMIT 1
            ");
            $req->execute([$prefix . '%']);
            $last = $req->fetchColumn();

            $ordre = $last ? (int) substr($last, -3) + 1 : 1;
            $numero_compte = $prefix . str_pad($ordre, 3, '0', STR_PAD_LEFT);
        }

        if ($id_membre) {
            /* ===== MODIFICATION ===== */
            $update = $pdo->prepare("
                UPDATE tmembrebl 
                SET noms=?, sexe=?, telephone=?, datenaissance=?, etatcivil=?, date_deblocage=?, adresse=?
                WHERE id_membre=?
            ");
            $update->execute([
                $nom, $genre, $telephone, $datenaiss,
                $etatcivil, $datedeblo, $adresse, $id_membre
            ]);

            $message = "Modification réussie";

        } else {
            /* ===== AJOUT ===== */
            $verif = $pdo->prepare("
                SELECT id_membre FROM tmembrebl
                WHERE noms=? AND sexe=? AND telephone=? AND datenaissance=? AND etatcivil=?
            ");
            $verif->execute([$nom, $genre, $telephone, $datenaiss, $etatcivil]);

            if ($verif->fetch()) {
                throw new Exception("Ce membre existe déjà");
            }

            $insert = $pdo->prepare("
                INSERT INTO tmembrebl
                (noms, numero_compte_bloque, sexe, telephone, datenaissance, etatcivil, date_deblocage, adresse, statut)
                VALUES (?,?,?,?,?,?,?,?, 'Actif')
            ");
            $insert->execute([
                $nom, $numero_compte, $genre, $telephone,
                $datenaiss, $etatcivil, $datedeblo, $adresse
            ]);

            $message = "Enregistrement effectué avec succès";
        }

    } catch (Throwable $e) {
        $message = "Erreur : " . $e->getMessage();
    }

    header("Location: ../compteblocque.php?message=" . urlencode($message));
    exit();
}
?>
