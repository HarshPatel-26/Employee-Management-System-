<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth();
$user = current_user();
if (!in_array($user['role'], ['admin','boss'], true)) {
	echo "<div class='card'><p>Access denied: admin only.</p></div>";
	return;
}
$pdo = Database::pdo();
$filter = $_GET['filter'] ?? 'all';
$where_clause = '';
$params = [];

if ($filter === 'unread') {
	$where_clause = 'WHERE n.is_read = 0';
} elseif ($filter === 'read') {
	$where_clause = 'WHERE n.is_read = 1';
}

$rows = $pdo->prepare("SELECT n.*, u.name FROM notifications n LEFT JOIN users u ON u.id = n.user_id $where_clause ORDER BY n.is_read ASC, n.id DESC LIMIT 100");
$rows->execute($params);
$rows = $rows->fetchAll();

$unread_count = $pdo->query('SELECT COUNT(*) FROM notifications WHERE is_read = 0')->fetchColumn();
$total_count = $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
?>
<?php
// Show flash message if set
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['notification_message'])) {
	list($type, $text) = explode(':', $_SESSION['notification_message'], 2);
	echo "<div class='alert $type' style='margin-bottom: 1rem;'>" . htmlspecialchars($text) . "</div>";
	unset($_SESSION['notification_message']);
}
?>
<div class="row cols-2">
	<div class="card">
		<div class="card__title">Notification Filters</div>
		<div class="tabs" style="margin-bottom: 16px;">
			<a href="dashboard.php?tab=notifications&filter=all" class="tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
			<a href="dashboard.php?tab=notifications&filter=unread" class="tab <?php echo $filter === 'unread' ? 'active' : ''; ?>">
				Unread (<?php echo $unread_count; ?>)
			</a>
			<a href="dashboard.php?tab=notifications&filter=read" class="tab <?php echo $filter === 'read' ? 'active' : ''; ?>">Read</a>
		</div>
		
		<?php if ($unread_count > 0): ?>
			<form method="post" style="margin-bottom: 16px;">
				<input type="hidden" name="action" value="mark_all_read" />
				<button class="btn secondary" type="submit" style="width: 100%;">Mark All as Read</button>
			</form>
		<?php endif; ?>
		
		<div style="padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
			<h4 style="margin: 0 0 12px 0; font-size: 16px;">📊 Notification Stats</h4>
			<div style="display: grid; gap: 8px;">
				<div style="display: flex; justify-content: space-between;">
					<span>Total Notifications</span>
					<span style="font-weight: 600;"><?php echo $total_count; ?></span>
				</div>
				<div style="display: flex; justify-content: space-between;">
					<span>Unread</span>
					<span style="font-weight: 600; color: var(--warning-solid);"><?php echo $unread_count; ?></span>
				</div>
				<div style="display: flex; justify-content: space-between;">
					<span>Read</span>
					<span style="font-weight: 600; color: var(--success-solid);"><?php echo $total_count - $unread_count; ?></span>
				</div>
			</div>
		</div>
	</div>
	
	<div class="card">
		<div class="card__title">Quick Actions</div>
		<div style="display: grid; gap: 12px;">
			<a href="dashboard.php?tab=leaves&status=pending" class="btn" style="text-align: center; text-decoration: none;">
				🏖️ Review Pending Leaves
			</a>
			<a href="dashboard.php?tab=ideas&status=pending" class="btn secondary" style="text-align: center; text-decoration: none;">
				💡 Review Pending Ideas
			</a>
			<a href="dashboard.php?tab=transfers&status=pending" class="btn secondary" style="text-align: center; text-decoration: none;">
				🔄 Review Transfer Requests
			</a>
			<a href="dashboard.php?tab=attendance" class="btn secondary" style="text-align: center; text-decoration: none;">
				📅 Check Today's Attendance
			</a>
		</div>
	</div>
</div>

<div class="card">
	<div class="card__title">Notifications</div>
	<div style="max-height: 600px; overflow-y: auto;">
		<?php if (empty($rows)): ?>
			<div style="text-align: center; padding: 40px; color: var(--text-muted);">
				<p>No notifications found for the selected filter.</p>
			</div>
		<?php else: ?>
			<?php foreach ($rows as $r): ?>
				<div style="margin-bottom: 16px; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border); <?php echo !$r['is_read'] ? 'border-left: 4px solid var(--navy); background: var(--bg-page);' : ''; ?>">
					<div style="display: flex; justify-content: space-between; align-items: start;">
						<div style="flex: 1;">
							<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
								<?php
								$icon = '🔔';
								$type_label = ucfirst(str_replace('_', ' ', $r['type']));
								
								if (strpos($r['type'], 'leave') !== false) {
									$icon = '🏖️';
								} elseif (strpos($r['type'], 'idea') !== false) {
									$icon = '💡';
								} elseif (strpos($r['type'], 'transfer') !== false) {
									$icon = '🔄';
								}
								?>
								<span style="font-size: 18px;"><?php echo $icon; ?></span>
								<span style="font-weight: 600; color: var(--text);"><?php echo $type_label; ?></span>
								<?php if (!$r['is_read']): ?>
									<span class="badge badge--navy" style="font-size:10px;">NEW</span>
								<?php endif; ?>
							</div>
							
							<?php if ($r['name']): ?>
								<div style="color: var(--text-muted); font-size: 14px; margin-bottom: 8px;">
									From: <?php echo htmlspecialchars($r['name']); ?>
								</div>
							<?php endif; ?>
							
							<?php if ($r['payload']): ?>
								<div style="color: var(--text); line-height: 1.6; margin-bottom: 8px;">
									<?php echo nl2br(htmlspecialchars($r['payload'])); ?>
								</div>
							<?php endif; ?>
							
							<div style="color: var(--text-muted); font-size: 12px;">
								<?php echo date('M j, Y g:i A', strtotime($r['created_at'])); ?>
							</div>
						</div>
						
						<?php if (!$r['is_read']): ?>
							<form method="post" style="margin-left: 16px;">
								<input type="hidden" name="action" value="mark_read" />
								<input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>" />
								<button class="btn secondary sm" type="submit">Mark Read</button>
							</form>
						<?php else: ?>
							<div style="margin-left: 16px;">
								<span class="badge badge--approved">✓ Read</span>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
