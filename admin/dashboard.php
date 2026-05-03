<?php
session_start();
require '../includes/db.php';

// Vérifier que c'est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); exit;
}

// Stats depuis la BD
$total_members = $pdo->query("SELECT COUNT(*) FROM users WHERE role='member'")->fetchColumn();
$active_subs = $pdo->query("
    SELECT COUNT(*) FROM memberships m
    JOIN users u ON m.user_id = u.id
    WHERE m.status = 'active' AND u.status = 'active'
")->fetchColumn();
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

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ── Add member ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    if (($_POST['csrf_token'] ?? '') === $csrf_token) {
        $fname = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email']     ?? '');
        $phone = trim($_POST['phone']     ?? '');
        $plan  = (int)($_POST['plan_id']  ?? 0);
        $start = $_POST['start_date']     ?? date('Y-m-d');
        if ($fname && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $ex = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $ex->execute([$email]);
            if (!$ex->fetch()) {
                $hash = password_hash('Shefit2026!', PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (full_name,email,password_hash,phone) VALUES (?,?,?,?)")
                    ->execute([$fname, $email, $hash, $phone]);
                $uid = $pdo->lastInsertId();
                if ($plan) {
                    $pr = $pdo->prepare("SELECT duration_months FROM plans WHERE id=?");
                    $pr->execute([$plan]);
                    $dur = (int)($pr->fetchColumn() ?: 1);
                    $end = date('Y-m-d', strtotime("+{$dur} month", strtotime($start)));
                    $pdo->prepare("INSERT INTO memberships (user_id,plan_id,start_date,end_date,status) VALUES (?,?,?,?,'active')")
                        ->execute([$uid, $plan, $start, $end]);
                }
            }
        }
    }
    header("Location: dashboard.php"); exit;
}

// ── Edit member ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_member'])) {
    if (($_POST['csrf_token'] ?? '') === $csrf_token) {
        $mid   = (int)$_POST['member_id'];
        $fname = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone']     ?? '');
        $email = trim($_POST['email']     ?? '');
        if ($fname && $email) {
            $pdo->prepare("UPDATE users SET full_name=?, phone=?, email=? WHERE id=? AND role='member'")
                ->execute([$fname, $phone, $email, $mid]);
        }
    }
    header("Location: dashboard.php"); exit;
}

// ── Assign / extend subscription ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_plan'])) {
    if (($_POST['csrf_token'] ?? '') === $csrf_token) {
        $mid   = (int)$_POST['member_id'];
        $pid   = (int)$_POST['plan_id'];
        $start = $_POST['start_date'] ?? date('Y-m-d');
        $pr    = $pdo->prepare("SELECT duration_months FROM plans WHERE id=?");
        $pr->execute([$pid]);
        $dur = (int)($pr->fetchColumn() ?: 1);
        $end = date('Y-m-d', strtotime("+{$dur} month", strtotime($start)));
        $pdo->prepare("UPDATE memberships SET status='cancelled' WHERE user_id=? AND status='active'")
            ->execute([$mid]);
        $pdo->prepare("INSERT INTO memberships (user_id,plan_id,start_date,end_date,status) VALUES (?,?,?,?,'active')")
            ->execute([$mid, $pid, $start, $end]);
    }
    header("Location: dashboard.php"); exit;
}

// Filtres + pagination membres
$filter_plan   = $_GET['filter_plan']   ?? 'All';
$filter_status = $_GET['filter_status'] ?? 'All';
$search_member = trim($_GET['search_member'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 10;
$offset        = ($page - 1) * $per_page;

$where  = "WHERE u.role = 'member'";
$params = [];
if ($filter_plan !== 'All')   { $where .= " AND p.name = ?";   $params[] = $filter_plan; }
if ($filter_status !== 'All') { $where .= " AND u.status = ?";  $params[] = $filter_status; }
if ($search_member !== '') {
    $where .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search_member%";
    $params[] = "%$search_member%";
}

$cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM users u
    LEFT JOIN memberships m ON m.user_id=u.id AND m.status='active'
    LEFT JOIN plans p ON m.plan_id=p.id $where");
$cnt_stmt->execute($params);
$total_rows  = (int)$cnt_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));

