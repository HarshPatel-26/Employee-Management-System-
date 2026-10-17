<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth('employee');
$pdo = Database::pdo();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$title = trim($_POST['title'] ?? '');
	$desc = trim($_POST['description'] ?? '');
	if ($title && $user) {
		$stmt = $pdo->prepare('INSERT INTO ideas (user_id, title, description, category, impact) VALUES (?, ?, ?, ?, ?)');
		$stmt->execute([$user['id'], $title, $desc, 'General', 'medium']);

		// Notify all admins and bosses
		$admins_stmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'boss')");
		$admins = $admins_stmt->fetchAll(PDO::FETCH_COLUMN);
		$notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'idea_submission', 'New Idea Submitted', ?)");
		foreach ($admins as $admin_id) {
			$notif_stmt->execute([$admin_id, "{$user['name']} has submitted a new idea: '{$title}'."]);
		}

		header('Location: dashboard.php?tab=ideas'); exit;
	}
}

$stmt = $pdo->prepare('SELECT * FROM ideas WHERE user_id = ? ORDER BY id DESC');
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();
?>
<div class="row cols-2">
	<div class="card">
		<div class="card__title">Submit New Idea</div>
		<form method="post" class="row">
			<input class="input" name="title" placeholder="Idea title" required />
			<textarea class="input" name="description" placeholder="Describe your idea in detail..." rows="4"></textarea>
			<button class="btn" type="submit">Submit Idea</button>
		</form>
		
		<div style="margin-top: 20px; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
			<h4 style="margin: 0 0 12px 0; font-size: 16px;">💡 Tips for Great Ideas</h4>
			<ul style="color: var(--text-muted); font-size: 14px; line-height: 1.6;">
				<li>Be specific about the problem you're solving</li>
				<li>Explain the potential benefits</li>
				<li>Consider implementation feasibility</li>
				<li>Think about target audience</li>
			</ul>
		</div>
	</div>
	
	<div class="card">
		<div class="card__title">My Submitted Ideas</div>
		<div style="max-height: 500px; overflow-y: auto;">
			<?php if (empty($rows)): ?>
				<div style="text-align: center; padding: 40px; color: var(--text-muted);">
					<p>No ideas submitted yet. Share your first idea above!</p>
				</div>
			<?php else: ?>
				<?php foreach ($rows as $r): ?>
					<div style="margin-bottom: 16px; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
						<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
							<h4 style="margin: 0; font-size: 16px;"><?php echo htmlspecialchars($r['title']); ?></h4>
							<span class="badge badge--<?php echo $r['status']; ?>">
								<?php echo ucfirst($r['status']); ?>
							</span>
						</div>
						<?php if ($r['description']): ?>
							<p style="color: var(--text-muted); font-size: 14px; margin: 8px 0;"><?php echo htmlspecialchars($r['description']); ?></p>
						<?php endif; ?>
						<?php if ($r['admin_feedback']): ?>
							<div style="margin-top: 12px; padding: 12px; background: var(--bg-page); border-radius: var(--radius-md); border-left: 4px solid var(--success-solid);">
								<strong style="color: var(--success-solid);">Admin Feedback:</strong>
								<p style="margin: 4px 0 0 0; color: var(--text);"><?php echo htmlspecialchars($r['admin_feedback']); ?></p>
							</div>
						<?php endif; ?>
						<div style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">
							Submitted: <?php echo date('M j, Y', strtotime($r['created_at'])); ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
