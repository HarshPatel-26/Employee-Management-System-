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
	$where_clause = 'WHERE t.status = ?';
	$params[] = $status_filter;
}

$rows = $pdo->prepare("
	SELECT t.*, u.name, from_team.name as from_team_name, to_team.name as to_team_name 
	FROM transfers t 
	JOIN users u ON u.id = t.user_id 
	LEFT JOIN teams from_team ON t.current_team_id = from_team.id
    LEFT JOIN teams to_team ON t.target_team_id = to_team.id
	$where_clause ORDER BY t.id DESC
");
$rows->execute($params);
$rows = $rows->fetchAll();
?>
<div class="row cols-2">
	<div class="card">
		<div class="card__title">Filter Transfers</div>
		<div class="tabs" style="margin-bottom: 16px;">
			<a href="dashboard.php?tab=transfers&status=all" class="tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
			<a href="dashboard.php?tab=transfers&status=pending" class="tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
			<a href="dashboard.php?tab=transfers&status=approved" class="tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">Approved</a>
			<a href="dashboard.php?tab=transfers&status=rejected" class="tab <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
		</div>
		
		<div style="padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
			<h4 style="margin: 0 0 12px 0; font-size: 16px;">🔄 Transfer Review Guidelines</h4>
			<ul style="color: var(--text-muted); font-size: 14px; line-height: 1.6;">
				<li>Consider team capacity and workload</li>
				<li>Evaluate employee skills and fit</li>
				<li>Check for ongoing projects and commitments</li>
				<li>Ensure smooth transition planning</li>
			</ul>
		</div>
	</div>
	
	<div class="card">
		<div class="card__title">Transfer Statistics</div>
		<div style="display: grid; gap: 12px;">
			<?php
			$total_transfers = $pdo->query("SELECT COUNT(*) FROM transfers")->fetchColumn();
			$pending_transfers = $pdo->query("SELECT COUNT(*) FROM transfers WHERE status='pending'")->fetchColumn();
			$approved_transfers = $pdo->query("SELECT COUNT(*) FROM transfers WHERE status='approved'")->fetchColumn();
			$rejected_transfers = $pdo->query("SELECT COUNT(*) FROM transfers WHERE status='rejected'")->fetchColumn();
			?>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Total Requests</span>
				<span class="badge badge--teal"><?php echo $total_transfers; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Pending Review</span>
				<span class="badge badge--pending"><?php echo $pending_transfers; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Approved</span>
				<span class="badge badge--approved"><?php echo $approved_transfers; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Rejected</span>
				<span class="badge badge--rejected"><?php echo $rejected_transfers; ?></span>
			</div>
		</div>
	</div>
</div>

<div class="card">
	<div class="card__title">Transfer Requests</div>
	<div style="max-height: 600px; overflow-y: auto;">
		<?php if (empty($rows)): ?>
			<div style="text-align: center; padding: 40px; color: var(--text-muted);">
				<p>No transfer requests found for the selected filter.</p>
			</div>
		<?php else: ?>
			<?php foreach ($rows as $r): ?>
				<div style="margin-bottom: 20px; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border);">
					<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
						<div>
							<h4 style="margin: 0; font-size: 18px;">
								Transfer from <strong><?php echo htmlspecialchars($r['from_team_name'] ?? 'N/A'); ?></strong> to <strong><?php echo htmlspecialchars($r['to_team_name'] ?? 'N/A'); ?></strong>
							</h4>
							<div style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">
								By: <?php echo htmlspecialchars($r['name']); ?> • 
								<?php echo date('M j, Y', strtotime($r['created_at'])); ?>
							</div>
						</div>
						<span class="badge badge--<?php echo $r['status']; ?>">
							<?php echo ucfirst($r['status']); ?>
						</span>
					</div>
					
					<?php if ($r['reason']): ?>
						<div style="margin: 12px 0; padding: 16px; background: var(--bg-page); border-radius: var(--radius-md);">
							<strong style="color: var(--text);">Reason for Transfer:</strong>
							<p style="margin: 8px 0 0 0; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($r['reason'])); ?></p>
						</div>
					<?php endif; ?>
					
					<?php if ($r['status'] === 'pending'): ?>
						<form method="post" style="display: flex; gap: 12px; align-items: end;">
							<input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>" />
							<div style="flex: 1;">
								<label>Admin Response</label>
								<textarea class="input" name="comment" placeholder="Provide feedback on this transfer request..." rows="3"></textarea>
							</div>
							<div>
								<label>Decision</label>
								<select class="input" name="status" style="width: 120px;">
									<option value="approved">Approve</option>
									<option value="rejected">Reject</option>
								</select>
							</div>
							<button class="btn info" type="submit" style="padding: 10px 20px;">Submit Decision</button>
						</form>
					<?php elseif ($r['admin_comment']): ?>
						<div style="margin-top: 12px; padding: 16px; border-radius: var(--radius-md); border-left: 4px solid var(--success-solid); background: var(--bg-page);">
							<strong style="color: var(--success-solid);">Admin Response:</strong>
							<p style="margin: 8px 0 0 0; color: var(--text); line-height: 1.6;"><?php echo nl2br(htmlspecialchars($r['admin_comment'])); ?></p>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
