<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); exit;
}

$success = '';
$error   = '';
$unread  = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE read_status=0")->fetchColumn();

// ── Supprimer une classe ──
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM classes WHERE id=?")->execute([(int)$_GET['delete']]);
    header("Location: classes.php?ok=class_deleted"); exit;
}

// ── Supprimer un trainer ──
if (isset($_GET['delete_trainer'])) {
    $tid = (int)$_GET['delete_trainer'];
    // Supprimer la photo si elle existe
    $row = $pdo->prepare("SELECT photo_path FROM trainers WHERE id=?");
    $row->execute([$tid]);
    $photo = $row->fetchColumn();
    if ($photo && file_exists('../' . $photo)) {
        unlink('../' . $photo);
    }
    $pdo->prepare("DELETE FROM trainers WHERE id=?")->execute([$tid]);
    header("Location: classes.php?ok=trainer_deleted"); exit;
}

// ── Messages de succès via GET ──
if (isset($_GET['ok'])) {
    $msgs = [
        'class_deleted'   => 'Class deleted.',
        'trainer_deleted' => 'Trainer deleted.',
    ];
    $success = $msgs[$_GET['ok']] ?? '';
}

// ── Ajouter un trainer ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trainer'])) {
    $t_name      = trim($_POST['trainer_name']      ?? '');
    $t_specialty = trim($_POST['trainer_specialty']  ?? '');
    $t_bio       = trim($_POST['trainer_bio']        ?? '');
    $t_exp       = (int)($_POST['trainer_exp']       ?? 0);
    $photo_path  = '';

    if (!$t_name) {
        $error = "Trainer name is required.";
    } else {
        // Gestion photo
        if (!empty($_FILES['trainer_photo']['name'])) {
            $allowed = ['jpg','jpeg','png'];
            $ext = strtolower(pathinfo($_FILES['trainer_photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = "Photo must be JPG or PNG.";
            } else {
                $filename   = 'trainer_' . time() . '_' . preg_replace('/[^a-z0-9.]/', '_', strtolower($_FILES['trainer_photo']['name']));
                $upload_dir = '../images/';
                if (move_uploaded_file($_FILES['trainer_photo']['tmp_name'], $upload_dir . $filename)) {
                    $photo_path = 'images/' . $filename;
                }
            }
        }

        if (!$error) {
            $pdo->prepare("INSERT INTO trainers (name, specialty, bio, years_experience, photo_path) VALUES (?,?,?,?,?)")
                ->execute([$t_name, $t_specialty, $t_bio, $t_exp, $photo_path]);
            $success = "Trainer '{$t_name}' added successfully.";
        }
    }
}

// ── Modifier un trainer ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_trainer'])) {
    $tid         = (int)$_POST['trainer_id'];
    $t_name      = trim($_POST['trainer_name']      ?? '');
    $t_specialty = trim($_POST['trainer_specialty']  ?? '');
    $t_bio       = trim($_POST['trainer_bio']        ?? '');
    $t_exp       = (int)($_POST['trainer_exp']       ?? 0);

    // Récupérer l'ancienne photo
    $old = $pdo->prepare("SELECT photo_path FROM trainers WHERE id=?");
    $old->execute([$tid]);
    $old_photo = $old->fetchColumn();
    $photo_path = $old_photo; // par défaut on garde l'ancienne

    if (!empty($_FILES['trainer_photo']['name'])) {
        $allowed = ['jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['trainer_photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $error = "Photo must be JPG or PNG.";
        } else {
            $filename   = 'trainer_' . time() . '_' . preg_replace('/[^a-z0-9.]/', '_', strtolower($_FILES['trainer_photo']['name']));
            $upload_dir = '../images/';
            if (move_uploaded_file($_FILES['trainer_photo']['tmp_name'], $upload_dir . $filename)) {
                // Supprimer l'ancienne photo si elle existait
                if ($old_photo && file_exists('../' . $old_photo)) {
                    unlink('../' . $old_photo);
                }
                $photo_path = 'images/' . $filename;
            }
        }
    }

    if (!$error) {
        $pdo->prepare("UPDATE trainers SET name=?, specialty=?, bio=?, years_experience=?, photo_path=? WHERE id=?")
            ->execute([$t_name, $t_specialty, $t_bio, $t_exp, $photo_path, $tid]);
        $success = "Trainer updated.";
    }
}