$stmt = $pdo->prepare("
    SELECT u.id, u.full_name AS name, u.email, u.phone,
           u.created_at AS date, u.status,
           COALESCE(p.name,'None') AS plan,
           m.id AS membership_id
    FROM users u
    LEFT JOIN memberships m ON m.user_id=u.id AND m.status='active'
    LEFT JOIN plans p ON m.plan_id=p.id
    $where
    ORDER BY u.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$membersFromDB = $stmt->fetchAll(PDO::FETCH_ASSOC);
$allPlans = $pdo->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll();

// Pour localStorage (admin-script.js)
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

    <!-- Members Management – PHP pur depuis MySQL -->
    <section class="activity-dashboard">
        <h2>Members (<span id="visible-count"><?= $total_rows ?></span>)</h2>

        <!-- Live Search + Filtres JS (instantané, sans rechargement) -->
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:15px;align-items:center;">
            <input type="text" id="searchMember"
                   placeholder="🔍 Search by name or email"
                   style="padding:8px 12px;border:1px solid #ccc;border-radius:6px;min-width:220px;">
            <select id="filterPlan" style="padding:8px;border:1px solid #ccc;border-radius:6px;">
                <option value="All">All Plans</option>
                <?php foreach ($allPlans as $ap): ?>
                <option value="<?= htmlspecialchars($ap['name']) ?>">
                    <?= htmlspecialchars($ap['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select id="filterStatus" style="padding:8px;border:1px solid #ccc;border-radius:6px;">
                <option value="All">All Status</option>
                <option value="active">Active</option>
                <option value="banned">Banned</option>
            </select>
            
            <button onclick="document.getElementById('searchMember').value='';document.getElementById('filterPlan').value='All';document.getElementById('filterStatus').value='All';filterMembers();"
                    style="background:#415A77;color:white;padding:8px 12px;border:none;border-radius:6px;cursor:pointer;">Clear</button>
        </div>

        <!-- Bouton Add Member -->
        <button onclick="document.getElementById('add-member-form').style.display='block';this.style.display='none';"
                style="background:#6b8e23;color:white;padding:9px 18px;border:none;border-radius:6px;cursor:pointer;margin-bottom:15px;">
            + Add Member
        </button>

        <!-- Formulaire Add Member -->
        <div id="add-member-form" style="display:none;background:#f0fff0;padding:15px;border-radius:10px;border:1px solid #90EE90;max-width:640px;margin-bottom:20px;">
            <h3 style="margin-top:0;color:#4a7c10;">New Member</h3>
            <p style="font-size:0.85rem;color:#555;">Default password: <strong>Shefit2026!</strong> (member can change it in profile)</p>
            <form method="POST" action="dashboard.php">
                <input type="hidden" name="add_member" value="1">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="text"  name="full_name" placeholder="Full Name *" required
                       style="width:98%;padding:8px;margin:4px 0;border:1px solid #ccc;border-radius:5px;">
                <input type="email" name="email" placeholder="Email *" required
                       style="width:48%;padding:8px;margin:4px 1%;border:1px solid #ccc;border-radius:5px;">
                <input type="tel"   name="phone" placeholder="Phone"
                       style="width:48%;padding:8px;margin:4px 1%;border:1px solid #ccc;border-radius:5px;">
                <select name="plan_id" style="width:48%;padding:8px;margin:4px 1%;border:1px solid #ccc;border-radius:5px;">
                    <option value="">No plan</option>
                    <?php foreach ($allPlans as $ap): ?>
                    <option value="<?= $ap['id'] ?>"><?= htmlspecialchars($ap['name']) ?> – <?= number_format($ap['price']) ?> DZD</option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="start_date" value="<?= date('Y-m-d') ?>"
                       style="width:48%;padding:8px;margin:4px 1%;border:1px solid #ccc;border-radius:5px;">
                <br>
                <button type="submit"
                        style="background:#6b8e23;color:white;padding:9px 18px;border:none;border-radius:6px;cursor:pointer;margin-top:8px;">
                    Save Member
                </button>
                <button type="button"
                        onclick="document.getElementById('add-member-form').style.display='none';"
                        style="background:#ccc;padding:9px 14px;border:none;border-radius:6px;cursor:pointer;margin-left:5px;">
                    Cancel
                </button>
            </form>
        </div>

        <!-- Tableau membres -->
        <table>
            <thead>
                <tr>
                    <th>Name</th><th>Email</th><th>Phone</th><th>Plan</th>
                    <th>Registered</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($membersFromDB)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#888;padding:20px;">No members found.</td></tr>
                <?php endif; ?>
                <?php foreach ($membersFromDB as $m): ?>
                <tr class="member-row"
                    data-name="<?= strtolower(htmlspecialchars($m['name'])) ?>"
                    data-email="<?= strtolower(htmlspecialchars($m['email'])) ?>"
                    data-plan="<?= htmlspecialchars($m['plan']) ?>"
                    data-status="<?= htmlspecialchars($m['status'] ?? 'active') ?>">
                    <td><?= htmlspecialchars($m['name']) ?></td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                    <td><?= htmlspecialchars($m['phone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($m['plan']) ?></td>
                    <td><?= date('d/m/Y', strtotime($m['date'])) ?></td>
                    <td>
                        <?php if (($m['status'] ?? 'active') === 'banned'): ?>
                            <span style="color:#e74c3c;font-weight:bold;">⛔ Banned</span>
                        <?php else: ?>
                            <span style="color:green;font-weight:bold;">✓ Active</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap;">
                        <button onclick="document.getElementById('edit-m-<?= $m['id'] ?>').style.display='block';this.style.display='none';"
                                style="background:#4a90e2;color:white;padding:4px 9px;border:none;border-radius:5px;cursor:pointer;font-size:13px;margin-right:3px;">
                            Edit
                        </button>
                        <?php if (($m['status'] ?? 'active') === 'banned'): ?>
                            <a href="dashboard.php?unban=<?= $m['id'] ?>"
                               style="background:#6b8e23;color:white;padding:4px 9px;border-radius:5px;text-decoration:none;font-size:13px;margin-right:3px;display:inline-block;">Unban</a>
                        <?php else: ?>
                            <a href="dashboard.php?ban=<?= $m['id'] ?>"
                               onclick="return confirm('Ban <?= htmlspecialchars(addslashes($m['name'])) ?>?')"
                               style="background:#e67e22;color:white;padding:4px 9px;border-radius:5px;text-decoration:none;font-size:13px;margin-right:3px;display:inline-block;">Ban</a>
                        <?php endif; ?>
                        <a href="dashboard.php?delete_member=<?= $m['id'] ?>"
                           onclick="return confirm('Delete <?= htmlspecialchars(addslashes($m['name'])) ?> permanently?')"
                           style="background:#e74c3c;color:white;padding:4px 9px;border-radius:5px;text-decoration:none;font-size:13px;display:inline-block;">Delete</a>
                    </td>
                </tr>
                <!-- Edit inline -->
                <tr id="edit-m-<?= $m['id'] ?>" style="display:none;background:#f0f6ff;">
                    <td colspan="7" style="padding:12px;">
                        <strong>Edit Member</strong>
                        <form method="POST" action="dashboard.php" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                            <input type="hidden" name="edit_member"  value="1">
                            <input type="hidden" name="member_id"   value="<?= $m['id'] ?>">
                            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="text"  name="full_name" value="<?= htmlspecialchars($m['name']) ?>" required
                                   placeholder="Full Name" style="padding:6px;border:1px solid #ccc;border-radius:4px;">
                            <input type="email" name="email" value="<?= htmlspecialchars($m['email']) ?>" required
                                   placeholder="Email" style="padding:6px;border:1px solid #ccc;border-radius:4px;">
                            <input type="tel"   name="phone" value="<?= htmlspecialchars($m['phone'] ?? '') ?>"
                                   placeholder="Phone" style="padding:6px;border:1px solid #ccc;border-radius:4px;width:120px;">
                            <button type="submit" style="background:#4a90e2;color:white;padding:6px 14px;border:none;border-radius:5px;cursor:pointer;">Save</button>
                            <button type="button" onclick="this.closest('tr').style.display='none';"
                                    style="background:#ccc;padding:6px 12px;border:none;border-radius:5px;cursor:pointer;">Cancel</button>
                        </form>
                        <details style="margin-top:10px;">
                            <summary style="cursor:pointer;color:#415A77;font-weight:bold;">📋 Assign / Extend Subscription</summary>
                            <form method="POST" action="dashboard.php" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                                <input type="hidden" name="assign_plan" value="1">
                                <input type="hidden" name="member_id"  value="<?= $m['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <select name="plan_id" required style="padding:6px;border:1px solid #ccc;border-radius:4px;">
                                    <?php foreach ($allPlans as $ap): ?>
                                    <option value="<?= $ap['id'] ?>"><?= htmlspecialchars($ap['name']) ?> (<?= number_format($ap['price']) ?> DZD)</option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="date" name="start_date" value="<?= date('Y-m-d') ?>"
                                       style="padding:6px;border:1px solid #ccc;border-radius:4px;">
                                <button type="submit" style="background:#6b8e23;color:white;padding:6px 14px;border:none;border-radius:5px;cursor:pointer;">Assign</button>
                            </form>
                        </details>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div style="display:flex;gap:6px;margin-top:15px;flex-wrap:wrap;">
            <?php for ($pg = 1; $pg <= $total_pages; $pg++): ?>
            <a href="dashboard.php?page=<?= $pg ?>&filter_plan=<?= urlencode($filter_plan) ?>&filter_status=<?= urlencode($filter_status) ?>&search_member=<?= urlencode($search_member) ?>"
               style="padding:6px 12px;border-radius:5px;text-decoration:none;
                      background:<?= $pg===$page?'#415A77':'#ddd' ?>;
                      color:<?= $pg===$page?'white':'#333' ?>;">
                <?= $pg ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- Chart – identique à ton HTML -->
    <section class="chart-section">
        <h2>Members by Plan</h2>
        <div class="chart">
            <div class="bar bronze" id="bar-bronze"
                 data-count="<?= $planCounts['Bronze'] ?>"
                 style="background-color: #cd7f32; height: <?= ($planCounts['Bronze']/$max*200) ?>px">
                <span>Bronze (<?= $planCounts['Bronze'] ?>)</span>
            </div>
            <div class="bar silver" id="bar-silver"
                 data-count="<?= $planCounts['Silver'] ?>"
                 style="background-color: #c0c0c0; height: <?= ($planCounts['Silver']/$max*200) ?>px">
                <span>Silver (<?= $planCounts['Silver'] ?>)</span>
            </div>
            <div class="bar gold" id="bar-gold"
                 data-count="<?= $planCounts['Gold'] ?>"
                 style="background-color: #ffd700; height: <?= ($planCounts['Gold']/$max*200) ?>px">
                <span>Gold (<?= $planCounts['Gold'] ?>)</span>
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

<!-- Live search + filter JS sur les lignes PHP du tableau -->
<script>
function filterMembers() {
    const search = (document.getElementById('searchMember').value || '').toLowerCase().trim();
    const plan   = document.getElementById('filterPlan').value;
    const status = document.getElementById('filterStatus').value;

    const rows      = document.querySelectorAll('.member-row');
    let visible     = 0;
    let activeSubs  = 0;
    const planCount = {};

    rows.forEach(row => {
        const name  = row.dataset.name   || '';
        const email = row.dataset.email  || '';
        const rPlan = row.dataset.plan   || '';
        const rStat = row.dataset.status || 'active';

        const matchSearch = !search || name.includes(search) || email.includes(search);
        const matchPlan   = plan   === 'All' || rPlan === plan;
        const matchStatus = status === 'All' || rStat === status;

        const editRow = row.nextElementSibling;

        if (matchSearch && matchPlan && matchStatus) {
            row.style.display = '';
            visible++;
            if (rStat === 'active') activeSubs++;
            planCount[rPlan] = (planCount[rPlan] || 0) + 1;
        } else {
            row.style.display = 'none';
            if (editRow && editRow.id && editRow.id.startsWith('edit-m-')) {
                editRow.style.display = 'none';
            }
        }
    });

    // Mise à jour compteur tableau
    const countEl = document.getElementById('visible-count');
    const badgeEl = document.getElementById('member-count');
    if (countEl) countEl.textContent = visible;
    if (badgeEl) badgeEl.textContent = visible + ' member(s)';

    // Mise à jour Summary Stats dynamique
    const isFiltered = search !== '' || plan !== 'All' || status !== 'All';

    const membDash = document.getElementById('memb-dash');
    const subDash  = document.getElementById('sub-dash');
    const revDash  = document.getElementById('rev-dash');

    if (membDash) membDash.textContent = isFiltered
        ? `Total Members: ${visible}`
        : membDash.dataset.original || membDash.textContent;

    if (subDash) subDash.textContent = isFiltered
        ? `Active Subscriptions: ${activeSubs}`
        : subDash.dataset.original || subDash.textContent;

    if (revDash && isFiltered) {
        const popular = Object.keys(planCount).sort((a,b) => planCount[b] - planCount[a])[0] || '-';
        revDash.textContent = `Most Popular Plan: ${popular}`;
    } else if (revDash && !isFiltered) {
        revDash.textContent = revDash.dataset.original || revDash.textContent;
    }

    // Update chart bars from filtered data
    const chartPlans = { Bronze: 0, Silver: 0, Gold: 0 };
    if (isFiltered) {
        document.querySelectorAll('.member-row').forEach(row => {
            if (row.style.display !== 'none') {
                const p = row.dataset.plan || '';
                if (chartPlans[p] !== undefined) chartPlans[p]++;
            }
        });
    } else {
        // Restore original PHP counts from data-count
        ['Bronze','Silver','Gold'].forEach(p => {
            const bar = document.getElementById('bar-' + p.toLowerCase());
            if (bar) chartPlans[p] = parseInt(bar.dataset.count) || 0;
        });
    }
    const chartMax = Math.max(...Object.values(chartPlans), 1);
    ['bronze','silver','gold'].forEach(p => {
        const bar = document.getElementById('bar-' + p);
        const key = p.charAt(0).toUpperCase() + p.slice(1);
        if (bar) {
            bar.style.height = (chartPlans[key] / chartMax * 200) + 'px';
            bar.querySelector('span').textContent = key + ' (' + chartPlans[key] + ')';
        }
    });
}

// Sauvegarder les valeurs originales PHP au chargement
document.addEventListener('DOMContentLoaded', function() {
    ['memb-dash','sub-dash','class-dash','rev-dash'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.dataset.original = el.textContent;
    });
});

document.getElementById('searchMember').addEventListener('input',  filterMembers);
document.getElementById('filterPlan').addEventListener('change',   filterMembers);
document.getElementById('filterStatus').addEventListener('change', filterMembers);
</script>

<!-- Injection des membres MySQL dans localStorage avant admin-script.js -->
<script>
    localStorage.setItem('shefit_members', JSON.stringify(<?= $membersJson ?>));
</script>
<!-- admin-script.js intact – gère tout le localStorage comme avant -->\n<script src="../admin-script.js"></script>
</body>
</html>
