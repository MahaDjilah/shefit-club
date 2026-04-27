<?php
session_start();
require 'includes/db.php';

$trainers = $pdo->query("SELECT * FROM trainers ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SheFit Club Trainers</title>
    <link rel="icon" href="images/cropped_circle_image.png">
    <link rel="stylesheet" href="style.project.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Oswald:wght@600&family=Roboto+Slab:wght@100..900&family=Tangerine:wght@700&display=swap" rel="stylesheet">
</head>
<body>

<!-- NAVIGATION BAR -->
<section id="navigation">
    <ul>
        <li><a href="home.php">Home</a></li>
        <li><a href="classes.php">Classes</a></li>
        <li><a href="membership.php">Membership</a></li>
        <li><a href="trainers.php" class="active">Trainers</a></li>
        <li><a href="contact.php">Contact</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="profile.php">My Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</section>

<header class="trainers-head">
    <h1>Meet Our Trainers</h1>
    <p>Our certified trainers are passionate about helping women achieve their fitness goals in a supportive and motivating environment.</p>
</header>

<div class="search-container">
    <input type="text" id="trainer-search" placeholder="Search trainers by name or specialty..."
           style="width: 100%; padding: 12px; font-size: 1.1em; margin-bottom: 20px;">
</div>

<!-- TRAINERS GRID – depuis la BD -->
<section class="trainers-grid">
    <?php foreach ($trainers as $t): ?>
    <article>
        <figure>
            <img src="<?= htmlspecialchars($t['photo_path']) ?>"
                 alt="<?= htmlspecialchars($t['name']) ?>" width="200">
            <figcaption>
                <strong><?= htmlspecialchars($t['name']) ?></strong> - <?= htmlspecialchars($t['specialty']) ?>
            </figcaption>
        </figure>
        <p><strong>Experience:</strong> <?= $t['years_experience'] ?> years</p>
        <p><?= htmlspecialchars($t['bio']) ?></p>
    </article>
    <?php endforeach; ?>
</section>

<section class="work-with-us">
    <h2>Work With Us</h2>
    <p>SheFit Club is always looking for passionate and certified fitness professionals who want to inspire and empower women through fitness. If you are interested in joining our team, we would love to hear from you.</p>
    <p><a href="contact.php">Contact us to apply</a></p>
    <address>
        SheFit Club Recruitment Team<br>
        Email: careers@shefitclub.com<br>
        Phone: +213 555 123 456
    </address>
</section>

<!-- FOOTER -->
<footer>
    <div class="footer-sections">
        <div class="contact">
            <h3>Our Contact info</h3>
            <p>Constantine-Zouaghi Slimane Rue 255</p>
            <p>Tel: 0552789566</p>
            <p><a href="mailto:Shefitclub@gmail.com">Shefitclub@gmail.com</a></p>
        </div>
        <div class="contact">
            <h3>Follow Us</h3>
            <a href="https://www.facebook.com/">Facebook</a>
            <a href="https://www.instagram.com/">Instagram</a>
            <a href="https://www.tiktok.com/">Tiktok</a>
        </div>
        <div class="contact">
            <h3>Opening Hours</h3>
            <p>Sunday - Thursday 8:00 AM - 11:00 PM</p>
            <p>Friday 10:00 AM - 11:00 PM</p>
            <p>Saturday 11:00 AM - 9:00 PM</p>
        </div>
    </div>
    <hr>
    <p>&copy; 2026 SheFit Club. All rights reserved.</p>
    <p><a href="admin/login.php">Admin Login</a></p>
</footer>

<script src="script.js"></script>
</body>
</html>
