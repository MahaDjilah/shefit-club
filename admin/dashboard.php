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

// ── Ban / Unban ──
if (isset($_GET['ban'])) {
    $pdo->prepare("UPDATE users SET status='banned' WHERE id=? AND role='member'")->execute([(int)$_GET['ban']]);
    header("Location: dashboard.php"); exit;
}
if (isset($_GET['unban'])) {
    $pdo->prepare("UPDATE users SET status='active' WHERE id=? AND role='member'")->execute([(int)$_GET['unban']]);
    header("Location: dashboard.php"); exit;
}
// ── Delete membre ──
if (isset($_GET['delete_member'])) {
    $mid = (int)$_GET['delete_member'];
    $pdo->prepare("DELETE FROM class_bookings WHERE user_id=?")->execute([$mid]);
    $pdo->prepare("DELETE FROM memberships   WHERE user_id=?")->execute([$mid]);
    $pdo->prepare("DELETE FROM users WHERE id=? AND role='member'")->execute([$mid]);
    header("Location: dashboard.php"); exit;
}

// Charger les membres depuis MySQL (avec status)
$membersFromDB = $pdo->query("
    SELECT u.id, u.full_name AS name, u.email, u.phone,
           u.created_at AS date, u.status,
           COALESCE(p.name, 'None') AS plan
    FROM users u
    LEFT JOIN memberships m ON m.user_id = u.id AND m.status = 'active'
    LEFT JOIN plans p ON m.plan_id = p.id
    WHERE u.role = 'member'
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Formater pour localStorage (admin-script.js)
$membersForJS = array_map(function($m) {
    return [
        'id'    => (int)$m['id'],
        'name'  => $m['name'],
        'email' => $m['email'],
        'phone' => $m['phone'] ?? '',
        'plan'  => $m['plan'],
        'date'  => date('d-m-Y', strtotime($m['date']))
    ];
}, $membersFromDB);

$membersJson = json_encode($membersForJS, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
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

<?php require '../includes/admin_sidebar.php'; ?>

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

    <!-- Members Management – rendu PHP depuis MySQL -->
    <section class="activity-dashboard">
        <h2>Members (<?= count($membersFromDB) ?>)</h2>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Plan</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($membersFromDB)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#888;padding:20px;">No members yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($membersFromDB as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['name']) ?></td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                    <td><?= htmlspecialchars($m['phone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($m['plan']) ?></td>
                    <td><?= date('d/m/Y', strtotime($m['date'])) ?></td>
                    <td>
                        <?php if (($m['status'] ?? 'active') === 'banned'): ?>
                            <span style="color:#e74c3c;font-weight:bold;">Banned</span>
                        <?php else: ?>
                            <span style="color:green;font-weight:bold;">Active</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap;">
                        <?php if (($m['status'] ?? 'active') === 'banned'): ?>
                            <a href="dashboard.php?unban=<?= $m['id'] ?>"
                               style="background:#6b8e23;color:white;padding:5px 10px;border-radius:5px;text-decoration:none;font-size:13px;margin-right:3px;display:inline-block;">
                                Unban
                            </a>
                        <?php else: ?>
                            <a href="dashboard.php?ban=<?= $m['id'] ?>"
                               onclick="return confirm('Ban <?= htmlspecialchars(addslashes($m['name'])) ?>?')"
                               style="background:#e67e22;color:white;padding:5px 10px;border-radius:5px;text-decoration:none;font-size:13px;margin-right:3px;display:inline-block;">
                                Ban
                            </a>
                        <?php endif; ?>
                        <a href="dashboard.php?delete_member=<?= $m['id'] ?>"
                           onclick="return confirm('Delete <?= htmlspecialchars(addslashes($m['name'])) ?> permanently?')"
                           style="background:#e74c3c;color:white;padding:5px 10px;border-radius:5px;text-decoration:none;font-size:13px;display:inline-block;">
                            Delete
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
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

<!-- Injection des membres MySQL dans localStorage avant admin-script.js -->
<script>
    localStorage.setItem('shefit_members', JSON.stringify(<?= $membersJson ?>));
</script>
<!-- admin-script.js intact – gère tout le localStorage comme avant -->\n<script src="../admin-script.js"></script>
</body>
</html>
