<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/db.php';
require_auth();
$user = current_user();
if (!in_array($user['role'], ['admin','boss'], true)) { http_response_code(403); exit; }
$pdo = Database::pdo();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="ideas_report_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Employee Name', 'Idea Title', 'Description', 'Status', 'Admin Feedback', 'Submitted Date']);

$sql = "SELECT u.name, i.title, i.description, i.status, i.admin_feedback, i.created_at
		FROM ideas i 
		JOIN users u ON u.id = i.user_id 
		ORDER BY i.created_at DESC";

$stmt = $pdo->query($sql);

foreach ($stmt->fetchAll() as $row) {
	fputcsv($out, [
		$row['name'],
		$row['title'],
		$row['description'] ?: '-',
		$row['status'],
		$row['admin_feedback'] ?: '-',
		$row['created_at']
	]);
}

fclose($out);
