<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/db.php';
require_auth();
$user = current_user();
$config = require __DIR__ . '/../app/config.php';
$is_admin = in_array($user['role'], ['admin', 'boss'], true);
$tab = $_GET['tab'] ?? ($is_admin ? 'admin_overview' : 'attendance');

// Handle leave request form submission before any HTML is output
if ($tab === 'leaves' && !$is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$pdo = Database::pdo();
	$type = trim($_POST['type'] ?? '');
	$start = $_POST['start_date'] ?? '';
	$end = $_POST['end_date'] ?? '';
	$reason = trim($_POST['reason'] ?? '');
	if ($type && $start && $end && $user) {
		$stmt = $pdo->prepare('INSERT INTO leaves (user_id, type, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)');
		$stmt->execute([$user['id'], $type, $start, $end, $reason]);
		$leave_id = $pdo->lastInsertId();

		// Notify all admins and bosses
		$admins_stmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'boss')");
		$admins = $admins_stmt->fetchAll(PDO::FETCH_COLUMN);
		$notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'leave_request', 'New Leave Request', ?)");
		foreach ($admins as $admin_id) {
			$notif_stmt->execute([$admin_id, "{$user['name']} has requested {$type} leave."]);
		}
		header('Location: dashboard.php?tab=leaves'); exit;
	}
}

// Handle tasks form submission
if ($tab === 'tasks' && !$is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Database::pdo();
    if (($_POST['action'] ?? '') === 'add_task') {
        $title = trim($_POST['title'] ?? '');
        if ($title) {
            $stmt = $pdo->prepare('INSERT INTO tasks (user_id, title, date) VALUES (?, ?, CURDATE())');
            $stmt->execute([$user['id'], $title]);
        }
        header('Location: dashboard.php?tab=tasks'); exit;
    }
    if (($_POST['action'] ?? '') === 'mark_done') {
		$id = (int)($_POST['id'] ?? 0);
		$stmt = $pdo->prepare('UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?');
        $stmt->execute(['done', $id, $user['id']]);
		header('Location: dashboard.php?tab=tasks'); exit;
	}
}

