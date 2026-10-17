<?php
require_once __DIR__ . '/db.php';

function migrateAndSeed(): void {
	$pdo = Database::pdo();
	
	// Users table
	$pdo->exec("CREATE TABLE IF NOT EXISTS users (
		id INT AUTO_INCREMENT PRIMARY KEY,
		name VARCHAR(100) NOT NULL,
		email VARCHAR(150) NOT NULL UNIQUE,
		password_hash VARCHAR(255) NOT NULL,
		role ENUM('employee','admin','boss') NOT NULL DEFAULT 'employee',
		team_id int(11) DEFAULT NULL,
		phone varchar(20) DEFAULT NULL,
		address text DEFAULT NULL,
		hire_date date DEFAULT NULL,
		salary decimal(10,2) DEFAULT NULL,
		status enum('active','inactive') DEFAULT 'active',
		created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	// Teams table (non-destructive)
	// Create table only if it does not exist to avoid removing user-created teams.
	$pdo->exec("CREATE TABLE IF NOT EXISTS teams (
		id INT AUTO_INCREMENT PRIMARY KEY,
		name VARCHAR(100) NOT NULL,
		description text DEFAULT NULL,
		manager_id int(11) DEFAULT NULL,
		created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	// Add missing description column to teams if it doesn't exist
	try {
		$pdo->exec("ALTER TABLE `teams` ADD COLUMN `description` TEXT NULL DEFAULT NULL AFTER `name`;");
	} catch (Throwable $e) {}

	// Add missing manager_id column to teams if it doesn't exist
	try {
		$pdo->exec("ALTER TABLE `teams` ADD COLUMN `manager_id` INT(11) NULL DEFAULT NULL, ADD KEY `idx_manager` (`manager_id`), ADD CONSTRAINT `fk_teams_manager` FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;");
	} catch (Throwable $e) {}

	// Attendance table
	$pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		date DATE NOT NULL,
		check_in TIME NULL,
		check_out TIME NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		INDEX(user_id),
		FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	
	try { 
		$pdo->exec('ALTER TABLE attendance ADD UNIQUE KEY uniq_user_date (user_id, date)'); 
	} catch (Throwable $e) {}

	// Leaves table
	$pdo->exec("CREATE TABLE IF NOT EXISTS leaves (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		type VARCHAR(50) NOT NULL,
		start_date DATE NOT NULL,
		end_date DATE NOT NULL,
		days_count INT NOT NULL,
		reason TEXT NULL,
		status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
		admin_comment TEXT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		INDEX(user_id),
		FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	// Tasks table
	$pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		date DATE NOT NULL,
		title VARCHAR(200) NOT NULL,
		status ENUM('pending','done') NOT NULL DEFAULT 'pending',
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		INDEX(user_id),
		FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	// Projects table
	$pdo->exec("CREATE TABLE IF NOT EXISTS projects (
		id INT AUTO_INCREMENT PRIMARY KEY,
		name VARCHAR(150) NOT NULL,
		description TEXT NULL,
		start_date DATE NULL,
		end_date DATE NULL,
		progress INT NOT NULL DEFAULT 0,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	// Add missing date columns to projects if they don't exist
	try {
		$pdo->exec("ALTER TABLE `projects` ADD COLUMN `start_date` DATE NULL AFTER `description`, ADD COLUMN `end_date` DATE NULL AFTER `start_date`;");
	} catch (Throwable $e) {}

	// Project steps table
	$pdo->exec("CREATE TABLE IF NOT EXISTS project_steps (
		id INT AUTO_INCREMENT PRIMARY KEY,
		project_id INT NOT NULL,
		title VARCHAR(200) NOT NULL,
		is_done TINYINT(1) NOT NULL DEFAULT 0,
		assigned_to INT NULL,
		updated_by INT NULL,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		INDEX(project_id),
		FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
		FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
		FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	// Add missing assigned_to column to project_steps if it doesn't exist
	try {
		$pdo->exec("ALTER TABLE `project_steps` ADD COLUMN `assigned_to` INT NULL, ADD FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL;");
	} catch (Throwable $e) {}

	// Triggers for project progress
	try {
		// Trigger to update project progress when a step's status changes
		$pdo->exec("DROP TRIGGER IF EXISTS `update_project_progress_on_update`");
		$pdo->exec("
			CREATE TRIGGER `update_project_progress_on_update` 
			AFTER UPDATE ON `project_steps`
			FOR EACH ROW
			BEGIN
				DECLARE total_steps INT;
				DECLARE completed_steps INT;
				DECLARE progress_percentage INT;
				
				SELECT COUNT(*) INTO total_steps FROM project_steps WHERE project_id = NEW.project_id;
				SELECT COUNT(*) INTO completed_steps FROM project_steps WHERE project_id = NEW.project_id AND is_done = 1;
				
				IF total_steps > 0 THEN
					SET progress_percentage = ROUND((completed_steps / total_steps) * 100);
				ELSE
					SET progress_percentage = 0;
				END IF;
				
				UPDATE projects SET progress = progress_percentage WHERE id = NEW.project_id;
			END
		");

		// Trigger to update project progress when a new step is added
		$pdo->exec("DROP TRIGGER IF EXISTS `update_project_progress_on_insert`");
		$pdo->exec("
			CREATE TRIGGER `update_project_progress_on_insert` 
			AFTER INSERT ON `project_steps`
			FOR EACH ROW
			BEGIN
				CALL RecalculateProjectProgress(NEW.project_id);
			END
		");

		// Trigger to update project progress when a step is deleted
		$pdo->exec("DROP TRIGGER IF EXISTS `update_project_progress_on_delete`");
		$pdo->exec("
			CREATE TRIGGER `update_project_progress_on_delete` 
			AFTER DELETE ON `project_steps`
			FOR EACH ROW
			BEGIN
				CALL RecalculateProjectProgress(OLD.project_id);
			END
		");

	} catch (Throwable $e) {}

	// Project Assignments table
	$pdo->exec("CREATE TABLE IF NOT EXISTS `project_assignments` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`project_id` int(11) NOT NULL,
		`user_id` int(11) DEFAULT NULL,
		`team_id` int(11) DEFAULT NULL,
		`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
		FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
		FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	// Stored Procedure for recalculating progress
	try {
		$pdo->exec("DROP PROCEDURE IF EXISTS `RecalculateProjectProgress`");
		$pdo->exec("
			CREATE PROCEDURE `RecalculateProjectProgress`(IN p_id INT)
			BEGIN
				DECLARE total_steps INT;
				DECLARE completed_steps INT;
				DECLARE progress_percentage INT;

				SELECT COUNT(*) INTO total_steps FROM project_steps WHERE project_id = p_id;
				SELECT COUNT(*) INTO completed_steps FROM project_steps WHERE project_id = p_id AND is_done = 1;

				IF total_steps > 0 THEN
					SET progress_percentage = ROUND((completed_steps / total_steps) * 100);
				ELSE
					SET progress_percentage = 0;
				END IF;

				UPDATE projects SET progress = progress_percentage WHERE id = p_id;
			END
		");
	} catch (Throwable $e) {}

	// Ideas table
	$pdo->exec("CREATE TABLE IF NOT EXISTS ideas (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		title VARCHAR(200) NOT NULL,
		description TEXT NULL,
		category varchar(50) DEFAULT 'General',
    	impact enum('low','medium','high') DEFAULT 'medium',
		status ENUM('pending','approved','rejected','implemented') NOT NULL DEFAULT 'pending',
		admin_feedback TEXT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		INDEX(user_id),
		FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	// Add missing columns to ideas table if they don't exist
	try {
		$pdo->exec("ALTER TABLE `ideas` ADD COLUMN `category` VARCHAR(50) DEFAULT 'General' AFTER `description`;");
	} catch (Throwable $e) {}
	try {
		$pdo->exec("ALTER TABLE `ideas` ADD COLUMN `impact` ENUM('low','medium','high') DEFAULT 'medium' AFTER `category`;");
	} catch (Throwable $e) {}

	// Transfers table
	$pdo->exec("CREATE TABLE IF NOT EXISTS transfers (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		current_team_id INT NULL,
		target_team_id INT NULL,
		target_team VARCHAR(150) NOT NULL,
		reason TEXT NULL,
		status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
		admin_comment TEXT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		INDEX(user_id),
		FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
		FOREIGN KEY (current_team_id) REFERENCES teams(id) ON DELETE SET NULL,
		FOREIGN KEY (target_team_id) REFERENCES teams(id) ON DELETE SET NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	// Add missing columns to transfers table if they don't exist
	try {
		$pdo->exec("ALTER TABLE `transfers` ADD COLUMN `current_team_id` INT NULL AFTER `user_id`, ADD FOREIGN KEY (`current_team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL;");
	} catch (Throwable $e) {}
	try {
		$pdo->exec("ALTER TABLE `transfers` ADD COLUMN `target_team_id` INT NULL AFTER `current_team_id`, ADD FOREIGN KEY (`target_team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL;");
	} catch (Throwable $e) {}

	// Notifications table
	$pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`user_id` int(11) DEFAULT NULL,
		`type` varchar(50) NOT NULL,
		`title` varchar(200) NOT NULL,
		`message` text NOT NULL,
		`payload` text DEFAULT NULL,
		`is_read` tinyint(1) NOT NULL DEFAULT 0,
		`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		KEY `idx_user` (`user_id`),
		FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	// Add missing columns to notifications table if they don't exist
	try {
		$pdo->exec("ALTER TABLE `notifications` ADD COLUMN `title` VARCHAR(200) NOT NULL AFTER `type`;");
	} catch (Throwable $e) {}
	try {
		$pdo->exec("ALTER TABLE `notifications` ADD COLUMN `message` TEXT NOT NULL AFTER `title`;");
	} catch (Throwable $e) {}

	// Employee Performance table
	$pdo->exec("CREATE TABLE IF NOT EXISTS `employee_performance` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`user_id` int(11) NOT NULL,
		`review_period` varchar(50) NOT NULL,
		`reviewer_id` int(11) NOT NULL,
		`overall_rating` decimal(3,2) DEFAULT NULL,
		`comments` text DEFAULT NULL,
		`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
		FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	// Assets table
	$pdo->exec("CREATE TABLE IF NOT EXISTS `assets` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(150) NOT NULL,
		`type` enum('laptop','mobile','id_card','other') NOT NULL,
		`serial_number` varchar(100) DEFAULT NULL,
		`status` enum('available','assigned','in_repair','retired') NOT NULL DEFAULT 'available',
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	// Asset Assignments table
	$pdo->exec("CREATE TABLE IF NOT EXISTS `asset_assignments` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`asset_id` int(11) NOT NULL,
		`user_id` int(11) NOT NULL,
		`status` enum('pending_request','assigned','returned','rejected') NOT NULL DEFAULT 'pending_request',
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$config = Database::getConfig();
	seedUserIfMissing($pdo, 'Admin', $config['admin_default_email'], $config['admin_default_password'], 'admin');
	seedUserIfMissing($pdo, 'Boss', $config['boss_default_email'], $config['boss_default_password'], 'boss');
	
	// Seed sample data
	seedSampleData($pdo);
}

function seedUserIfMissing(PDO $pdo, string $name, string $email, string $password, string $role): void {
	// Ensure the users table exists and is accessible before querying
	try {
		$check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users'");
		$check->execute();
		$exists = (int)$check->fetchColumn() > 0;
	} catch (Throwable $e) {
		error_log('Seed check failed: ' . $e->getMessage());
		return; // Bail out if information_schema can't be queried
	}

	if (!$exists) {
		error_log('Seed skipped: users table does not exist in database.');
		return;
	}

	try {
		$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
		$stmt->execute([$email]);
		if (!$stmt->fetch()) {
			$hash = password_hash($password, PASSWORD_BCRYPT);
			$insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
			$insert->execute([$name, $email, $hash, $role]);
		}
	} catch (Throwable $e) {
		// Log and skip seeding if users table cannot be queried (avoid fatal error)
		error_log('Seed user failed: ' . $e->getMessage());
		return;
	}
}

function seedSampleData(PDO $pdo): void {
    // Check if teams table exists and is accessible
    try {
        $pdo->query("SELECT 1 FROM teams LIMIT 1");
    } catch (PDOException $e) {
        if ($e->getCode() == "42S02") {
            // Table doesn't exist or is corrupted, recreate it
            $pdo->exec("DROP TABLE IF EXISTS teams");
            $pdo->exec("CREATE TABLE `teams` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `description` text DEFAULT NULL,
                `manager_id` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_manager` (`manager_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } else {
            throw $e; // Re-throw if it's a different error
        }
    }

    // Add sample teams
    $teams = ['Development', 'Design', 'Marketing', 'Sales', 'HR', 'Finance'];
    foreach ($teams as $team) {
        $stmt = $pdo->prepare('SELECT id FROM teams WHERE name = ?');
        $stmt->execute([$team]);
        if (!$stmt->fetch()) {
            $pdo->prepare('INSERT INTO teams (name, description) VALUES (?, ?)')
                ->execute([$team, 'Sample ' . $team . ' team.']);
        }
    }
	
	// Add sample projects
	$projects = [
		['name' => 'Website Redesign', 'description' => 'Complete overhaul of company website with modern design and improved UX'],
		['name' => 'Mobile App Development', 'description' => 'Cross-platform mobile application for customer engagement and support'],
		['name' => 'CRM System Implementation', 'description' => 'Customer relationship management system with advanced analytics'],
		['name' => 'Analytics Dashboard', 'description' => 'Real-time analytics and reporting dashboard for business insights'],
		['name' => 'E-commerce Platform', 'description' => 'Online shopping platform with payment integration and inventory management'],
		['name' => 'Employee Portal', 'description' => 'Internal portal for employee management and communication']
	];
	
	foreach ($projects as $project) {
		$stmt = $pdo->prepare('SELECT id FROM projects WHERE name = ?');
		$stmt->execute([$project['name']]);
		if (!$stmt->fetch()) {
			$pdo->prepare('INSERT INTO projects (name, description) VALUES (?, ?)')->execute([$project['name'], $project['description']]);
		}
	}
	
	// Add sample project steps
	$projectSteps = [
		['project' => 'Website Redesign', 'steps' => ['Wireframe Design', 'UI/UX Design', 'Frontend Development', 'Backend Integration', 'Testing & QA', 'Deployment', 'SEO Optimization']],
		['project' => 'Mobile App Development', 'steps' => ['Requirements Analysis', 'App Architecture', 'UI Design', 'Development', 'Testing', 'App Store Submission', 'Marketing']],
		['project' => 'CRM System Implementation', 'steps' => ['Database Design', 'API Development', 'Frontend Interface', 'Integration', 'User Training', 'Go Live', 'Support']],
		['project' => 'Analytics Dashboard', 'steps' => ['Data Collection Setup', 'Dashboard Design', 'Chart Implementation', 'Real-time Updates', 'Performance Optimization', 'User Training']],
		['project' => 'E-commerce Platform', 'steps' => ['Platform Setup', 'Payment Integration', 'Inventory Management', 'User Interface', 'Testing', 'Launch', 'Marketing']],
		['project' => 'Employee Portal', 'steps' => ['System Architecture', 'User Management', 'Feature Development', 'Security Implementation', 'Testing', 'Deployment']]
	];
	
	foreach ($projectSteps as $projectData) {
		$stmt = $pdo->prepare('SELECT id FROM projects WHERE name = ?');
		$stmt->execute([$projectData['project']]);
		$project = $stmt->fetch();
		if ($project) {
			foreach ($projectData['steps'] as $step) {
				$stepStmt = $pdo->prepare('SELECT id FROM project_steps WHERE project_id = ? AND title = ?');
				$stepStmt->execute([$project['id'], $step]);
				if (!$stepStmt->fetch()) {
					$pdo->prepare('INSERT INTO project_steps (project_id, title, is_done) VALUES (?, ?, ?)')
						->execute([$project['id'], $step, rand(0, 1)]);
				}
			}
		}
	}

}

migrateAndSeed();
