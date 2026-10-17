<?php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_auth();
$user = current_user();
if (!in_array($user['role'], ['admin', 'boss'], true)) {
    header('Location: dashboard.php');
    exit;
}
$pdo = Database::pdo();

// Fetch data for the page
$teams_stmt = $pdo->query("
    SELECT t.*, u.name as manager_name 
    FROM teams t 
    LEFT JOIN users u ON t.manager_id = u.id 
    ORDER BY t.name
");
$teams = $teams_stmt->fetchAll();

$employees = $pdo->query("SELECT id, name, team_id FROM users WHERE role = 'employee' ORDER BY name")->fetchAll();
$all_users = $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll();

$team_members = [];
foreach ($employees as $employee) {
    if ($employee['team_id']) {
        $team_members[$employee['team_id']][] = $employee;
    }
}
?>

<div class="row cols-2">
    <div class="card">
        <div class="card__title">Create New Team</div>
        <form method="post" class="row">
            <input type="hidden" name="action" value="create_team" />
            <input class="input" name="name" placeholder="Team Name (e.g., 'Frontend Developers')" required />
            <textarea class="input" name="description" placeholder="Team Description" rows="3"></textarea>
            <button class="btn" type="submit">Create Team</button>
        </form>
    </div>

    <div class="card">
        <div class="card__title">Team Statistics</div>
        <?php
        $unassigned_employees = count(array_filter($employees, fn ($e) => $e['team_id'] === null));
        ?>
        <div style="display: grid; gap: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                <span>Total Teams</span>
                <span class="badge badge--teal"><?php echo count($teams); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                <span>Total Employees</span>
                <span class="badge badge--approved"><?php echo count($employees); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                <span>Unassigned Employees</span>
                <span class="badge badge--pending"><?php echo $unassigned_employees; ?></span>
            </div>
        </div>
    </div>
</div>

<div class="card__title" style="margin-top:24px;">Manage Teams</div>
<div class="grid-3" style="margin-bottom: 24px;">
    <?php foreach ($teams as $team) : ?>
        <div class="card">
            <h4 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 700;"><?php echo htmlspecialchars($team['name'] ?? 'Unnamed Team'); ?></h4>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 16px;"><?php echo htmlspecialchars($team['description'] ?? ''); ?></p>
            <div style="font-size: 13px; margin-bottom: 8px;">
                <strong>Manager:</strong> <?php echo htmlspecialchars($team['manager_name'] ?? 'None'); ?>
            </div>
            <div style="font-size: 13px; margin-bottom: 16px;">
                <strong>Members:</strong> <span class="badge badge--approved"><?php echo count($team_members[$team['id']] ?? []); ?></span>
            </div>
            <button type="button" class="btn sm secondary" onclick="const el=document.getElementById('edit-team-<?php echo $team['id']; ?>'); el.style.display=el.style.display==='none'?'block':'none'">Edit Team</button>

            <div id="edit-team-<?php echo $team['id']; ?>" style="display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border);">
                <form method="post">
                    <input type="hidden" name="action" value="update_team" />
                    <input type="hidden" name="team_id" value="<?php echo (int)$team['id']; ?>" />

                    <div>
                        <label>Team Leader / Manager</label>
                        <select class="input" name="manager_id">
                            <option value="">-- No Manager --</option>
                            <?php foreach ($all_users as $u) : ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo $team['manager_id'] == $u['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="margin-top: 12px;">
                        <label>Team Members</label>
                        <input type="text" class="input employee-search" data-target="team-members-<?php echo (int)$team['id']; ?>" placeholder="Search employees..." style="margin-bottom: 8px;">
                        <select id="team-members-<?php echo (int)$team['id']; ?>" class="input" name="members[]" multiple size="5">
                            <?php foreach ($employees as $employee) : ?>
                                <?php if ($employee['team_id'] === null || $employee['team_id'] == $team['id']) : ?>
                                    <option value="<?php echo $employee['id']; ?>" <?php echo $employee['team_id'] == $team['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($employee['name']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--text-muted); font-size: 12px;">Hold Ctrl/Cmd to select multiple.</small>
                    </div>
                    <button class="btn info sm" type="submit" style="margin-top: 12px;">Save Changes</button>
                </form>
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