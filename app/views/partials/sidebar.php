<?php
$user = current_user();
$is_admin = in_array($user['role'], ['admin', 'boss'], true);
$current_tab = $_GET['tab'] ?? ($is_admin ? 'admin_overview' : 'attendance');

function get_icon($name) {
    global $icon_mapping;
    $filename = isset($icon_mapping[$name]) ? $icon_mapping[$name] : $name;
    $path = __DIR__ . "/../../../public/svg/{$filename}.svg";
    if (file_exists($path)) {
        $svg = file_get_contents($path);
        
        // Remove all color-related attributes
        $svg = preg_replace('/\s+fill="[^"]*"/', '', $svg);
        $svg = preg_replace('/\s+stroke="[^"]*"/', '', $svg);
        $svg = preg_replace('/\s+color="[^"]*"/', '', $svg);
        
        // Remove fixed dimensions but keep viewBox
        $svg = preg_replace('/\s+width="[^"]*"/', '', $svg);
        $svg = preg_replace('/\s+height="[^"]*"/', '', $svg);
        
        // Add our class for styling and set currentColor
        $svg = str_replace('<svg', '<svg class="icon-svg" fill="currentColor"', $svg);
        
        // Replace path/shape specific colors with currentColor
        $svg = preg_replace('/(path|rect|circle|line|polygon)([^>]*?)(fill|stroke)="[^"]*"/', '$1$2$3="currentColor"', $svg);
        
        return $svg;
    }
    return ''; // Return empty string if icon not found
}

// Map menu items to actual SVG filenames
$icon_mapping = [
    'attendance' => 'clock',
    'projects' => 'folders',
    'leaves' => 'calendar-check',
    'tasks' => 'list-checks',
    'ideas' => 'lightbulb',
    'transfers' => 'arrows-left-right',
    'profile' => 'user-circle',
    'overview' => 'chart-line',
    'teams' => 'users-three',
    'notifications' => 'bell',
    'employees' => 'users',
    'reports' => 'chart-bar',
    'logout' => 'sign-out',
    'add_employees' => 'user-plus',
    'edit' => 'pencil-simple'
];

$icon_keys = array_keys($icon_mapping);
$icons = [];
foreach ($icon_keys as $key) {
    $icons[$key] = get_icon($key);
}

$employee_tabs = [
    'attendance' => ['icon' => $icons['attendance'], 'name' => 'Attendance'],
    'projects' => ['icon' => $icons['projects'], 'name' => 'My Projects'],
    'leaves' => ['icon' => $icons['leaves'], 'name' => 'Leave Requests'],
    'tasks' => ['icon' => $icons['tasks'], 'name' => 'Daily Tasks'],
    'ideas' => ['icon' => $icons['ideas'], 'name' => 'Ideas'],
    'transfers' => ['icon' => $icons['transfers'], 'name' => 'Transfers'],
    'profile' => ['icon' => $icons['profile'], 'name' => 'My Profile'],
];

$admin_tabs = [
    'admin_overview' => ['icon' => $icons['overview'], 'name' => 'Admin Overview'],
    'teams' => ['icon' => $icons['teams'], 'name' => 'Manage Teams'],
    'projects' => ['icon' => $icons['projects'], 'name' => 'Manage Projects'],
    'leaves' => ['icon' => $icons['leaves'], 'name' => 'Manage Leaves'],
    'ideas' => ['icon' => $icons['ideas'], 'name' => 'Manage Ideas'],
    'transfers' => ['icon' => $icons['transfers'], 'name' => 'Manage Transfers'],
    'notifications' => ['icon' => $icons['notifications'], 'name' => 'Notifications'],
    'employees' => ['icon' => $icons['employees'], 'name' => 'Manage Employees'],
    'reports' => ['icon' => $icons['reports'], 'name' => 'Reports'],
];

$tabs_to_display = $is_admin ? $admin_tabs : $employee_tabs;

