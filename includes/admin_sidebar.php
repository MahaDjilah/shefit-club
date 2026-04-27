<?php
// includes/admin_sidebar.php – Sidebar commune à toutes les pages admin
// Nécessite $pdo déjà initialisé et session démarrée
$_unread_count = isset($pdo)
    ? (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE read_status=0")->fetchColumn()
    : 0;

// Détecter la page courante pour marquer l'entrée active
$_current_page = basename($_SERVER['PHP_SELF']);
?>
<aside style="width:200px;padding:10px;" class="nav-dashboard">
    <nav>
        <h3>Menu</h3>
        <ul>
            <li>
                <a href="dashboard.php" <?= $_current_page === 'dashboard.php' ? 'style="font-weight:bold;"' : '' ?>>
                    Members
                </a>
            </li>
            <li>
                <a href="classes.php" <?= $_current_page === 'classes.php' ? 'style="font-weight:bold;"' : '' ?>>
                    Classes
                </a>
            </li>
            <li>
                <a href="plans.php" <?= $_current_page === 'plans.php' ? 'style="font-weight:bold;"' : '' ?>>
                    Plans
                </a>
            </li>
            <li>
                <a href="messages.php" <?= $_current_page === 'messages.php' ? 'style="font-weight:bold;"' : '' ?>>
                    Messages
                    <?php if ($_unread_count > 0): ?>
                        <span style="background:red;color:white;border-radius:50%;padding:1px 6px;font-size:11px;margin-left:4px;">
                            <?= $_unread_count ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="reports.php" <?= $_current_page === 'reports.php' ? 'style="font-weight:bold;"' : '' ?>>
                    Reports
                </a>
            </li>
            <li><a href="../home.php">View Site</a></li>
            <li><a href="logout.php" id="logout-dash">Logout</a></li>
        </ul>
    </nav>
</aside>
