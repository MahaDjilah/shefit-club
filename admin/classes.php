<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); exit;
}

$success = '';
$error   = '';
$unread  = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE read_status=0")->fetchColumn();
$trainers = $pdo->query("SELECT id, name FROM trainers ORDER BY name")->fetchAll();

// Supprimer
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM classes WHERE id=?")->execute([(int)$_GET['delete']]);
    $success = "Class deleted.";
}

// Ajouter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $name       = trim($_POST['name']       ?? '');
    $trainer_id = (int)($_POST['trainer_id'] ?? 0);
    $day        = $_POST['day']        ?? '';
    $time       = $_POST['start_time'] ?? '';
    $duration   = (int)($_POST['duration']   ?? 0);
    $difficulty = $_POST['difficulty'] ?? 'Beginner';
    $capacity   = (int)($_POST['capacity']   ?? 15);

    if (!$name || !$trainer_id || !$day || !$time || !$duration) {
        $error = "Please fill all required fields.";
    } else {
        $pdo->prepare("INSERT INTO classes (trainer_id,name,day_of_week,start_time,duration_minutes,difficulty,capacity) VALUES (?,?,?,?,?,?,?)")
            ->execute([$trainer_id, $name, $day, $time, $duration, $difficulty, $capacity]);
        $success = "Class added.";
    }
}

// Modifier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_class'])) {
    $id         = (int)$_POST['class_id'];
    $name       = trim($_POST['name']       ?? '');
    $trainer_id = (int)($_POST['trainer_id'] ?? 0);
    $day        = $_POST['day']        ?? '';
    $time       = $_POST['start_time'] ?? '';
    $duration   = (int)($_POST['duration']   ?? 0);
    $difficulty = $_POST['difficulty'] ?? 'Beginner';
    $capacity   = (int)($_POST['capacity']   ?? 15);

    $pdo->prepare("UPDATE classes SET name=?,trainer_id=?,day_of_week=?,start_time=?,duration_minutes=?,difficulty=?,capacity=? WHERE id=?")
        ->execute([$name, $trainer_id, $day, $time, $duration, $difficulty, $capacity, $id]);
    $success = "Class updated.";
}

