<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth();
$user = current_user();
if (!in_array($user['role'], ['admin','boss'], true)) { header('Location: dashboard.php'); exit; }
$pdo = Database::pdo();

$employees = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='employee'")->fetch()['c'] ?? 0;
$teams = $pdo->query("SELECT COUNT(*) c FROM teams")->fetch()['c'] ?? 0;
$projects = $pdo->query("SELECT COUNT(*) c FROM projects")->fetch()['c'] ?? 0;
$att_today = $pdo->query("SELECT COUNT(*) c FROM attendance WHERE date = CURDATE() AND check_in IS NOT NULL")->fetch()['c'] ?? 0;
$pending_leaves = $pdo->query("SELECT COUNT(*) c FROM leaves WHERE status='pending'")->fetch()['c'] ?? 0;
$pending_ideas = $pdo->query("SELECT COUNT(*) c FROM ideas WHERE status='pending'")->fetch()['c'] ?? 0;
$pending_transfers = $pdo->query("SELECT COUNT(*) c FROM transfers WHERE status='pending'")->fetch()['c'] ?? 0;
$unread_notifications = $pdo->query("SELECT COUNT(*) c FROM notifications WHERE is_read=0")->fetch()['c'] ?? 0;

// Get weekly attendance data for the chart
$attendance_query = $pdo->query("
    SELECT 
        CAST(date AS CHAR) as attendance_date, 
        COUNT(*) as count 
    FROM attendance 
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND check_in IS NOT NULL
    GROUP BY date 
    ORDER BY date ASC
");
$db_attendance = $attendance_query->fetchAll(PDO::FETCH_KEY_PAIR);

$chart_data = ['labels' => [], 'data' => []];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_data['labels'][] = date('D', strtotime($date));
    $chart_data['data'][] = (int)($db_attendance[$date] ?? 0);
}
?>
<div class="grid-4" style="margin-bottom: 24px;">
	<div class="stat-card">
		<div class="stat-card__icon stat-card__icon--navy"><svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div>
		<div>
			<div class="stat-card__value"><?php echo (int)$employees; ?></div>
			<div class="stat-card__label">Total Employees</div>
		</div>
	</div>
	<div class="stat-card">
		<div class="stat-card__icon stat-card__icon--teal"><svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12.75c1.63 0 3.07.39 4.24.9 1.08.48 1.76 1.56 1.76 2.73V18H6v-1.61c0-1.18.68-2.26 1.76-2.73 1.17-.52 2.61-.91 4.24-.91M12 11c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4zm0-9c2.21 0 4 1.79 4 4s-1.79 4-4 4-4-1.79-4-4 1.79-4 4-4m0 1.5c-1.38 0-2.5 1.12-2.5 2.5s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5z"/></svg></div>
		<div>
			<div class="stat-card__value"><?php echo (int)$teams; ?></div>
			<div class="stat-card__label">Teams</div>
		</div>
	</div>
	<div class="stat-card">
		<div class="stat-card__icon stat-card__icon--navy"><svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-2 10H6v-2h12v2zm0-4H6V10h12v2z"/></svg></div>
		<div>
			<div class="stat-card__value"><?php echo (int)$projects; ?></div>
			<div class="stat-card__label">Projects</div>
		</div>
	</div>
	<div class="stat-card">
		<div class="stat-card__icon stat-card__icon--green"><svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm4.59-12.42L10 14.17l-2.59-2.58L6 13l4 4 8-8z"/></svg></div>
		<div>
			<div class="stat-card__value"><?php echo (int)$att_today; ?></div>
			<div class="stat-card__label">Attendance</div>
		</div>
	</div>
</div>

<div class="grid-4" style="margin-bottom: 28px;">
	<div class="stat-card">
		<div class="stat-card__icon stat-card__icon--amber"><svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .89-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg></div>
		<div>
			<div class="stat-card__value"><?php echo (int)$pending_leaves; ?></div>
			<div class="stat-card__label">Pending Leaves</div>
		</div>
	</div>
	<div class="stat-card">
		<div class="stat-card__icon stat-card__icon--amber"><svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg></div>
		<div>
			<div class="stat-card__value"><?php echo (int)$pending_ideas; ?></div>
			<div class="stat-card__label">Pending Ideas</div>
		</div>
	</div>
	<div class="stat-card">
		<div class="stat-card__icon stat-card__icon--amber"><svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M16 1l-3 3h2v2h-4v2h4v2h-2v2h2v-2h2v-2h-2V6h2V4h2l-3-3zM8 23l3-3h-2v-2h4v-2h-4v-2h2v-2H9v2H7v2h2v2H7v2h-2v2l3 3z"/></svg></div>
		<div>
			<div class="stat-card__value"><?php echo (int)$pending_transfers; ?></div>
			<div class="stat-card__label">Pending Transfers</div>
		</div>
	</div>
	<div class="stat-card">
		<div class="stat-card__icon stat-card__icon--red"><svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg></div>
		<div>
			<div class="stat-card__value"><?php echo (int)$unread_notifications; ?></div>
			<div class="stat-card__label">Alerts</div>
		</div>
	</div>
</div>

<div class="row cols-2" style="margin-bottom: 28px;">
	<div class="card">
		<div class="card__title">Weekly Attendance Trend</div>
		<div class="chart-container">
			<canvas id="attendanceChart" class="chart" data-chart-data="<?php echo htmlspecialchars(json_encode($chart_data)); ?>"></canvas>
		</div>
	</div>
	<div class="card">
		<div class="card__title">Recent Activity</div>
		<div style="max-height: 300px; overflow-y: auto;">
			<?php
			$recentActivity = $pdo->query("
				(SELECT 'leave' as type, l.created_at, u.name, CONCAT('Applied for ', l.type, ' leave') as description
				 FROM leaves l JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC LIMIT 3)
				UNION ALL
				(SELECT 'idea' as type, i.created_at, u.name, CONCAT('Submitted idea: ', i.title) as description
				 FROM ideas i JOIN users u ON u.id = i.user_id ORDER BY i.created_at DESC LIMIT 3)
				UNION ALL
				(SELECT 'transfer' as type, t.created_at, u.name, CONCAT('Requested transfer to ', t.target_team) as description
				 FROM transfers t JOIN users u ON u.id = t.user_id ORDER BY t.created_at DESC LIMIT 3)
				ORDER BY created_at DESC LIMIT 10
			")->fetchAll();
			?>
			<?php foreach ($recentActivity as $activity): ?>
				<div style="padding: 12px; border-bottom: 1px solid var(--border);">
					<div style="display: flex; justify-content: space-between; align-items: start;">
						<div>
							<div style="font-weight: 600; font-size: 13.5px;"><?php echo htmlspecialchars($activity['name']); ?></div>
							<div style="color: var(--text-muted); font-size: 13px;"><?php echo htmlspecialchars($activity['description']); ?></div>
						</div>
						<span style="color: var(--text-muted); font-size: 11.5px; white-space: nowrap;">
							<?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
						</span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
