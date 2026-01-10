<?php
require_once("connexion/connexion.php");

if (!isset($_GET['id_retrait'])) die("Aucun retrait sélectionné.");
$id_retrait = (int)$_GET['id_retrait'];

// Récupérer le retrait et le compte
$sql = $pdo->prepare("
    SELECT r.*, c.numero_compte, c.solde, m.noms AS nom_membre, m.id_entreprise
    FROM Tretrait r
    JOIN Tcompte c ON c.id_compte = r.id_compte
    JOIN Tmembre m ON m.id_membre = c.id_membre
    WHERE r.id_retrait = ?
");
$sql->execute([$id_retrait]);
$retrait = $sql->fetch(PDO::FETCH_ASSOC);
if (!$retrait) die("Retrait introuvable.");

// Infos entreprise
$sqlEnt = $pdo->prepare("SELECT * FROM Tentreprise WHERE id_entreprise = ?");
$sqlEnt->execute([$retrait['id_entreprise']]);
$entreprise = $sqlEnt->fetch(PDO::FETCH_ASSOC);

// Calcul du solde restant
$solde_restant = $retrait['solde'] - $retrait['montant'];

// Date et heure actuelle pour le ticket
$datetime = date('d/m/Y H:i:s');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bon de Retrait</title>
<style>
body {
    font-family: monospace;
    width: 240px; /* Largeur imprimante 80mm */
    margin: 0;
    padding: 5px;
    font-size: 12px;
    background: #fff;
}
.center { text-align: center; }
.bold { font-weight: bold; }
.line { border-bottom: 1px dashed #000; margin: 4px 0; }
.table-ticket { width: 100%; border-collapse: collapse; }
td { padding: 2px 0; vertical-align: top; }
.right { text-align: right; }
.amount { font-weight: bold; font-size: 14px; }
.logo { max-width: 80px; display: block; margin: 0 auto 5px auto; }
.no-print { text-align: center; margin-top: 10px; }
@media print { .no-print { display: none; } }
</style>
</head>
<body>

<?php if(!empty($entreprise['logo'])): ?>
    <img src="<?= htmlspecialchars($entreprise['logo']) ?>" class="logo" alt="Logo">
<?php endif; ?>

<div class="center bold"><?= htmlspecialchars($entreprise['nom_entreprise']) ?></div>
<?php if(!empty($entreprise['adresse'])): ?>
<div class="center"><?= htmlspecialchars($entreprise['adresse']) ?></div>
<?php endif; ?>
<?php if(!empty($entreprise['telephone'])): ?>
<div class="center">Tél: <?= htmlspecialchars($entreprise['telephone']) ?></div>
<?php endif; ?>
<?php if(!empty($entreprise['email'])): ?>
<div class="center"><?= htmlspecialchars($entreprise['email']) ?></div>
<?php endif; ?>

<div class="line"></div>

<div class="bold center">BON DE RETRAIT</div>
<div>Date & Heure: <?= $datetime ?></div>

<div class="line"></div>

<table class="table-ticket">
<tr><td>Compte:</td><td class="right"><?= htmlspecialchars($retrait['numero_compte']) ?></td></tr>
<tr><td>Bénéficiaire:</td><td class="right"><?= htmlspecialchars($retrait['nom_membre']) ?></td></tr>
<tr><td>Libellé:</td><td class="right"><?= htmlspecialchars($retrait['libelle']) ?></td></tr>
<tr><td class="amount">Montant:</td><td class="right amount"><?= number_format($retrait['montant'],2,',',' ') ?> CDF</td></tr>
<tr><td class="amount">Solde restant:</td><td class="right amount"><?= number_format($solde_restant,2,',',' ') ?> CDF</td></tr>
</table>

<div class="line"></div>

<div class="center">
Merci pour votre confiance !<br>
-------------------------------
</div>

<div class="no-print">
    <button onclick="window.print()">Imprimer</button>
</div>

</body>
</html>