// Handle ideas form submission
if ($tab === 'ideas' && !$is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Database::pdo();
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($title && $description) {
        $stmt = $pdo->prepare('INSERT INTO ideas (user_id, title, description, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$user['id'], $title, $description]);
        header('Location: dashboard.php?tab=ideas');
        exit;
    }
}

// Handle transfer request form submission
if ($tab === 'transfers' && !$is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Database::pdo();
    $to_team_id = (int)($_POST['team_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    // Get the user's current team_id from the users table
    $user_stmt = $pdo->prepare('SELECT team_id FROM users WHERE id = ?');
    $user_stmt->execute([$user['id']]);
    $from_team_id = $user_stmt->fetchColumn();

    if ($to_team_id && $reason && $from_team_id !== null) {
        $stmt = $pdo->prepare('INSERT INTO transfers (user_id, current_team_id, target_team_id, reason, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$user['id'], $from_team_id, $to_team_id, $reason]);
        header('Location: dashboard.php?tab=transfers');
        exit;
    }
}

// Handle admin transfer request form submission
if ($tab === 'transfers' && $is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Database::pdo();
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    if ($id && in_array($status, ['approved', 'rejected'], true)) {
        $pdo->prepare('UPDATE transfers SET status = ?, admin_comment = ? WHERE id = ?')->execute([$status, $comment, $id]);

        // Create notification
        $transfer_info_stmt = $pdo->prepare('SELECT user_id, target_team_id FROM transfers WHERE id = ?');
        $transfer_info_stmt->execute([$id]);
        $transfer_info = $transfer_info_stmt->fetch();
        if ($transfer_info) {
            $notif_type = 'transfer_' . $status;
            $notif_title = 'Transfer Request ' . ucfirst($status);
            $notif_message = "Your request to transfer has been {$status}.";
            if ($comment) {
                $notif_message .= " Admin comment: \"{$comment}\"";
            }
            $pdo->prepare('INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)')->execute([$transfer_info['user_id'], $notif_type, $notif_title, $notif_message]);
        }
    }
    header('Location: dashboard.php?tab=transfers'); exit;
}

// Handle admin team management form submission
if ($tab === 'teams' && $is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Database::pdo();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_team') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($name) {
            $pdo->prepare('INSERT INTO teams (name, description) VALUES (?, ?)')->execute([$name, $description]);
        }
    }

    if ($action === 'update_team') {
        $team_id = (int)($_POST['team_id'] ?? 0);
        $manager_id = (int)($_POST['manager_id'] ?? 0) ?: null;
        $members = $_POST['members'] ?? [];

        if ($team_id) {
            // Update team manager
            $pdo->prepare('UPDATE teams SET manager_id = ? WHERE id = ?')->execute([$manager_id, $team_id]);

            // Un-assign all previous members of this team
            $pdo->prepare('UPDATE users SET team_id = NULL WHERE team_id = ?')->execute([$team_id]);

            // Assign new members
            if (!empty($members)) {
                $in_clause = implode(',', array_fill(0, count($members), '?'));
                $params = array_merge([$team_id], $members);
                $pdo->prepare("UPDATE users SET team_id = ? WHERE id IN ($in_clause)")->execute($params);
            }
        }
    }
    header('Location: dashboard.php?tab=teams');
    exit;
}

// Handle admin project management form submission
if ($tab === 'projects' && $is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Database::pdo();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_project') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $start_date = empty($_POST['start_date']) ? null : $_POST['start_date'];
        $end_date = empty($_POST['end_date']) ? null : $_POST['end_date'];
        if ($name) {
            $pdo->prepare('INSERT INTO projects (name, description, start_date, end_date) VALUES (?, ?, ?, ?)')
                ->execute([$name, $desc, $start_date, $end_date]);
        }
    }
    if ($action === 'assign_project') {
        $pid = (int)($_POST['project_id'] ?? 0);
        if ($pid) {
            $pdo->prepare('DELETE FROM project_assignments WHERE project_id = ?')->execute([$pid]);
            foreach (($_POST['users'] ?? []) as $uid) {
                $pdo->prepare('INSERT INTO project_assignments (project_id, user_id) VALUES (?, ?)')->execute([$pid, (int)$uid]);
            }
            foreach (($_POST['teams'] ?? []) as $tid) {
                $pdo->prepare('INSERT INTO project_assignments (project_id, team_id) VALUES (?, ?)')->execute([$pid, (int)$tid]);
            }
        }
    }
    if ($action === 'add_step') {
        $pid = (int)($_POST['project_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $assigned_to = (int)($_POST['assigned_to'] ?? 0) ?: null;
        if ($pid && $title) {
            $pdo->prepare('INSERT INTO project_steps (project_id, title, assigned_to) VALUES (?, ?, ?)')->execute([$pid, $title, $assigned_to]);
        }
    }
    header('Location: dashboard.php?tab=projects'); exit;
}

// Handle admin employee actions (remove employee)
if ($tab === 'employees' && $is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Database::pdo();
    $action = $_POST['action'] ?? '';
    if ($action === 'remove_employee') {
        $removeId = (int)($_POST['id'] ?? 0);
        if (!isset($_SESSION)) session_start();
        if (!$removeId) {
            $_SESSION['employee_message'] = 'error:Invalid employee ID';
        } elseif ($removeId === $user['id']) {
            $_SESSION['employee_message'] = 'error:You cannot remove your own account';
        } else {
            // Ensure the user to delete is an employee (not admin/boss)
            $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
            $stmt->execute([$removeId]);
            $role = $stmt->fetchColumn();
            if (!$role) {
                $_SESSION['employee_message'] = 'error:Employee not found';
            } elseif ($role !== 'employee') {
                $_SESSION['employee_message'] = 'error:Only users with role "employee" can be removed via this action';
            } else {
                // Delete the user (foreign keys will cascade where defined)
                try {
                    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$removeId]);
                    $_SESSION['employee_message'] = 'success:Employee removed successfully';
                } catch (Throwable $e) {
                    $_SESSION['employee_message'] = 'error:Failed to remove employee: ' . $e->getMessage();
                }
            }
        }
        header('Location: dashboard.php?tab=employees'); exit;
    }
}

// Handle admin ideas form submission
if ($tab === 'ideas' && $is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Database::pdo();
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $feedback = trim($_POST['feedback'] ?? '');
    if ($id && in_array($status, ['approved','rejected'], true)) {
        $pdo->prepare('UPDATE ideas SET status = ?, admin_feedback = ? WHERE id = ?')->execute([$status, $feedback, $id]);

        // Create notification
        $idea_info_stmt = $pdo->prepare('SELECT user_id, title FROM ideas WHERE id = ?');
        $idea_info_stmt->execute([$id]);
        $idea_info = $idea_info_stmt->fetch();
        if ($idea_info) {
            $notif_type = 'idea_' . $status;
            $notif_title = 'Idea Submission ' . ucfirst($status);
            $notif_message = "Your idea '{$idea_info['title']}' has been {$status}.";
            if ($feedback) {
                $notif_message .= " Admin feedback: \"{$feedback}\"";
            }
            $pdo->prepare('INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)')->execute([$idea_info['user_id'], $notif_type, $notif_title, $notif_message]);
        }
    }
    header('Location: dashboard.php?tab=ideas'); exit;
}

// Handle admin leave request form submission
if ($tab === 'leaves' && $is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Database::pdo();
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    if ($id && in_array($status, ['approved', 'rejected'], true)) {
        $stmt = $pdo->prepare('UPDATE leaves SET status = ?, admin_comment = ? WHERE id = ?');
        $stmt->execute([$status, $comment, $id]);

        // Create notification
        $leave_info_stmt = $pdo->prepare('SELECT user_id, type, start_date, end_date FROM leaves WHERE id = ?');
        $leave_info_stmt->execute([$id]);
        $leave_info = $leave_info_stmt->fetch();

        if ($leave_info) {
            $notif_type = 'leave_' . $status;
            $notif_title = 'Leave Request ' . ucfirst($status);
            $notif_message = "Your {$leave_info['type']} leave request for {$leave_info['start_date']} to {$leave_info['end_date']} has been {$status}.";
            if ($comment) {
                $notif_message .= " Admin comment: \"{$comment}\"";
            }
            $pdo->prepare('INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)')->execute([$leave_info['user_id'], $notif_type, $notif_title, $notif_message]);
        }
    }
    header('Location: dashboard.php?tab=leaves'); exit;
}

// Map tabs to partial files
$employee_partials = [
    'attendance' => 'employee_attendance.php',
	'projects' => 'employee_projects.php',
    'leaves' => 'employee_leaves.php',
    'tasks' => 'employee_tasks.php',
    'ideas' => 'employee_ideas.php',
    'transfers' => 'employee_transfers.php',
    'profile' => 'employee_profile.php',
];

$admin_partials = [
    'admin_overview' => 'admin_overview.php',
    'users' => 'admin_users.php',
    'employees' => 'admin_employees.php',
    'teams' => 'admin_teams.php',
    'projects' => 'admin_projects.php',
    'leaves' => 'admin_leaves.php',
    'ideas' => 'admin_ideas.php',
    'transfers' => 'admin_transfers.php',
    'notifications' => 'admin_notifications.php',
    'reports' => 'admin_reports.php',
    'attendance' => 'admin_attendance.php',
];

$all_tabs = array_merge($employee_partials, $admin_partials);
$current_tab_name = $all_tabs[$tab] ?? 'Dashboard';
$current_tab_name = ucwords(str_replace(['_', '.php'], [' ', ''], $current_tab_name));
?>
<?php
$partial_to_load = ($is_admin ? ($admin_partials[$tab] ?? null) : ($employee_partials[$tab] ?? null)) ?? '404.php';
?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Dashboard - <?php echo htmlspecialchars($config['app_name']); ?></title>
	<link rel="stylesheet" href="assets/style.css" />
	<link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script src="assets/script.js" defer></script>
</head>
<body>
<div class="dashboard-wrapper">
    <div class="dashboard-grid">
    <?php include __DIR__ . '/../app/views/partials/sidebar.php'; ?>
    <div class="dashboard-container">
        <div class="container">
            <header class="main-header">
                <div>
                    <h1>Welcome back, <?php echo htmlspecialchars($user['name'] ?: $user['email']); ?></h1>
                    <p class="subtitle">
                        <?php echo date('l, F j, Y'); ?> &nbsp;&middot;&nbsp; 
                        <span style="text-transform:capitalize"><?php echo htmlspecialchars($user['role']); ?></span>
                    </p>
                </div>
                <a class="btn secondary sm logout-btn" href="logout.php">
                    <svg class="logout-svg" width="16" height="16" viewBox="0 0 256 256" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M120,216a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V40a8,8,0,0,1,8-8h64a8,8,0,0,1,0,16H56V208h56A8,8,0,0,1,120,216Zm109.66-93.66-40-40a8,8,0,0,0-11.32,11.32L204.69,120H112a8,8,0,0,0,0,16h92.69l-26.35,26.34a8,8,0,0,0,11.32,11.32l40-40A8,8,0,0,0,229.66,122.34Z" fill="currentColor"></path>
                    </svg> Logout
                </a>
            </header>
            <main>
                <?php
                $partial_path = __DIR__ . '/../app/views/partials/' . $partial_to_load;
                if (file_exists($partial_path)) {
                    include $partial_path;
                } else {
                    echo "<div class='card'><p>Content not found for tab: " . htmlspecialchars($tab) . "</p></div>";
                }
                ?>
            </main>
        </div>
    </div>
    </div><!-- /.dashboard-grid -->
</div><!-- /.dashboard-wrapper -->
</body>
</html>