// ── Ajouter une classe ──
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

// ── Modifier une classe ──
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

// ── Charger données ──
$trainers = $pdo->query("SELECT * FROM trainers ORDER BY name")->fetchAll();
$stmt = $pdo->query("
    SELECT c.*, t.name AS trainer_name,
           (SELECT COUNT(*) FROM class_bookings WHERE class_id=c.id) AS bookings_count
    FROM classes c
    JOIN trainers t ON c.trainer_id=t.id
    ORDER BY FIELD(c.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
");
$classes = $stmt->fetchAll();
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

$default_photo = 'images/cropped_circle_image.png';
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
    <style>
        .trainer-table { width:100%; border-collapse:collapse; margin-top:10px; }
        .trainer-table th { background:#415A77; color:white; padding:8px; text-align:left; }
        .trainer-table td { padding:8px; border-bottom:1px solid #ddd; vertical-align:middle; }
        .trainer-table tr:nth-child(even) td { background:#f9f9f9; }
        .trainer-thumb { width:48px; height:48px; border-radius:50%; object-fit:cover; }
        .trainer-edit-form { background:#fffbea; padding:12px; border-radius:8px; border:1px solid #f0d060; margin-top:6px; display:none; }
    </style>
</head>
<body>

<header class="Dashboard-head">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></p>
</header>

<div style="display:flex;">
<?php require '../includes/admin_sidebar.php'; ?>

<main class="main-dash" style="flex:1;padding:20px;">

    <?php if ($success): ?><p style="color:green;font-weight:bold;padding:10px;background:#f0fff0;border-radius:6px;"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php if ($error):   ?><p style="color:red;font-weight:bold;padding:10px;background:#fff0f0;border-radius:6px;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <section class="classes-dashboard">
        <h2>Trainer Management</h2>

        <!-- Bouton ajouter trainer -->
        <button id="add-trainer-btn"
                onclick="document.getElementById('add-trainer-form').style.display='block';this.style.display='none';"
                style="background:#6b8e23;color:white;padding:10px 18px;border:none;border-radius:8px;cursor:pointer;font-size:15px;margin-bottom:15px;">
            + Add New Trainer
        </button>

        <!-- Formulaire ajout trainer -->
        <div id="add-trainer-form" style="display:none;background:#f0fff0;padding:15px;border-radius:12px;border:1px solid #90EE90;max-width:620px;margin-bottom:20px;">
            <h3 style="margin-top:0;color:#4a7c10;">New Trainer</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_trainer" value="1">
                <input type="text" name="trainer_name" placeholder="Full Name *" required
                       style="width:98%;padding:8px;margin:4px 0;border:1px solid #ccc;border-radius:5px;">
                <input type="text" name="trainer_specialty" placeholder="Specialty (e.g. Yoga, Pilates)"
                       style="width:48%;padding:8px;margin:4px 1%;border:1px solid #ccc;border-radius:5px;">
                <input type="number" name="trainer_exp" placeholder="Years of experience" min="0"
                       style="width:48%;padding:8px;margin:4px 1%;border:1px solid #ccc;border-radius:5px;">
                <textarea name="trainer_bio" placeholder="Short bio (optional)"
                          style="width:98%;padding:8px;margin:4px 0;height:60px;border:1px solid #ccc;border-radius:5px;"></textarea>
                <label style="display:block;margin:6px 0 2px;">Profile Photo (JPG/PNG):</label>
                <input type="file" name="trainer_photo" accept=".jpg,.jpeg,.png"
                       style="margin-bottom:8px;">
                <br>
                <button type="submit" style="background:#6b8e23;color:white;padding:10px 15px;border:none;border-radius:8px;cursor:pointer;">Save Trainer</button>
                <button type="button" onclick="document.getElementById('add-trainer-form').style.display='none';document.getElementById('add-trainer-btn').style.display='block';"
                        style="background:#ccc;padding:10px 15px;border:none;border-radius:8px;cursor:pointer;margin-left:5px;">Cancel</button>
            </form>
        </div>

        <!-- Tableau trainers avec Edit/Delete -->
        <table class="trainer-table">
            <thead>
                <tr>
                    <th>Photo</th><th>Name</th><th>Specialty</th><th>Experience</th><th>Bio</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trainers as $t): ?>
                <tr>
                    <td>
                        <img src="../<?= htmlspecialchars($t['photo_path'] ?: $default_photo) ?>"
                             alt="<?= htmlspecialchars($t['name']) ?>" class="trainer-thumb"
                             onerror="this.src='../<?= $default_photo ?>'">
                    </td>
                    <td><?= htmlspecialchars($t['name']) ?></td>
                    <td><?= htmlspecialchars($t['specialty']) ?></td>
                    <td><?= (int)$t['years_experience'] ?> yrs</td>
                    <td style="max-width:200px;font-size:0.85rem;"><?= htmlspecialchars(substr($t['bio'], 0, 80)) ?>...</td>
                    <td>
                        <button onclick="document.getElementById('edit-trainer-<?= $t['id'] ?>').style.display='block';this.style.display='none';"
                                style="background:#4a90e2;color:white;padding:5px 10px;border:none;border-radius:5px;cursor:pointer;margin-bottom:4px;">
                            Edit
                        </button>
                        <a href="classes.php?delete_trainer=<?= $t['id'] ?>"
                           onclick="return confirm('Delete trainer <?= htmlspecialchars(addslashes($t['name'])) ?>? This will also remove them from their classes.')"
                           style="background:#e74c3c;color:white;padding:5px 10px;border-radius:5px;text-decoration:none;display:inline-block;">
                            Delete
                        </a>
                        <!-- Formulaire edit inline -->
                        <div id="edit-trainer-<?= $t['id'] ?>" class="trainer-edit-form">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="edit_trainer" value="1">
                                <input type="hidden" name="trainer_id" value="<?= $t['id'] ?>">
                                <input type="text" name="trainer_name" value="<?= htmlspecialchars($t['name']) ?>" required
                                       style="width:98%;padding:6px;margin:3px 0;border:1px solid #ccc;border-radius:4px;">
                                <input type="text" name="trainer_specialty" value="<?= htmlspecialchars($t['specialty']) ?>"
                                       style="width:48%;padding:6px;margin:3px 1%;border:1px solid #ccc;border-radius:4px;">
                                <input type="number" name="trainer_exp" value="<?= (int)$t['years_experience'] ?>"
                                       style="width:48%;padding:6px;margin:3px 1%;border:1px solid #ccc;border-radius:4px;">
                                <textarea name="trainer_bio" style="width:98%;padding:6px;height:50px;border:1px solid #ccc;border-radius:4px;"><?= htmlspecialchars($t['bio']) ?></textarea>
                                <label style="display:block;margin:4px 0 2px;font-size:0.85rem;">New Photo (leave empty to keep current):</label>
                                <input type="file" name="trainer_photo" accept=".jpg,.jpeg,.png" style="margin-bottom:6px;">
                                <br>
                                <button type="submit" style="background:#4a90e2;color:white;padding:6px 14px;border:none;border-radius:5px;cursor:pointer;">Save</button>
                                <button type="button" onclick="document.getElementById('edit-trainer-<?= $t['id'] ?>').style.display='none';"
                                        style="background:#ccc;padding:6px 12px;border:none;border-radius:5px;cursor:pointer;margin-left:4px;">Cancel</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <br><hr><br>

        <h2>Class Schedule Manager</h2>

        <!-- Bouton Add Class -->
        <button id="add-class-btn"
                onclick="document.getElementById('add-form').style.display='block';this.style.display='none';">
            + Add New Class
        </button>

        <!-- Formulaire ajout classe -->
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

        <!-- Table des classes -->
        <table border="1" id="admin-classes-table">
            <thead>
                <tr>
                    <th>Class Name</th><th>Trainer</th><th>Day</th><th>Time</th>
                    <th>Duration</th><th>Difficulty</th><th>Capacity</th>
                    <th>Bookings</th><th>Remaining</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
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
                                onclick="document.getElementById('edit-<?= $c['id'] ?>').style.display='block';this.style.display='none';">
                            Edit
                        </button>
                        <a href="classes.php?delete=<?= $c['id'] ?>"
                           onclick="return confirm('Delete this class?')"
                           class="btn-delete" style="text-decoration:none;padding:5px 10px;">Delete</a>
                    </td>
                </tr>
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
