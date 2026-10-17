-- ===============================================
-- Company Portal Database Setup
-- Use this file in phpMyAdmin to create the complete database
-- ===============================================
-- Create database
CREATE DATABASE `company_portal` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE `company_portal`;

-- ===============================================
-- 1. USERS TABLE
-- ===============================================
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(150) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `role` enum('employee','admin','boss') NOT NULL DEFAULT 'employee',
    `team_id` int(11) DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `hire_date` date DEFAULT NULL,
    `salary` decimal(10,2) DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_team` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 2. TEAMS TABLE
-- ===============================================
CREATE TABLE `teams` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `manager_id` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_manager` (`manager_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 3. ATTENDANCE TABLE
-- ===============================================
CREATE TABLE `attendance` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `date` date NOT NULL,
    `check_in` time DEFAULT NULL,
    `check_out` time DEFAULT NULL,
    `break_time` int(11) DEFAULT 0 COMMENT 'Break time in minutes',
    `total_hours` decimal(4,2) DEFAULT NULL,
    `status` enum('present','absent','late','half_day') DEFAULT 'present',
    `notes` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_user_date` (`user_id`, `date`),
    KEY `idx_date` (`date`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 4. LEAVES TABLE
-- ===============================================
CREATE TABLE `leaves` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `type` varchar(50) NOT NULL,
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `days_count` int(11) NOT NULL,
    `reason` text DEFAULT NULL,
    `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_comment` text DEFAULT NULL,
    `approved_by` int(11) DEFAULT NULL,
    `approved_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_dates` (`start_date`, `end_date`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 5. TASKS TABLE
-- ===============================================
CREATE TABLE `tasks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `date` date NOT NULL,
    `title` varchar(200) NOT NULL,
    `description` text DEFAULT NULL,
    `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
    `status` enum('pending','in_progress','done','cancelled') NOT NULL DEFAULT 'pending',
    `estimated_hours` decimal(4,2) DEFAULT NULL,
    `actual_hours` decimal(4,2) DEFAULT NULL,
    `due_date` date DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_date` (`date`),
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 6. PROJECTS TABLE
-- ===============================================
CREATE TABLE `projects` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(150) NOT NULL,
    `description` text DEFAULT NULL,
    `status` enum('planning','active','on_hold','completed','cancelled') DEFAULT 'planning',
    `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
    `progress` int(11) NOT NULL DEFAULT 0,
    `start_date` date DEFAULT NULL,
    `end_date` date DEFAULT NULL,
    `budget` decimal(12,2) DEFAULT NULL,
    `manager_id` int(11) DEFAULT NULL,
    `client_name` varchar(100) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_manager` (`manager_id`),
    FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 7. PROJECT STEPS TABLE
-- ===============================================
CREATE TABLE `project_steps` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `project_id` int(11) NOT NULL,
    `title` varchar(200) NOT NULL,
    `description` text DEFAULT NULL,
    `order_index` int(11) DEFAULT 0,
    `is_done` tinyint(1) NOT NULL DEFAULT 0,
    `assigned_to` int(11) DEFAULT NULL,
    `due_date` date DEFAULT NULL,
    `completed_at` timestamp NULL DEFAULT NULL,
    `updated_by` int(11) DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_assigned` (`assigned_to`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 8. IDEAS TABLE
-- ===============================================
CREATE TABLE `ideas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `title` varchar(200) NOT NULL,
    `description` text DEFAULT NULL,
    `category` varchar(50) DEFAULT NULL,
    `impact` enum('low','medium','high') DEFAULT 'medium',
    `status` enum('pending','approved','rejected','implemented') NOT NULL DEFAULT 'pending',
    `admin_feedback` text DEFAULT NULL,
    `implementation_notes` text DEFAULT NULL,
    `reviewed_by` int(11) DEFAULT NULL,
    `reviewed_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_category` (`category`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 9. TRANSFERS TABLE
-- ===============================================
CREATE TABLE `transfers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `current_team_id` int(11) DEFAULT NULL,
    `target_team_id` int(11) DEFAULT NULL,
    `target_team` varchar(150) NOT NULL,
    `reason` text DEFAULT NULL,
    `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_comment` text DEFAULT NULL,
    `effective_date` date DEFAULT NULL,
    `approved_by` int(11) DEFAULT NULL,
    `approved_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`current_team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`target_team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 10. NOTIFICATIONS TABLE
-- ===============================================
CREATE TABLE `notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `type` varchar(50) NOT NULL,
    `title` varchar(200) NOT NULL,
    `message` text NOT NULL,
    `payload` text DEFAULT NULL COMMENT 'JSON data for additional info',
    `is_read` tinyint(1) NOT NULL DEFAULT 0,
    `priority` enum('low','medium','high') DEFAULT 'medium',
    `expires_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_read` (`is_read`),
    KEY `idx_created` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- FOREIGN KEY CONSTRAINTS FOR USERS TABLE
-- ===============================================
ALTER TABLE `users` 
ADD CONSTRAINT `fk_users_team` 
FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL;

ALTER TABLE `teams` 
ADD CONSTRAINT `fk_teams_manager` 
FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- ===============================================
-- SAMPLE DATA INSERTION
-- ===============================================

-- Insert Teams
INSERT INTO `teams` (`name`, `description`) VALUES
('Development', 'Software development and engineering team'),
('Design', 'UI/UX and graphic design team'),
('Marketing', 'Digital marketing and promotions team'),
('Sales', 'Sales and customer acquisition team'),
('HR', 'Human resources and recruitment team'),
('Finance', 'Finance and accounting team'),
('Support', 'Customer support and service team'),
('Operations', 'Business operations and management team');

-- Insert Default Users (Admin, Boss, Sample Employees)
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `phone`, `address`, `hire_date`, `salary`, `team_id`) VALUES
('System Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+1-555-0001', '123 Admin Street, City', '2023-01-01', 75000.00, 8),
('Company Boss', 'boss@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'boss', '+1-555-0002', '456 Executive Ave, City', '2022-01-01', 150000.00, 8),
('John Doe', 'employee@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '+1-555-0003', '789 Employee Rd, City', '2023-03-15', 55000.00, 1),
('Jane Smith', 'jane.smith@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '+1-555-0004', '321 Worker Lane, City', '2023-02-10', 58000.00, 2),
('Mike Johnson', 'mike.johnson@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '+1-555-0005', '654 Staff St, City', '2023-04-20', 52000.00, 3),
('Sarah Wilson', 'sarah.wilson@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '+1-555-0006', '987 Team Ave, City', '2023-05-01', 60000.00, 4),
('David Brown', 'david.brown@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '+1-555-0007', '147 Member Blvd, City', '2023-06-15', 54000.00, 5);

-- Update team managers
UPDATE `teams` SET `manager_id` = 1 WHERE `name` = 'Operations';
UPDATE `teams` SET `manager_id` = 3 WHERE `name` = 'Development';
UPDATE `teams` SET `manager_id` = 4 WHERE `name` = 'Design';

-- Insert Sample Projects
INSERT INTO `projects` (`name`, `description`, `status`, `priority`, `start_date`, `end_date`, `budget`, `manager_id`, `client_name`) VALUES
('Website Redesign', 'Complete overhaul of company website with modern design and improved UX', 'active', 'high', '2024-01-01', '2024-06-30', 50000.00, 1, 'Internal Project'),
('Mobile App Development', 'Cross-platform mobile application for customer engagement and support', 'active', 'high', '2024-02-01', '2024-08-31', 75000.00, 3, 'TechCorp Inc'),
('CRM System Implementation', 'Customer relationship management system with advanced analytics', 'planning', 'medium', '2024-03-01', '2024-10-31', 100000.00, 1, 'BusinessSoft Ltd'),
('Analytics Dashboard', 'Real-time analytics and reporting dashboard for business insights', 'active', 'medium', '2024-01-15', '2024-05-31', 35000.00, 3, 'DataViz Solutions'),
('E-commerce Platform', 'Online shopping platform with payment integration and inventory management', 'planning', 'high', '2024-04-01', '2024-12-31', 120000.00, 1, 'ShopMaster Co'),
('Employee Portal Enhancement', 'Upgrade existing employee portal with new features and modern UI', 'completed', 'medium', '2023-09-01', '2024-01-31', 25000.00, 1, 'Internal Project');

-- Insert Project Steps
INSERT INTO `project_steps` (`project_id`, `title`, `description`, `order_index`, `is_done`, `assigned_to`) VALUES
-- Website Redesign Steps
(1, 'Wireframe Design', 'Create wireframes for all pages', 1, 1, 4),
(1, 'UI/UX Design', 'Design user interface and experience', 2, 1, 4),
(1, 'Frontend Development', 'Develop responsive frontend', 3, 0, 3),
(1, 'Backend Integration', 'Integrate with backend services', 4, 0, 3),
(1, 'Testing & QA', 'Comprehensive testing and quality assurance', 5, 0, 5),
(1, 'Deployment', 'Deploy to production environment', 6, 0, 1),
(1, 'SEO Optimization', 'Search engine optimization', 7, 0, 5),

-- Mobile App Steps
(2, 'Requirements Analysis', 'Analyze and document requirements', 1, 1, 1),
(2, 'App Architecture', 'Design application architecture', 2, 1, 3),
(2, 'UI Design', 'Design mobile user interface', 3, 0, 4),
(2, 'Development', 'Develop mobile application', 4, 0, 3),
(2, 'Testing', 'Test on multiple devices', 5, 0, 5),
(2, 'App Store Submission', 'Submit to app stores', 6, 0, 1),
(2, 'Marketing', 'Marketing and promotion', 7, 0, 5);

-- Insert Sample Attendance Records
INSERT INTO `attendance` (`user_id`, `date`, `check_in`, `check_out`, `total_hours`, `status`) VALUES
-- Recent attendance for all employees
(3, '2024-01-08', '09:00:00', '17:30:00', 8.5, 'present'),
(3, '2024-01-09', '09:15:00', '17:30:00', 8.25, 'late'),
(4, '2024-01-08', '08:45:00', '17:00:00', 8.25, 'present'),
(4, '2024-01-09', '09:00:00', '17:30:00', 8.5, 'present'),
(5, '2024-01-08', '09:30:00', '17:30:00', 8.0, 'late'),
(5, '2024-01-09', '09:00:00', '17:00:00', 8.0, 'present'),
(6, '2024-01-08', '09:00:00', '17:30:00', 8.5, 'present'),
(6, '2024-01-09', '09:00:00', '17:30:00', 8.5, 'present'),
(7, '2024-01-08', '09:00:00', '17:30:00', 8.5, 'present'),
(7, '2024-01-09', '09:00:00', '17:30:00', 8.5, 'present');

-- Insert Sample Leave Requests
INSERT INTO `leaves` (`user_id`, `type`, `start_date`, `end_date`, `days_count`, `reason`, `status`) VALUES
(3, 'Annual Leave', '2024-01-15', '2024-01-19', 5, 'Family vacation', 'approved'),
(4, 'Sick Leave', '2024-01-12', '2024-01-12', 1, 'Medical appointment', 'approved'),
(5, 'Personal Leave', '2024-01-20', '2024-01-21', 2, 'Personal matters', 'pending'),
(6, 'Annual Leave', '2024-02-01', '2024-02-05', 5, 'Winter vacation', 'pending'),
(7, 'Sick Leave', '2024-01-10', '2024-01-11', 2, 'Flu symptoms', 'approved');

-- Insert Sample Tasks
INSERT INTO `tasks` (`user_id`, `date`, `title`, `description`, `priority`, `status`, `estimated_hours`) VALUES
(3, CURDATE(), 'Fix login bug', 'Resolve authentication issues in user login', 'high', 'in_progress', 4.0),
(3, CURDATE(), 'Update documentation', 'Update API documentation for new endpoints', 'medium', 'pending', 2.0),
(4, CURDATE(), 'Design homepage mockup', 'Create new homepage design mockup', 'high', 'done', 6.0),
(4, CURDATE(), 'Review design guidelines', 'Review and update design system guidelines', 'low', 'pending', 3.0),
(5, CURDATE(), 'Social media campaign', 'Plan Q1 social media marketing campaign', 'medium', 'in_progress', 5.0);

-- Insert Sample Ideas
INSERT INTO `ideas` (`user_id`, `title`, `description`, `category`, `impact`, `status`) VALUES
(3, 'Automated Testing Suite', 'Implement comprehensive automated testing for all applications', 'Technology', 'high', 'approved'),
(4, 'Dark Mode Interface', 'Add dark mode option to all company applications', 'User Experience', 'medium', 'pending'),
(5, 'Employee Referral Program', 'Create incentive program for employee referrals', 'HR', 'high', 'approved'),
(6, 'Customer Feedback Portal', 'Build dedicated portal for customer feedback and suggestions', 'Customer Service', 'high', 'pending'),
(7, 'Flexible Working Hours', 'Implement flexible working hours policy', 'Policy', 'medium', 'approved');
-- Insert Sample Assets removed by request (no assets sample rows will be inserted)

-- Insert Sample Notifications
INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `priority`) VALUES
(3, 'leave_approved', 'Leave Request Approved', 'Your annual leave request for Jan 15-19 has been approved.', 'medium'),
(4, 'task_assigned', 'New Task Assigned', 'You have been assigned a new design task for the mobile app project.', 'high'),
(5, 'meeting_reminder', 'Meeting Reminder', 'Marketing team meeting scheduled for tomorrow at 10:00 AM.', 'medium'),
('6', 'performance_review', 'Performance Review Due', 'Your quarterly performance review is due next week.', 'high');

-- ===============================================
-- CREATE INDEXES FOR BETTER PERFORMANCE
-- ===============================================

-- Attendance indexes
CREATE INDEX `idx_attendance_user_date` ON `attendance` (`user_id`, `date`);
CREATE INDEX `idx_attendance_status` ON `attendance` (`status`);

-- Tasks indexes
CREATE INDEX `idx_tasks_user_status` ON `tasks` (`user_id`, `status`);
CREATE INDEX `idx_tasks_due_date` ON `tasks` (`due_date`);

-- Projects indexes
CREATE INDEX `idx_projects_status_priority` ON `projects` (`status`, `priority`);
CREATE INDEX `idx_projects_dates` ON `projects` (`start_date`, `end_date`);

-- Notifications indexes
CREATE INDEX `idx_notifications_user_read` ON `notifications` (`user_id`, `is_read`);

-- ===============================================
-- CREATE VIEWS FOR COMMON QUERIES
-- ===============================================

-- Employee Summary View
CREATE VIEW `employee_summary` AS
SELECT 
    u.id,
    u.name,
    u.email,
    u.role,
    t.name as team_name,
    u.hire_date,
    u.salary,
    u.status,
    (SELECT COUNT(*) FROM attendance a WHERE a.user_id = u.id AND a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as attendance_30days,
    (SELECT COUNT(*) FROM leaves l WHERE l.user_id = u.id AND l.status = 'pending') as pending_leaves,
    (SELECT COUNT(*) FROM tasks tk WHERE tk.user_id = u.id AND tk.status != 'done') as active_tasks
FROM users u
LEFT JOIN teams t ON u.team_id = t.id
WHERE u.role = 'employee';

-- Project Progress View
CREATE VIEW `project_progress` AS
SELECT 
    p.id,
    p.name,
    p.description,
    p.status,
    p.priority,
    p.start_date,
    p.end_date,
    p.budget,
    CONCAT(m.name) as manager_name,
    COUNT(ps.id) as total_steps,
    SUM(ps.is_done) as completed_steps,
    CASE 
        WHEN COUNT(ps.id) > 0 THEN ROUND((SUM(ps.is_done) / COUNT(ps.id)) * 100, 2)
        ELSE 0 
    END as completion_percentage
FROM projects p
LEFT JOIN users m ON p.manager_id = m.id
LEFT JOIN project_steps ps ON p.id = ps.project_id
GROUP BY p.id, p.name, p.description, p.status, p.priority, p.start_date, p.end_date, p.budget, m.name;

-- ===============================================
-- STORED PROCEDURES
-- ===============================================

DELIMITER //

-- Procedure to calculate monthly attendance
CREATE PROCEDURE `GetMonthlyAttendance`(IN user_id INT, IN month_year VARCHAR(7))
BEGIN
    SELECT
        DATE(date) as attendance_date,
        check_in,
        check_out,
        total_hours,
        status
    FROM attendance
    WHERE user_id = user_id -- This should be p_user_id
    AND DATE_FORMAT(date, '%Y-%m') = month_year
    ORDER BY date;
END //

-- Procedure to get employee performance metrics
CREATE PROCEDURE `GetEmployeeMetrics`(IN emp_id INT)
BEGIN
    SELECT 
        u.name,
        u.email,
        t.name as team_name,
        (SELECT COUNT(*) FROM attendance a WHERE a.user_id = emp_id AND a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as attendance_last_30_days,
        (SELECT COUNT(*) FROM tasks tk WHERE tk.user_id = emp_id AND tk.status = 'done' AND tk.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as tasks_completed_30_days,
        (SELECT COUNT(*) FROM leaves l WHERE l.user_id = emp_id AND l.status = 'approved' AND YEAR(l.start_date) = YEAR(CURDATE())) as approved_leaves_this_year,
        (SELECT AVG(overall_rating) FROM employee_performance ep WHERE ep.user_id = emp_id) as avg_performance_rating
    FROM users u
    LEFT JOIN teams t ON u.team_id = t.id
    WHERE u.id = emp_id;
END //

DELIMITER ;

-- ===============================================
-- TRIGGERS
-- ===============================================

DELIMITER //

-- Trigger to update project progress when step is completed
CREATE TRIGGER `update_project_progress` 
AFTER UPDATE ON `project_steps`
FOR EACH ROW
BEGIN
    DECLARE total_steps INT;
    DECLARE completed_steps INT;
    DECLARE progress_percentage INT;
    
    SELECT COUNT(*) INTO total_steps FROM project_steps WHERE project_id = NEW.project_id;
    SELECT COUNT(*) INTO completed_steps FROM project_steps WHERE project_id = NEW.project_id AND is_done = 1;
    
    SET progress_percentage = ROUND((completed_steps / total_steps) * 100);
    
    UPDATE projects SET progress = progress_percentage WHERE id = NEW.project_id;
END //

-- Trigger to create notification when leave is approved/rejected
CREATE TRIGGER `leave_status_notification`
AFTER UPDATE ON `leaves`
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status AND NEW.status IN ('approved', 'rejected') THEN
        INSERT INTO notifications (user_id, type, title, message, priority) 
        VALUES (
            NEW.user_id, 
            CONCAT('leave_', NEW.status), 
            CONCAT('Leave Request ', UPPER(NEW.status)),
            CONCAT('Your ', NEW.type, ' request for ', NEW.start_date, ' to ', NEW.end_date, ' has been ', NEW.status, '.'),
            'medium'
        );
    END IF;
END //

DELIMITER ;
-- ===============================================
-- END OF DATABASE SETUP
-- ===============================================

-- Display success message
SELECT 'Database setup completed successfully!' as status,
       'All tables, sample data, views, procedures, and triggers have been created.' as message;
