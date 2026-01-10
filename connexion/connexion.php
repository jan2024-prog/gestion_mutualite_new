<?php
try {
    // Connexion à la base de données
    $pdo = new PDO(
        "mysql:host=localhost;dbname=gestion_mutualite;charset=utf8",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Active les exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Mode de récupération par défaut
            PDO::ATTR_EMULATE_PREPARES => false // Pour plus de sécurité
        ]
    );

   //echo "Connexion réussie à la base de données !";

} catch (PDOException $e) {
    // En cas d’erreur de connexion
    echo "Erreur de connexion à la base de données : " . $e->getMessage();
}
?>