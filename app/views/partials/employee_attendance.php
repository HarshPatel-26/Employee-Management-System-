<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth('employee');
$pdo = Database::pdo();
$user = current_user();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'check_in') {
        $stmt = $pdo->prepare('INSERT INTO attendance (user_id, date, check_in) VALUES (?, CURDATE(), CURTIME()) ON DUPLICATE KEY UPDATE check_in = IFNULL(check_in, VALUES(check_in))');
        try { 
            $stmt->execute([$user['id']]); 
            $message = 'success:Checked in successfully';
        } catch (Throwable $e) {
            $message = 'error:Failed to check in';
        }
    }
    if ($action === 'check_out') {
        $stmt = $pdo->prepare('UPDATE attendance SET check_out = CURTIME() WHERE user_id = ? AND date = CURDATE()');
        try {
            $stmt->execute([$user['id']]);
            $message = 'success:Checked out successfully';
        } catch (Throwable $e) {
            $message = 'error:Failed to check out';
        }
    }
    
    if ($message) {
        $_SESSION['attendance_message'] = $message;
        echo "<script>window.location.href = 'dashboard.php?tab=attendance';</script>";
        exit;
    }
}

$stmt = $pdo->prepare('SELECT * FROM attendance WHERE user_id = ? ORDER BY date DESC LIMIT 14');
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

// Get all attendance records for calendar and total
$allStmt = $pdo->prepare('SELECT date, check_in, check_out FROM attendance WHERE user_id = ? ORDER BY date DESC');
$allStmt->execute([$user['id']]);
$allRows = $allStmt->fetchAll();

// Calculate total attendance days
$total_attendance = 0;
foreach ($allRows as $row) {
	if ($row['check_in']) $total_attendance++;
}

// For recent table, keep last 14
$rows = array_slice($allRows, 0, 14);

