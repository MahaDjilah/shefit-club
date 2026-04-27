<?php
session_start();
require 'includes/db.php';

$success = '';
$error   = '';

// ---- Traitement du formulaire ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['fullname'] ?? '');
    $email     = trim($_POST['email']    ?? '');
    $phone     = trim($_POST['phone']    ?? '');
    $dob       = $_POST['dob']      ?? '';
    $plan      = $_POST['plan']     ?? '';   // bronze / silver / gold
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    // Validation serveur
    if (strlen($full_name) < 3 || !preg_match('/^[A-Za-z\s]{3,}$/', $full_name)) {
        $error = "Full name must be at least 3 letters (letters only).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match('/^[0-9]{8,15}$/', $phone)) {
        $error = "Phone must be 8-15 digits.";
    } elseif (empty($dob)) {
        $error = "Date of birth is required.";
    } elseif ((new DateTime())->diff(new DateTime($dob))->y < 16) {
        $error = "You must be at least 16 years old.";
    } elseif (empty($plan)) {
        $error = "Please select a plan.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $password2) {
        $error = "Passwords do not match.";
    } else {
        // Vérifier email unique
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "This email is already registered.";
        } else {
            // Trouver le plan_id
            $stmt = $pdo->prepare("SELECT * FROM plans WHERE LOWER(name) = ?");
            $stmt->execute([strtolower($plan)]);
            $planRow = $stmt->fetch();

            if (!$planRow) {
                $error = "Invalid plan selected.";
            } else {
                // Insérer membre
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, phone, dob) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $email, $hash, $phone, $dob]);
                $user_id = $pdo->lastInsertId();

                // Créer abonnement
                $start = date('Y-m-d');
                $end   = date('Y-m-d', strtotime("+{$planRow['duration_months']} month"));
                $pdo->prepare("INSERT INTO memberships (user_id, plan_id, start_date, end_date) VALUES (?, ?, ?, ?)")
                    ->execute([$user_id, $planRow['id'], $start, $end]);

                $success = "Registration successful! <a href='login.php'>Login here</a>.";
            }
        }
    }
}

// Charger les plans
$plans = $pdo->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SheFit Club Membership</title>
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
        <li><a href="membership.php" class="active">Membership</a></li>
        <li><a href="trainers.php">Trainers</a></li>
        <li><a href="contact.php">Contact</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="profile.php">My Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</section>

<header class="membership-header">
    <h1>SheFit Club Membership Plans</h1>
    <p>Choose the membership plan that best fits your lifestyle and fitness goals. All members enjoy a supportive women-only environment, modern equipment, and a wide variety of fitness classes.</p>
</header>

<!-- PLAN CARDS – depuis la BD, même structure que ton HTML -->
<section id="membership-plans-section">

    <?php
    $cardClasses = ['bronze-card', 'silver-card recommended-card', 'gold-card'];
    $i = 0;
    foreach ($plans as $plan):
        $cc = $cardClasses[$i++] ?? 'bronze-card';
        $features = explode(';', $plan['features']);
    ?>
    <article class="membership-card <?= $cc ?>">
        <h3><?= htmlspecialchars($plan['name']) ?> Plan</h3>
        <dl>
            <dt>Overview</dt>
            <dd><?= htmlspecialchars($plan['description']) ?></dd>
        </dl>
        <ul>
            <?php foreach ($features as $f): ?>
                <li><?= htmlspecialchars(trim($f)) ?></li>
            <?php endforeach; ?>
        </ul>
        <div class="membership-price"><?= number_format($plan['price']) ?> DZD / month</div>
    </article>
    <?php endforeach; ?>

</section>

<!-- Mini Cart Sticky (identique à ton HTML) -->
<div id="mini-cart" class="mini-cart" style="display: none; position: fixed; bottom: 20px; right: 20px; background: #333; color: white; padding: 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);"></div>

