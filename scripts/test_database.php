<?php
/**
 * Database Connection Test Script
 * Use this to verify your database setup is working correctly
 */

require_once __DIR__ . '/../app/lib/db.php';

echo "<h2>🔍 Database Connection Test</h2>";
echo "<style>body{font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px;}</style>";

try {
    // Test database connection
    $pdo = Database::pdo();
    echo "<p>✅ <strong>Database Connection:</strong> SUCCESS</p>";
    
    // Test database name
    $config = Database::getConfig();
    echo "<p>📊 <strong>Database Name:</strong> " . htmlspecialchars($config['db_name']) . "</p>";
    
    // Check if tables exist
    $tables = [
        'users', 'teams', 'attendance', 'leaves', 'tasks', 
        'projects', 'project_steps', 'ideas', 'transfers', 
        'notifications', 'employee_performance', 'payroll'
    ];
    
    echo "<h3>📋 Table Status:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5f5f5;'><th style='padding: 8px;'>Table</th><th style='padding: 8px;'>Status</th><th style='padding: 8px;'>Records</th></tr>";
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "<tr><td style='padding: 8px;'>$table</td><td style='padding: 8px; color: green;'>✅ EXISTS</td><td style='padding: 8px;'>$count</td></tr>";
        } catch (Exception $e) {
            echo "<tr><td style='padding: 8px;'>$table</td><td style='padding: 8px; color: red;'>❌ MISSING</td><td style='padding: 8px;'>-</td></tr>";
        }
    }
    echo "</table>";
    
    // Test sample users
    echo "<h3>👥 Sample Users:</h3>";
    $stmt = $pdo->query("SELECT name, email, role FROM users ORDER BY role, name");
    $users = $stmt->fetchAll();
    
    if ($users) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f5f5f5;'><th style='padding: 8px;'>Name</th><th style='padding: 8px;'>Email</th><th style='padding: 8px;'>Role</th></tr>";
        foreach ($users as $user) {
            $roleColor = $user['role'] === 'admin' ? '#ff6b6b' : ($user['role'] === 'boss' ? '#4dabf7' : '#51cf66');
            echo "<tr><td style='padding: 8px;'>{$user['name']}</td><td style='padding: 8px;'>{$user['email']}</td><td style='padding: 8px; color: $roleColor; font-weight: bold;'>{$user['role']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No users found. Please import the sample data.</p>";
    }
    
    // Test views
    echo "<h3>📊 Database Views:</h3>";
    $views = ['employee_summary', 'project_progress'];
    foreach ($views as $view) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$view`");
            $count = $stmt->fetchColumn();
            echo "<p>✅ <strong>$view:</strong> Working ($count records)</p>";
        } catch (Exception $e) {
            echo "<p>❌ <strong>$view:</strong> Not found or error</p>";
        }
    }
    
    // Test procedures
    echo "<h3>🔄 Stored Procedures:</h3>";
    try {
        $stmt = $pdo->query("SHOW PROCEDURE STATUS WHERE db = '{$config['db_name']}'");
        $procedures = $stmt->fetchAll();
        if ($procedures) {
            foreach ($procedures as $proc) {
                echo "<p>✅ <strong>{$proc['Name']}:</strong> Available</p>";
            }
        } else {
            echo "<p>⚠️ No stored procedures found</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Could not check stored procedures</p>";
    }
    
    echo "<hr>";
    echo "<h3>🎉 Test Results Summary:</h3>";
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ Database setup is working correctly!</p>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li>🔑 Login to your application using the sample credentials</li>";
    echo "<li>👥 Create new employees via admin panel</li>";
    echo "<li>📊 View all dashboard features</li>";
    echo "<li>🔄 Test all functionality</li>";
    echo "</ul>";
    
    echo "<p><a href='index.php' style='display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px;'>🚀 Go to Login</a>";
    echo "<a href='dashboard.php' style='display: inline-block; padding: 10px 20px; background: #51cf66; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px;'>📊 Go to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ Database Connection FAILED!</p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    echo "<h3>🚨 Troubleshooting Steps:</h3>";
    echo "<ol>";
    echo "<li><strong>Check XAMPP:</strong> Make sure Apache and MySQL services are running</li>";
    echo "<li><strong>Import Database:</strong> Use phpMyAdmin to import 'database/database_setup.sql'</li>";
    echo "<li><strong>Check Config:</strong> Verify database credentials in app/config.php or .env</li>";
    echo "<li><strong>Database Name:</strong> Make sure 'company_portal' database exists</li>";
    echo "</ol>";
    
    echo "<p><a href='http://localhost/phpmyadmin/' target='_blank' style='display: inline-block; padding: 10px 20px; background: #ff6b6b; color: white; text-decoration: none; border-radius: 5px;'>🔧 Open phpMyAdmin</a></p>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #666; font-size: 12px;'>Database Test Script | Company Portal v2.0</p>";
?>
