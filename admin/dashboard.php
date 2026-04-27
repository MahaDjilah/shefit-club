<?php
session_start();
require '../includes/db.php';

// Vérifier que c'est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); exit;
}

// Stats depuis la BD
$total_members = $pdo->query("SELECT COUNT(*) FROM users WHERE role='member'")->fetchColumn();
$active_subs   = $pdo->query("SELECT COUNT(*) FROM memberships WHERE status='active'")->fetchColumn();
$total_classes = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();

// Plan le plus populaire
$stmt = $pdo->query("
    SELECT p.name, COUNT(m.id) AS cnt
    FROM memberships m JOIN plans p ON m.plan_id=p.id
    WHERE m.status='active'
    GROUP BY p.name ORDER BY cnt DESC LIMIT 1
");
$popular = $stmt->fetch();

// Unread messages count
$unread = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE read_status=0")->fetchColumn();

// Membres par plan pour le chart
$stmt = $pdo->query("
    SELECT p.name AS plan_name, COUNT(m.id) AS cnt
    FROM memberships m JOIN plans p ON m.plan_id=p.id
    WHERE m.status='active' GROUP BY p.name
");
$planCounts = ['Bronze' => 0, 'Silver' => 0, 'Gold' => 0];
foreach ($stmt->fetchAll() as $row) {
    $planCounts[$row['plan_name']] = (int)$row['cnt'];
}
$max = max(array_values($planCounts)) ?: 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SheFit Club - Admin Dashboard</title>
    <link rel="icon" href="../images/cropped_circle_image (1).png">
    <link rel="stylesheet" href="../style.project.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@400;700&family=Oswald:wght@600&display=swap" rel="stylesheet">
</head>
<body>

<header class="Dashboard-head">
    <h1>Admin Dashboard</h1>
    <p>Welcome to the SheFit Club admin panel. Manage members, classes, and subscriptions here.</p>
</header>

<div style="display: flex;">

<!-- Sidebar – identique à ton HTML -->
<aside style="width: 200px; padding: 10px;" class="nav-dashboard">
    <nav>
        <h3>Menu</h3>
        <ul>
            <li><a href="dashboard.php">Members</a></li>
            <li><a href="classes.php">Classes</a></li>
            <li><a href="#">Subscriptions</a></li>
            <li><a href="#">Trainers</a></li>
            <li><a href="#">Orders</a></li>
            <li><a href="plans.php">Plans</a></li>
            <li><a href="messages.php">
                Messages
                <?php if ($unread > 0): ?>
                    <span style="background:red;color:white;border-radius:50%;padding:1px 6px;font-size:11px;margin-left:4px;">
                        <?= $unread ?>
                    </span>
                <?php endif; ?>
            </a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="../home.php">View Site</a></li>
            <li><a href="logout.php" id="logout-dash">Logout</a></li>
        </ul>
    </nav>
</aside>

<main class="main-dash">

    <!-- Summary Stats – remplis depuis la BD, mêmes IDs que ton HTML -->
    <section class="stat-dashboard">
        <h2>Summary Stats</h2>
        <ul>
            <li id="memb-dash">Total Members: <?= $total_members ?></li>
            <li id="sub-dash">Active Subscriptions: <?= $active_subs ?></li>
            <li id="class-dash">Classes per Week: <?= $total_classes ?></li>
            <li id="rev-dash">Most Popular Plan: <?= htmlspecialchars($popular['name'] ?? '-') ?></li>
        </ul>
    </section>

    <!-- Members Management – identique à ton HTML, admin-script.js gère tout -->
    <section class="activity-dashboard">
        <h2>Recent Registrations</h2>

        <input type="text" id="searchMember" placeholder="Search by name or email">
        <select id="filterPlan">
            <option value="All">All Plans</option>
            <option value="Bronze">Bronze</option>
            <option value="Silver">Silver</option>
            <option value="Gold">Gold</option>
        </select>

        <button onclick="toggleForm()">Add Member</button>

        <form id="memberForm" style="margin-top:15px; display:none;">
            <input type="text" id="memberName" placeholder="Name" required>
            <input type="email" id="memberEmail" placeholder="Email" required>
            <input type="text" id="memberPhone" placeholder="Phone" required>
            <select id="memberPlan" required>
                <option value="">Select Plan</option>
                <option value="Bronze">Bronze</option>
                <option value="Silver">Silver</option>
                <option value="Gold">Gold</option>
            </select>
            <input type="date" id="memberDate" required>
            <button type="submit">Save Member</button>
            <button type="button" onclick="toggleForm()">Cancel</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>Plan</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </section>

    <!-- Chart – identique à ton HTML -->
    <section class="chart-section">
        <h2>Members by Plan</h2>
        <div class="chart">
            <div class="bar bronze" id="bar-bronze"
                 style="background-color: #cd7f32; height: <?= ($planCounts['Bronze']/$max*200) ?>px">
                <span>Bronze</span>
            </div>
            <div class="bar silver" id="bar-silver"
                 style="background-color: #c0c0c0; height: <?= ($planCounts['Silver']/$max*200) ?>px">
                <span>Silver</span>
            </div>
            <div class="bar gold" id="bar-gold"
                 style="background-color: #ffd700; height: <?= ($planCounts['Gold']/$max*200) ?>px">
                <span>Gold</span>
            </div>
        </div>
    </section>

    <!-- Classes Management – identique à ton HTML -->
    <section class="classes-dashboard">
        <h2>Class Schedule Manager</h2>
        <button id="add-class-btn">+ Add New Class</button>

        <form id="classForm" style="display:none; margin:15px 0;">
            <input type="text" id="className" placeholder="Class Name" required>
            <input type="text" id="classTrainer" placeholder="Trainer" required>
            <input type="text" id="classDay" placeholder="Day" required>
            <input type="text" id="classTime" placeholder="Time (10:00 AM)" required>
            <input type="number" id="classDuration" placeholder="Duration" required>
            <select id="classDifficulty">
                <option value="Beginner">Beginner</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
            </select>
            <input type="number" id="classCapacity" placeholder="Capacity" value="15">
            <button type="submit">Save</button>
            <button type="button" onclick="toggleClassForm()">Cancel</button>
        </form>

        <table border="1" id="admin-classes-table">
            <thead>
                <tr>
                    <th>Class Name</th>
                    <th>Trainer</th>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Duration</th>
                    <th>Difficulty</th>
                    <th>Capacity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="admin-classes-tbody"></tbody>
        </table>
    </section>

</main>
</div>

<!-- admin-script.js intact – gère tout le localStorage comme avant -->
<script src="../admin-script.js"></script>
</body>
</html>
