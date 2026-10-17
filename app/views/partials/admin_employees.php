<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth();
$user = current_user();
if (!in_array($user['role'], ['admin','boss'], true)) { header('Location: dashboard.php'); exit; }
$pdo = Database::pdo();

$message = '';
$message_type = '';

// Handle employee creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_employee') {
	$name = trim($_POST['name'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$role = $_POST['role'] ?? 'employee';
	
	if (empty($name) || empty($email) || empty($password)) {
		$message = 'All fields are required.';
		$message_type = 'error';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$message = 'Please enter a valid email address.';
		$message_type = 'error';
	} elseif (strlen($password) < 6) {
		$message = 'Password must be at least 6 characters long.';
		$message_type = 'error';
	} else {
		try {
			// Check if email already exists
			$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
			$stmt->execute([$email]);
			if ($stmt->fetch()) {
				$message = 'Email address already exists.';
				$message_type = 'error';
			} else {
				// Create new employee
				$password_hash = password_hash($password, PASSWORD_BCRYPT);
				$stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
				$stmt->execute([$name, $email, $password_hash, $role]);
				$message = 'Employee created successfully!';
				$message_type = 'success';
			}
		} catch (PDOException $e) {
			$message = 'Database error: ' . $e->getMessage();
			$message_type = 'error';
		}
	}
}

$q = trim($_GET['q'] ?? '');
$role_filter = $_GET['role'] ?? 'all';

$where_conditions = [];
$params = [];

if ($q) {
	$where_conditions[] = '(u.name LIKE ? OR u.email LIKE ?)';
	$params[] = '%' . $q . '%';
	$params[] = '%' . $q . '%';
}

if ($role_filter !== 'all') {
	$where_conditions[] = 'u.role = ?';
	$params[] = $role_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT u.id, u.name, u.email, u.role, u.created_at, 
		(SELECT COUNT(*) FROM attendance a WHERE a.user_id = u.id AND a.date = CURDATE() AND a.check_in IS NOT NULL) as today_attendance,
		(SELECT COUNT(*) FROM leaves l WHERE l.user_id = u.id AND l.status = 'pending') as pending_leaves,
		(SELECT COUNT(*) FROM tasks t WHERE t.user_id = u.id AND t.date = CURDATE()) as today_tasks
		FROM users u $where_clause ORDER BY u.role, u.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<?php if ($message): ?>
	<div class="alert <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php
// Show flash message for employee actions
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['employee_message'])) {
	list($etype, $etext) = explode(':', $_SESSION['employee_message'], 2);
	echo "<div class='alert $etype'>" . htmlspecialchars($etext) . "</div>";
	unset($_SESSION['employee_message']);
}
?>

<div class="card" style="margin-bottom: 24px;">
	<div class="card__title">Add New Employee</div>
	<form method="post" class="row cols-2" style="gap: 16px;">
		<input type="hidden" name="action" value="create_employee">
		<div>
			<label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text);">Full Name</label>
			<input class="input" type="text" name="name" placeholder="Enter full name" required />
		</div>
		<div>
			<label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text);">Email Address</label>
			<input class="input" type="email" name="email" placeholder="Enter email address" required />
		</div>
		<div>
			<label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text);">Password</label>
			<input class="input" type="password" name="password" placeholder="Enter password (min 6 chars)" required minlength="6" />
		</div>
		<div>
			<label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text);">Role</label>
			<select class="input" name="role" required>
				<option value="employee">Employee</option>
				<option value="admin">Admin</option>
				<option value="boss">Boss</option>
			</select>
		</div>
		<div style="grid-column: 1 / -1;">
			<button class="btn success" type="submit" style="width: auto;">Create Employee</button>
		</div>
	</form>
</div>

