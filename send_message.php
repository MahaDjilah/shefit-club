<?php
// send_message.php
// Reçoit le POST du formulaire contact et insère en base de données.
// Ce fichier séparé évite toute interception par script.js.

session_start();
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: contact.php");
    exit;
}

$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validation serveur
if (strlen($name) < 2) {
    header("Location: contact.php?error=name");
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: contact.php?error=email");
    exit;
}
if (strlen($subject) < 5) {
    header("Location: contact.php?error=subject");
    exit;
}
if (strlen($message) < 20) {
    header("Location: contact.php?error=message");
    exit;
}

// Insertion en base de données
$pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)")
    ->execute([$name, $email, $subject, $message]);

// Redirection vers contact.php avec succès
header("Location: contact.php?sent=1");
exit;
