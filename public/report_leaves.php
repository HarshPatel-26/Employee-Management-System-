<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/db.php';
require_auth();
$user = current_user();
if (!in_array($user['role'], ['admin','boss'], true)) { http_response_code(403); exit; }
$pdo = Database::pdo();

$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';

$where_conditions = [];
$params = [];

if ($status_filter) {
	$where_conditions[] = 'l.status = ?';
	$params[] = $status_filter;
}

if ($type_filter) {
	$where_conditions[] = 'l.type = ?';
	$params[] = $type_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="leaves_report_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Employee Name', 'Leave Type', 'Start Date', 'End Date', 'Duration (Days)', 'Status', 'Reason', 'Admin Comment', 'Applied Date']);

$sql = "SELECT u.name, l.type, l.start_date, l.end_date, l.status, l.reason, l.admin_comment, l.created_at
		FROM leaves l 
		JOIN users u ON u.id = l.user_id 
		$where_clause 
		ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

foreach ($stmt->fetchAll() as $row) {
	$start = new DateTime($row['start_date']);
	$end = new DateTime($row['end_date']);
	$duration = $start->diff($end)->days + 1;
	
	fputcsv($out, [
		$row['name'],
		$row['type'],
		$row['start_date'],
		$row['end_date'],
		$duration,
		$row['status'],
		$row['reason'] ?: '-',
		$row['admin_comment'] ?: '-',
		$row['created_at']
	]);
}

fclose($out);
