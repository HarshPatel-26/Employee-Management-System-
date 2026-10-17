<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth();
$user = current_user();
if (!in_array($user['role'], ['admin','boss'], true)) { header('Location: dashboard.php'); exit; }
$pdo = Database::pdo();

$date = $_GET['date'] ?? date('Y-m-d');
$employee_filter = $_GET['employee'] ?? '';

$where_conditions = ['a.date = ?'];
$params = [$date];

if ($employee_filter) {
	$where_conditions[] = 'u.name LIKE ?';
	$params[] = '%' . $employee_filter . '%';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

$sql = "SELECT a.*, u.name FROM attendance a JOIN users u ON u.id = a.user_id $where_clause ORDER BY u.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Get attendance statistics
$total_employees = $pdo->query("SELECT COUNT(*) FROM users WHERE role='employee'")->fetchColumn();
$present_today = count($rows);
$absent_today = $total_employees - $present_today;
?>
<div class="row cols-2">
	<div class="card">
		<div class="card__title">Attendance Summary</div>
		<div style="display: grid; gap: 16px;">
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<div>
					<div style="font-size: 24px; font-weight: 700; color: var(--success-solid);"><?php echo $present_today; ?></div>
					<div style="color: var(--text-muted);">Present Today</div>
				</div>
				<div style="font-size: 32px;">✅</div>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<div>
					<div style="font-size: 24px; font-weight: 700; color: var(--danger-solid);"><?php echo $absent_today; ?></div>
					<div style="color: var(--text-muted);">Absent Today</div>
				</div>
				<div style="font-size: 32px;">❌</div>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<div>
					<div style="font-size: 24px; font-weight: 700; color: var(--primary-solid);"><?php echo $total_employees; ?></div>
					<div style="color: var(--text-muted);">Total Employees</div>
				</div>
				<div style="font-size: 32px;">👥</div>
			</div>
		</div>
	</div>
	
	<div class="card">
		<div class="card__title">Filters</div>
		<form method="get" class="row">
			<input type="hidden" name="tab" value="attendance" />
			<div>
				<label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text);">Date</label>
				<input class="input" type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" />
			</div>
			<div>
				<label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text);">Employee Name</label>
				<input class="input" name="employee" placeholder="Search by name" value="<?php echo htmlspecialchars($employee_filter); ?>" />
			</div>
			<button class="btn" type="submit">Apply Filters</button>
		</form>
	</div>
</div>

<div class="card">
	<div class="card__title">Attendance Records - <?php echo date('M j, Y', strtotime($date)); ?></div>
	<div class="table-wrap">
		<table>
			<thead>
				<tr><th>Employee</th><th>Check-in</th><th>Check-out</th><th>Duration</th><th>Status</th></tr>
			</thead>
			<tbody>
			<?php foreach ($rows as $r): ?>
				<tr>
					<td><?php echo htmlspecialchars($r['name']); ?></td>
					<td>
						<?php if ($r['check_in']): ?>
							<span style="color: var(--success-solid); font-weight: 600;">
								<?php echo htmlspecialchars($r['check_in']); ?>
							</span>
						<?php else: ?>
							<span style="color: var(--danger-solid);">Not checked in</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ($r['check_out']): ?>
							<span style="color: var(--warning-solid); font-weight: 600;">
								<?php echo htmlspecialchars($r['check_out']); ?>
							</span>
						<?php else: ?>
							<span style="color: var(--text-muted);">Not checked out</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ($r['check_in'] && $r['check_out']): ?>
							<?php
							$checkin = new DateTime($r['check_in']);
							$checkout = new DateTime($r['check_out']);
							$duration = $checkin->diff($checkout);
							echo $duration->format('%h hours %i minutes');
							?>
						<?php elseif ($r['check_in']): ?>
							<span style="color: var(--warning-solid);">Still working</span>
						<?php else: ?>
							<span style="color: var(--text-muted);">-</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ($r['check_in'] && $r['check_out']): ?>
							<span class="badge badge--approved">Complete</span>
						<?php elseif ($r['check_in']): ?>
							<span class="badge badge--pending">Partial</span>
						<?php else: ?>
							<span class="badge badge--rejected">Absent</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
