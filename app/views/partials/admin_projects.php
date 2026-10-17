<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth();
$user = current_user();
if (!in_array($user['role'], ['admin','boss'], true)) { header('Location: dashboard.php'); exit; }
$pdo = Database::pdo();

if (($_POST['action'] ?? '') === 'update_progress') {
	$pid = (int)($_POST['project_id'] ?? 0);
	$progress = (int)($_POST['progress'] ?? 0);
	if ($pid && $progress >= 0 && $progress <= 100) {
		$pdo->prepare('UPDATE projects SET progress = ? WHERE id = ?')->execute([$progress, $pid]);
	}
	header('Location: dashboard.php?tab=projects'); exit;
}

// Query to get all projects with user information
$projects = $pdo->query('SELECT * FROM projects ORDER BY id DESC')->fetchAll();


$employees = $pdo->query("SELECT id, name FROM users WHERE role = 'employee' ORDER BY name")->fetchAll();
$teams = $pdo->query("SELECT id, name FROM teams ORDER BY name")->fetchAll();

// --- Performance Optimization: Eager load related data ---
$project_ids = array_map(fn($p) => $p['id'], $projects);
$all_steps = [];
$all_assignments = ['users' => [], 'teams' => []];
$project_members = [];

if (!empty($project_ids)) {
	$in_clause = implode(',', array_fill(0, count($project_ids), '?'));

	// Get all steps for all projects
	$steps_stmt = $pdo->prepare("SELECT ps.*, u_updated.name as updated_by_name, u_assigned.name as assigned_to_name FROM project_steps ps LEFT JOIN users u_updated ON u_updated.id = ps.updated_by LEFT JOIN users u_assigned ON u_assigned.id = ps.assigned_to WHERE ps.project_id IN ($in_clause) ORDER BY ps.id ASC");
	$steps_stmt->execute($project_ids);
	foreach ($steps_stmt->fetchAll() as $step) {
		$all_steps[$step['project_id']][] = $step;
	}

	// Get all assignments for all projects
	$assignments_stmt = $pdo->prepare("SELECT pa.project_id, pa.user_id, pa.team_id, u.name as user_name FROM project_assignments pa LEFT JOIN users u ON u.id = pa.user_id WHERE pa.project_id IN ($in_clause)");
	$assignments_stmt->execute($project_ids);
	foreach ($assignments_stmt->fetchAll() as $assignment) {
		if ($assignment['user_id']) {
			$all_assignments['users'][$assignment['project_id']][] = $assignment['user_id'];
			$project_members[$assignment['project_id']][$assignment['user_id']] = $assignment['user_name'];
		}
		if ($assignment['team_id']) {
			$all_assignments['teams'][$assignment['project_id']][] = $assignment['team_id'];
		}
	}
}
?>
<div class="row cols-2">
	<div class="card">
		<div class="card__title">Create New Project</div>
		<form method="post" class="row">
			<input type="hidden" name="action" value="add_project" />
			<div style="grid-column: 1 / -1;"><input class="input" name="name" placeholder="Project name" required /></div>
			<div style="grid-column: 1 / -1;"><textarea class="input" name="description" placeholder="Project description" rows="2"></textarea></div>
			<div class="row cols-2">
				<div>
					<label>Start Date</label>
					<input class="input" type="date" name="start_date" />
				</div>
				<div>
					<label>End Date</label>
					<input class="input" type="date" name="end_date" />
				</div>
			</div>
			<button class="btn" type="submit">Create Project</button>
		</form>
	</div>
	
	<div class="card">
		<div class="card__title">Project Statistics</div>
		<div style="display: grid; gap: 12px;">
			<?php
			$total_projects = count($projects);
			$completed_projects = count(array_filter($projects, fn($p) => $p['progress'] >= 100));
			$in_progress_projects = count(array_filter($projects, fn($p) => $p['progress'] > 0 && $p['progress'] < 100));
			$pending_projects = count(array_filter($projects, fn($p) => $p['progress'] == 0));
			?>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Total Projects</span>
				<span class="badge badge--teal"><?php echo $total_projects; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Completed</span>
				<span class="badge badge--approved"><?php echo $completed_projects; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>In Progress</span>
				<span class="badge badge--pending"><?php echo $in_progress_projects; ?></span>
			</div>
			<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
				<span>Pending</span>
				<span class="badge badge--inactive"><?php echo $pending_projects; ?></span>
			</div>
		</div>
	</div>
</div>

