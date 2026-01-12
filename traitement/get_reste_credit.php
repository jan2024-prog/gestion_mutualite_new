<?php
require_once("../connexion/connexion.php");

if (!isset($_GET['id_credit'], $_GET['devise'])) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

$id_credit = (int) $_GET['id_credit'];
$devise    = $_GET['devise'];

// Récupérer le montant total du crédit
$creditReq = $pdo->prepare("SELECT montant_credit, devise FROM credit WHERE id_credit = ?");
$creditReq->execute([$id_credit]);
$credit = $creditReq->fetch(PDO::FETCH_ASSOC);

if (!$credit) {
    echo json_encode(['error' => 'Crédit introuvable']);
    exit;
}

// Vérifier la devise
if ($credit['devise'] !== $devise) {
    echo json_encode(['error' => 'La devise du crédit est ' . $credit['devise']]);
    exit;
}

// Calculer le total déjà remboursé
$totalReq = $pdo->prepare("SELECT IFNULL(SUM(montant_rembourse),0) AS total_rembourse FROM remboursement WHERE id_credit = ? AND devise = ?");
$totalReq->execute([$id_credit, $devise]);
$totalRembourse = $totalReq->fetch(PDO::FETCH_ASSOC)['total_rembourse'];

// Calculer le reste
$reste = $credit['montant_credit'] - $totalRembourse;
if ($reste < 0) $reste = 0;

// Retourner le JSON
echo json_encode(['reste' => $reste]);