?>
<aside class="vertical-sidebar">
    <input type="checkbox" role="switch" id="checkbox-input" class="checkbox-input" checked />
    <nav class="sidebar">
        <header>
            <div class="sidebar__logo">
              <img src="logo.php" alt="Logo" />
              <span class="sidebar__logo-text">Employee<br>Workflow &amp;<br>Workforce Mgmt</span>
            </div>
            <!-- Toggle moved below logo or removed? I'll keep it as the prompt didn't say to remove it, but replace existing logo/title. -->
            <div class="sidebar__toggle-container">
                <label for="checkbox-input" class="nav__toggle">
                    <span class="toggle--icons" aria-hidden="true">
                        <svg width="17.5px" height="17.5px" viewBox="0 0 24 24" class="toggle-svg-icon toggle--open">
                            <path d="M3 5a1 1 0 1 0 0 2h18a1 1 0 1 0 0-2zM2 12a1 1 0 0 1 1-1h18a1 1 0 1 1 0 2H3a1 1 0 0 1-1-1M2 18a1 1 0 0 1 1-1h18a1 1 0 1 1 0 2H3a1 1 0 0 1-1-1" fill="currentColor"></path>
                        </svg>
                        <svg width="17.5px" height="17.5px" viewBox="0 0 24 24" class="toggle-svg-icon toggle--close">
                            <path d="M18.707 6.707a1 1 0 0 0-1.414-1.414L12 10.586 6.707 5.293a1 1 0 0 0-1.414 1.414L10.586 12l-5.293 5.293a1 1 0 1 0 1.414 1.414L12 13.414l5.293 5.293a1 1 0 0 0 1.414-1.414L13.414 12z" fill="currentColor"></path>
                        </svg>
                    </span>
                </label>
            </div>
        </header>

        <section class="sidebar__wrapper">
            <ul class="sidebar__list list--primary">
                <?php if ($is_admin): ?>
                    <div class="sidebar__section-label">Management</div>
                <?php else: ?>
                    <div class="sidebar__section-label">My Workspace</div>
                <?php endif; ?>
                <?php foreach ($tabs_to_display as $key => $tab_info): ?>
                    <li class="sidebar__item">
                        <a href="dashboard.php?tab=<?php echo $key; ?>" class="sidebar__link <?php echo $current_tab === $key ? 'active' : ''; ?>" data-tooltip="<?php echo $tab_info['name']; ?>">
                            <span class="icon"><?php echo $tab_info['icon']; ?></span>
                            <span class="text"><?php echo $tab_info['name']; ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <ul class="sidebar__list list--secondary">
                <li class="sidebar__item item--heading">
                    <h2 class="sidebar__item--heading">Account</h2>
                </li>
                <?php if (!$is_admin): ?>
                    <!-- Profile link is already in the main employee tabs -->
                <?php endif; ?>
                <li class="sidebar__item">
                    <a class="sidebar__link" href="logout.php" data-tooltip="Logout">
                        <span class="icon"><?php echo $icons['logout']; ?></span>
                        <span class="text">Logout</span>
                    </a>
                </li>
            </ul>
        </section>
    </nav>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        try {
            var cb = document.getElementById('checkbox-input');
            if (!cb) return;
            var key = 'sidebarExpanded';
            var stored = null;
            try { stored = localStorage.getItem(key); } catch (e) { stored = null; }
            if (stored !== null) {
                try { cb.checked = JSON.parse(stored); } catch (e) { cb.checked = !!stored; }
            }

            cb.addEventListener('change', function() {
                try { localStorage.setItem(key, JSON.stringify(cb.checked)); } catch (e) {}
            });

            // Ensure state is saved before navigating away when a sidebar link is clicked
            document.querySelectorAll('.sidebar__link').forEach(function(a) {
                a.addEventListener('click', function() {
                    try { localStorage.setItem(key, JSON.stringify(cb.checked)); } catch (e) {}
                });
            });
        } catch (e) {
            // silent
        }
    });
    </script>
</aside>