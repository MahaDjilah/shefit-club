<?php
// ============================================================
//  includes/db.php – Connexion PDO
// ============================================================
$host   = '127.0.0.1';
$port   = '3307';        // ← change en 3306 si MySQL tourne sur le port par défaut
$dbname = 'shefit_db';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion BD : " . $e->getMessage());
}