<div class="grid-2">
	<?php foreach ($projects as $p): ?>
		<div class="card">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
				<div style="font-size:18px; font-weight:700;"><?php echo htmlspecialchars($p['name']); ?></div>
				<span class="badge <?php echo $p['progress'] >= 100 ? 'badge--approved' : ($p['progress'] > 0 ? 'badge--teal' : 'badge--pending'); ?>">
					<?php echo $p['progress'] >= 100 ? 'Completed' : ($p['progress'] > 0 ? 'In Progress' : 'Pending'); ?>
				</span>
			</div>
			
			<div style="color:var(--text-muted);font-size:13.5px;margin-bottom:12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
				<?php echo htmlspecialchars($p['description']); ?>
			</div>
			
			<?php if ($p['start_date'] || $p['end_date']): ?>
				<div style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px; display: flex; align-items: center; gap: 4px;">
					<span>📅</span>
					<span><?php echo $p['start_date'] ? date('M j, Y', strtotime($p['start_date'])) : 'N/A'; ?></span>
					<span>-</span>
					<span><?php echo $p['end_date'] ? date('M j, Y', strtotime($p['end_date'])) : 'N/A'; ?></span>
				</div>
			<?php endif; ?>

			<div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom: 4px;">
				<span style="font-weight:700;">Progress</span>
				<span><?php echo (int)$p['progress']; ?>%</span>
			</div>
			<div class="progress-bar" style="margin-bottom: 16px;">
				<div class="progress-bar__fill" style="width: <?php echo (int)$p['progress']; ?>%"></div>
			</div>
			
			<div style="display: flex; gap: -8px; margin-bottom: 16px; padding-left: 8px;">
				<?php
					$assigned_user_ids = $all_assignments['users'][$p['id']] ?? [];
					$assigned_team_ids = $all_assignments['teams'][$p['id']] ?? [];
					$assigned_users_chunk = array_slice($assigned_user_ids, 0, 5);
					foreach ($assigned_users_chunk as $uid) {
						$uname = $project_members[$p['id']][$uid] ?? 'U';
						echo '<div class="avatar" style="width:28px; height:28px; font-size:10px; border:2px solid #fff; margin-left:-8px;" title="'.htmlspecialchars($uname).'">'.strtoupper(substr($uname,0,1)).'</div>';
					}
					if (count($assigned_user_ids) > 5) {
						echo '<div class="avatar" style="width:28px; height:28px; font-size:10px; border:2px solid #fff; margin-left:-8px; background:var(--bg-page); color:var(--text-muted);">+</div>';
					}
				?>
			</div>

			<form method="post" style="display: flex; gap: 8px; margin-bottom: 16px;">
				<input type="hidden" name="action" value="update_progress" />
				<input type="hidden" name="project_id" value="<?php echo (int)$p['id']; ?>" />
				<input class="input" name="progress" type="number" min="0" max="100" placeholder="%" value="<?php echo (int)$p['progress']; ?>" style="width: 70px;" />
				<button class="btn sm" type="submit">Update</button>
			</form>
			
			<button class="btn sm secondary" style="width:100%" onclick="const el=document.getElementById('project-details-<?php echo $p['id']; ?>'); el.style.display=el.style.display==='none'?'block':'none'">View Details / Assign</button>

			<div id="project-details-<?php echo $p['id']; ?>" style="display:none; margin-top:16px; border-top:1px solid var(--border); padding-top:16px;">
				<h4 style="font-size: 14px; margin-bottom: 12px;">Assign Project</h4>
				<form method="post">
					<input type="hidden" name="action" value="assign_project" />
					<input type="hidden" name="project_id" value="<?php echo (int)$p['id']; ?>" />
					<div class="row">
						<div>
							<label>Employees</label>
							<select class="input" name="users[]" multiple size="4">
								<?php foreach ($employees as $employee): ?>
									<option value="<?php echo $employee['id']; ?>" <?php echo in_array($employee['id'], $assigned_user_ids) ? 'selected' : ''; ?>><?php echo htmlspecialchars($employee['name']); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label>Teams</label>
							<select class="input" name="teams[]" multiple size="3">
								<?php foreach ($teams as $team): ?>
									<option value="<?php echo $team['id']; ?>" <?php echo in_array($team['id'], $assigned_team_ids) ? 'selected' : ''; ?>><?php echo htmlspecialchars($team['name']); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<button class="btn sm info" type="submit" style="margin-top: 12px;">Save Assignments</button>
				</form>

				<h4 style="font-size: 14px; margin-top:20px; margin-bottom: 12px;">Steps</h4>
				<form method="post" style="display: flex; gap: 8px; margin-bottom: 12px;">
					<input type="hidden" name="action" value="add_step" />
					<input type="hidden" name="project_id" value="<?php echo (int)$p['id']; ?>" />
					<input class="input" name="title" placeholder="New step" required />
					<select class="input" name="assigned_to">
						<option value="">Unassigned</option>
						<?php foreach (($project_members[$p['id']] ?? []) as $user_id => $user_name): ?>
							<option value="<?php echo $user_id; ?>"><?php echo htmlspecialchars($user_name); ?></option>
						<?php endforeach; ?>
					</select>
					<button class="btn sm" type="submit">Add</button>
				</form>

				<div class="table-wrap">
					<table>
						<thead><tr><th>Step</th><th>Assigned</th><th>Status</th></tr></thead>
						<tbody>
							<?php foreach (($all_steps[$p['id']] ?? []) as $st): ?>
								<tr>
									<td style="<?php echo $st['is_done'] ? 'text-decoration: line-through; opacity: 0.7;' : ''; ?>">
										<?php echo htmlspecialchars($st['title']); ?>
									</td>
									<td><?php echo htmlspecialchars($st['assigned_to_name'] ?? '-'); ?></td>
									<td>
										<span class="badge badge--<?php echo $st['is_done'] ? 'done' : 'pending'; ?>">
											<?php echo $st['is_done'] ? 'Done' : 'Pending'; ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = document.querySelectorAll('.employee-search');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const selectId = this.getAttribute('data-target');
            const select = document.getElementById(selectId);
            if (!select) return;

            const options = select.options;
            for (let i = 0; i < options.length; i++) {
                const txtValue = options[i].textContent || options[i].innerText;
                options[i].style.display = txtValue.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
            }
        });
    });
});
</script>
