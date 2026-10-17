<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/db.php';
require_auth();
$user = current_user();
if (!in_array($user['role'], ['admin','boss'], true)) { http_response_code(403); exit; }
$pdo = Database::pdo();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="employees_report_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Name', 'Email', 'Role', 'Joined Date', 'Total Attendance Days', 'Total Leave Days', 'Ideas Submitted', 'Transfer Requests']);

$sql = "SELECT 
		u.name, 
		u.email, 
		u.role, 
		u.created_at,
		(SELECT COUNT(*) FROM attendance a WHERE a.user_id = u.id) as attendance_count,
		(SELECT COUNT(*) FROM leaves l WHERE l.user_id = u.id AND l.status = 'approved') as approved_leaves,
		(SELECT COUNT(*) FROM ideas i WHERE i.user_id = u.id) as ideas_count,
		(SELECT COUNT(*) FROM transfers t WHERE t.user_id = u.id) as transfers_count
		FROM users u 
		ORDER BY u.role, u.name";

$stmt = $pdo->query($sql);

foreach ($stmt->fetchAll() as $row) {
	fputcsv($out, [
		$row['name'],
		$row['email'],
		$row['role'],
		$row['created_at'],
		$row['attendance_count'],
		$row['approved_leaves'],
		$row['ideas_count'],
		$row['transfers_count']
	]);
}

fclose($out);