// Charger les cours
$stmt = $pdo->query("
    SELECT c.*, t.name AS trainer_name,
           (SELECT COUNT(*) FROM class_bookings WHERE class_id=c.id) AS bookings_count
    FROM classes c
    JOIN trainers t ON c.trainer_id=t.id
    ORDER BY FIELD(c.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
");
$classes = $stmt->fetchAll();
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes – Admin SheFit</title>
    <link rel="icon" href="../images/cropped_circle_image (1).png">
    <link rel="stylesheet" href="../style.project.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="Dashboard-head">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></p>
</header>

<div style="display:flex;">

<aside style="width:200px;padding:10px;" class="nav-dashboard">
    <nav>
        <h3>Menu</h3>
        <ul>
            <li><a href="dashboard.php">Members</a></li>
            <li><a href="classes.php">Classes</a></li>
            <li><a href="plans.php">Plans</a></li>
            <li><a href="messages.php">Messages
                <?php if ($unread > 0): ?>
                    <span style="background:red;color:white;border-radius:50%;padding:1px 6px;font-size:11px;margin-left:4px;"><?= $unread ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="../home.php">View Site</a></li>
            <li><a href="logout.php" id="logout-dash">Logout</a></li>
        </ul>
    </nav>
</aside>

<main class="main-dash" style="flex:1;padding:20px;">

    <?php if ($success): ?><p style="color:green;font-weight:bold;"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php if ($error):   ?><p style="color:red;font-weight:bold;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <section class="classes-dashboard">
        <h2>Class Schedule Manager</h2>

        <!-- Bouton Add – identique à ton HTML -->
        <button id="add-class-btn"
                onclick="document.getElementById('add-form').style.display='block';this.style.display='none';">
            + Add New Class
        </button>

        <!-- Formulaire ajout -->
        <div id="add-form" style="display:none;background:#f4f8ff;padding:15px;border-radius:12px;border:1px solid #d0e2ff;max-width:600px;margin:15px 0;">
            <form method="POST">
                <input type="hidden" name="add_class" value="1">
                <input type="text" name="name" placeholder="Class Name" required style="width:98%;padding:8px;margin:4px 0;">
                <select name="trainer_id" required style="width:48%;padding:8px;margin:4px 1%;">
                    <option value="">Select Trainer</option>
                    <?php foreach ($trainers as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="day" required style="width:48%;padding:8px;margin:4px 1%;">
                    <option value="">Day</option>
                    <?php foreach ($days as $d): ?><option><?= $d ?></option><?php endforeach; ?>
                </select>
                <input type="time" name="start_time" required style="width:48%;padding:8px;margin:4px 1%;">
                <input type="number" name="duration" placeholder="Duration (min)" required style="width:48%;padding:8px;margin:4px 1%;">
                <select name="difficulty" style="width:48%;padding:8px;margin:4px 1%;">
                    <option>Beginner</option><option>Intermediate</option><option>Advanced</option>
                </select>
                <input type="number" name="capacity" value="15" placeholder="Capacity" style="width:48%;padding:8px;margin:4px 1%;">
                <br>
                <button type="submit" style="background:#4a90e2;color:white;padding:10px 15px;border:none;border-radius:8px;cursor:pointer;margin-top:10px;">Save</button>
                <button type="button" onclick="document.getElementById('add-form').style.display='none';document.getElementById('add-class-btn').style.display='block';"
                        style="background:#ccc;padding:10px 15px;border:none;border-radius:8px;cursor:pointer;margin-left:5px;">Cancel</button>
            </form>
        </div>

        <!-- Table des cours – identique à ton HTML -->
        <table border="1" id="admin-classes-table">
            <thead>
                <tr>
                    <th>Class Name</th><th>Trainer</th><th>Day</th><th>Time</th>
                    <th>Duration</th><th>Difficulty</th><th>Capacity</th>
                    <th>Bookings</th><th>Remaining</th><th>Actions</th>
                </tr>
            </thead>
            <tbody id="admin-classes-tbody">
                <?php foreach ($classes as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['trainer_name']) ?></td>
                    <td><?= htmlspecialchars($c['day_of_week']) ?></td>
                    <td><?= date('g:i A', strtotime($c['start_time'])) ?></td>
                    <td><?= $c['duration_minutes'] ?> min</td>
                    <td><span class="difficulty <?= strtolower($c['difficulty']) ?>"><?= $c['difficulty'] ?></span></td>
                    <td><?= $c['capacity'] ?></td>
                    <td><?= $c['bookings_count'] ?></td>
                    <td><?= max(0, $c['capacity'] - $c['bookings_count']) ?></td>
                    <td>
                        <button class="btn-edit"
                                onclick="document.getElementById('edit-<?= $c['id'] ?>').style.display='block';this.parentNode.querySelector('.btn-edit').style.display='none';">
                            Edit
                        </button>
                        <a href="classes.php?delete=<?= $c['id'] ?>"
                           onclick="return confirm('Delete this class?')"
                           class="btn-delete" style="text-decoration:none;padding:5px 10px;">Delete</a>
                    </td>
                </tr>
                <!-- Edit row -->
                <tr id="edit-<?= $c['id'] ?>" style="display:none;background:#f0f6ff;">
                    <td colspan="10">
                        <form method="POST" style="padding:10px;">
                            <input type="hidden" name="edit_class" value="1">
                            <input type="hidden" name="class_id" value="<?= $c['id'] ?>">
                            <input type="text" name="name" value="<?= htmlspecialchars($c['name']) ?>" required style="padding:6px;margin:3px;">
                            <select name="trainer_id" required style="padding:6px;margin:3px;">
                                <?php foreach ($trainers as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= $t['id']==$c['trainer_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="day" required style="padding:6px;margin:3px;">
                                <?php foreach ($days as $d): ?>
                                    <option <?= $d===$c['day_of_week'] ? 'selected' : '' ?>><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="time" name="start_time" value="<?= $c['start_time'] ?>" required style="padding:6px;margin:3px;">
                            <input type="number" name="duration" value="<?= $c['duration_minutes'] ?>" required style="padding:6px;margin:3px;width:80px;">
                            <select name="difficulty" style="padding:6px;margin:3px;">
                                <?php foreach (['Beginner','Intermediate','Advanced'] as $d): ?>
                                    <option <?= $d===$c['difficulty'] ? 'selected' : '' ?>><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="capacity" value="<?= $c['capacity'] ?>" style="padding:6px;margin:3px;width:70px;">
                            <button type="submit" class="btn-edit">Save</button>
                            <button type="button" class="btn-delete"
                                    onclick="document.getElementById('edit-<?= $c['id'] ?>').style.display='none';">Cancel</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

</main>
</div>
</body>
</html>
