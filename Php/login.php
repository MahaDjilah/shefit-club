<?php
session_start();
require 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: profile.php"); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];

        if (isset($_POST['remember'])) {
            setcookie('remember_token', bin2hex(random_bytes(32)), time() + 7 * 24 * 3600, '/');
        }

        header("Location: " . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'profile.php'));
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SheFit Club – Login</title>
    <link rel="icon" href="images/cropped_circle_image.png">
    <link rel="stylesheet" href="style.project.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Oswald:wght@600&family=Roboto+Slab:wght@100..900&family=Tangerine:wght@700&display=swap" rel="stylesheet">
</head>
<body>

<section id="navigation">
    <ul>
        <li><a href="home.php">Home</a></li>
        <li><a href="classes.php">Classes</a></li>
        <li><a href="membership.php">Membership</a></li>
        <li><a href="trainers.php">Trainers</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li class="nav-auth"><a href="login.php" class="active">Login</a></li>
    </ul>
</section>

<header class="admin-head">
    <h1>SheFit Club – Member Login</h1>
    <p>Access your SheFit Club account.</p>
</header>

<section class="admin-form">
    <h2>Login</h2>

    <?php if ($error): ?>
        <p style="color:red;font-weight:bold;text-align:center;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required>
        <br><br>

        <label><input type="checkbox" name="remember"> Remember me (7 days)</label>
        <br><br>

        <button type="submit">Login</button>
    </form>

    <br>
    <p style="text-align:center;">No account? <a href="membership.php">Register here</a></p>
</section>

<p class="return-admin"><a href="home.php">Return to Main Website</a></p>

</body>
</html>
