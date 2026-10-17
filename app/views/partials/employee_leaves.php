<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth('employee');
$pdo = Database::pdo();
$user = current_user();

$stmt = $pdo->prepare('SELECT * FROM leaves WHERE user_id = ? ORDER BY id DESC');
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();
?>
<div class="row cols-2">
	<div class="card">
		<div class="card__title">Apply for Leave</div>
		<form method="post" class="row">
			<div>
				<label for="type">Type of Leave</label>
				<select id="type" class="input" name="type" required>
					<option value="">Select Leave Type</option>
					<option value="Sick">Sick Leave</option>
					<option value="Casual">Casual Leave</option>
					<option value="Earned">Earned Leave</option>
					<option value="Personal">Personal Leave</option>
					<option value="Emergency">Emergency Leave</option>
				</select>
			</div>
			<div>
				<label for="start_date">Start Date</label>
				<input id="start_date" class="input" type="date" name="start_date" required />
			</div>
			<div>
				<label for="end_date">End Date</label>
				<input id="end_date" class="input" type="date" name="end_date" required />
			</div>
			<textarea class="input" name="reason" placeholder="Reason for leave (optional)" rows="3"></textarea>
			<button class="btn" type="submit">Submit Request</button>
		</form>
	</div>
	
	<div class="card">
		<div class="card__title">My Leave Requests</div>
		<div class="table-wrap">
			<table>
				<thead>
					<tr><th>Type</th><th>From</th><th>To</th><th>Status</th><th>Comment</th></tr>
				</thead>
				<tbody>
				<?php foreach ($rows as $r): ?>
					<tr>
						<td><?php echo htmlspecialchars($r['type']); ?></td>
						<td><?php echo htmlspecialchars($r['start_date']); ?></td>
						<td><?php echo htmlspecialchars($r['end_date']); ?></td>
						<td>
							<span class="badge badge--<?php echo $r['status']; ?>">
								<?php echo ucfirst($r['status']); ?>
							</span>
						</td>
						<td><?php echo htmlspecialchars($r['admin_comment'] ?: '-'); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
