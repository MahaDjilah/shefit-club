<?php
session_start();
require 'includes/db.php';

// Charger les plans depuis la BD
$plans = $pdo->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SheFit Club</title>
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
        <li><a href="home.php" class="active">Home</a></li>
        <li><a href="classes.php">Classes</a></li>
        <li><a href="membership.php">Membership</a></li>
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

<!-- HERO SECTION -->
<section id="hero">
    <header>
        <h1>SheFit Club</h1>
        <p>Fitness Made for Her</p>
        <button class="button"><span><a href="membership.php">JOIN NOW</a></span></button>
    </header>
</section>

<br><br><br>

<!-- GYM FACILITIES -->
<section id="facilities">
    <section class="head-space">
        <h2>Club Spaces</h2>
        <p>Welcome to SheFit, your new favorite gym club designed just for you. A place where women can freely enjoy their favorite workouts in a warm, supportive atmosphere with top-quality spaces. Explore our facilities and start your journey today!</p>
    </section>

    <div class="facilities-container">
        <article class="facilities-box">
            <h3>Weights Area</h3>
            <p>A fully equipped strength-training zone featuring dumbbells, barbells, kettlebells, and resistance machines. Perfect for building muscle, toning your body, and boosting overall strength.</p>
            <img src="images/be485868717d47a89cb622c187ed2ff2.jpg" width="200" alt="weights area">
            <br><a href="classes.php">See Strength Classes</a>
        </article>
        <article class="facilities-box">
            <h3>Cardio Room</h3>
            <p>A fully equipped space with treadmills, ellipticals, stationary bikes, and rowing machines. Perfect for burning calories, building endurance, and keeping your heart strong.</p>
            <img src="images/fa7c95886403fd9500b388900e2be637.jpg" alt="" width="200">
            <br><a href="classes.php">See Cardio Classes</a>
        </article>
        <article class="facilities-box">
            <h3>Pilates Studio</h3>
            <p>A serene studio dedicated to Pilates and core-strength workouts. With mats, reformers, and resistance tools, this space helps improve flexibility, posture, balance, and overall body tone.</p>
            <img src="images/pilates.jpg" alt="" width="200">
            <br><a href="classes.php">Join Pilates Classes</a>
        </article>
        <article class="facilities-box">
            <h3>Swimming Pool</h3>
            <p>Our indoor swimming pool offers both lap lanes and leisure areas. Ideal for cardiovascular fitness, low-impact training, and full-body conditioning.</p>
            <img src="images/swim.jpg" alt="" width="200">
            <br><a href="classes.php">View Swim Classes</a>
        </article>
        <article class="facilities-box">
            <h3>Kickboxing Zone</h3>
            <p>High-energy zone equipped with punching bags, mats, and pads for kickboxing and martial arts-inspired workouts. Build strength, stamina, and confidence.</p>
            <img src="images/kick.jpg" alt="" width="200">
            <br><a href="classes.php">Try Kickboxing</a>
        </article>
        <article class="facilities-box">
            <h3>Paddle Area</h3>
            <p>Enjoy racquet and paddle sports in a dedicated space that improves coordination, agility, and reflexes. A fun, social area perfect for friendly matches.</p>
            <img src="images/paddle.jpg" alt="" width="200">
            <br><a href="classes.php">Book Paddle Class</a>
        </article>
        <article class="facilities-box">
            <h3>Pre/Postnatal Fitness Room</h3>
            <p>A safe, specialized space for moms-to-be and new mothers. Offers gentle strength, mobility, and cardio exercises tailored to support pregnancy and postpartum recovery.</p>
            <img src="images/mom-fit.jpg" alt="" width="200">
            <br><a href="classes.php">Join Mom Fitness</a>
        </article>
        <article class="facilities-box">
            <h3>Locker & Shower Area</h3>
            <p>Spacious, secure locker rooms with private showers and changing areas. Relax and refresh before or after your workout.</p>
            <img src="images/shower.jpg" alt="" width="200">
            <br><a href="#">Explore Amenities</a>
        </article>
        <article class="facilities-box">
            <h3>Fit Bar</h3>
            <p>Our on-site Fit Bar serves healthy snacks, protein shakes, energy drinks, and post-workout recovery options. A perfect stop to refuel.</p>
            <img src="images/bar.jpg" alt="" width="200">
            <br><a href="#">Check the Menu</a>
        </article>
    </div>
</section>

<!-- MEMBERSHIP PLANS – depuis la BD -->
<section id="Membership">
    <h2 style="text-align:center">SheFit Club Membership plan</h2>
    <br>
    <div class="columns">
        <?php
        $cssClasses = ['bronze', 'silver', 'gold'];
        $i = 0;
        foreach ($plans as $plan):
            $css = $cssClasses[$i++] ?? 'bronze';
            $features = explode(';', $plan['features']);
        ?>
        <ul class="price <?= $css ?>">
            <li class="header"><?= htmlspecialchars($plan['name']) ?></li>
            <li class="grey"><?= number_format($plan['price']) ?> DZD / month</li>
            <?php foreach (array_slice($features, 0, 4) as $f): ?>
                <li><?= htmlspecialchars(trim($f)) ?></li>
            <?php endforeach; ?>
            <li class="grey"><a href="membership.php" class="button">See More Details</a></li>
        </ul>
        <?php endforeach; ?>
    </div>
</section>

<br>

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