<div class="row cols-2">
	<div class="card">
		<div class="card__title">Search & Filter</div>
		<form method="get" class="row">
			<input type="hidden" name="tab" value="employees" />
			<input class="input" name="q" placeholder="Search by name or email" value="<?php echo htmlspecialchars($q); ?>" />
			<select class="input" name="role">
				<option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Roles</option>
				<option value="employee" <?php echo $role_filter === 'employee' ? 'selected' : ''; ?>>Employee</option>
				<option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
				<option value="boss" <?php echo $role_filter === 'boss' ? 'selected' : ''; ?>>Boss</option>
			</select>
			<button class="btn" type="submit">Search</button>
		</form>
	</div>
	
	<div class="card">
		<div class="card__title">Employee Statistics</div>
		<div style="display: grid; gap: 12px;">
			<?php
			$total_employees = $pdo->query("SELECT COUNT(*) FROM users WHERE role='employee'")->fetchColumn();
			$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin','boss')")->fetchColumn();
			$present_today = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND check_in IS NOT NULL")->fetchColumn();
			$active_tasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE date = CURDATE()")->fetchColumn();
			?>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--glass); border-radius: 8px;">
				<span>Total Employees</span>
				<span style="font-weight: 600; color: var(--primary-solid);"><?php echo $total_employees; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--glass); border-radius: 8px;">
				<span>Admins & Bosses</span>
				<span style="font-weight: 600; color: var(--success-solid);"><?php echo $total_admins; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--glass); border-radius: 8px;">
				<span>Present Today</span>
				<span style="font-weight: 600; color: var(--success-solid);"><?php echo $present_today; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--glass); border-radius: 8px;">
				<span>Active Tasks</span>
				<span style="font-weight: 600; color: var(--warning-solid);"><?php echo $active_tasks; ?></span>
			</div>
		</div>
	</div>
</div>

<div class="card">
	<div class="card__title">Employee Directory</div>
	<div class="table-wrap">
		<table>
			<thead>
				<tr><th>Employee</th><th>Email</th><th>Role</th><th>Today's Status</th><th>Pending Items</th><th>Joined</th><th style="text-align:right">Action</th></tr>
			</thead>
			<tbody>
			<?php foreach ($rows as $r): ?>
				<tr>
					<td>
						<div style="display: flex; align-items: center; gap: 12px;">
							<div class="avatar"><?php echo strtoupper(substr($r['name'], 0, 1)); ?></div>
							<div style="font-weight: 600;"><?php echo htmlspecialchars($r['name']); ?></div>
						</div>
					</td>
					<td><?php echo htmlspecialchars($r['email']); ?></td>
					<td>
						<span class="badge badge--<?php echo $r['role'] === 'employee' ? 'pending' : 'active'; ?>">
							<?php echo ucfirst($r['role']); ?>
						</span>
					</td>
					<td>
						<?php if ($r['role'] === 'employee'): ?>
							<?php if ($r['today_attendance']): ?>
								<span class="badge badge--approved">Present</span>
							<?php else: ?>
								<span class="badge badge--rejected">Absent</span>
							<?php endif; ?>
						<?php else: ?>
							<span style="color: var(--text-muted);">-</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ($r['role'] === 'employee'): ?>
							<div style="display: flex; gap: 8px;">
								<?php if ($r['pending_leaves'] > 0): ?>
									<span class="badge badge--pending">
										<?php echo $r['pending_leaves']; ?> Leave<?php echo $r['pending_leaves'] > 1 ? 's' : ''; ?>
									</span>
								<?php endif; ?>
								<?php if ($r['today_tasks'] > 0): ?>
									<span class="badge badge--teal">
										<?php echo $r['today_tasks']; ?> Task<?php echo $r['today_tasks'] > 1 ? 's' : ''; ?>
									</span>
								<?php endif; ?>
								<?php if ($r['pending_leaves'] == 0 && $r['today_tasks'] == 0): ?>
									<span style="color: var(--text-muted); font-size: 12px;">None</span>
								<?php endif; ?>
							</div>
						<?php else: ?>
							<span style="color: var(--text-muted);">-</span>
						<?php endif; ?>
					</td>
					<td>
						<span style="color: var(--text-muted); font-size: 12px;">
							<?php echo date('M j, Y', strtotime($r['created_at'])); ?>
						</span>
					</td>
					<td style="text-align: right;">
						<?php if ($r['role'] === 'employee'): ?>
							<?php if ($r['id'] != $user['id']): ?>
							<form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove this employee? This action cannot be undone.');">
								<input type="hidden" name="action" value="remove_employee" />
								<input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>" />
								<button class="btn danger sm" type="submit">Remove</button>
							</form>
							<?php else: ?>
								<span style="color: var(--text-muted); font-size:12px;">(You)</span>
							<?php endif; ?>
						<?php else: ?>
							<span style="color: var(--text-muted);">-</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
