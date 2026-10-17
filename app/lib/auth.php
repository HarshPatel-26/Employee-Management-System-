<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/migrate.php';

function startSession(): void {
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}
}

function login_user(string $email, string $password): ?array { // Authentication Function.
	$pdo = Database::pdo(); // PHP Data Object
	$stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ?');// placeholder email
	$stmt->execute([$email]); 
	$user = $stmt->fetch();
	if ($user && password_verify($password, $user['password_hash'])) {
		startSession();
		$_SESSION['user'] = [ // Loads Information from the data base to the PHP Session.
			'id' => (int)$user['id'],
			'name' => $user['name'],
			'email' => $user['email'],
			'role' => $user['role'],
		];
		return $_SESSION['user'];
	}
	return null;
}

function require_auth(?string $role = null): void {
	startSession();
	if (!isset($_SESSION['user'])) {
		$redirect = str_contains($_SERVER['REQUEST_URI'], '/partials/') ? '../index.php' : 'index.php';
		header('Location: ' . $redirect);
		exit; //If no authenticated session exists, immediately halts execution and redirect to login page
	}
	if ($role !== null && $_SESSION['user']['role'] !== $role) {
		$redirect = str_contains($_SERVER['REQUEST_URI'], '/partials/') ? '../dashboard.php' : 'dashboard.php';
		header('Location: ' . $redirect);
		exit;
	} // if the role asked is not maching then redirect to the dashboard.
}

function current_user(): ?array {
	startSession();
	return $_SESSION['user'] ?? null;
} // Returns the currently authenticated user's session data.

function logout_user(): void {
	startSession();
	$_SESSION = [];
	if (ini_get('session.use_cookies')) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
	}
	session_destroy();
}
