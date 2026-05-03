<?php
session_start();
require 'includes/db.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (strlen($name) < 2) {
        $error = "Name must be at least 2 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($subject) < 5) {
        $error = "Subject must be at least 5 characters.";
    } elseif (strlen($message) < 20) {
        $error = "Message must be at least 20 characters.";
    } else {
        $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)")
            ->execute([$name, $email, $subject, $message]);
        $success = "Thank you! Your message has been sent successfully.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SheFit Club Contact</title>
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
        <li><a href="trainers.php">Trainers</a></li>
        <li><a href="contact.php" class="active">Contact</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li class="nav-auth"><a href="profile.php">My Profile</a></li>
            <li class="nav-auth-next"><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li class="nav-auth"><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</section>

<header class="contact-head">
    <h1>Contact SheFit Club</h1>
    <p>Have questions or want to join our community? Get in touch with us and our team will be happy to help you.</p>
</header>

<!-- CONTACT FORM -->
<section class="contact-form">
    <h2>Send Us a Message</h2>

    <?php if ($success): ?>
        <p style="color:green;font-weight:bold;text-align:center;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p style="color:red;font-weight:bold;text-align:center;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="contact.php" method="POST">

        <label for="name">Full Name:</label><br>
        <input type="text" id="name" name="name"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        <br><br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <br><br>

        <label for="subject">Subject:</label><br>
        <input type="text" id="subject" name="subject"
               value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
        <br><br>

        <label for="message">Message:</label><br>
        <textarea id="message" name="message" rows="6" cols="40"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        <br><br>

        <button type="submit">Send Message</button>
    </form>
</section>

<!-- CONTACT INFORMATION -->
<section>
    <h2>Gym Contact Information</h2>
    <address>
        <strong>SheFit Club</strong><br>
        Constantine - Zouaghi Slimane, Rue 255<br><br>
        Phone: 0552789566<br>
        Email: Shefitclub@gmail.com
    </address>
</section>

<!-- MAP -->
<section class="contact-map">
    <h2>Find Us on the Map</h2>
    <iframe src="https://maps.google.com/maps?q=Constantine%20Zouaghi%20Slimane&t=&z=13&ie=UTF8&iwloc=&output=embed"
            width="400" height="300"></iframe>
</section>

<!-- OPENING HOURS -->
<section class="contact-table">
    <h2>Opening Hours</h2>
    <table border="1">
        <thead>
            <tr><th>Day</th><th>Opening Time</th><th>Closing Time</th></tr>
        </thead>
        <tbody>
            <tr><td>Sunday - Thursday</td><td>8:00 AM</td><td>11:00 PM</td></tr>
            <tr><td>Friday</td><td>10:00 AM</td><td>11:00 PM</td></tr>
            <tr><td>Saturday</td><td>11:00 AM</td><td>9:00 PM</td></tr>
        </tbody>
    </table>
</section>

<!-- FOOTER -->
<footer>
    <div class="contact">
        <h3>Follow Us</h3>
        <a href="https://www.facebook.com/">Facebook</a>
        <a href="https://www.instagram.com/">Instagram</a>
        <a href="https://www.tiktok.com/">Tiktok</a>
    </div>
    <br><hr>
    <p>&copy; 2026 SheFit Club. All rights reserved.</p>
    <p><a href="admin/login.php">Admin Login</a></p>
</footer>

<script src="script.js"></script>
</body>
</html>
