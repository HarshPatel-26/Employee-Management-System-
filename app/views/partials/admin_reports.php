<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth();
$user = current_user();
if (!in_array($user['role'], ['admin','boss'], true)) { header('Location: dashboard.php'); exit; }
$pdo = Database::pdo();

// Get statistics for reports
$total_employees = $pdo->query("SELECT COUNT(*) FROM users WHERE role='employee'")->fetchColumn();
$total_attendance_records = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
$total_leaves = $pdo->query("SELECT COUNT(*) FROM leaves")->fetchColumn();
$total_ideas = $pdo->query("SELECT COUNT(*) FROM ideas")->fetchColumn();
$total_transfers = $pdo->query("SELECT COUNT(*) FROM transfers")->fetchColumn();

// Get monthly attendance data
$monthly_attendance = $pdo->query("
	SELECT 
		DATE_FORMAT(date, '%Y-%m') as month,
		COUNT(*) as total_records,
		COUNT(CASE WHEN check_in IS NOT NULL THEN 1 END) as present_count
	FROM attendance 
	WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
	GROUP BY DATE_FORMAT(date, '%Y-%m')
	ORDER BY month DESC
")->fetchAll();

// Get leave statistics
$leave_stats = $pdo->query("
	SELECT 
		type,
		COUNT(*) as count,
		AVG(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) * 100 as approval_rate
	FROM leaves 
	GROUP BY type
	ORDER BY count DESC
")->fetchAll();
?>
<div class="row cols-2">
	<div class="card">
		<div class="card__title">Export Reports</div>
		<div style="display: grid; gap: 12px;">
			<a href="report_attendance.php" class="btn" style="text-align: center; text-decoration: none;">
				📅 Export Attendance Data (CSV)
			</a>
			<a href="report_leaves.php" class="btn secondary" style="text-align: center; text-decoration: none;">
				🏖️ Export Leave Data (CSV)
			</a>
			<a href="report_employees.php" class="btn secondary" style="text-align: center; text-decoration: none;">
				👥 Export Employee Data (CSV)
			</a>
			<a href="report_ideas.php" class="btn secondary" style="text-align: center; text-decoration: none;">
				💡 Export Ideas Data (CSV)
			</a>
		</div>
		
		<div style="margin-top: 20px; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
			<h4 style="margin: 0 0 12px 0; font-size: 16px;">📊 Report Information</h4>
			<ul style="color: var(--text-muted); font-size: 14px; line-height: 1.6;">
				<li>CSV files can be opened in Excel or Google Sheets</li>
				<li>Data includes all historical records</li>
				<li>Reports are generated in real-time</li>
				<li>Use filters in respective sections for custom reports</li>
			</ul>
		</div>
	</div>
	
	<div class="card">
		<div class="card__title">Data Overview</div>
		<div style="display: grid; gap: 12px;">
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Total Employees</span>
				<span class="badge badge--teal"><?php echo $total_employees; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Attendance Records</span>
				<span class="badge badge--approved"><?php echo $total_attendance_records; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Leave Requests</span>
				<span class="badge badge--pending"><?php echo $total_leaves; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Ideas Submitted</span>
				<span class="badge badge--active"><?php echo $total_ideas; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Transfer Requests</span>
				<span class="badge badge--rejected"><?php echo $total_transfers; ?></span>
			</div>
		</div>
	</div>
</div>

<div class="row cols-2">
	<div class="card">
		<div class="card__title">Monthly Attendance Trend</div>
		<div class="table-wrap">
			<table>
				<thead>
					<tr><th>Month</th><th>Total Records</th><th>Present</th><th>Attendance Rate</th></tr>
				</thead>
				<tbody>
				<?php foreach ($monthly_attendance as $month): ?>
					<tr>
						<td><?php echo date('M Y', strtotime($month['month'] . '-01')); ?></td>
						<td><?php echo (int)$month['total_records']; ?></td>
						<td><?php echo (int)$month['present_count']; ?></td>
						<td>
							<?php 
							$rate = $month['total_records'] > 0 ? round(($month['present_count'] / $month['total_records']) * 100) : 0;
							echo $rate . '%';
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	
	<div class="card">
		<div class="card__title">Leave Type Statistics</div>
		<div class="table-wrap">
			<table>
				<thead>
					<tr><th>Leave Type</th><th>Count</th><th>Approval Rate</th></tr>
				</thead>
				<tbody>
				<?php foreach ($leave_stats as $stat): ?>
					<tr>
						<td><?php echo htmlspecialchars($stat['type']); ?></td>
						<td><?php echo (int)$stat['count']; ?></td>
						<td>
							<?php 
							$approval_rate = round($stat['approval_rate']);
							$color = $approval_rate >= 80 ? 'var(--success-solid)' : ($approval_rate >= 60 ? 'var(--warning-solid)' : 'var(--danger-solid)');
							?>
							<span style="color: <?php echo $color; ?>; font-weight: 600;">
								<?php echo $approval_rate; ?>%
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="card">
	<div class="card__title">Custom Report Generator</div>
	<div style="display: grid; gap: 16px;">
		<div style="padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border);">
			<h4 style="margin: 0 0 16px 0; font-size: 16px;">📅 Attendance Report</h4>
			<form method="get" action="report_attendance.php" class="row cols-2" style="align-items: end;">
				<div>
					<label>From Date</label>
					<input class="input" type="date" name="from_date" />
				</div>
				<div style="display: flex; gap: 12px; align-items: end;">
					<div style="flex-grow: 1;">
						<label>To Date</label>
						<input class="input" type="date" name="to_date" />
					</div>
					<button class="btn info" type="submit">Generate</button>
				</div>
			</form>
		</div>
				<div>
					<label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text);">To Date</label>
					<input class="input" type="date" name="to_date" />
				</div>
				<button class="btn" type="submit">Generate Report</button>
			</form>
		</div>
		
		<div style="padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border);">
			<h4 style="margin: 0 0 16px 0; font-size: 16px;">🏖️ Leave Report</h4>
			<form method="get" action="report_leaves.php" class="row cols-2" style="align-items: end;">
				<div>
					<label>Status</label>
					<select class="input" name="status">
						<option value="">All Statuses</option>
						<option value="pending">Pending</option>
						<option value="approved">Approved</option>
						<option value="rejected">Rejected</option>
					</select>
				</div>
				<div style="display: flex; gap: 12px; align-items: end;">
					<div style="flex-grow: 1;">
						<label>Leave Type</label>
						<select class="input" name="type">
							<option value="">All Types</option>
							<option value="Sick">Sick</option>
							<option value="Casual">Casual</option>
							<option value="Earned">Earned</option>
							<option value="Personal">Personal</option>
						</select>
					</div>
					<button class="btn info" type="submit">Generate</button>
				</div>
			</form>
		</div>
	</div>
</div>
