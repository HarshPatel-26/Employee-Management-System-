
<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth('employee');
$pdo = Database::pdo();
$user = current_user();

$stmt = $pdo->prepare('SELECT * FROM tasks WHERE user_id = ? AND date = CURDATE() ORDER BY id DESC');
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

// Get task statistics
$totalTasks = count($rows);
$completedTasks = count(array_filter($rows, fn($r) => $r['status'] === 'done'));
$progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
?>
<div class="row cols-2">
	<div class="card">
		<div class="card__title">Add New Task</div>
		<form method="post" class="row">
			<input type="hidden" name="action" value="add_task" />
			<input class="input" name="title" placeholder="What needs to be done today?" required />
			<button class="btn" type="submit">Add Task</button>
		</form>
		
		<div style="margin-top: 20px; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
			<h4 style="margin: 0 0 12px 0; font-size: 16px;">Today's Progress</h4>
			<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
				<span>Completed: <?php echo $completedTasks; ?>/<?php echo $totalTasks; ?></span>
				<span><?php echo $progress; ?>%</span>
			</div>
			<div class="progress-bar">
				<div class="progress-bar__fill" style="width: <?php echo $progress; ?>%"></div>
			</div>
		</div>
	</div>
	
	<div class="card">
		<div class="card__title">Today's Tasks</div>
		<div style="max-height: 400px; overflow-y: auto;">
			<?php if (empty($rows)): ?>
				<div style="text-align: center; padding: 40px; color: var(--text-muted);">
					<p>No tasks for today. Add one above to get started!</p>
				</div>
			<?php else: ?>
				<div class="table-wrap">
					<table>
						<thead>
							<tr><th>Task</th><th>Status</th><th>Action</th></tr>
						</thead>
						<tbody>
					<?php foreach ($rows as $r): ?>
						<tr>
							<td style="<?php echo $r['status'] === 'done' ? 'text-decoration: line-through; opacity: 0.7;' : ''; ?>">
								<?php echo htmlspecialchars($r['title'] ?? ''); ?>
							</td>
							<td>
								<span class="badge badge--<?php echo $r['status'] === 'done' ? 'done' : 'pending'; ?>">
									<?php echo ucfirst($r['status']); ?>
								</span>
							</td>
							<td>
								<?php if ($r['status'] !== 'done'): ?>
									<form method="post" style="display:inline;">
										<input type="hidden" name="action" value="mark_done" />
										<input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>" />
										<button class="btn success sm" type="submit">Mark Done</button>
									</form>
								<?php else: ?>
									<span style="color: var(--success-solid);">✓ Completed</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
