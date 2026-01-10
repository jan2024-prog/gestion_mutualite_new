<?php
require_once("connexion/connexion.php");

header('Content-Type: application/json');

$id_compte = $_GET['id_compte'] ?? null;
if (!$id_compte) {
    echo json_encode(null);
    exit;
}

$sql = $pdo->prepare("
    SELECT tmembre.nom AS nom_membre
    FROM tcompte
    INNER JOIN tmembre ON tmembre.id_membre = tcompte.id_membre
    WHERE tcompte.id_compte = ?
");
$sql->execute([$id_compte]);

echo json_encode($sql->fetch(PDO::FETCH_ASSOC));
