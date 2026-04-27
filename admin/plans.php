<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); exit;
}

$success = '';
$error   = '';

// Supprimer un plan
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM plans WHERE id=?")->execute([(int)$_GET['delete']]);
    $success = "Plan deleted.";
}

// Ajouter un plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_plan'])) {
    $name     = trim($_POST['name']        ?? '');
    $price    = (int)($_POST['price']       ?? 0);
    $duration = (int)($_POST['duration']    ?? 1);
    $desc     = trim($_POST['description'] ?? '');

    if (!$name || !$price) {
        $error = "Name and price are required.";
    } else {
        $pdo->prepare("INSERT INTO plans (name, price, duration_months, description, features) VALUES (?,?,?,?,?)")
            ->execute([$name, $price, $duration, $desc, $desc]);
        $success = "Plan added.";
    }
}

// Modifier un plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_plan'])) {
    $id       = (int)$_POST['plan_id'];
    $name     = trim($_POST['name']        ?? '');
    $price    = (int)($_POST['price']       ?? 0);
    $duration = (int)($_POST['duration']    ?? 1);
    $desc     = trim($_POST['description'] ?? '');

    $pdo->prepare("UPDATE plans SET name=?, price=?, duration_months=?, description=?, features=? WHERE id=?")
        ->execute([$name, $price, $duration, $desc, $desc, $id]);
    $success = "Plan updated.";
}

$plans = $pdo->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SheFit Club - Manage Membership Plans</title>
    <link rel="icon" href="../images/cropped_circle_image (1).png">
    <link rel="stylesheet" href="../style.project.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="plan-head">
    <h1>Membership Plans Management</h1>
    <p>View and manage all membership plans. Add new plans or edit existing ones.</p>
</header>

<?php if ($success): ?><p style="color:green;font-weight:bold;text-align:center;"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<?php if ($error):   ?><p style="color:red;font-weight:bold;text-align:center;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<!-- Existing plans table – identique à ton HTML -->
<section class="table-plan">
    <h2>Existing Plans</h2>
    <table border="1">
        <thead>
            <tr>
                <th>Plan Name</th>
                <th>Price (DZD)</th>
                <th>Duration</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($plans as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= number_format($p['price']) ?></td>
                <td><?= $p['duration_months'] ?> month(s)</td>
                <td><?= htmlspecialchars(substr($p['description'], 0, 60)) ?>...</td>
                <td>
                    <button type="button"
                            onclick="document.getElementById('edit-<?= $p['id'] ?>').style.display='block';this.parentNode.style.display='none';">
                        Edit
                    </button>
                    <a href="plans.php?delete=<?= $p['id'] ?>"
                       onclick="return confirm('Delete this plan?')"
                       style="background:#f44336;color:white;padding:6px 12px;border-radius:5px;text-decoration:none;font-weight:bold;">
                        Delete
                    </a>
                </td>
            </tr>
            <!-- Formulaire edit inline -->
            <tr id="edit-<?= $p['id'] ?>" style="display:none;background:#f0f6ff;">
                <td colspan="5">
                    <form method="POST" style="padding:15px;">
                        <input type="hidden" name="edit_plan" value="1">
                        <input type="hidden" name="plan_id" value="<?= $p['id'] ?>">
                        <label>Name: <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" required style="padding:6px;margin:4px;"></label>
                        <label>Price: <input type="number" name="price" value="<?= $p['price'] ?>" required style="padding:6px;margin:4px;width:100px;"></label>
                        <label>Duration (months): <input type="number" name="duration" value="<?= $p['duration_months'] ?>" style="padding:6px;margin:4px;width:80px;"></label>
                        <br>
                        <label>Description:<br>
                            <textarea name="description" rows="2" style="width:80%;padding:6px;margin:4px;"><?= htmlspecialchars($p['description']) ?></textarea>
                        </label>
                        <br>
                        <button type="submit" style="background:#415A77;color:white;padding:8px 20px;border:none;border-radius:5px;cursor:pointer;">Save</button>
                        <button type="button" onclick="document.getElementById('edit-<?= $p['id'] ?>').style.display='none';"
                                style="background:#ccc;padding:8px 15px;border:none;border-radius:5px;cursor:pointer;margin-left:5px;">Cancel</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<!-- Add New Plan form – identique à ton HTML -->
<section class="new-plan">
    <h2>Add New Plan</h2>
    <form method="POST">
        <input type="hidden" name="add_plan" value="1">

        <label for="plan-name">Plan Name:</label><br>
        <input type="text" id="plan-name" name="name" required><br><br>

        <label for="price">Price (DZD):</label><br>
        <input type="number" id="price" name="price" required><br><br>

        <label for="duration">Duration (months):</label><br>
        <input type="number" id="duration" name="duration" value="1" required><br><br>

        <label for="description">Description:</label><br>
        <textarea id="description" name="description" rows="4" cols="50"
                  placeholder="Describe the plan features"></textarea><br><br>

        <input type="submit" value="Add Plan">
    </form>
</section>

<p class="return-plan">
    <a href="dashboard.php">Return to Dashboard</a>
</p>

</body>
</html>
