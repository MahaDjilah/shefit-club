<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}

$class_id = (int)($_GET['class_id'] ?? 0);
if ($class_id > 0) {
    $pdo->prepare("INSERT IGNORE INTO class_bookings (user_id, class_id) VALUES (?, ?)")
        ->execute([$_SESSION['user_id'], $class_id]);
}

header("Location: profile.php?booked=1#available-classes-section");
exit;