<!-- FORMULAIRE D'INSCRIPTION – identique à ton HTML + PHP -->
<section id="membership-register-section">
    <h2>Register for Membership</h2>

    <?php if ($success): ?>
        <p style="color:green;font-weight:bold;"><?= $success ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p style="color:red;font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form id="registerForm" action="membership.php" method="POST">

        <fieldset>
            <legend>Personal Information</legend>

            <label for="name">Full Name:</label><br>
            <input type="text" id="name" name="fullname"
                   value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required>
            <span class="error" id="nameError"></span>
            <br><br>

            <label for="email">Email:</label><br>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            <span class="error" id="emailError"></span>
            <br><br>

            <label for="phone">Phone Number:</label><br>
            <input type="tel" id="phone" name="phone"
                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            <span class="error" id="phoneError"></span>
            <br><br>

            <label for="dob">Date of Birth:</label><br>
            <input type="date" id="dob" name="dob"
                   value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
            <span class="error" id="dobError"></span>
            <br><br>

            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required>
            <br><br>

            <label for="password2">Confirm Password:</label><br>
            <input type="password" id="password2" name="password2" required>
            <br><br>
        </fieldset>

        <fieldset>
            <legend>Select Your Plan</legend>

            <label>
                <input type="radio" name="plan" value="bronze"
                       <?= (($_POST['plan'] ?? '') === 'bronze') ? 'checked' : '' ?>> Bronze Plan
            </label><br>
            <label>
                <input type="radio" name="plan" value="silver"
                       <?= (($_POST['plan'] ?? '') === 'silver') ? 'checked' : '' ?>> Silver Plan
            </label><br>
            <label>
                <input type="radio" name="plan" value="gold"
                       <?= (($_POST['plan'] ?? '') === 'gold') ? 'checked' : '' ?>> Gold Plan
            </label>

            <br>
            <span class="error" id="planError"></span>
        </fieldset>

        <fieldset>
            <legend>Additional Information</legend>
            <label for="message">Fitness Goals:</label><br>
            <textarea id="message" name="goals" rows="4" cols="40"></textarea>
        </fieldset>

        <br>
        <label>
            <input type="checkbox" id="terms">
            I agree to the membership terms and conditions
        </label>
        <span class="error" id="termsError"></span>
        <br><br>

        <input type="submit" value="Register">
    </form>
</section>

<section id="membership-payment-section" class="bck-membership">
    <h2>Accepted Payment Methods</h2>
    <p>We accept the following payment methods:</p>
    <ul>
        <li>Cash (Payment at the gym reception)</li>
        <li>CIB Card (Carte Interbancaire)</li>
        <li>EDAHABIA Card (Algérie Poste)</li>
        <li>Bank Transfer</li>
    </ul>
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
<script>
/*
 * CORRECTIF BACKEND – membership.php
 *
 * 1) Le formulaire #registerForm doit être soumis en POST vers membership.php
 *    pour créer le compte en base de données.
 *    Le script.js intercepte le submit et appelle alert() sans envoyer de données.
 *    Solution : remplacer le nœud formulaire pour supprimer les listeners JS,
 *    puis rattacher uniquement la validation visuelle sans preventDefault final.
 *
 * 2) Le mini-panier utilise REGISTER_PAGE_URL = "membership.html" (fichier statique).
 *    Sur membership.php le chemin ne se termine pas par ".html", donc
 *    proceedToRegister() redirige vers membership.html au lieu de faire défiler.
 *    Solution : surcharger REGISTER_PAGE_URL et proceedToRegister() pour qu'ils
 *    pointent vers membership.php.
 */

/* ── Correctif 2 : mini-panier ── */
if (typeof REGISTER_PAGE_URL !== 'undefined') {
    // Redéfinir la constante et la fonction qui l'utilise
    window.REGISTER_PAGE_URL_FIXED = 'membership.php';

    window.proceedToRegister = function () {
        var savedPlan = sessionStorage.getItem('selectedPlan');
        if (!savedPlan) return;

        var currentPath = window.location.pathname;
        var isOnRegisterPage = currentPath.endsWith('membership.php') ||
                               currentPath.endsWith('membership');

        if (isOnRegisterPage) {
            var plan = JSON.parse(savedPlan);
            var radioButton = document.querySelector('input[name="plan"][value="' + plan.value + '"]');
            if (radioButton) radioButton.checked = true;
            var registerSection = document.getElementById('membership-register-section');
            if (registerSection) registerSection.scrollIntoView({ behavior: 'smooth' });
        } else {
            window.location.href = 'membership.php#membership-register-section';
        }
    };
}

/* ── Correctif 1 : formulaire d'inscription ── */
(function () {
    var form = document.getElementById('registerForm');
    if (!form) return;

    // Cloner pour retirer tous les listeners attachés par script.js
    var freshForm = form.cloneNode(true);
    form.parentNode.replaceChild(freshForm, form);

    freshForm.addEventListener('submit', function (e) {
        // Validation côté client minimale (PHP re-valide de toute façon)
        var nameVal  = (freshForm.querySelector('#name')  || {value:''}).value.trim();
        var emailVal = (freshForm.querySelector('#email') || {value:''}).value.trim();
        var planVal  = freshForm.querySelector('input[name="plan"]:checked');
        var termsVal = freshForm.querySelector('input[type="checkbox"]');

        var ok = true;

        if (!/^[A-Za-z\s]{3,}$/.test(nameVal))           { ok = false; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) { ok = false; }
        if (!planVal)                                       { ok = false; }
        if (termsVal && !termsVal.checked)                  { ok = false; }

        if (!ok) {
            e.preventDefault(); // Bloquer l'envoi invalide
            return;
        }
        // Envoi natif POST → membership.php → PHP crée le compte en BDD
    });
})();
</script>
</body>
</html>
