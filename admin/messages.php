<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); exit;
}

// Supprimer
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([(int)$_GET['delete']]);
}
// Marquer lu
if (isset($_GET['read'])) {
    $pdo->prepare("UPDATE contact_messages SET read_status=1 WHERE id=?")->execute([(int)$_GET['read']]);
}
// Marquer non lu
if (isset($_GET['unread'])) {
    $pdo->prepare("UPDATE contact_messages SET read_status=0 WHERE id=?")->execute([(int)$_GET['unread']]);
}

$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY submitted_at DESC")->fetchAll();
$unread   = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE read_status=0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages – Admin SheFit</title>
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

<?php require '../includes/admin_sidebar.php'; ?>

<main class="main-dash" style="flex:1;padding:20px;">

    <section class="activity-dashboard">
        <h2>Contact Messages</h2>

        <?php if (empty($messages)): ?>
            <p>No messages yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                        <tr style="background:<?= $msg['read_status'] ? 'white' : '#fffde7' ?>">
                            <td><?= date('d/m/Y H:i', strtotime($msg['submitted_at'])) ?></td>
                            <td><?= htmlspecialchars($msg['name']) ?></td>
                            <td><?= htmlspecialchars($msg['email']) ?></td>
                            <td><?= htmlspecialchars($msg['subject'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(substr($msg['message'], 0, 60)) ?>...</td>
                            <td>
                                <?php if ($msg['read_status']): ?>
                                    <span style="color:green;">✔ Read</span>
                                <?php else: ?>
                                    <span style="color:orange;font-weight:bold;">● Unread</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$msg['read_status']): ?>
                                    <a href="messages.php?read=<?= $msg['id'] ?>"
                                       style="background:#4CAF50;color:white;padding:4px 8px;border-radius:4px;text-decoration:none;font-size:12px;">
                                        Mark Read
                                    </a>
                                <?php else: ?>
                                    <a href="messages.php?unread=<?= $msg['id'] ?>"
                                       style="background:#f39c12;color:white;padding:4px 8px;border-radius:4px;text-decoration:none;font-size:12px;">
                                        Mark Unread
                                    </a>
                                <?php endif; ?>
                                <a href="messages.php?delete=<?= $msg['id'] ?>"
                                   onclick="return confirm('Delete this message?')"
                                   style="background:#e74c3c;color:white;padding:4px 8px;border-radius:4px;text-decoration:none;font-size:12px;margin-left:4px;">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

</main>
</div>
</body>
</html>