// Get today's attendance
$todayStmt = $pdo->prepare('SELECT * FROM attendance WHERE user_id = ? AND date = CURDATE()');
$todayStmt->execute([$user['id']]);
$today = $todayStmt->fetch();
?>
<?php
// Display message if exists
if (isset($_SESSION['attendance_message'])) {
    list($type, $text) = explode(':', $_SESSION['attendance_message'], 2);
    echo "<div class='alert $type' style='margin-bottom: 1rem;'>" . htmlspecialchars($text) . "</div>";
    unset($_SESSION['attendance_message']);
}
?>
<div class="row cols-2">
   <div class="card">
	   <div class="card__title">Today's Attendance</div>
	   <div style="margin-bottom: 12px;">
		   <strong>Total Attendance Days:</strong> <span style="color: var(--success-solid); font-weight: 600; font-size: 1.1em;"> <?php echo $total_attendance; ?> </span>
	   </div>

	   <!-- Calendar Attendance Grid -->
	   <?php
	   // Build calendar for current month
	   $year = (int)date('Y');
	   $month = (int)date('m');
	   $days_in_month = (int)date('t');
	   $first_day_of_week = (int)date('N', strtotime("$year-$month-01")); // 1=Mon, 7=Sun
	   $attendance_map = [];
	   foreach ($allRows as $row) {
		   $d = $row['date'];
		   if (substr($d, 0, 7) === sprintf('%04d-%02d', $year, $month)) {
			   $attendance_map[$d] = ['check_in' => $row['check_in'], 'check_out' => $row['check_out']];
		   }
	   }
	   ?>
	   <div style="margin: 18px 0;">
		   <h4 style="font-size: 1rem; margin-bottom: 8px;">Attendance Calendar (<?php echo date('F Y'); ?>)</h4>
		   <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; background: var(--bg-page); padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
			   <?php
			   // Weekday headers
			   $weekdays = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
			   foreach ($weekdays as $wd) {
				   echo '<div style="text-align:center; font-size:0.9em; font-weight:600; color:var(--text-muted);">'.$wd.'</div>';
			   }
			   // Empty cells before first day
			   for ($i = 1; $i < $first_day_of_week; $i++) {
				   echo '<div></div>';
			   }
			   // Days of month
			   for ($day = 1; $day <= $days_in_month; $day++) {
				   $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
				   $has_record = isset($attendance_map[$date_str]);
				   $has_check_in = $has_record && !empty($attendance_map[$date_str]['check_in']);
				   $has_check_out = $has_record && !empty($attendance_map[$date_str]['check_out']);

				   if ($has_check_out) {
				       $circle_color = 'var(--success-solid)';
				       $circle_border = '2px solid var(--success-solid)';
				       $tooltip = 'Complete (Checked Out)';
				   } elseif ($has_check_in) {
				       $circle_color = 'var(--warning-solid)';
				       $circle_border = '2px solid var(--warning-solid)';
				       $tooltip = 'Partial (Checked In)';
				   } else {
				       $circle_color = 'var(--danger-solid)';
				       $circle_border = '2px solid var(--danger-solid)';
				       $tooltip = 'Absent';
				   }

				   echo '<div style="display:flex; flex-direction:column; align-items:center; gap:2px;">
					   <span style="width:24px; height:24px; border-radius:50%; background:'.$circle_color.'; border:'.$circle_border.'; opacity: 0.8; display:inline-block;" title="'.$tooltip.'"></span>
					   <span style="font-size:0.85em; color:var(--text-muted);">'.$day.'</span>
				   </div>';
			   }
			   ?>
		   </div>
		   <div style="margin-top:8px; font-size:0.95em; color:var(--text-muted);">
			   <span style="display:inline-block; width:18px; height:18px; border-radius:50%; background:var(--success-solid); opacity:0.8; border:2px solid var(--success-solid); margin-right:4px; vertical-align: middle;"></span> Checked Out
			   <span style="display:inline-block; width:18px; height:18px; border-radius:50%; background:var(--warning-solid); opacity:0.8; border:2px solid var(--warning-solid); margin-left:16px; margin-right:4px; vertical-align: middle;"></span> Checked In
			   <span style="display:inline-block; width:18px; height:18px; border-radius:50%; background:var(--danger-solid); opacity:0.8; border:2px solid var(--danger-solid); margin-left:16px; margin-right:4px; vertical-align: middle;"></span> Absent
		   </div>
	   </div>
		<?php if ($today): ?>
			<div style="margin: 16px 0;">
				<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
					<span>Check-in:</span>
					<span style="color: var(--success-solid); font-weight: 600;">
						<?php echo htmlspecialchars($today['check_in'] ?: 'Not checked in'); ?>
					</span>
				</div>
				<div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
					<span>Check-out:</span>
					<span style="color: var(--warning-solid); font-weight: 600;">
						<?php echo htmlspecialchars($today['check_out'] ?: 'Not checked out'); ?>
					</span>
				</div>
			</div>
		<?php endif; ?>
		
		<div style="display: flex; gap: 12px;">
			<form method="post" style="flex: 1;">
				<input type="hidden" name="action" value="check_in" />
				<button class="btn success" type="submit" <?php echo $today && $today['check_in'] ? 'disabled' : ''; ?>>
					<?php echo $today && $today['check_in'] ? 'Already Checked In' : 'Check In'; ?>
				</button>
			</form>
			<form method="post" style="flex: 1;">
				<input type="hidden" name="action" value="check_out" />
				<button class="btn warn" type="submit" <?php echo !$today || !$today['check_in'] || $today['check_out'] ? 'disabled' : ''; ?>>
					<?php echo $today && $today['check_out'] ? 'Already Checked Out' : 'Check Out'; ?>
				</button>
			</form>
		</div>
	</div>
	
	<div class="card">
		<div class="card__title">Recent Attendance</div>
		<div class="table-wrap">
			<table>
				<thead>
					<tr><th>Date</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr>
				</thead>
				<tbody>
				<?php foreach ($rows as $r): ?>
					<tr>
						<td><?php echo htmlspecialchars($r['date']); ?></td>
						<td><?php echo htmlspecialchars($r['check_in'] ?: '-'); ?></td>
						<td><?php echo htmlspecialchars($r['check_out'] ?: '-'); ?></td>
						<td>
							<?php if ($r['check_in'] && $r['check_out']): ?>
								<span class="badge badge--approved">Complete</span>
							<?php elseif ($r['check_in']): ?>
								<span class="badge badge--pending">Partial</span>
							<?php else: ?>
								<span class="badge badge--rejected">Absent</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
