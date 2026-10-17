<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/db.php';
require_auth();
$user = current_user();
if (!in_array($user['role'], ['admin','boss'], true)) { http_response_code(403); exit; }
$pdo = Database::pdo();

$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$where_conditions = [];
$params = [];

if ($from_date) {
	$where_conditions[] = 'a.date >= ?';
	$params[] = $from_date;
}

if ($to_date) {
	$where_conditions[] = 'a.date <= ?';
	$params[] = $to_date;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Employee Name', 'Date', 'Check-in', 'Check-out', 'Duration', 'Status']);

$sql = "SELECT u.name, a.date, a.check_in, a.check_out 
		FROM attendance a 
		JOIN users u ON u.id = a.user_id 
		$where_clause 
		ORDER BY a.date DESC, u.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

foreach ($stmt->fetchAll() as $row) {
	$duration = '-';
	$status = 'Absent';
	
	if ($row['check_in'] && $row['check_out']) {
		$checkin = new DateTime($row['check_in']);
		$checkout = new DateTime($row['check_out']);
		$duration = $checkin->diff($checkout)->format('%h hours %i minutes');
		$status = 'Complete';
	} elseif ($row['check_in']) {
		$status = 'Partial';
	}
	
	fputcsv($out, [
		$row['name'],
		$row['date'],
		$row['check_in'] ?: '-',
		$row['check_out'] ?: '-',
		$duration,
		$status
	]);
}

fclose($out);
