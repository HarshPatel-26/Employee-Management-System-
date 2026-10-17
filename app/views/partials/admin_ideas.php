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
	$where_clause = 'WHERE i.status = ?';
	$params[] = $status_filter;
}

$rows = $pdo->prepare("SELECT i.*, u.name FROM ideas i JOIN users u ON u.id = i.user_id $where_clause ORDER BY i.id DESC");
$rows->execute($params);
$rows = $rows->fetchAll();
?>
<div class="row cols-2">
	<div class="card">
		<div class="card__title">Filter Ideas</div>
		<div class="tabs" style="margin-bottom: 16px;">
			<a href="dashboard.php?tab=ideas&status=all" class="tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
			<a href="dashboard.php?tab=ideas&status=pending" class="tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
			<a href="dashboard.php?tab=ideas&status=approved" class="tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">Approved</a>
			<a href="dashboard.php?tab=ideas&status=rejected" class="tab <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
		</div>
		
		<div style="padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
			<h4 style="margin: 0 0 12px 0; font-size: 16px;">💡 Idea Review Guidelines</h4>
			<ul style="color: var(--text-muted); font-size: 14px; line-height: 1.6;">
				<li>Consider feasibility and business value</li>
				<li>Evaluate resource requirements</li>
				<li>Provide constructive feedback</li>
				<li>Approve ideas that align with company goals</li>
			</ul>
		</div>
	</div>
	
	<div class="card">
		<div class="card__title">Idea Statistics</div>
		<div style="display: grid; gap: 12px;">
			<?php
			$total_ideas = $pdo->query("SELECT COUNT(*) FROM ideas")->fetchColumn();
			$pending_ideas = $pdo->query("SELECT COUNT(*) FROM ideas WHERE status='pending'")->fetchColumn();
			$approved_ideas = $pdo->query("SELECT COUNT(*) FROM ideas WHERE status='approved'")->fetchColumn();
			$rejected_ideas = $pdo->query("SELECT COUNT(*) FROM ideas WHERE status='rejected'")->fetchColumn();
			?>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Total Ideas</span>
				<span class="badge badge--teal"><?php echo $total_ideas; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Pending Review</span>
				<span class="badge badge--pending"><?php echo $pending_ideas; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Approved</span>
				<span class="badge badge--approved"><?php echo $approved_ideas; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Rejected</span>
				<span class="badge badge--rejected"><?php echo $rejected_ideas; ?></span>
			</div>
		</div>
	</div>
</div>

<div class="card">
	<div class="card__title">Employee Ideas</div>
	<div style="max-height: 600px; overflow-y: auto;">
		<?php if (empty($rows)): ?>
			<div style="text-align: center; padding: 40px; color: var(--muted);">
				<p>No ideas found for the selected filter.</p>
			</div>
		<?php else: ?>
			<?php foreach ($rows as $r): ?>
				<div style="margin-bottom: 20px; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border);">
					<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
						<div>
							<h4 style="margin: 0; font-size: 18px;"><?php echo htmlspecialchars($r['title']); ?></h4>
							<div style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">
								By: <?php echo htmlspecialchars($r['name']); ?> • 
								<?php echo date('M j, Y', strtotime($r['created_at'])); ?>
							</div>
						</div>
						<span class="badge badge--<?php echo $r['status']; ?>">
							<?php echo ucfirst($r['status']); ?>
						</span>
					</div>
					
					<?php if ($r['description']): ?>
						<div style="margin: 12px 0; padding: 16px; background: var(--bg-page); border-radius: var(--radius-md);">
							<p style="margin: 0; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($r['description'])); ?></p>
						</div>
					<?php endif; ?>
					
					<?php if ($r['status'] === 'pending'): ?>
						<form method="post" style="display: flex; gap: 12px; align-items: end;">
							<input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>" />
							<div style="flex: 1;">
								<label>Feedback</label>
								<textarea class="input" name="feedback" placeholder="Provide feedback on this idea..." rows="3"></textarea>
							</div>
							<div>
								<label>Decision</label>
								<select class="input" name="status" style="width: 120px;">
									<option value="approved">Approve</option>
									<option value="rejected">Reject</option>
								</select>
							</div>
							<button class="btn info" type="submit" style="padding: 10px 20px;">Submit Review</button>
						</form>
					<?php elseif ($r['admin_feedback']): ?>
						<div style="margin-top: 12px; padding: 16px; border-radius: var(--radius-md); border-left: 4px solid var(--success-solid); background: var(--bg-page);">
							<strong style="color: var(--success-solid);">Admin Feedback:</strong>
							<p style="margin: 8px 0 0 0; color: var(--text); line-height: 1.6;"><?php echo nl2br(htmlspecialchars($r['admin_feedback'])); ?></p>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
