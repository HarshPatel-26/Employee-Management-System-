<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/db.php';

header('Content-Type: application/json; charset=utf-8');

startSession();

$config = require __DIR__ . '/../app/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode([
		'success' => false,
		'error' => 'Invalid request method'
	]);
	exit;
}

$idToken = $_POST['id_token'] ?? '';
$idToken = is_string($idToken) ? trim($idToken) : '';

if ($idToken === '') {
	echo json_encode([
		'success' => false,
		'error' => 'Missing id_token'
	]);
	exit;
}

if (empty($config['google_oauth_client_id'])) {
	echo json_encode([
		'success' => false,
		'error' => 'Server misconfiguration: missing GOOGLE_OAUTH_CLIENT_ID'
	]);
	exit;
}

function google_tokeninfo(string $idToken): array {
	$url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);

	// tokeninfo returns JSON or an error object.
	$body = @file_get_contents($url);
	if ($body !== false) {
		$data = json_decode($body, true);
		return is_array($data) ? $data : ['error' => 'Failed to parse tokeninfo response'];
	}

	// Fallback to cURL if allow_url_fopen is disabled.
	if (!function_exists('curl_init')) {
		return ['error' => 'Unable to call Google tokeninfo (no allow_url_fopen and no cURL)'];
	}

	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_TIMEOUT => 15,
	]);
	$body = curl_exec($ch);
	$errNo = curl_errno($ch);
	$errMsg = curl_error($ch);
	curl_close($ch);

	if ($body === false) {
		return ['error' => 'Google tokeninfo request failed: ' . ($errNo ? (string)$errMsg : 'unknown error')];
	}

	$data = json_decode($body, true);
	return is_array($data) ? $data : ['error' => 'Failed to parse tokeninfo response'];
}

$tokenData = google_tokeninfo($idToken);

if (isset($tokenData['error'])) {
	echo json_encode([
		'success' => false,
		'error' => $tokenData['error']
	]);
	exit;
}

// Basic validation: audience must match our client id.
$aud = $tokenData['aud'] ?? null;
$email = $tokenData['email'] ?? null;
$emailVerified = $tokenData['email_verified'] ?? null;
$name = $tokenData['name'] ?? ($tokenData['given_name'] ?? 'Google User');

if (!$aud || !$email) {
	echo json_encode([
		'success' => false,
		'error' => 'Invalid token payload'
	]);
	exit;
}

if (!hash_equals((string)$config['google_oauth_client_id'], (string)$aud)) {
	echo json_encode([
		'success' => false,
		'error' => 'Token audience mismatch'
	]);
	exit;
}

// For most apps, you typically require a verified email.
if ($emailVerified !== null && (string)$emailVerified !== 'true') {
	echo json_encode([
		'success' => false,
		'error' => 'Google account email is not verified'
	]);
	exit;
}

$emailLower = mb_strtolower($email);
$adminEmailLower = mb_strtolower($config['admin_default_email']);
$bossEmailLower = mb_strtolower($config['boss_default_email']);

$role = 'employee';
if ($emailLower === $adminEmailLower) {
	$role = 'admin';
} elseif ($emailLower === $bossEmailLower) {
	$role = 'boss';
}

$pdo = Database::pdo();
$stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$emailLower]);
$user = $stmt->fetch();

if (!$user) {
	// users.password_hash is NOT NULL in this project schema,
	// so we create a random password hash for Google users.
	$randomPassword = bin2hex(random_bytes(16));
	$passwordHash = password_hash($randomPassword, PASSWORD_BCRYPT);

	$insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
	$insert->execute([
		$name,
		$emailLower,
		$passwordHash,
		$role,
	]);

	$userId = (int)$pdo->lastInsertId();

	$user = [
		'id' => $userId,
		'name' => $name,
		'email' => $emailLower,
		'role' => $role,
	];
} else {
	// Ensure session uses the name/email we have in DB
	$user = [
		'id' => (int)$user['id'],
		'name' => $user['name'],
		'email' => $user['email'],
		'role' => $user['role'],
	];
}

$_SESSION['user'] = [
	'id' => $user['id'],
	'name' => $user['name'] ?: $user['email'],
	'email' => $user['email'],
	'role' => $user['role'],
];

echo json_encode([
	'success' => true,
	'url' => 'dashboard.php'
]);
exit;
