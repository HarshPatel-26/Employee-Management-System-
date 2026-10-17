<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth('employee');
$pdo = Database::pdo();
$user = current_user();

$msg = '';
if (($_POST['action'] ?? '') === 'update_profile') {
	$name = trim($_POST['name'] ?? '');
	if ($name) {
		$stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
		$stmt->execute([$name, $user['id']]);
		$_SESSION['user']['name'] = $name;
		$msg = 'Profile updated successfully!';
	}
}
if (($_POST['action'] ?? '') === 'change_password') {
	$new = $_POST['new_password'] ?? '';
	if (strlen($new) >= 6) {
		$hash = password_hash($new, PASSWORD_BCRYPT);
		$pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $user['id']]);
		$msg = 'Password changed successfully!';
	} else {
		$msg = 'Password must be at least 6 characters long.';
	}
}

$stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$u = $stmt->fetch();
?>
<div class="row cols-2">
	<div class="card">
		<div class="card__title">Profile Information</div>
		<?php if ($msg): ?>
			<div class="alert success"><?php echo htmlspecialchars($msg); ?></div>
		<?php endif; ?>
		<form method="post" class="row">
			<input type="hidden" name="action" value="update_profile" />
			<div>
				<label>Full Name</label>
				<input class="input" name="name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($u['name']); ?>" required />
			</div>
			<div>
				<label>Email Address</label>
				<input class="input" value="<?php echo htmlspecialchars($u['email']); ?>" disabled style="opacity: 0.6;" />
				<small style="color: var(--text-muted); font-size: 12px;">Email cannot be changed</small>
			</div>
			<button class="btn" type="submit">Update Profile</button>
		</form>
	</div>
	
	<div class="card">
		<div class="card__title">Change Password</div>
		<form method="post" class="row">
			<input type="hidden" name="action" value="change_password" />
			<div>
				<label>New Password</label>
				<input class="input" name="new_password" type="password" placeholder="Enter new password (min 6 characters)" required />
			</div>
			<div>
				<label>Confirm Password</label>
				<input class="input" name="confirm_password" type="password" placeholder="Confirm new password" required />
			</div>
			<button class="btn" type="submit">Change Password</button>
		</form>
		
		<div style="margin-top: 20px; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
			<h4 style="margin: 0 0 12px 0; font-size: 16px;">🔒 Password Security Tips</h4>
			<ul style="color: var(--text-muted); font-size: 14px; line-height: 1.6;">
				<li>Use at least 8 characters</li>
				<li>Include uppercase and lowercase letters</li>
				<li>Add numbers and special characters</li>
				<li>Don't use personal information</li>
				<li>Change password regularly</li>
			</ul>
		</div>
	</div>
</div>

<script>
// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
	const newPassword = document.querySelector('input[name="new_password"]');
	const confirmPassword = document.querySelector('input[name="confirm_password"]');
	
	function validatePasswords() {
		if (newPassword.value !== confirmPassword.value) {
			confirmPassword.setCustomValidity('Passwords do not match');
		} else {
			confirmPassword.setCustomValidity('');
		}
	}
	
	newPassword.addEventListener('input', validatePasswords);
	confirmPassword.addEventListener('input', validatePasswords);
});
</script>
