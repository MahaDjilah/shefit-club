<?php
session_start();
require '../includes/db.php';

if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    header("Location: dashboard.php"); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = 'admin';
        header("Location: dashboard.php"); exit;
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
    <title>SheFit Club – Admin Login</title>
    <link rel="icon" href="../images/cropped_circle_image (1).png">
    <link rel="stylesheet" href="../style.project.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="admin-head">
    <h1>SheFit Club Admin Login</h1>
    <p>Access the administration panel to manage classes, trainers, and memberships.</p>
</header>

<section class="admin-form">
    <h2>Administrator Login</h2>

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

        <button type="submit">Login</button>
    </form>
</section>

<p class="return-admin"><a href="../home.php">Return to Main Website</a></p>

</body>
</html>
