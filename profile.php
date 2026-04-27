<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

// Annuler une réservation
if (isset($_GET['cancel'])) {
    $bid = (int)$_GET['cancel'];
    $pdo->prepare("DELETE FROM class_bookings WHERE id = ? AND user_id = ?")
        ->execute([$bid, $user_id]);
    $success = "Booking cancelled.";
}

// Modifier le profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name    = trim($_POST['full_name']       ?? '');
    $phone        = trim($_POST['phone']            ?? '');
    $current_pass = $_POST['current_password']      ?? '';
    $new_pass     = $_POST['new_password']          ?? '';

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();

    if (strlen($full_name) < 3) {
        $error = "Name must be at least 3 characters.";
    } elseif (!empty($new_pass) && !password_verify($current_pass, $row['password_hash'])) {
        $error = "Current password is incorrect.";
    } elseif (!empty($new_pass) && strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters.";
    } else {
        if (!empty($new_pass)) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET full_name=?, phone=?, password_hash=? WHERE id=?")
                ->execute([$full_name, $phone, $hash, $user_id]);
        } else {
            $pdo->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?")
                ->execute([$full_name, $phone, $user_id]);
        }
        $_SESSION['user_name'] = $full_name;
        $success = "Profile updated successfully!";
    }
}

// Charger membre
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$member = $stmt->fetch();

// Charger abonnement actif
$stmt = $pdo->prepare("
    SELECT m.*, p.name AS plan_name, p.price
    FROM memberships m JOIN plans p ON m.plan_id = p.id
    WHERE m.user_id = ? AND m.status = 'active'
    ORDER BY m.start_date DESC LIMIT 1
");
$stmt->execute([$user_id]);
$membership = $stmt->fetch();

// Charger réservations
$stmt = $pdo->prepare("
    SELECT cb.id AS booking_id, c.name, c.day_of_week, c.start_time, t.name AS trainer
    FROM class_bookings cb
    JOIN classes c ON cb.class_id = c.id
    JOIN trainers t ON c.trainer_id = t.id
    WHERE cb.user_id = ?
    ORDER BY cb.booked_at DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile – SheFit Club</title>
    <link rel="icon" href="images/cropped_circle_image.png">
    <link rel="stylesheet" href="style.project.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@400;700&family=Oswald:wght@600&display=swap" rel="stylesheet">
</head>
<body>

<section id="navigation">
    <ul>
        <li><a href="home.php">Home</a></li>
        <li><a href="classes.php">Classes</a></li>
        <li><a href="membership.php">Membership</a></li>
        <li><a href="trainers.php">Trainers</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li><a href="profile.php" class="active">My Profile</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</section>

<header class="admin-head">
    <h1>My Profile</h1>
    <p>Welcome, <?= htmlspecialchars($member['full_name']) ?>!</p>
</header>

<div style="max-width:900px;margin:40px auto;padding:0 20px;">

    <?php if ($success): ?><p style="color:green;font-weight:bold;"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php if ($error):   ?><p style="color:red;font-weight:bold;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <div style="display:flex;gap:30px;flex-wrap:wrap;margin-bottom:30px;">

        <!-- Modifier profil -->
        <div style="flex:1;min-width:280px;background:#f9f9f9;padding:25px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <h2 style="color:#6b8e23;margin-bottom:20px;">Edit Profile</h2>
            <form method="POST">
                <label>Full Name:</label><br>
                <input type="text" name="full_name" value="<?= htmlspecialchars($member['full_name']) ?>"
                       style="width:100%;padding:8px;margin:5px 0 15px;border:1px solid #ccc;border-radius:5px;" required>

                <label>Phone:</label><br>
                <input type="tel" name="phone" value="<?= htmlspecialchars($member['phone'] ?? '') ?>"
                       style="width:100%;padding:8px;margin:5px 0 15px;border:1px solid #ccc;border-radius:5px;">

                <label>Current Password (to change):</label><br>
                <input type="password" name="current_password"
                       style="width:100%;padding:8px;margin:5px 0 15px;border:1px solid #ccc;border-radius:5px;">

                <label>New Password (leave blank to keep):</label><br>
                <input type="password" name="new_password"
                       style="width:100%;padding:8px;margin:5px 0 15px;border:1px solid #ccc;border-radius:5px;">

                <button type="submit"
                        style="background:#6b8e23;color:white;padding:10px 20px;border:none;border-radius:6px;cursor:pointer;width:100%;">
                    Save Changes
                </button>
            </form>
        </div>

        <!-- Abonnement -->
        <div style="flex:1;min-width:280px;background:#f9f9f9;padding:25px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <h2 style="color:#6b8e23;margin-bottom:20px;">My Membership</h2>
            <?php if ($membership): ?>
                <p><strong>Plan:</strong> <?= htmlspecialchars($membership['plan_name']) ?></p>
                <p><strong>Price:</strong> <?= number_format($membership['price']) ?> DZD/month</p>
                <p><strong>Start:</strong> <?= $membership['start_date'] ?></p>
                <p><strong>Expires:</strong> <?= $membership['end_date'] ?></p>
                <p><strong>Status:</strong> <span style="color:green;font-weight:bold;"><?= ucfirst($membership['status']) ?></span></p>
            <?php else: ?>
                <p>No active membership.</p>
                <a href="membership.php" style="display:inline-block;margin-top:10px;background:#6b8e23;color:white;padding:10px 20px;border-radius:6px;text-decoration:none;">
                    Subscribe Now
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Réservations -->
    <div style="background:#f9f9f9;padding:25px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="color:#6b8e23;margin-bottom:20px;">My Class Bookings</h2>
        <?php if (empty($bookings)): ?>
            <p>No bookings yet. <a href="classes.php">Book a class!</a></p>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#415A77;color:white;">
                        <th style="padding:10px;">Class</th>
                        <th style="padding:10px;">Trainer</th>
                        <th style="padding:10px;">Day</th>
                        <th style="padding:10px;">Time</th>
                        <th style="padding:10px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr style="border-bottom:1px solid #ddd;text-align:center;">
                            <td style="padding:10px;"><?= htmlspecialchars($b['name']) ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars($b['trainer']) ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars($b['day_of_week']) ?></td>
                            <td style="padding:10px;"><?= date('g:i A', strtotime($b['start_time'])) ?></td>
                            <td style="padding:10px;">
                                <a href="profile.php?cancel=<?= $b['booking_id'] ?>"
                                   onclick="return confirm('Cancel this booking?')"
                                   style="background:#e74c3c;color:white;padding:5px 10px;border-radius:5px;text-decoration:none;">
                                    Cancel
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

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
</footer>

</body>
</html>
