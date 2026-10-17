<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth('employee');
$pdo = Database::pdo();
$user = current_user();

// Display flash message
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['transfer_message'])) {
	list($type, $text) = explode(':', $_SESSION['transfer_message'], 2);
	echo "<div class='alert $type' style='margin-bottom: 1rem;'>" . htmlspecialchars($text) . "</div>";
	unset($_SESSION['transfer_message']);
}

$stmt = $pdo->prepare('
    SELECT 
        t.*, 
        from_team.name AS from_team_name, 
        to_team.name AS to_team_name 
    FROM transfers t
    LEFT JOIN teams from_team ON t.current_team_id = from_team.id
    LEFT JOIN teams to_team ON t.target_team_id = to_team.id
    WHERE t.user_id = ? 
    ORDER BY t.id DESC
');
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

// Get available teams
$teams = $pdo->query('SELECT id, name FROM teams ORDER BY name')->fetchAll();

?>
<div class="row cols-2">
	<div class="card">
		<div class="card__title">Request Team Transfer</div>
		<form method="post" class="row">
			<select class="input" name="team_id" required>
				<option value="">Select Target Team</option>
				<?php foreach ($teams as $team): ?>
					<option value="<?php echo (int)$team['id']; ?>">
						<?php echo htmlspecialchars($team['name']); ?>
					</option>
				<?php endforeach; ?>
				<!-- <option value="Other">Other (specify in reason)</option> -->
			</select>
			<textarea class="input" name="reason" placeholder="Why do you want to transfer? What skills or interests do you have for this team?" rows="4"></textarea>
			<button class="btn" type="submit">Submit Request</button>
		</form>
		
		<div style="margin-top: 20px; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
			<h4 style="margin: 0 0 12px 0; font-size: 16px;">📋 Transfer Guidelines</h4>
			<ul style="color: var(--text-muted); font-size: 14px; line-height: 1.6;">
				<li>Explain your motivation clearly</li>
				<li>Highlight relevant skills and experience</li>
				<li>Consider current workload and commitments</li>
				<li>Transfer requests are reviewed monthly</li>
			</ul>
		</div>
	</div>
	
	<div class="card">
		<div class="card__title">My Transfer Requests</div>
		<div style="max-height: 500px; overflow-y: auto;">
			<?php if (empty($rows)): ?>
				<div style="text-align: center; padding: 40px; color: var(--text-muted);">
					<p>No transfer requests submitted yet.</p>
				</div>
			<?php else: ?>
				<?php foreach ($rows as $r): ?>
					<div style="margin-bottom: 16px; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
						<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
							<h4 style="margin: 0; font-size: 16px;">
                                Transfer from <strong><?php echo htmlspecialchars($r['from_team_name'] ?? 'N/A'); ?></strong> to <strong><?php echo htmlspecialchars($r['to_team_name'] ?? 'N/A'); ?></strong>
                            </h4>
							<span class="badge badge--<?php echo $r['status']; ?>">
								<?php echo ucfirst($r['status']); ?>
							</span>
						</div>
						<?php if ($r['reason']): ?>
							<p style="color: var(--text-muted); font-size: 14px; margin: 8px 0;"><?php echo htmlspecialchars($r['reason']); ?></p>
						<?php endif; ?>
						<?php if ($r['admin_comment']): ?>
							<div style="margin-top: 12px; padding: 12px; background: var(--bg-page); border-radius: var(--radius-md); border-left: 4px solid var(--success-solid);">
								<strong style="color: var(--success-solid);">Admin Response:</strong>
								<p style="margin: 4px 0 0 0; color: var(--text);"><?php echo htmlspecialchars($r['admin_comment']); ?></p>
							</div>
						<?php endif; ?>
						<div style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">
							Requested: <?php echo date('M j, Y', strtotime($r['created_at'])); ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
