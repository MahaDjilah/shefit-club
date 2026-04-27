<?php
session_start();
require 'includes/db.php';

// Charger les cours depuis la BD (JOIN avec trainers)
$stmt = $pdo->query("
    SELECT c.*, t.name AS trainer_name
    FROM classes c
    JOIN trainers t ON c.trainer_id = t.id
    ORDER BY FIELD(c.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
");
$classes = $stmt->fetchAll();

// Passer les données au JS via json_encode
$classesJson = json_encode(array_map(function($c) {
    return [
        'name'     => $c['name'],
        'trainer'  => $c['trainer_name'],
        'day'      => $c['day_of_week'],
        'time'     => date('g:i A', strtotime($c['start_time'])),
        'duration' => (int)$c['duration_minutes'],
        'level'    => $c['difficulty']
    ];
}, $classes));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SheFit Club Classes</title>
    <link rel="icon" href="images/cropped_circle_image.png">
    <link rel="stylesheet" href="style.project.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Bad+Script&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Oswald:wght@600&family=Roboto+Slab:wght@100..900&family=Tangerine:wght@700&display=swap" rel="stylesheet">
</head>
<body>

<!-- NAVIGATION BAR -->
<section id="navigation">
    <ul>
        <li><a href="home.php">Home</a></li>
        <li><a href="classes.php" class="active">Classes</a></li>
        <li><a href="membership.php">Membership</a></li>
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

<!-- PAGE HEADER -->
<header class="classes">
    <h1>SheFit Club Classes</h1>
    <p>Explore all the fitness classes available at SheFit Club. Check the schedule, trainers, and difficulty levels to find the class that fits your fitness journey.</p>
</header>

<!-- FILTERS -->
<section class="difficulty">
    <h2>Filter Classes</h2>
    <div class="filters">
        <div class="filter-item">
            <label for="day">Day:</label>
            <select id="day">
                <option>All Days</option>
                <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
                <option>Thursday</option><option>Friday</option><option>Saturday</option>
            </select>
        </div>
        <div class="filter-item">
            <label for="level">Difficulty:</label>
            <select id="level">
                <option>All Levels</option>
                <option>Beginner</option><option>Intermediate</option><option>Advanced</option>
            </select>
        </div>
        <div class="filter-item">
            <label for="trainer">Trainer:</label>
            <select id="trainer">
                <option>All Trainers</option>
                <?php foreach ($classes as $c): ?>
                    <option><?= htmlspecialchars($c['trainer_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</section>

<!-- CLASSES TABLE -->
<section class="classes-table">
    <table>
        <caption>Weekly Gym Classes Schedule</caption>
        <thead>
            <tr>
                <th>Class Name</th>
                <th>Trainer</th>
                <th>Day</th>
                <th>Time</th>
                <th>Duration</th>
                <th>Difficulty Level</th>
            </tr>
        </thead>
        <tbody id="classes-tbody">
            <?php foreach ($classes as $c): ?>
                <tr>
                    <td><a href="classes.php"><?= htmlspecialchars($c['name']) ?></a></td>
                    <td><?= htmlspecialchars($c['trainer_name']) ?></td>
                    <td><?= htmlspecialchars($c['day_of_week']) ?></td>
                    <td><?= date('g:i A', strtotime($c['start_time'])) ?></td>
                    <td><?= $c['duration_minutes'] ?> min</td>
                    <td><span class="difficulty <?= strtolower($c['difficulty']) ?>"><?= $c['difficulty'] ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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

<!-- Passer les données BD au JS -->
<script>
    // On remplace classesData du script.js par les données de la BD
    const classesDataFromDB = <?= $classesJson ?>;
</script>
<script src="script.js"></script>
<script>
    // Remplacer classesData par les données BD pour que les filtres fonctionnent
    Object.assign(classesData, classesDataFromDB);
    classesData.length = 0;
    classesDataFromDB.forEach(c => classesData.push(c));
    // Rafraîchir la table avec les données BD
    applyFiltersAndSort();
</script>
</body>
</html>
