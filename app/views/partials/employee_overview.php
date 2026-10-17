<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth('employee');
$pdo = Database::pdo();
$user = current_user();

// Get employee-specific statistics
$pending_leaves = $pdo->prepare("SELECT COUNT(*) FROM leaves WHERE user_id = ? AND status='pending'");
$pending_leaves->execute([$user['id']]);
$pending_leaves_count = $pending_leaves->fetchColumn();

$approved_leaves = $pdo->prepare("SELECT SUM(days_count) FROM leaves WHERE user_id = ? AND status='approved' AND YEAR(start_date) = YEAR(CURDATE())");
$approved_leaves->execute([$user['id']]);
$approved_leaves_count = $approved_leaves->fetchColumn() ?: 0;

$active_projects = $pdo->prepare("
    SELECT COUNT(DISTINCT p.id) FROM projects p
    JOIN project_assignments pa ON p.id = pa.project_id
    WHERE (pa.user_id = ? OR pa.team_id = (SELECT team_id FROM users WHERE id = ?)) AND p.status = 'active'
");
$active_projects->execute([$user['id'], $user['id']]);
$active_projects_count = $active_projects->fetchColumn();

$tasks_today = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND date = CURDATE()");
$tasks_today->execute([$user['id']]);
$tasks_today_count = $tasks_today->fetchColumn();

?>

<div class="grid-4" style="margin-bottom: 24px;">
	<div class="stat-card">
		<div class="stat-card__title">Active Projects</div>
		<div class="stat-card__value"><?php echo (int)$active_projects_count; ?></div>
	</div>
	<div class="stat-card">
		<div class="stat-card__title">Tasks for Today</div>
		<div class="stat-card__value"><?php echo (int)$tasks_today_count; ?></div>
	</div>
	<div class="stat-card">
		<div class="stat-card__title">Pending Leaves</div>
		<div class="stat-card__value"><?php echo (int)$pending_leaves_count; ?></div>
	</div>
	<div class="stat-card">
		<div class="stat-card__title">Leaves Taken (Year)</div>
		<div class="stat-card__value"><?php echo (int)$approved_leaves_count; ?></div>
	</div>
</div>

<div class="row cols-2">
    <div class="card">
        <div class="card__title">Quick Actions</div>
        <div style="display: grid; gap: 12px;">
            <a href="dashboard.php?tab=attendance" class="btn" style="text-align: center; text-decoration: none;">
                📅 Mark Attendance
            </a>
            <a href="dashboard.php?tab=tasks" class="btn secondary" style="text-align: center; text-decoration: none;">
                ✅ View My Tasks
            </a>
            <a href="dashboard.php?tab=leaves" class="btn secondary" style="text-align: center; text-decoration: none;">
                🏖️ Apply for Leave
            </a>
            <a href="dashboard.php?tab=ideas" class="btn secondary" style="text-align: center; text-decoration: none;">
                💡 Submit an Idea
            </a>
        </div>
    </div>
    <div class="card">
        <div class="card__title">Welcome!</div>
        <p>This is your personal dashboard. Use the quick links to navigate to your most common tasks, and use the sidebar to explore all available features.</p>
        <p>Your progress and contributions are valuable to the team. Keep up the great work!</p>
    </div>
</div>