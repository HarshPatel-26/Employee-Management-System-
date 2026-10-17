<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth();
$user = current_user();
if (!in_array($user['role'], ['admin','boss'], true)) { header('Location: dashboard.php'); exit; }
$pdo = Database::pdo();

$status_filter = $_GET['status'] ?? 'all';
$where_clause = '';
$params = [];
if ($status_filter !== 'all') {
	$where_clause = 'WHERE l.status = ?';
	$params[] = $status_filter;
}

$sql = "SELECT l.*, u.name FROM leaves l JOIN users u ON u.id = l.user_id $where_clause ORDER BY l.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<div class="card">
	<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
		<div class="card__title" style="margin-bottom:0; border:none; padding:0;">Leave Management</div>
		<div class="tabs" style="margin-bottom:0; border:none;">
			<a href="dashboard.php?tab=leaves&status=all" class="tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
			<a href="dashboard.php?tab=leaves&status=pending" class="tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
			<a href="dashboard.php?tab=leaves&status=approved" class="tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">Approved</a>
			<a href="dashboard.php?tab=leaves&status=rejected" class="tab <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
		</div>
	</div>
	
	<div class="table-wrap">
		<table>
			<thead>
				<tr><th>Employee</th><th>Type</th><th>Dates</th><th>Duration</th><th>Status</th><th>Action</th></tr>
			</thead>
			<tbody>
			<?php foreach ($rows as $r): ?>
				<tr>
					<td><?php echo htmlspecialchars($r['name']); ?></td>
					<td><?php echo htmlspecialchars($r['type']); ?></td>
					<td>
						<div><?php echo htmlspecialchars($r['start_date']); ?></div>
						<div style="color: var(--text-muted); font-size: 12px;">to <?php echo htmlspecialchars($r['end_date']); ?></div>
					</td>
					<td>
						<?php 
						$start = new DateTime($r['start_date']);
						$end = new DateTime($r['end_date']);
						$duration = $start->diff($end)->days + 1;
						echo $duration . ' day' . ($duration > 1 ? 's' : '');
						?>
					</td>
					<td>
						<span class="badge badge--<?php echo $r['status']; ?>">
							<?php echo ucfirst($r['status']); ?>
						</span>
					</td>
					<td>
						<?php if ($r['status'] === 'pending'): ?>
							<form method="post" style="display: flex; gap: 8px; align-items: center;">
								<input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>" />
								<select class="input" name="status" style="width: 120px;">
									<option value="approved">Approve</option>
									<option value="rejected">Reject</option>
								</select>
								<button class="btn sm" type="submit">Update</button>
							</form>
						<?php else: ?>
							<div style="color: var(--text-muted); font-size: 13px;">
								Processed: <?php echo date('M j, Y', strtotime($r['created_at'])); ?>
							</div>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
