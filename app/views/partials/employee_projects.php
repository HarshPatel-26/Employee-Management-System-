<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth('employee');
$pdo = Database::pdo();
$user = current_user();

$message = '';

if (($_POST['action'] ?? '') === 'toggle_step') {
	$stepId = (int)($_POST['id'] ?? 0);

	// Fetch the step and its project and assignee
	$checkStmt = $pdo->prepare("SELECT ps.is_done, ps.assigned_to, ps.project_id FROM project_steps ps WHERE ps.id = ?");
	$checkStmt->execute([$stepId]);
	$step = $checkStmt->fetch();

	if (!$step) {
		$message = 'error:Step not found';
	} elseif ($step['is_done'] != 0) {
		$message = 'error:Step is already marked as complete';
	} else {
		$assignedTo = $step['assigned_to'];
		$projectId = $step['project_id'];

		$isAllowed = false;

		// Case A: step is unassigned -> allow any user who is assigned to the project to take it
		if ($assignedTo === null || $assignedTo === '' ) {
			$projCheck = $pdo->prepare("SELECT COUNT(*) FROM project_assignments pa JOIN users u ON u.id = ? WHERE pa.project_id = ? AND (pa.user_id = ? OR pa.team_id = u.team_id)");
			$projCheck->execute([$user['id'], $projectId, $user['id']]);
			$isAllowed = $projCheck->fetchColumn() > 0;
		} else {
			// Case B: step is assigned -> allow assignee or the manager of the assignee
			if ($assignedTo == $user['id']) {
				$isAllowed = true;
			} else {
				$mgrStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND team_id IN (SELECT id FROM teams WHERE manager_id = ?)");
				$mgrStmt->execute([$assignedTo, $user['id']]);
				$isAllowed = $mgrStmt->fetchColumn() > 0;
			}
		}

		if (!$isAllowed) {
			$message = 'error:You are not authorized to complete this step';
		} else {
			try {
				if ($assignedTo === null || $assignedTo === '') {
					// Auto-assign to the current user and mark done
					$stmt = $pdo->prepare('UPDATE project_steps SET is_done = 1, assigned_to = ?, updated_by = ?, updated_at = NOW() WHERE id = ?');
					$success = $stmt->execute([$user['id'], $user['id'], $stepId]);
				} else {
					$stmt = $pdo->prepare('UPDATE project_steps SET is_done = 1, updated_by = ?, updated_at = NOW() WHERE id = ?');
					$success = $stmt->execute([$user['id'], $stepId]);
				}

				if ($success && $stmt->rowCount() > 0) {
					$message = 'success:Step marked as complete';
				} else {
					$message = 'error:No changes were made. Please ensure you have permission.';
				}
			} catch (Throwable $e) {
				error_log("Project step update error: " . $e->getMessage());
				$message = 'error:Database error: ' . $e->getMessage();
			}
		}
	}
    
    $_SESSION['project_message'] = $message;
    echo "<script>window.location.href = 'dashboard.php?tab=projects';</script>";
    exit;
}
if (($_POST['action'] ?? '') === 'add_step') {
    $projectId = (int)($_POST['project_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $assigned_to_id = (int)($_POST['assigned_to'] ?? $user['id']);

    // Security check: ensure user is part of the project (directly or via team)
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) FROM projects p
        JOIN project_assignments pa ON p.id = pa.project_id
        JOIN users u ON u.id = ?
        WHERE p.id = ? AND (pa.user_id = ? OR pa.team_id = u.team_id)
    ");
    $checkStmt->execute([$user['id'], $projectId, $user['id']]);
    $is_assigned_to_project = $checkStmt->fetchColumn() > 0;

    // Security check: if manager, ensure they are assigning to their own team member
    $can_assign = false;
    if ($assigned_to_id === $user['id']) {
        $can_assign = true; // Can always assign to self
    } else {
        $member_check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND team_id IN (SELECT id FROM teams WHERE manager_id = ?)");
        $member_check_stmt->execute([$assigned_to_id, $user['id']]);
        $is_own_team_member = $member_check_stmt->fetchColumn() > 0;
        $can_assign = $is_own_team_member;
    }

    if (!$projectId || !$title) {
        $message = 'error:Please provide both project and step title';
    } elseif (!$is_assigned_to_project) {
        $message = 'error:You are not authorized to add steps to this project';
    } elseif (!$can_assign) {
        $message = 'error:You cannot assign steps to this user';
    } else {
        try {
            // Add the step and assign it
            $stmt = $pdo->prepare('INSERT INTO project_steps (project_id, title, assigned_to, updated_by, updated_at) VALUES (?, ?, ?, ?, NOW())');
            $success = $stmt->execute([$projectId, $title, $assigned_to_id, $user['id']]);
            
            if ($success && $stmt->rowCount() > 0) {
                $message = 'success:Step added successfully';
            } else {
                $message = 'error:Failed to add step. Please try again.';
            }
        } catch (Throwable $e) {
            error_log("Project step insert error: " . $e->getMessage());
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $message = 'error:Invalid project or user reference';
            } else {
                $message = 'error:Database error: ' . $e->getMessage();
            }
        }
    }
    
    $_SESSION['project_message'] = $message;
    echo "<script>window.location.href = 'dashboard.php?tab=projects';</script>";
    exit;
}

