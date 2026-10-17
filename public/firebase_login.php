<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/db.php';

header('Content-Type: application/json; charset=utf-8');

startSession();
$config = require __DIR__ . '/../app/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode([
		'success' => false,
		'error' => 'Invalid request method',
	]);
	exit;
}

$idToken = $_POST['id_token'] ?? '';
$idToken = is_string($idToken) ? trim($idToken) : '';
if ($idToken === '') {
	echo json_encode([
		'success' => false,
		'error' => 'Missing id_token',
	]);
	exit;
}

if (empty($config['firebase_api_key'])) {
	echo json_encode([
		'success' => false,
		'error' => 'Server misconfiguration: missing VITE_FIREBASE_API_KEY',
	]);
	exit;
}

function firebase_accounts_lookup(string $apiKey, string $idToken): array {
	$url = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . urlencode($apiKey);

	$payload = json_encode([
		'idToken' => $idToken,
	]);

	// Prefer cURL if available.
	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
			CURLOPT_POSTFIELDS => $payload,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 20,
		]);

		$body = curl_exec($ch);
		$errNo = curl_errno($ch);
		$errMsg = curl_error($ch);
		$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($body === false) {
			return ['error' => 'Firebase lookup failed: ' . ($errNo ? (string)$errMsg : 'unknown error')];
		}

		$data = json_decode($body, true);
		if (!is_array($data)) {
			return ['error' => 'Failed to parse Firebase lookup response'];
		}

		// Some invalid-token responses still return JSON with "error".
		if ($httpCode >= 400) {
			if (isset($data['error']['message'])) {
				return ['error' => (string)$data['error']['message']];
			}
			return ['error' => 'Firebase lookup returned HTTP ' . $httpCode];
		}

		return $data;
	}

	// Fallback to file_get_contents if cURL is not available.
	$context = stream_context_create([
		'http' => [
			'method' => 'POST',
			'header' => "Content-Type: application/json\r\n",
			'content' => $payload,
			'timeout' => 20,
		],
	]);

	$body = @file_get_contents($url, false, $context);
	if ($body === false) {
		return ['error' => 'Firebase lookup failed (no cURL and file_get_contents failed)'];
	}

	$data = json_decode($body, true);
	return is_array($data) ? $data : ['error' => 'Failed to parse Firebase lookup response'];
}

$lookup = firebase_accounts_lookup($config['firebase_api_key'], $idToken);
if (isset($lookup['error'])) {
	echo json_encode([
		'success' => false,
		'error' => $lookup['error'],
	]);
	exit;
}

$users = $lookup['users'] ?? null;
if (!is_array($users) || empty($users)) {
	echo json_encode([
		'success' => false,
		'error' => 'Invalid token',
	]);
	exit;
}

$firebaseUser = $users[0];
$email = $firebaseUser['email'] ?? null;
$name = $firebaseUser['displayName'] ?? ($firebaseUser['localId'] ?? 'Google User');

if (!$email) {
	echo json_encode([
		'success' => false,
		'error' => 'Google account email not found in token',
	]);
	exit;
}

$emailLower = mb_strtolower((string)$email);
$adminEmailLower = mb_strtolower((string)$config['admin_default_email']);
$bossEmailLower = mb_strtolower((string)$config['boss_default_email']);

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
	// This table requires password_hash, so create a random hash for Google users.
	$randomPassword = bin2hex(random_bytes(16));
	$passwordHash = password_hash($randomPassword, PASSWORD_BCRYPT);

	$insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
	$insert->execute([
		(string)$name,
		$emailLower,
		$passwordHash,
		$role,
	]);

	$userId = (int)$pdo->lastInsertId();
	$user = [
		'id' => $userId,
		'name' => (string)$name,
		'email' => $emailLower,
		'role' => $role,
	];
} else {
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
	'url' => 'dashboard.php',
]);
exit;
