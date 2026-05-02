<?php
session_start();
require 'includes/db.php';

$success = '';
$error          = '';
$error_password = '';
$show_toast = isset($_GET['registered']) && $_GET['registered'] === '1';

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
        $error_password = "Password must be at least 6 characters.";
    } elseif ($password !== $password2) {
        $error_password = "Passwords do not match.";
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
                // Redirect to avoid re-submission and trigger toast notification
                header("Location: membership.php?registered=1");
                exit;
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
            <li class="nav-auth"><a href="profile.php">My Profile</a></li>
            <li class="nav-auth-next"><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li class="nav-auth"><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</section>

<header class="membership-header">
    <h1>SheFit Club Membership Plans</h1>
    <p>Choose the membership plan that best fits your lifestyle and fitness goals. All members enjoy a supportive women-only environment, modern equipment, and a wide variety of fitness classes.</p>
</header>

<!-- PLAN CARDS – dynamiques depuis MySQL -->
<section id="membership-plans-section">

<?php
$card_styles = [
    'bronze' => ['card_class' => 'bronze-card', 'recommended' => false],
    'silver' => ['card_class' => 'silver-card', 'recommended' => true],
    'gold'   => ['card_class' => 'gold-card',   'recommended' => false],
];

foreach ($plans as $plan):
    $key   = strtolower($plan['name']);
    $style = $card_styles[$key] ?? ['card_class' => 'bronze-card', 'recommended' => false];
    $recommended = $style['recommended'] ? ' recommended-card' : '';
    $duration_label = $plan['duration_months'] == 1 ? 'month' : $plan['duration_months'] . ' months';
    $features = array_filter(array_map('trim', explode("\n", $plan['description'] ?? '')));
?>
  <article class="membership-card <?= $style['card_class'] . $recommended ?>">
    <h3><?= htmlspecialchars($plan['name']) ?> Plan</h3>
    <?php if (!empty($features)): ?>
    <ul>
      <?php foreach ($features as $f): ?>
        <li><?= htmlspecialchars($f) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <div class="membership-price">
      <?= number_format($plan['price']) ?> DZD / <?= $duration_label ?>
    </div>
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

    <form id="registerForm" action="membership.php#membership-register-section" method="POST">

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
            <?php if ($error_password): ?>
                <p style="color:red;font-weight:bold;margin:4px 0;"><?= htmlspecialchars($error_password) ?></p>
            <?php endif; ?>
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

<!-- Toast notification for successful registration (Bug 4) -->
<div id="reg-toast" style="display:none;position:fixed;top:30px;right:30px;z-index:9999;
     background:#6b8e23;color:#fff;padding:16px 28px;border-radius:10px;
     font-family:'Oswald',sans-serif;font-size:1.1rem;
     box-shadow:0 4px 16px rgba(0,0,0,0.25);transition:opacity 0.5s;">
    ✅ Registration successful! <a href="login.php" style="color:#ecf39e;text-decoration:underline;">Login here</a>
</div>

<script>
// Bug 3 fix: if there's an error, scroll to the form section (page reloaded with anchor)
(function() {
    if (window.location.hash === '#membership-register-section') {
        var sec = document.getElementById('membership-register-section');
        if (sec) { setTimeout(function(){ sec.scrollIntoView({behavior:'smooth'}); }, 100); }
    }
})();

// Bug 4: show toast on ?registered=1
(function() {
    var showToast = <?= $show_toast ? 'true' : 'false' ?>;
    if (showToast) {
        // Vider le mini-cart après inscription réussie
        sessionStorage.removeItem('selectedPlan');
        sessionStorage.removeItem('scrollToForm');
        var toast = document.getElementById('reg-toast');
        if (toast) {
            toast.style.display = 'block';
            setTimeout(function() {
                toast.style.opacity = '0';
                setTimeout(function(){ toast.style.display = 'none'; }, 500);
            }, 4000);
        }
    }
})();
</script>

<script>
// ── Correctif mini-panier : pointer vers membership.php ──
window.proceedToRegister = function () {
    var savedPlan = sessionStorage.getItem('selectedPlan');
    if (!savedPlan) return;
    var currentPath = window.location.pathname;
    if (currentPath.endsWith('membership.php') || currentPath.endsWith('membership')) {
        var plan = JSON.parse(savedPlan);
        var radio = document.querySelector('input[name="plan"][value="' + plan.value + '"]');
        if (radio) radio.checked = true;
        var section = document.getElementById('membership-register-section');
        if (section) section.scrollIntoView({ behavior: 'smooth' });
    } else {
        window.location.href = 'membership.php#membership-register-section';
    }
};

// ── Correctif formulaire : laisser JS valider, puis soumettre vers PHP ──
// On NE clone PAS le formulaire pour que les références JS (nameError, etc.) restent valides.
// On ajoute juste un listener supplémentaire qui, après que JS a validé,
// soumet réellement le formulaire vers PHP.
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('registerForm');
    if (!form) return;

    form.addEventListener('submit', function () {
        // script.js a déjà appelé e.preventDefault() et vérifié la validation.
        // Si on arrive ici via form.submit() (appelé programmatiquement),
        // les navigateurs n'ont pas de double-déclenchement — ce listener
        // sert uniquement à intercepter le submit natif du bouton.
        // La soumission réelle est déclenchée par le setTimeout ci-dessous.
    });

    // Intercepter le bouton submit pour lancer la validation JS PUIS soumettre PHP
    var submitBtn = form.querySelector('input[type="submit"], button[type="submit"]');
    if (submitBtn) {
        submitBtn.addEventListener('click', function (e) {
            e.preventDefault();

            // Déclencher la validation JS de script.js via les fonctions globales
            var nameOk  = typeof validateName  === 'function' ? validateName()  : true;
            var emailOk = typeof validateEmail === 'function' ? validateEmail() : true;
            var phoneOk = typeof validatePhone === 'function' ? validatePhone() : true;
            var dobOk   = typeof validateDOB   === 'function' ? validateDOB()   : true;
            var planOk  = typeof validatePlan  === 'function' ? validatePlan()  : true;
            var termsOk = typeof validateTerms === 'function' ? validateTerms() : true;

            if (nameOk && emailOk && phoneOk && dobOk && planOk && termsOk) {
                // Toutes les validations passent → soumettre vers PHP
                form.submit();
            }
            // Sinon les messages d'erreur JS sont déjà affichés par les fonctions ci-dessus
        });
    }
});





</script>
</body>
</html>
