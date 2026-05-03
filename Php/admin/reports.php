<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); exit;
}

// Export CSV membres
if (isset($_GET['export_members'])) {
    $members = $pdo->query("
        SELECT u.full_name, u.email, u.phone, u.created_at, p.name AS plan
        FROM users u
        LEFT JOIN memberships m ON m.user_id=u.id AND m.status='active'
        LEFT JOIN plans p ON m.plan_id=p.id
        WHERE u.role='member'
        ORDER BY u.created_at DESC
    ")->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="shefit_members_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Full Name', 'Email', 'Phone', 'Registered', 'Plan']);
    foreach ($members as $m) {
        fputcsv($out, [$m['full_name'], $m['email'], $m['phone'] ?? '', $m['created_at'], $m['plan'] ?? 'None']);
    }
    fclose($out);
    exit;
}

// Export subscription summary as printable HTML (Bug 7)
if (isset($_GET['export_subscriptions'])) {
    $subs = $pdo->query("
        SELECT u.full_name, u.email, p.name AS plan_name, p.price,
               m.start_date, m.end_date, m.status
        FROM memberships m
        JOIN users u ON m.user_id = u.id
        JOIN plans p ON m.plan_id = p.id
        ORDER BY m.start_date DESC
    ")->fetchAll();

    $total_active   = 0;
    $total_revenue  = 0;
    foreach ($subs as $s) {
        if ($s['status'] === 'active') {
            $total_active++;
            $total_revenue += $s['price'];
        }
    }

    $generated = date('Y-m-d H:i');
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><head>
    <meta charset="UTF-8">
    <title>Subscription Summary – SheFit Club</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; color: #222; }
        h1 { color: #415A77; }
        h2 { color: #6b8e23; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #415A77; color: white; padding: 10px; text-align: left; }
        td { padding: 9px 10px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f5f5f5; }
        .summary-box { display: flex; gap: 30px; margin: 20px 0; }
        .stat { background: #f0f8e0; border-left: 4px solid #6b8e23; padding: 14px 22px; border-radius: 6px; }
        .stat .num { font-size: 2rem; font-weight: bold; color: #415A77; }
        .stat .label { font-size: 0.9rem; color: #555; }
        .badge-active   { color: #276221; font-weight: bold; }
        .badge-expired  { color: #b00020; }
        .badge-cancelled{ color: #888; }
        .print-note { color: #888; font-size: 0.85rem; margin-bottom: 20px; }
        @media print {
            .no-print { display: none; }
            body { margin: 15px; }
        }
    </style>
    </head><body>
    <div class="no-print" style="margin-bottom:20px;">
        <button onclick="window.print()" style="background:#415A77;color:white;padding:10px 20px;border:none;border-radius:6px;cursor:pointer;font-size:15px;">🖨️ Print / Save as PDF</button>
        <button onclick="window.history.back()" style="background:#ccc;padding:10px 20px;border:none;border-radius:6px;cursor:pointer;font-size:15px;margin-left:10px;">← Back</button>
    </div>
    <h1>SheFit Club – Subscription Summary</h1>
    <p class="print-note">Generated on: ' . htmlspecialchars($generated) . '</p>
    <div class="summary-box">
        <div class="stat">
            <div class="num">' . count($subs) . '</div>
            <div class="label">Total Subscriptions</div>
        </div>
        <div class="stat">
            <div class="num">' . $total_active . '</div>
            <div class="label">Active Subscriptions</div>
        </div>
        <div class="stat">
            <div class="num">' . number_format($total_revenue) . ' DZD</div>
            <div class="label">Monthly Revenue (active)</div>
        </div>
    </div>
    <h2>All Subscriptions</h2>
    <table>
        <thead>
            <tr><th>Member</th><th>Email</th><th>Plan</th><th>Price/mo (DZD)</th><th>Start</th><th>End</th><th>Status</th></tr>
        </thead>
        <tbody>';
    foreach ($subs as $s) {
        $badge = match($s['status']) {
            'active'    => 'badge-active',
            'expired'   => 'badge-expired',
            default     => 'badge-cancelled'
        };
        echo '<tr>
            <td>' . htmlspecialchars($s['full_name']) . '</td>
            <td>' . htmlspecialchars($s['email'])     . '</td>
            <td>' . htmlspecialchars($s['plan_name']) . '</td>
            <td>' . number_format($s['price'])        . '</td>
            <td>' . htmlspecialchars($s['start_date']) . '</td>
            <td>' . htmlspecialchars($s['end_date'])   . '</td>
            <td class="' . $badge . '">' . ucfirst($s['status']) . '</td>
        </tr>';
    }
    echo '</tbody></table>
    <p style="margin-top:40px;color:#888;font-size:0.85rem;">&copy; ' . date('Y') . ' SheFit Club — Confidential</p>
    </body></html>';
    exit;
}

$unread = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE read_status=0")->fetchColumn();

// Revenue par mois
$revenue = $pdo->query("
    SELECT DATE_FORMAT(m.start_date,'%Y-%m') AS month,
           SUM(p.price) AS total, COUNT(m.id) AS subs
    FROM memberships m JOIN plans p ON m.plan_id=p.id
    WHERE m.status='active'
    GROUP BY month ORDER BY month DESC LIMIT 12
")->fetchAll();

// Membres par plan
$by_plan = $pdo->query("
    SELECT p.name, COUNT(m.id) AS total
    FROM memberships m JOIN plans p ON m.plan_id=p.id
    WHERE m.status='active' GROUP BY p.name
")->fetchAll();

// Cours populaires
$popular_classes = $pdo->query("
    SELECT c.name, t.name AS trainer, COUNT(cb.id) AS bookings
    FROM classes c
    LEFT JOIN class_bookings cb ON cb.class_id=c.id
    JOIN trainers t ON c.trainer_id=t.id
    GROUP BY c.id ORDER BY bookings DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports – Admin SheFit</title>
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

    <h2 style="margin-bottom:20px;">Reports & Data Export</h2>

    <!-- Export buttons -->
    <div style="margin-bottom:30px;">
        <a href="reports.php?export_members=1"
           style="background:#415A77;color:white;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:bold;margin-right:10px;">
            📥 Export Members CSV
        </a>
        <a href="reports.php?export_subscriptions=1" target="_blank"
           style="background:#6b8e23;color:white;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:bold;">
            🖨️ Export Subscription Summary (Printable)
        </a>
    </div>

    <!-- Revenue report -->
    <section class="activity-dashboard" style="margin-bottom:30px;">
        <h2>Revenue Report (by Month)</h2>
        <table>
            <thead>
                <tr><th>Month</th><th>Subscriptions</th><th>Total Revenue (DZD)</th></tr>
            </thead>
            <tbody>
                <?php foreach ($revenue as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['month']) ?></td>
                        <td><?= $r['subs'] ?></td>
                        <td><?= number_format($r['total']) ?> DZD</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($revenue)): ?>
                    <tr><td colspan="3">No data yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <!-- Members by plan -->
    <section class="activity-dashboard" style="margin-bottom:30px;">
        <h2>Members by Plan</h2>
        <table>
            <thead>
                <tr><th>Plan</th><th>Active Members</th></tr>
            </thead>
            <tbody>
                <?php foreach ($by_plan as $b): ?>
                    <tr><td><?= htmlspecialchars($b['name']) ?></td><td><?= $b['total'] ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($by_plan)): ?>
                    <tr><td colspan="2">No data yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <!-- Class popularity -->
    <section class="activity-dashboard">
        <h2>Class Popularity Report</h2>
        <table>
            <thead>
                <tr><th>Class</th><th>Trainer</th><th>Bookings</th></tr>
            </thead>
            <tbody>
                <?php foreach ($popular_classes as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><?= htmlspecialchars($c['trainer']) ?></td>
                        <td><?= $c['bookings'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

</main>
</div>
</body>
</html>