$project_stmt = $pdo->prepare("
    SELECT DISTINCT p.* FROM projects p
    JOIN project_assignments pa ON p.id = pa.project_id
    JOIN users u ON u.id = ?
    WHERE pa.user_id = ? OR pa.team_id = u.team_id
    ORDER BY p.id DESC
");
$project_stmt->execute([$user['id'], $user['id']]);
$projects = $project_stmt->fetchAll();

// Check if the current user is a manager of any team
$managed_teams_stmt = $pdo->prepare("SELECT id FROM teams WHERE manager_id = ?");
$managed_teams_stmt->execute([$user['id']]);
$managed_team_ids = $managed_teams_stmt->fetchAll(PDO::FETCH_COLUMN);
$is_manager = !empty($managed_team_ids);

$managed_team_members = [];
if ($is_manager) {
    $in_clause = implode(',', array_fill(0, count($managed_team_ids), '?'));
    $members_stmt = $pdo->prepare("SELECT id, name FROM users WHERE team_id IN ($in_clause) ORDER BY name");
    $members_stmt->execute($managed_team_ids);
    $managed_team_members = $members_stmt->fetchAll();
}

?>
<?php
// Display message if exists
if (isset($_SESSION['project_message'])) {
    list($type, $text) = explode(':', $_SESSION['project_message'], 2);
    echo "<div class='alert $type' style='margin-bottom: 1rem;'>" . htmlspecialchars($text) . "</div>";
    unset($_SESSION['project_message']);
}
?>
<div class="grid-2">
	<?php foreach ($projects as $p): ?>
		<div class="card">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
				<div style="font-size:18px; font-weight:700;"><?php echo htmlspecialchars($p['name']); ?></div>
			</div>
			<div style="color:var(--text-muted);font-size:13.5px;margin-bottom:12px;">
				<?php echo htmlspecialchars($p['description']); ?>
			</div>
			<div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom: 4px;">
				<span style="font-weight:700;">Progress</span>
				<span><?php echo (int)$p['progress']; ?>%</span>
			</div>
			
			<div class="progress-bar" style="margin-bottom: 16px;">
				<div class="progress-bar__fill" style="width: <?php echo (int)$p['progress']; ?>%"></div>
			</div>
			
			<div style="margin-bottom: 20px;">
				<form method="post" style="display: flex; gap: 8px; align-items: center;">
					<input type="hidden" name="action" value="add_step" />
					<input type="hidden" name="project_id" value="<?php echo (int)$p['id']; ?>" />
					<input class="input" name="title" placeholder="Add a new task..." style="flex: 1;" required />
					<?php if ($is_manager && !empty($managed_team_members)): ?>
						<select class="input" name="assigned_to" style="width: 180px;">
							<option value="<?php echo $user['id']; ?>">Assign to Myself</option>
							<optgroup label="Assign to Team Member">
								<?php foreach ($managed_team_members as $member): ?>
									<option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['name']); ?></option>
								<?php endforeach; ?>
							</optgroup>
						</select>
					<?php endif; ?>
					<button class="btn secondary" type="submit">Add</button>
				</form>
			</div>
			
			<div class="table-wrap">
				<table>
					<thead>
					<tr><th>Step</th><th>Status</th><th>Action</th></tr>
					</thead>
					<tbody>
				<?php
					$stmt = $pdo->prepare('SELECT ps.*, u.name as assigned_to_name FROM project_steps ps LEFT JOIN users u ON u.id = ps.assigned_to WHERE ps.project_id = ? ORDER BY ps.id ASC');
					$stmt->execute([$p['id']]);
					$steps = $stmt->fetchAll();
					foreach ($steps as $s):
				?>
				<tr>
					<td>
						<div style="<?php echo $s['is_done'] ? 'text-decoration: line-through; opacity: 0.7;' : ''; ?>">
							<?php echo htmlspecialchars($s['title']); ?>
						</div>
						<?php if ($s['assigned_to_name']): ?>
							<div style="font-size: 12px; color: var(--text-muted);">
								Assigned to: <?php echo htmlspecialchars($s['assigned_to_name']); ?>
							</div>
						<?php endif; ?>
					</td>
					<td>
						<span class="badge badge--<?php echo $s['is_done'] ? 'done' : 'pending'; ?>">
							<?php echo $s['is_done'] ? 'Done' : 'Pending'; ?>
						</span>
					</td>
					<td>
						<?php
							$assigned_to_id = $s['assigned_to'] ?? null;
							$managed_member_ids = array_column($managed_team_members ?? [], 'id');
							$can_mark = false;
							if (!$s['is_done']) {
								// If assigned to current user
								if ($assigned_to_id == $user['id']) {
									$can_mark = true;
								}
								// If unassigned, allow if user is part of the project (directly or via team)
								elseif ($assigned_to_id === null || $assigned_to_id === '' ) {
									try {
										$projCheck = $pdo->prepare("SELECT COUNT(*) FROM project_assignments pa JOIN users u ON u.id = ? WHERE pa.project_id = ? AND (pa.user_id = ? OR pa.team_id = u.team_id)");
										$projCheck->execute([$user['id'], $p['id'], $user['id']]);
										$can_mark = $projCheck->fetchColumn() > 0;
									} catch (Throwable $e) {
										$can_mark = false;
									}
								}
								// If user is manager and assigned_to is a member, allow
								elseif ($is_manager && in_array($assigned_to_id, $managed_member_ids)) {
									$can_mark = true;
								}
							}
						?>
						<?php if ($can_mark): ?>
							<form method="post" style="display:inline;">
								<input type="hidden" name="action" value="toggle_step" />
								<input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>" />								
								<button class="btn success sm" type="submit">Mark Done</button>
							</form>
						<?php elseif ($s['is_done']): ?>
							<span style="color: var(--success-solid);">✓ Completed</span>
						<?php else: ?>
							<span style="color: var(--text-muted);">-</span>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			</div>

			<?php
			// Show team progress if the user is a manager and their team is assigned to this project
			$assigned_managed_team = null;
			if ($is_manager) {
				$team_assignment_stmt = $pdo->prepare("SELECT team_id FROM project_assignments WHERE project_id = ? AND team_id IN (" . implode(',', $managed_team_ids) . ")");
				$team_assignment_stmt->execute([$p['id']]);
				$assigned_managed_team = $team_assignment_stmt->fetch();
			}

			if ($is_manager && $assigned_managed_team):
				$team_id = $assigned_managed_team['team_id'];
				$team_progress_stmt = $pdo->prepare("
					SELECT u.name, COUNT(ps.id) as total_tasks, SUM(ps.is_done) as completed_tasks
					FROM users u
					JOIN project_steps ps ON u.id = ps.assigned_to
					WHERE u.team_id = ? AND ps.project_id = ?
					GROUP BY u.id, u.name
					ORDER BY u.name
				");
				$team_progress_stmt->execute([$team_id, $p['id']]);
				$team_progress = $team_progress_stmt->fetchAll();
			?>
				<div style="margin-top: 20px; border-top: 1px solid var(--border); padding-top: 20px;">
					<h4 style="font-size: 16px; margin-bottom: 12px;">Team Task Progress</h4>
					<?php if (empty($team_progress)): ?>
						<p style="color: var(--text-muted); font-size: 14px;">No tasks have been assigned to your team members for this project yet.</p>
					<?php else: ?>
						<div class="table-wrap">
						<table>
							<thead>
							<tr><th>Team Member</th><th>Completed / Total Tasks</th><th>Progress</th></tr>
							</thead>
							<tbody>
							<?php foreach ($team_progress as $member_progress):
								$member_p = $member_progress['total_tasks'] > 0 ? round(($member_progress['completed_tasks'] / $member_progress['total_tasks']) * 100) : 0;
							?>
							<tr>
								<td style="font-weight: 600;"><?php echo htmlspecialchars($member_progress['name']); ?></td>
								<td><?php echo (int)$member_progress['completed_tasks']; ?> / <?php echo (int)$member_progress['total_tasks']; ?></td>
								<td>
									<div class="progress-bar" style="height: 12px;">
										<div class="progress-bar__fill" style="width: <?php echo $member_p; ?>%; height: 12px;"></div>
									</div>
								</td>
							</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
