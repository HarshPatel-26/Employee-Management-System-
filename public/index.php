<?php
require_once __DIR__ . '/../app/lib/auth.php';
startSession();
$config = require __DIR__ . '/../app/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$user = login_user($email, $password);
	if ($user) {
		$role = $user['role'];
		header('Location: dashboard.php');
		exit;
	} else {
		$error = 'Invalid credentials';
	}
}
?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo htmlspecialchars($config['app_name']); ?> - Login</title>
	<link rel="stylesheet" href="assets/style.css" />
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
	<script src="assets/script.js" defer></script>
	<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
	<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
</head>
<body class="login-page">
	<div class="login-container">
		<div class="login-logo">
			<img src="logo.php?v=2" alt="Employee Workflow & Workforce Management" />
		</div>
		<div class="login-card">
			<div class="login-header">
				<h1 class="login-title">Welcome Back</h1>
				<p class="login-subtitle">Sign in to your account</p>
			</div>
			
			<?php if ($error): ?>
				<div class="alert error"><?php echo htmlspecialchars($error); ?></div>
			<?php endif; ?>
			
			<form method="post" class="row" style="gap: 8px;">
				<div>
					<label style="font-size: 11px; margin-bottom: 2px;">EMAIL ADDRESS</label>
					<input class="input" type="email" name="email" placeholder="Enter your email here" required style="padding: 8px 12px;" />
				</div>
				<div>
					<label style="font-size: 11px; margin-bottom: 2px;">PASSWORD</label>
					<div style="position: relative;">
						<input class="input" type="password" id="login-password" name="password" placeholder="Enter your password" required style="padding: 8px 12px; padding-right: 40px;" />
						<button type="button" onclick="togglePasswordVisibility()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6B7280; padding: 0; display: flex; align-items: center; justify-content: center;">
							<svg id="eye-icon-show" width="16" height="16" fill="currentColor" viewBox="0 0 256 256" style="display: block;">
								<path d="M247.31,124.76c-.35-.79-8.82-19.58-27.65-38.41C194.57,61.26,162.88,48,128,48S61.43,61.26,36.34,86.35C17.51,105.18,9,124,8.69,124.76a8,8,0,0,0,0,6.5c.35.79,8.82,19.58,27.65,38.41C61.43,194.74,93.12,208,128,208s66.57-13.26,91.66-38.35c18.83-18.83,27.3-37.62,27.65-38.41A8,8,0,0,0,247.31,124.76ZM128,192c-30.78,0-57.67-11.19-79.93-33.25A133.47,133.47,0,0,1,25,128,133.33,133.33,0,0,1,48.07,97.25C70.33,75.19,97.22,64,128,64s57.67,11.19,79.93,33.25A133.46,133.46,0,0,1,231.05,128C223.84,141.46,192.43,192,128,192Zm0-112a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160Z"></path>
							</svg>
							<svg id="eye-icon-hide" width="16" height="16" fill="currentColor" viewBox="0 0 256 256" style="display: none;">
								<path d="M128,56C48,56,16,128,16,128s32,72,112,72,112-72,112-72S208,56,128,56Zm0,128C72.82,184,39.3,142.31,29.11,128,39.3,113.69,72.82,72,128,72c55.12,0,88.7,41.69,98.89,56C216.7,142.31,183.18,184,128,184Z"/><circle cx="128" cy="128" r="32"/><line x1="216" y1="40" x2="40" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
						</button>
					</div>
				</div>
				<button class="btn" type="submit" style="width: 100%; margin-top: 4px; padding: 8px; font-size: 13px;">
					Sign In
				</button>
			</form>

			<div style="margin: 10px 0; text-align: center;">
				<div style="font-size: 12px; color: var(--navy); font-weight: 700;">OR</div>
			</div>

			<div id="firebaseSignInDiv" style="width: 100%;">
				<button id="googleSignInButton" class="btn secondary" type="button" style="width:100%; gap:6px; padding: 8px; font-size: 13px;">
					<svg width="15" height="15" viewBox="0 0 24 24"><path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg> 
					Continue with Google
				</button>
			</div>
			<div style="font-size: 11px; color: #494e57ff; margin-top: 6px; text-align: center;">
				Sign in with your Google account.
			</div>
			
			<!-- <div class="demo-creds">
				<h4>Demo Credentials</h4>
				<div>
					<div style="margin-bottom: 8px;">
						<strong>Admin:</strong> <?php echo htmlspecialchars($config['admin_default_email']); ?> / <span style="font-family: monospace; background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px; cursor: pointer;" onclick="this.textContent = this.textContent === '••••••••' ? '<?php echo htmlspecialchars($config['admin_default_password']); ?>' : '••••••••'">••••••••</span>
					</div>
					<div>
						<strong>Boss:</strong> <?php echo htmlspecialchars($config['boss_default_email']); ?> / <span style="font-family: monospace; background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px; cursor: pointer;" onclick="this.textContent = this.textContent === '••••••••' ? '<?php echo htmlspecialchars($config['boss_default_password']); ?>' : '••••••••'">••••••••</span>
					</div>
					<p style="font-size: 11px; margin-top: 8px; color: var(--text-muted);">Click on password to reveal</p>
				</div>
			</div> -->
		</div>
	</div>

	<script>
		(function () {
			const firebaseConfig = {
				apiKey: <?php echo json_encode($config['firebase_api_key'] ?? ''); ?>,
				authDomain: <?php echo json_encode($config['firebase_auth_domain'] ?? ''); ?>,
				projectId: <?php echo json_encode($config['firebase_project_id'] ?? ''); ?>,
				storageBucket: <?php echo json_encode($config['firebase_storage_bucket'] ?? ''); ?>,
				messagingSenderId: <?php echo json_encode($config['firebase_messaging_sender_id'] ?? ''); ?>,
				appId: <?php echo json_encode($config['firebase_app_id'] ?? ''); ?>
			};

			const required = ['apiKey', 'authDomain', 'projectId', 'storageBucket', 'messagingSenderId', 'appId'];
			const missing = required.filter(k => !firebaseConfig[k]);

			const btn = document.getElementById('googleSignInButton');
			if (!btn) return;

			if (!window.firebase || !window.firebase.auth) {
				btn.disabled = true;
				btn.textContent = 'Google login not loaded';
				return;
			}

			if (missing.length) {
				btn.disabled = true;
				btn.textContent = 'Google login not configured';
				return;
			}

			// Initialize Firebase once per page load.
			if (!firebase.apps || !firebase.apps.length) {
				firebase.initializeApp(firebaseConfig);
			}

			const auth = firebase.auth();
			const provider = new firebase.auth.GoogleAuthProvider();
			provider.setCustomParameters({ prompt: 'select_account' });

			btn.addEventListener('click', async function () {
				const oldText = btn.textContent;
				btn.disabled = true;
				btn.textContent = 'Signing in...';

				try {
					const result = await auth.signInWithPopup(provider);
					const idToken = await result.user.getIdToken();

					const res = await fetch('firebase_login.php', {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
						body: new URLSearchParams({ id_token: idToken })
					});

					const data = await res.json();
					if (data && data.success) {
						window.location.href = data.url || 'dashboard.php';
					} else {
						alert((data && data.error) ? data.error : 'Firebase login failed.');
					}
				} catch (e) {
					console.error(e);
					alert('Firebase login failed.');
				} finally {
					btn.disabled = false;
					btn.textContent = oldText;
				}
			});
		})();
	</script>
</body>
</html>
